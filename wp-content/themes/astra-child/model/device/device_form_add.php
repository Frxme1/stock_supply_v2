<?php
function device_form($editing = null)
{
	global $wpdb;
	$table_device = 'Devices';

	$brands     = $wpdb->get_results("SELECT BrandID, BrandName FROM Brands");
	$categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories");
	$keywords   = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords");
	$statuses   = $wpdb->get_results("SELECT StatusID, StatusName FROM Statuses");



	// Keywords เฉพาะ Accessories
	$accessory_keywords = ['Keyboard', 'Mouse', 'Power Supply', 'PC', 'Adapter','SSD'];
	$accessory_keyword_ids = [];

	foreach ($accessory_keywords as $name) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT KeywordID FROM Keywords WHERE KeywordName = %s", $name));
		if ($row) {
			$accessory_keyword_ids[] = intval($row->KeywordID);
		}
	}


	// Map KeywordName -> KeywordID
	$keyword_map = [];
	$keyword_names = ['Laptop', 'Monitor', 'Accessories'];

	foreach ($keyword_names as $name) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT KeywordID FROM Keywords WHERE KeywordName = %s", $name));
		if ($row) {
			$keyword_map[strtolower($name)] = intval($row->KeywordID);
		}
	}




	$category_data = [];
	foreach ($categories as $cat) {
		$prefix = $wpdb->get_var($wpdb->prepare(
			"SELECT Prefix FROM Categories WHERE CategoryID = %d",
			$cat->CategoryID
		));

		if ($prefix) {
			$last_device = $wpdb->get_var($wpdb->prepare(
				"SELECT DeviceID FROM {$table_device}
             WHERE DeviceID LIKE %s
             ORDER BY CAST(SUBSTRING(DeviceID, LENGTH(%s) + 1) AS UNSIGNED) DESC
             LIMIT 1",
				$prefix . '%',
				$prefix
			));

			$last_number = ($last_device)
				? intval(substr($last_device, strlen($prefix)))
				: 0;

			$category_data[$cat->CategoryID] = [
				'prefix' => $prefix,
				'last_number' => $last_number,
			];
		}
	}

	$category_id = null;
	if ($editing && !empty($editing->CategoryID)) {
		$category_id = intval($editing->CategoryID);
	} elseif (isset($_POST['CategoryID'])) {
		$category_id = intval($_POST['CategoryID']);
	}

	if (!function_exists('generate_next_device_id')) {
		function generate_next_device_id($category_data, $category_id)
		{
			if (!$category_id || !isset($category_data[$category_id])) {
				return '';
			}
			$prefix = $category_data[$category_id]['prefix'];
			$last_number = $category_data[$category_id]['last_number'] + 1;
			return $prefix . str_pad($last_number, 3, '0', STR_PAD_LEFT);
		}
	}

	$device_id = $editing
		? $editing->DeviceID
		: generate_next_device_id($category_data, $category_id);

	$dateValue = '';
	if (!empty($editing->AddDeviceDate)) {
		$timestamp = strtotime($editing->AddDeviceDate);
		$dateValue = date('Y-m-d', $timestamp);
	}



?>

	<script>
		window.categoryData = <?= json_encode($category_data); ?>;
	</script>
	<script src="<?= get_stylesheet_directory_uri() ?>/js/change_prefix.js"></script>


	<form method="POST" action="">
		<input type="hidden" name="DeviceID" value="<?= esc_attr($device_id) ?>">

		<h2 style="text-align: center;"><?= $editing ? 'Edit Device' : 'Add Device' ?></h2>

		<div class="form-grid">
			<div class="form-group">
				<label>Category</label>
				<select name="CategoryID" id="category_select" required>
					<option value="" style="text-align: center;">-- Select --</option>
					<?php foreach ($categories as $cat): ?>
						<option value="<?= $cat->CategoryID ?>" <?= selected($editing->CategoryID ?? '', $cat->CategoryID, false) ?>>
							<?= esc_html($cat->CategoryName) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>


			<div class="form-group">
				<label>DeviceID</label>
				<input type="text" name="DeviceID" value="<?= esc_attr($device_id) ?>" id="device_id_input" readonly>
			</div>

			<div class="form-group">
				<label>Brand</label>
				<select name="BrandID" id="brand-select">
					<option value="" style="text-align: center;">-- Select --</option>
					<?php foreach ($brands as $brand): ?>
						<option value="<?= $brand->BrandID ?>" <?= selected($editing->BrandID ?? '', $brand->BrandID, false) ?>>
							<?= esc_html($brand->BrandName) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>


			<div class="form-group">
				<label>Model</label>
				<input type="text" name="Model" value="<?= esc_attr($editing->Model ?? '') ?>">
			</div>

			<div class="form-group">
				<label>Serial No</label>
				<input type="text" name="SerialNumber" value="<?= esc_attr($editing->SerialNumber ?? '') ?>">
			</div>

			<div class="form-group">
				<label>Keyword</label>
				<select name="KeywordID" id="keyword_select">
					<option value="" style="text-align: center;">-- Select --</option>
					<?php foreach ($keywords as $key): ?>
						<option value="<?= $key->KeywordID ?>" <?= selected($editing->KeywordID ?? '', $key->KeywordID, false) ?>>
							<?= esc_html($key->KeywordName) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>


			<div class="form-group">
				<label>Status</label>
				<?php foreach ($statuses as $stat): ?>
					<?php if (strtolower($stat->StatusName) === 'available'): ?>
						<input type="hidden" name="StatusID" value="<?= $stat->StatusID ?>">
						<input type="text" value="<?= esc_html($stat->StatusName) ?>" readonly>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>


			<div class="form-group">
				<label>Add Device Date</label>
				<input type="date" name="AddDeviceDate" value="<?= esc_attr($dateValue) ?>" min="<?= esc_attr($editing->AddDeviceDate ?? date('Y-m-d')) ?>" required>
			</div>
		</div>


		<div class="form-actions">
			<button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
			<button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57" name="<?= $editing ? 'update_device' : 'add_device' ?>">
				<?= $editing ? 'Update' : 'Submit' ?>
			</button>
		</div>
	</form>

	<!-- Script js -->
	<script>
		window.keywordMap = <?= json_encode($keyword_map); ?>;
		window.accessoryKeywordIDs = <?= json_encode($accessory_keyword_ids); ?>;
	</script>
	<script src="<?= get_stylesheet_directory_uri() ?>/js/category_keyword_filter.js?v=<?= time() ?>"></script>

	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/sweetalert_check_serial.js"></script>



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


	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/alert_add_devices.js"></script>

<?php
	return ob_get_clean();
}

add_shortcode('device_form', 'device_form');
?>