<?php
function form_add_owner($editing = null)
{
	global $wpdb;
	$table_owner = 'Owners';

	$departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments");
	$positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions");
	$status_emp = $wpdb->get_results("SELECT StatusID, Status_name FROM Status_Employee");

	ob_start();
	echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$owner_id = $_POST['OwnerID'] ?? null;
		$nickname = trim($_POST['Nickname'] ?? '');
	
		$data = [
			'Nickname'     => $nickname,
			'FirstName'    => $_POST['FirstName'] ?? '',
			'LastName'     => $_POST['LastName'] ?? '',
			'DepartmentID' => !empty($_POST['DepartmentID']) ? $_POST['DepartmentID'] : null,
			'PositionID'   => !empty($_POST['PositionID']) ? $_POST['PositionID'] : null,
			'StatusID'     => !empty($_POST['StatusID']) ? $_POST['StatusID'] : null,
		];


		$current_user = wp_get_current_user();
		$user_email = $current_user->user_email ?? 'system';

		$category_id = 1; // set -> Employee

		if (!$owner_id && !empty($nickname)) {
			// ===== Add Owner =====
			$inserted = $wpdb->insert($table_owner, $data);
			if ($inserted) {
				$new_owner_id = $wpdb->insert_id;

				$description = "Added Employee: {$nickname}";

				$wpdb->insert('History_new', [
					'DeviceID'    => 0,
					'Action'      => 'Add Employee',
					'Date'        => current_time('mysql'),
					'Description' => $description,
					'user_email'  => $user_email,
					'CategoryID'  => $category_id,
					'Owner'       => $nickname
				]);
				echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Add Employee Success',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '/Owner';
                });
            </script>";
				exit;
			} else {
				echo '<p style="color:red;">Insert failed: ' . $wpdb->last_error . '</p>';
			}
		}
	}


?>

	<form method="POST" action="">
		<?php if ($editing): ?>
			<input type="hidden" name="OwnerID" value="<?= esc_attr($editing->OwnerID) ?>">
		<?php endif; ?>

		<h2 style="text-align: center;"><?= $editing ? 'Edit Owner' : 'Add Owner' ?></h2>

		<div class="form-grid">
			<div class="form-group">
				<label>Nickname</label>
				<input type="text" name="Nickname" value="<?= esc_attr($editing->Nickname ?? '') ?>" required>
			</div>

			<div class="form-group">
				<label>First Name</label>
				<input type="text" name="FirstName" value="<?= esc_attr($editing->FirstName ?? '') ?>">
			</div>

			<div class="form-group">
				<label>Last Name</label>
				<input type="text" name="LastName" value="<?= esc_attr($editing->LastName ?? '') ?>">
			</div>

			<div class="form-group">
				<label>Status</label>
				<select name="StatusID">
					<option value="" style="text-align:center;">-- Select --</option>
					<?php foreach ($status_emp as $status):
						$emoji = '';
						if (($status->Status_name) === 'Active') {
							$emoji = '<i class="fa-solid fa-circle text-success"></i> ';
						} elseif (($status->Status_name) === 'Resigned') {
							$emoji = '<i class="fa-solid fa-circle text-danger"></i> ';
						}
					?>
						<option value="<?= $status->StatusID ?>" <?= selected($editing->StatusID ?? '', $status->StatusID, false) ?>>
							<?= esc_html($emoji . $status->Status_name) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

		</div>

		<!-- Full width below the grid -->
		<div class="form-grid">
			<div class="form-group">
				<label>Department</label>
				<select name="DepartmentID">
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
				<select name="PositionID">
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
<?php
	return ob_get_clean();
}

add_shortcode('form_add_owner', 'form_add_owner');
?>