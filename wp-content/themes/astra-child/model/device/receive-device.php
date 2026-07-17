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
        $device_id = $_POST['DeviceID'];
        $owner_id = $_POST['OwnerID'];
        $department_id = $_POST['DepartmentID'];
        $receive_date = $_POST['ReceiveDate'];
        $position_id = $_POST['PositionID'];

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

    <form method="POST" style="width: 600px">
        <h2 style="text-align: center;">Receive Device</h2>

        <input type="hidden" name="DeviceID" value="<?= esc_attr($device_data->DeviceID ?? '') ?>">

        <div class="form-grid">

            <div class="form-group">
                <label>DeviceID</label>
                <input type="text" value="<?= esc_attr($device_data->DeviceID ?? '') ?>" readonly
                    style="background-color: #f0f0f0; color: #666; cursor: not-allowed;">
            </div>

        </div>



        <div class="d-flex gap-0 column-gap-3">
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
                <select name="DepartmentID" id="DepartmentID"
                    style="background-color: #f0f0f0; color: #666; pointer-events: none; cursor: not-allowed; appearance: none; -webkit-appearance: none; -moz-appearance: none;" tabindex="-1"
                    onmousedown="return false;">
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= $dep->DepartmentID ?>" <?= selected($device_data->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                            <?= esc_html($dep->DepartmentName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>


        <div class="d-flex gap-0 column-gap-3">

            <div class="form-group">
                <label>Position</label>
                <select name="PositionID" id="PositionID"
                    style="background-color: #f0f0f0; color: #666; pointer-events: none; cursor: not-allowed; appearance: none; -webkit-appearance: none; -moz-appearance: none;" tabindex="-1"
                    onmousedown="return false;">
                    <option value="">-- Select --</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= $pos->PositionID ?>" <?= selected($device_data->PositionID ?? '', $pos->PositionID, false) ?>>
                            <?= esc_html($pos->PositionName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label>Receive Date</label>
                <input type="date" name="ReceiveDate" value="<?= esc_attr($date_value) ?>" max="<?= date('Y-m-d') ?>"
                    required>
            </div>
        </div>


        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" name="update_device" class="btn btn-success border rounded-pill"
                style="background-color: #6ABF57">Receive</button>
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
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }

        .d-flex {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
        }
    </style>


    <?php
    return ob_get_clean();
}
add_shortcode('receive_device', 'receive_device');
