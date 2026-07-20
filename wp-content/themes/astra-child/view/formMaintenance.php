<?php
function form_maintenance($editing = null)
{
    global $wpdb;
    $table_device = 'Devices';
    $table_maintenance = 'Maintenance';
    $table_statuses = 'Statuses';

    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // Alert function
    if (!function_exists('show_alert')) {
        function show_alert($icon, $title, $html = '', $redirect = '')
        {
            echo "<script>
                Swal.fire({
                    icon: '$icon',
                    title: '$title',
                    html: `" . $html . "` ,
                    showConfirmButton: " . ($redirect ? "false" : "true") . ",
                    timer: " . ($redirect ? "1500" : "null") . "
                })" . ($redirect ? ".then(() => { window.location.href = '$redirect'; })" : "") . ";
            </script>";
        }
    }

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_device'])) {
        $DeviceID = sanitize_text_field($_POST['DeviceID']);
        $RepairDate = sanitize_text_field($_POST['RepairDate']);
        $Details = sanitize_textarea_field($_POST['Details']);
        if ($Details === 'อื่นๆ / Others' && !empty($_POST['OtherDetails'])) {
            $Details = 'อื่นๆ / Others - ' . sanitize_text_field($_POST['OtherDetails']);
        }

        $device_info = $wpdb->get_row($wpdb->prepare(
            "SELECT user_email, CategoryID, OwnerID FROM $table_device WHERE DeviceID = %s",
            $DeviceID
        ));

        if (!$device_info) {
            show_alert('error', 'Error!', 'Device not found.');
            return ob_get_clean();
        }

        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email ?: $device_info->user_email;

        // Insert into Maintenance
        $inserted = $wpdb->insert(
            $table_maintenance,
            [
                'DeviceID' => $DeviceID,
                'RepairDate' => $RepairDate,
                'Details' => $Details,
                'user_email' => $user_email,
                'CreatedAt' => current_time('mysql'),
                'UpdatedAt' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted || $wpdb->last_error) {
            show_alert('error', 'Database Error', esc_html($wpdb->last_error));
            return ob_get_clean();
        }

        // Get StatusID for Maintenance
        $status_id = $wpdb->get_var($wpdb->prepare("SELECT StatusID FROM $table_statuses WHERE StatusName = %s", 'Maintenance'));
        if (!$status_id) {
            show_alert('error', 'Error!', 'Status "Maintenance" not found.');
            return ob_get_clean();
        }

        // Update device status
        $updated = $wpdb->update(
            $table_device,
            ['StatusID' => $status_id],
            ['DeviceID' => $DeviceID],
            ['%d'],
            ['%s']
        );

        if ($updated === false || $wpdb->last_error) {
            show_alert('error', 'Database Error', esc_html($wpdb->last_error));
            return ob_get_clean();
        }

        // Get Owner nickname
        $owner_nickname = '-';
        if (!empty($device_info->OwnerID)) {
            $owner_info = $wpdb->get_var($wpdb->prepare(
                "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                $device_info->OwnerID
            ));
            if ($owner_info) {
                $owner_nickname = $owner_info;
            }
        }

        // Insert history
        $wpdb->insert('History_new', [
            'DeviceID' => $DeviceID,
            'Action' => 'Maintenance',
            'Date' => current_time('mysql'),
            'Description' => "Device ID {$DeviceID} set to Maintenance.",
            'user_email' => $user_email,
            'CategoryID' => $device_info->CategoryID ?? null,
            'Owner' => $owner_nickname
        ]);

        show_alert('success', 'Maintenance Device!', '', home_url('/maintenance/'));
        return ob_get_clean();
    }

    // Load data for form (ensure CategoryName, Model, etc. are included)
    if ($editing && !isset($editing->CategoryName)) {
        $editing = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, c.CategoryName, b.BrandName
        FROM Devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        WHERE d.DeviceID = %s
    ", $editing->DeviceID));
    }


    $dateValue = !empty($editing->RepairDate) ? date('Y-m-d', strtotime($editing->RepairDate)) : '';
    ?>

    <form class="form-maintenance" method="POST">
        <h2>Form Maintenance</h2>
        <div class="mt-4 mb-4">
            <h5 style="font-weight: 600; color: #374151;">Device Information</h5>
        </div>
        <div class="form-grid">

            <div class="form-group">
                <label>Device ID</label>
                <input type="text" name="DeviceID" value="<?= esc_attr($editing->DeviceID ?? '') ?>" required readonly>
            </div>

            <div class="form-group">
                <label>Brand</label>
                <input type="text" value="<?= esc_attr($editing->BrandName ?? '') ?>" readonly disabled>
            </div>


            <div class="form-group">
                <label>Category</label>
                <input type="text" value="<?= esc_attr($editing->CategoryName ?? '') ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label>Model</label>
                <input type="text" value="<?= esc_attr($editing->Model ?? '') ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label>Serial Number</label>
                <input type="text" value="<?= esc_attr($editing->SerialNumber ?? '') ?>" readonly disabled>
            </div>

        </div>

        <div class="mt-4 mb-4">
            <h5 style="font-weight: 600; color: #374151;">Maintenance</h5>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Repair Date</label>
                <input type="date" name="RepairDate" value="<?= esc_attr($dateValue) ?>" required>
            </div>

        </div>

        <?php
        $details_val = $editing->Details ?? '';
        $is_other = false;
        $other_text = '';
        $known_options = [
            'จอแสดงผลมีปัญหา / Screen Issue',
            'แบตเตอรี่เสื่อม / Battery Issue',
            'เครื่องเปิดไม่ติด / Power Issue',
            'คีย์บอร์ด/เมาส์มีปัญหา / Input Device Issue',
            'อัปเกรดอุปกรณ์ / Hardware Upgrade',
            'ซอฟต์แวร์มีปัญหา / Software Issue'
        ];

        if (!empty($details_val) && !in_array($details_val, $known_options)) {
            $is_other = true;
            if (strpos($details_val, 'อื่นๆ / Others - ') === 0) {
                $other_text = substr($details_val, strlen('อื่นๆ / Others - '));
            } elseif ($details_val !== 'อื่นๆ / Others') {
                $other_text = $details_val;
            }
        }
        ?>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Details</label>
            <select name="Details" required>
                <option value="" disabled selected>-- Select Maintenance Reason --</option>
                <option value="จอแสดงผลมีปัญหา / Screen Issue" <?= $details_val === 'จอแสดงผลมีปัญหา / Screen Issue' ? 'selected' : '' ?>>จอแสดงผลมีปัญหา / Screen Issue</option>
                <option value="แบตเตอรี่เสื่อม / Battery Issue" <?= $details_val === 'แบตเตอรี่เสื่อม / Battery Issue' ? 'selected' : '' ?>>แบตเตอรี่เสื่อม / Battery Issue</option>
                <option value="เครื่องเปิดไม่ติด / Power Issue" <?= $details_val === 'เครื่องเปิดไม่ติด / Power Issue' ? 'selected' : '' ?>>เครื่องเปิดไม่ติด / Power Issue</option>
                <option value="คีย์บอร์ด/เมาส์มีปัญหา / Input Device Issue" <?= $details_val === 'คีย์บอร์ด/เมาส์มีปัญหา / Input Device Issue' ? 'selected' : '' ?>>คีย์บอร์ด/เมาส์มีปัญหา / Input Device Issue</option>
                <option value="อัปเกรดอุปกรณ์ / Hardware Upgrade" <?= $details_val === 'อัปเกรดอุปกรณ์ / Hardware Upgrade' ? 'selected' : '' ?>>อัปเกรดอุปกรณ์ / Hardware Upgrade</option>
                <option value="ซอฟต์แวร์มีปัญหา / Software Issue" <?= $details_val === 'ซอฟต์แวร์มีปัญหา / Software Issue' ? 'selected' : '' ?>>ซอฟต์แวร์มีปัญหา / Software Issue</option>
                <option value="อื่นๆ / Others" <?= $is_other ? 'selected' : '' ?>>อื่นๆ / Others</option>
            </select>
        </div>

        <div class="form-group" id="other-details-group"
            style="display: <?= $is_other ? 'flex' : 'none' ?>; margin-top: 1.5rem;">
            <label>Additional Details <span class="text-danger">*</span></label>
            <input type="text" name="OtherDetails" id="OtherDetails" placeholder="Please specify reason..."
                value="<?= esc_attr($other_text) ?>" <?= $is_other ? 'required' : '' ?>>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-danger border rounded-pill"
                onclick="window.history.back();">Cancel</button>
            <button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57"
                name="update_device">Maintenance</button>
        </div>
    </form>

    <style>
        /* Next.js Inspired Form UI */
        form {
            max-width: 650px;
            margin: 40px auto;
            margin-top: 10px;
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            animation: formFadeIn 0.5s ease-out forwards;
        }

        @keyframes formFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        form h2 {
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.025em;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
            position: relative;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            transition: color 0.2s ease;
        }

        .form-group:focus-within label {
            color: #3b82f6;
        }

        /* Unified Input and Select Styling */
        .form-group input,
        .form-group select {
            width: 100%;
            box-sizing: border-box;
            height: 44px;
            /* Ensure uniform height */
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            color: #111827;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            appearance: none;
            /* For custom select arrow */
        }

        /* Select specific - Custom Arrow */
        .form-group select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            cursor: pointer;
        }

        /* Hover and Focus States */
        .form-group input:hover:not([readonly]):not([disabled]),
        .form-group select:hover:not([readonly]):not([disabled]) {
            border-color: #9ca3af;
        }

        .form-group input:focus:not([readonly]):not([disabled]),
        .form-group select:focus:not([readonly]):not([disabled]) {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            transform: translateY(-1px);
        }

        /* Click Animation for Select (Active state) */
        .form-group select:active:not([disabled]) {
            transform: scale(0.98);
        }

        /* Readonly/Disabled Input Styling */
        .form-group input[readonly],
        .form-group input[disabled],
        .form-group select[disabled] {
            background-color: #f9fafb !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            border-color: #e5e7eb !important;
            box-shadow: none !important;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f3f4f6;
        }

        .form-actions button {
            padding: 0.6rem 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .form-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .form-actions button:active {
            transform: translateY(0);
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            form {
                margin: 20px;
                padding: 1.5rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const detailsSelect = document.querySelector('select[name="Details"]');
            const otherGroup = document.getElementById('other-details-group');
            const otherInput = document.getElementById('OtherDetails');

            if (detailsSelect) {
                detailsSelect.addEventListener('change', function () {
                    if (this.value === 'อื่นๆ / Others') {
                        otherGroup.style.display = 'flex';
                        otherInput.required = true;
                    } else {
                        otherGroup.style.display = 'none';
                        otherInput.required = false;
                        otherInput.value = '';
                    }
                });
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('form_maintenance', 'form_maintenance');
