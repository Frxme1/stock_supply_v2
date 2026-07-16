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
        $device_id     = $_POST['DeviceID'];
        $owner_id      = $_POST['OwnerID'];
        $department_id = $_POST['DepartmentID'];
        $receive_date  = $_POST['ReceiveDate'];
        $position_id   = $_POST['PositionID'];

        // ดึงสถานะ "In Use"
        $status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");

        // อัปเดตข้อมูลอุปกรณ์
        $updated = $wpdb->update(
            $devices_table,
            [
                'OwnerID'      => $owner_id,
                'DepartmentID' => $department_id,
                'PositionID'   => $position_id,
                'ReceiveDate'  => $receive_date,
                'StatusID'     => $status_id,
                'ReturnDate'   => null,
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
                'DeviceID'    => $device_id,
                'Action'      => 'Receive',
                'Date'        => current_time('mysql'),
                'Description' => "Device ID {$device_id} received and assigned to owner",
                'user_email'  => $user_email,
                'CategoryID'  => $category_id,
                'Owner'       => $owner_nickname
            ]);

            // แสดงแจ้งเตือนสำเร็จ
            $redirect_url = '/' . urlencode($category_name);
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
                <input type="text" value="<?= esc_attr($device_data->DeviceID ?? '') ?>" readonly>
            </div>

        </div>



        <div class="d-flex gap-0 column-gap-3">
            <div class="form-group" style="position: relative;">
                <label>Owner</label>
                <input type="text" id="owner_input" placeholder="Nickname" autocomplete="off" required>
                <input type="hidden" name="OwnerID" id="OwnerID">
                <ul id="owner_dropdown" class="dropdown-list"></ul>
            </div>

            <div class="form-group">
                <label>Department</label>
                <select name="DepartmentID" id="DepartmentID">
                    <option value="">-- Select --</option>
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
                <select name="PositionID" id="PositionID">
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
                <input type="date" name="ReceiveDate" value="<?= esc_attr($date_value) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
        </div>


        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" name="update_device" class="btn btn-success border rounded-pill" style="background-color: #6ABF57">Receive</button>
        </div>

    </form>

    <script>
        window.ownersData = <?= json_encode($owners_data) ?>;
    </script>

    <script src="<?= get_stylesheet_directory_uri() ?>/js/list_owner.js"></script>

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

        .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ccc;
            max-height: 140px;
            overflow-y: auto;
            z-index: 999;
            display: none;
            list-style: none;
            margin: 4px 0 0 0;
            padding: 0;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            font-size: 14px;
        }

        .dropdown-list li {
            padding: 8px 12px;
            /* ลด padding */
            cursor: pointer;
            transition: background 0.2s;
        }

        .dropdown-list li:hover {
            background-color: #e6f0ff;
            /* สีสว่างนวลเท่ากับธีม */
        }

        .entry-content ul,
        .entry-content ol {
            margin: 0;
            padding: 0;
            padding-left: 0;
        }
    </style>


<?php
    return ob_get_clean();
}
add_shortcode('receive_device', 'receive_device');
