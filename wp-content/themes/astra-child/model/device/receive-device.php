<?php
function receive_device($device_data = null)
{
    global $wpdb;

    $devices_table = 'Devices';

    // ดึงข้อมูล Departments
    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions");

    // ดึงข้อมูล Owners ที่มี Status Active (StatusID = 1)
    $owners_data = $wpdb->get_results("
    SELECT o.OwnerID, o.Nickname, o.DepartmentID, o.PositionID, d.DepartmentName
    FROM Owners o
    LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
    WHERE o.StatusID = 1
");


    ob_start();

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // เมื่อฟอร์มถูกส่ง
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_device'])) {
        $device_id = $_POST['DeviceID'] ?? null;
        $owner_id = $_POST['OwnerID'] ?? null;
        $receive_date = $_POST['ReceiveDate'] ?? null;

        $owner_info = $wpdb->get_row($wpdb->prepare("SELECT DepartmentID, PositionID FROM Owners WHERE OwnerID = %d", $owner_id));
        $department_id = $owner_info->DepartmentID ?? null;
        $position_id = $owner_info->PositionID ?? null;

        // ดึงสถานะ "In Use"
        $status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");

        // อัปเดตข้อมูลอุปกรณ์
        $updated = $wpdb->update(
            $devices_table,
            [
                'OwnerID' => $owner_id,
                'DepartmentID' => $department_id,
                'PositionID' => $position_id,
                'ReceiveDate' => $receive_date,
                'StatusID' => $status_id,
                'ReturnDate' => null,
            ],
            ['DeviceID' => $device_id]
        );


        // ดึงชื่อหมวดหมู่
        $category_name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT c.CategoryName 
        FROM $devices_table d 
        JOIN Categories c ON d.CategoryID = c.CategoryID 
        WHERE d.DeviceID = %s",
                $device_id
            )
        );

        if ($updated !== false && $category_name) {
            $owner_nickname = $wpdb->get_var(
                $wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $owner_id)
            );

            $category_id = $wpdb->get_var(
                $wpdb->prepare("SELECT CategoryID FROM $devices_table WHERE DeviceID = %s", $device_id)
            );

            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? '';

            // บันทึกประวัติ
            $wpdb->insert('History_new', [
                'DeviceID' => $device_id,
                'Action' => 'Receive',
                'Date' => current_time('mysql'),
                'Description' => "Device ID {$device_id} received and assigned to owner",
                'user_email' => $user_email,
                'CategoryID' => $category_id,
                'Owner' => $owner_nickname
            ]);

            // ส่งอีเมลแจ้งเตือน
            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('Assign', $device_id, $owner_id);
            }

            // แสดงแจ้งเตือนสำเร็จ
            $redirect_url = home_url('/' . sanitize_title($category_name) . '/');
            echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Receive Device!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.href = '{$redirect_url}';
        });
    </script>";
            return ob_get_clean();
        } else {
            echo "<script>
        Swal.fire({
            icon: 'error',
            showConfirmButton: true
        });
    </script>";
        }
    }


    $date_value = !empty($device_data->ReceiveDate) ? date('Y-m-d', strtotime($device_data->ReceiveDate)) : '';
    ?>

    <form method="POST">
        <h2>Assign Device</h2>

        <input type="hidden" name="DeviceID" value="<?= esc_attr($device_data->DeviceID ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>DeviceID</label>
                <input type="text" value="<?= esc_attr($device_data->DeviceID ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label>Owner</label>
                <select name="OwnerID" id="OwnerID" required onchange="handleOwnerChange()">
                    <option value="">-- Select Owner --</option>
                    <?php foreach ($owners_data as $o): ?>
                        <option value="<?= esc_attr($o->OwnerID) ?>" data-dept="<?= esc_attr($o->DepartmentID) ?>"
                            data-pos="<?= esc_attr($o->PositionID) ?>">
                            <?= esc_html($o->Nickname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Department</label>
                <select name="DepartmentID" id="DepartmentID" disabled tabindex="-1">
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= $dep->DepartmentID ?>" <?= selected($device_data->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                            <?= esc_html($dep->DepartmentName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Position</label>
                <select name="PositionID" id="PositionID" disabled tabindex="-1">
                    <option value="">-- Select --</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= $pos->PositionID ?>" <?= selected($device_data->PositionID ?? '', $pos->PositionID, false) ?>>
                            <?= esc_html($pos->PositionName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Assign Date</label>
                <input type="date" name="ReceiveDate" value="<?= esc_attr($date_value) ?>" min="<?= date('Y-m-d') ?>"
                    required>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" name="update_device" class="btn btn-success border rounded-pill"
                style="background-color: #6ABF57">Assign</button>
        </div>

    </form>

    <script>
        function handleOwnerChange() {
            const ownerSelect = document.getElementById('OwnerID');
            const deptSelect = document.getElementById('DepartmentID');
            const posSelect = document.getElementById('PositionID');

            const selectedOption = ownerSelect.options[ownerSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const dept = selectedOption.getAttribute('data-dept');
                const pos = selectedOption.getAttribute('data-pos');

                if (dept) deptSelect.value = dept;
                if (pos) posSelect.value = pos;
            } else {
                deptSelect.value = '';
                posSelect.value = '';
            }
        }
    </script>

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

    <?php
    return ob_get_clean();
}
add_shortcode('receive_device', 'receive_device');
