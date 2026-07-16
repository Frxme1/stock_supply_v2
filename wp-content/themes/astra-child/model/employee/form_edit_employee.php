<?php
function form_edit_owner($editing = null)
{
    global $wpdb;
    $table_owner = 'Owners';

    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions");
    $status_emp = $wpdb->get_results("SELECT StatusID, Status_name FROM Status_Employee");

    ob_start();


    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['OwnerID'])) {
        $owner_id     = $_POST['OwnerID'];
        $nickname     = $_POST['Nickname'];
        $firstname    = $_POST['FirstName'];
        $lastname     = $_POST['LastName'];
        $departmentID = $_POST['DepartmentID'];
        $positionID   = $_POST['PositionID'];
        $statusID     = $_POST['StatusID'];

        $updated = $wpdb->update(
            $table_owner,
            [
                'Nickname'     => $nickname,
                'FirstName'    => $firstname,
                'LastName'     => $lastname,
                'DepartmentID' => $departmentID,
                'PositionID'   => $positionID,
                'StatusID'     => $statusID
            ],
            ['OwnerID' => $owner_id]
        );

        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner WHERE OwnerID = %d", $owner_id));

        if ($updated !== false) {
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? 'system';


            $firstname = trim($firstname);
            $lastname = trim($lastname);

            if ($firstname === '' && $lastname === '') {
                $description = "Updated Employee: {$nickname}";
            } else {
                $description = "Updated Employee: {$nickname} ({$firstname} {$lastname})";
            }
            // insert to History_new
            $wpdb->insert('History_new', [
                'DeviceID'    => 0,
                'Action'      => 'Update Employee',
                'Date'        => current_time('mysql'),
                'Description' => $description,
                'user_email'  => $user_email,
                'CategoryID'  => 1,
                'Owner'       => $nickname
            ]);

            echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Edited Employee!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.href = '/Owner';
        });
    </script>";
            return ob_get_clean();
        }
    }


?>
    <form method="POST" style="width: 30rem;">
        <h2 style="text-align: center;">Edit Owner</h2>
        <input type="hidden" name="OwnerID" value="<?= esc_attr($editing->OwnerID ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>NickName</label>
                <input type="text" name="Nickname" value="<?= esc_attr($editing->Nickname ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>FirstName</label>
                <input type="text" name="FirstName" value="<?= esc_attr($editing->FirstName ?? '') ?>">
            </div>

            <div class="form-group">
                <label>LastName</label>
                <input type="text" name="LastName" value="<?= esc_attr($editing->LastName ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="StatusID">
                    <option value="" style="text-align:center;">-- Select --</option>
                    <?php foreach ($status_emp as $status):
                        // กำหนดอิโมจิตามชื่อ status
                        $emoji = '';
                        if (($status->Status_name) === 'Active') {
                            $emoji = '🟢 ';
                        } elseif (($status->Status_name) === 'Resigned') {
                            $emoji = '🔴 ';
                        }
                    ?>
                        <option value="<?= $status->StatusID ?>" <?= selected($editing->StatusID ?? '', $status->StatusID, false) ?>>
                            <?= esc_html($emoji . $status->Status_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label>Department</label>
                <select name="DepartmentID" required>
                    <option value="" style="text-align:center;">-- Select --</option>
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= $dep->DepartmentID ?>" <?= selected($editing->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                            <?= esc_html($dep->DepartmentName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Position</label>
                <select name="PositionID" required>
                    <option value="" style="text-align:center;">-- Select --</option>
                    <?php foreach ($positions as $position): ?>
                        <option value="<?= $position->PositionID ?>" <?= selected($editing->PositionID ?? '', $position->PositionID, false) ?>>
                            <?= esc_html($position->PositionName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>



        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57"><?= $editing ? 'Update' : 'Submit' ?></button>
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
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }
    </style>

<?php return ob_get_clean();
}
add_shortcode('form_edit_owner', 'form_edit_owner');
