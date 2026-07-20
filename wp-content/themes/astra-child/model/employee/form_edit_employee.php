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
            window.location.href = '" . esc_url(home_url('/Owner/')) . "';
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
			from { opacity: 0; transform: translateY(10px); }
			to { opacity: 1; transform: translateY(0); }
		}

		form h2 {
			font-weight: 700;
			color: #111827;
			letter-spacing: -0.025em;
			margin-bottom: 1.5rem;
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
			height: 44px; /* Ensure uniform height */
			padding: 0.5rem 1rem;
			font-size: 0.95rem;
			color: #111827;
			background-color: #ffffff;
			border: 1px solid #d1d5db;
			border-radius: 10px;
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			appearance: none; /* For custom select arrow */
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

<?php return ob_get_clean();
}
add_shortcode('form_edit_owner', 'form_edit_owner');
