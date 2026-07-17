<?php
function form_maintenance($editing = null)
{
    global $wpdb;
    $table_device      = 'Devices';
    $table_maintenance = 'Maintenance';
    $table_statuses    = 'Statuses';

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
        $DeviceID   = sanitize_text_field($_POST['DeviceID']);
        $RepairDate = sanitize_text_field($_POST['RepairDate']);
        $Details    = sanitize_textarea_field($_POST['Details']);
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
                'DeviceID'   => $DeviceID,
                'RepairDate' => $RepairDate,
                'Details'    => $Details,
                'user_email' => $user_email,
                'CreatedAt'  => current_time('mysql'),
                'UpdatedAt'  => current_time('mysql'),
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
            'DeviceID'    => $DeviceID,
            'Action'      => 'Maintenance',
            'Date'        => current_time('mysql'),
            'Description' => "Device ID {$DeviceID} set to Maintenance.",
            'user_email'  => $user_email,
            'CategoryID'  => $device_info->CategoryID ?? null,
            'Owner'       => $owner_nickname
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
        <h2 style="text-align: center;">Form Maintenance</h2>
        <div class="mt-4 mb-4">
            <h5>Device Information</h5>
        </div>
        <div class="form-grid">

            <div class="form-group">
                <label>Device ID</label>
                <input type="text" style="cursor: not-allowed;" name="DeviceID" value="<?= esc_attr($editing->DeviceID ?? '') ?>" required readonly>
            </div>

            <div class="form-group">
                <label>Brand</label>
                <input type="text" style="cursor: not-allowed;" value="<?= esc_attr($editing->BrandName ?? '') ?>" readonly disabled>
            </div>


            <div class="form-group">
                <label>Category</label>
                <input type="text" style="cursor: not-allowed;" value="<?= esc_attr($editing->CategoryName ?? '') ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label>Model</label>
                <input type="text" style="cursor: not-allowed;" value="<?= esc_attr($editing->Model ?? '') ?>" readonly disabled>
            </div>

            <div class="form-group">
                <label>Serial Number</label>
                <input type="text" style="cursor: not-allowed;" value="<?= esc_attr($editing->SerialNumber ?? '') ?>" readonly disabled>
            </div>

        </div>

        <div class="mt-4 mb-4">
            <h5>Maintenance</h5>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Repair Date</label>
                <input type="date" name="RepairDate" style="background-color: #fff;" value="<?= esc_attr($dateValue) ?>" required>
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

        <div class="form-group">
            <label>Details</label>
            <select name="Details" class="rounded-4" style="padding: 10px; border: 1px solid #ccc; background-color: #fff;" required>
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
        
        <div class="form-group" id="other-details-group" style="display: <?= $is_other ? 'flex' : 'none' ?>;">
            <label>Additional Details <span class="text-danger">*</span></label>
            <input type="text" name="OtherDetails" id="OtherDetails" placeholder="Please specify reason..." value="<?= esc_attr($other_text) ?>" <?= $is_other ? 'required' : '' ?>>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-dark border rounded-pill" style="background-color: #000" onclick="window.history.back();">Cancel</button>
            <button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57" name="update_device">Maintenance</button>
        </div>
    </form>

    <style>
        form {
            max-width: 600px;
            margin: 20px auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 50px;
            font-size: 14px;
            background-color: #e7e7e7;
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const detailsSelect = document.querySelector('select[name="Details"]');
        const otherGroup = document.getElementById('other-details-group');
        const otherInput = document.getElementById('OtherDetails');

        if (detailsSelect) {
            detailsSelect.addEventListener('change', function() {
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
