<?php
if (!defined('ABSPATH')) {
    exit;
}

function device_form($editing = null)
{
	ob_start();

	global $wpdb;
	$table_device = 'Devices';

	$brands = $wpdb->get_results("SELECT BrandID, BrandName FROM Brands WHERE LOWER(BrandName) != 'other' ORDER BY BrandName ASC");
	$categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories WHERE LOWER(CategoryName) != 'other' ORDER BY CategoryName ASC");
	$keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords");
	$statuses = $wpdb->get_results("SELECT StatusID, StatusName FROM Statuses");



	// Keywords เฉพาะ Accessories
	$accessory_keywords = ['Keyboard', 'Mouse', 'Power Supply', 'PC', 'Adapter', 'SSD'];
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

		<div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 600px; margin: 0 auto;">
			<h2 style="text-align: center; margin: 0; flex-grow: 1;"><?= $editing ? 'Edit Device' : 'Add Device' ?></h2>
		</div>

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
				<select name="BrandID" id="brand-select" required onchange="toggleNewBrandInput(this)">
					<option value="" style="text-align: center;">-- Select --</option>
					<?php foreach ($brands as $brand): ?>
						<option value="<?= $brand->BrandID ?>" <?= selected($editing->BrandID ?? '', $brand->BrandID, false) ?>>
							<?= esc_html($brand->BrandName) ?>
						</option>
					<?php endforeach; ?>
					<option value="add_new">+ Add New Brand</option>
				</select>
				<div id="new_brand_wrapper" style="display: none; margin-top: 8px;">
					<input type="text" name="new_brand_name" id="new_brand_name" placeholder="Type new brand name (e.g. Razer, Anker)..." class="form-control form-control-sm" style="border-radius: 6px; padding: 6px 12px; border: 1px solid #15A5DA;">
				</div>
			</div>
			<script>
				function toggleNewBrandInput(selectElem) {
					var wrapper = document.getElementById('new_brand_wrapper');
					var input = document.getElementById('new_brand_name');
					if (!wrapper || !input) return;
					if (selectElem.value === 'add_new') {
						wrapper.style.display = 'block';
						input.focus();
					} else {
						wrapper.style.display = 'none';
						input.value = '';
					}
				}
			</script>


			<div class="form-group">
				<label>Model</label>
				<input type="text" name="Model" value="<?= esc_attr($editing->Model ?? '') ?>" required>
			</div>

			<div class="form-group">
				<label>Serial No</label>
				<input type="text" name="SerialNumber" value="<?= esc_attr($editing->SerialNumber ?? '') ?>" required>
			</div>

			<div class="form-group">
				<label>Keyword</label>
				<select name="KeywordID" id="keyword_select" required>
					<option value="" style="text-align: center; margin-top: -12px;">-- Select --</option>
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
				<input type="date" name="AddDeviceDate" value="<?= esc_attr($dateValue) ?>"
					min="<?= esc_attr($editing->AddDeviceDate ?? date('Y-m-d')) ?>" required>
			</div>
		</div>


		<div class="form-actions">
			<button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
			<button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57"
				name="<?= $editing ? 'update_device' : 'add_device' ?>">
				<?= $editing ? 'Update' : 'Submit' ?>
			</button>
		</div>
	</form>

	<!-- Script js -->
	<script>
		window.keywordMap = <?= json_encode($keyword_map); ?>;
		window.accessoryKeywordIDs = <?= json_encode($accessory_keyword_ids); ?>;
		window.siteUrl = "<?= esc_url(home_url('/')) ?>";
	</script>
	<script src="<?= get_stylesheet_directory_uri() ?>/js/category_keyword_filter.js?v=<?= time() ?>"></script>

	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/sweetalert_check_serial.js"></script>



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

		.d-flex h2 {
			font-weight: 700;
			color: #111827;
			letter-spacing: -0.025em;
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
			margin-bottom: 5px;
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
		.form-group input:hover,
		.form-group select:hover {
			border-color: #9ca3af;
		}

		.form-group input:focus,
		.form-group select:focus {
			outline: none;
			border-color: #3b82f6;
			box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
			transform: translateY(-1px);
		}

		/* Click Animation for Select (Active state) */
		.form-group select:active {
			transform: scale(0.98);
		}

		/* Readonly Input Styling */
		.form-group input[readonly] {
			background-color: #f9fafb;
			color: #6b7280;
			cursor: not-allowed;
			border-color: #e5e7eb;
			box-shadow: none;
		}

		.form-group input[readonly]:focus {
			box-shadow: none;
			border-color: #e5e7eb;
			transform: none;
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


	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/alert_add_devices.js?v=<?= time() ?>"></script>

	<?php
	return ob_get_clean();
}

add_shortcode('device_form', 'device_form');
?>