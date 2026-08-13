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


	<form method="POST" action="" id="add-device-form" class="edit-data-form">
		<input type="hidden" name="DeviceID" value="<?= esc_attr($device_id) ?>">

		<div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 600px; margin: 0 auto;">
			<h2 style="text-align: center; margin: 0; flex-grow: 1;"><?= $editing ? 'Edit Device' : 'Add Device' ?></h2>
		</div>

		<div class="form-grid">
			<div class="form-group">
				<label>Category</label>
				<select name="CategoryID" id="category_select" class="form-select staggered-dropdown" required>
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

				<div id="brand-select-wrapper">
					<select name="BrandID" id="brand-select" required onchange="checkBrandSelection(this)">
						<option value="" style="text-align: center;">-- Select --</option>
						<?php foreach ($brands as $brand): ?>
							<option value="<?= $brand->BrandID ?>" <?= selected($editing->BrandID ?? '', $brand->BrandID, false) ?>>
								<?= esc_html($brand->BrandName) ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" id="btn-add-new-brand" class="btn w-100 mt-2"
						style="border: 1.5px dashed #cbd5e1; color: #475569; font-weight: 600; border-radius: 8px; padding: 10px; background: #ffffff; transition: all 0.2s; <?= (!empty($editing->BrandID)) ? 'display: none;' : '' ?>"
						onclick="toggleNewBrandMode()"
						onmouseover="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6';"
						onmouseout="this.style.borderColor='#cbd5e1'; this.style.color='#475569';">
						<i class="fa-solid fa-plus me-1"></i> Add New Brand
					</button>
				</div>

				<div id="new_brand_wrapper"
					style="display: none; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-top: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<label class="mb-0" style="font-size: 0.85rem; font-weight: 700; color: #334155;"><i
								class="fa-solid fa-sparkles text-primary me-1"></i> Create New Brand</label>
						<button type="button" class="btn btn-link p-0 text-danger text-decoration-none"
							style="font-weight: 600; font-size: 0.8rem;" onclick="cancelNewBrandMode()">
							<i class="fa-solid fa-times me-1"></i> Cancel
						</button>
					</div>
					<input type="text" name="new_brand_name" id="new_brand_name" placeholder="e.g. Razer, Anker, Dell..."
						class="form-control"
						style="border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.95rem; padding: 10px 14px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
				</div>
			</div>

			<script>
				function checkBrandSelection(selectElem) {
					var addBtn = document.getElementById('btn-add-new-brand');
					if (addBtn) {
						if (selectElem.value !== '') {
							addBtn.style.display = 'none';
						} else {
							addBtn.style.display = 'block';
						}
					}
					var badge = document.getElementById('brand_stock_badge');
					if (badge) badge.style.display = 'none';
				}

				function toggleNewBrandMode() {
					var selectWrapper = document.getElementById('brand-select-wrapper');
					var newWrapper = document.getElementById('new_brand_wrapper');
					var input = document.getElementById('new_brand_name');
					var select = document.getElementById('brand-select');

					if (!selectWrapper || !newWrapper || !input || !select) return;

					selectWrapper.style.display = 'none';
					newWrapper.style.display = 'block';

					select.required = false;
					input.required = true;
					input.focus();
					select.value = '';
				}

				function cancelNewBrandMode() {
					var selectWrapper = document.getElementById('brand-select-wrapper');
					var newWrapper = document.getElementById('new_brand_wrapper');
					var input = document.getElementById('new_brand_name');
					var select = document.getElementById('brand-select');

					if (!selectWrapper || !newWrapper || !input || !select) return;

					selectWrapper.style.display = 'block';
					newWrapper.style.display = 'none';

					select.required = true;
					input.required = false;
					input.value = '';
				}
			</script>


			<div class="form-group" style="position: relative;">
				<label>Model</label>
				<input type="text" name="Model" id="model_input" value="<?= esc_attr($editing->Model ?? '') ?>"
					list="suggested_models" autocomplete="off" required>
				<datalist id="suggested_models"></datalist>
			</div>

			<script>
				document.addEventListener('DOMContentLoaded', function () {
					const categorySelect = document.getElementById('category_select');
					const brandSelect = document.getElementById('brand-select');
					const modelList = document.getElementById('suggested_models');

					function fetchSuggestedModels() {
						const catId = categorySelect.value;
						const brandId = brandSelect.value;

						// clear current datalist
						modelList.innerHTML = '';

						if (!catId || !brandId || brandId === 'add_new') return;

						const formData = new FormData();
						formData.append('action', 'get_suggested_models');
						formData.append('category_id', catId);
						formData.append('brand_id', brandId);

						fetch("<?= admin_url('admin-ajax.php') ?>", {
							method: 'POST',
							body: formData
						})
							.then(res => res.json())
							.then(res => {
								if (res.success && res.data.length > 0) {
									res.data.forEach(modelName => {
										const option = document.createElement('option');
										option.value = modelName;
										modelList.appendChild(option);
									});
								}
							})
							.catch(err => console.error('Error fetching models:', err));
					}

					if (categorySelect) categorySelect.addEventListener('change', fetchSuggestedModels);
					if (brandSelect) brandSelect.addEventListener('change', fetchSuggestedModels);

					// Fetch once on page load if editing
					fetchSuggestedModels();
				});
			</script>

			<div class="form-group">
				<label>Serial No</label>
				<input type="text" name="SerialNumber" value="<?= esc_attr($editing->SerialNumber ?? '') ?>" required>
			</div>

			<div class="form-group">
				<label>Keyword</label>
				<select name="KeywordID" id="keyword_select" class="form-select staggered-dropdown" required>
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