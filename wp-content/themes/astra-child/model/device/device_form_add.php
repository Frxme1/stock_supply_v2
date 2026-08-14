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

	// Keywords for Accessories only
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
	} else {
		$dateValue = date('Y-m-d');
	}

	$available_status_id = 1;
	foreach ($statuses as $stat) {
		if (strtolower($stat->StatusName) === 'available') {
			$available_status_id = $stat->StatusID;
			break;
		}
	}
	?>

	<script>
		window.categoryData = <?= json_encode($category_data); ?>;
	</script>
	<script src="<?= get_stylesheet_directory_uri() ?>/js/change_prefix.js"></script>

	<!-- Main Add Device Container -->
	<div class="add-device-responsive-container">
		<!-- Desktop Ambient Glows -->
		<div class="bg-glow-orb bg-glow-orb-1 desktop-only-element"></div>
		<div class="bg-glow-orb bg-glow-orb-2 desktop-only-element"></div>

		<!-- Desktop Sleek Header Bar -->
		<div class="add-device-desktop-header desktop-only-element">
			<div class="desktop-header-left">
				<div class="desktop-icon-badge">
					<i class="fa-solid fa-microchip"></i>
				</div>
				<div>
					<br>
					<p class="desktop-header-subtitle">
						<?= $editing ? 'Update hardware parameters and asset records' : 'Register and categorize hardware equipment to central inventory' ?>
					</p>
				</div>
			</div>
			<div class="desktop-header-badge">
				<span class="pulse-dot-green"></span>
				<span><?= $editing ? 'Asset Update' : 'Hardware Asset Registry' ?></span>
			</div>
		</div>

		<!-- Main Form Component (Responsive) -->
		<form method="POST" action="" id="add-device-form" class="edit-data-form add-device-main-form">
			<input type="hidden" name="DeviceID" value="<?= esc_attr($device_id) ?>">

			<!-- Mobile Only Title (Classic look) -->
			<div class="mobile-only-header">
				<h2><?= $editing ? 'Edit Device' : 'Add Device' ?></h2>
			</div>

			<!-- Desktop & Mobile Content Layout -->
			<div class="add-device-layout-grid">
				<!-- Form Fields Side -->
				<div class="form-fields-wrapper">
					<div class="form-grid modern-grid">
						<!-- Category Select -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="category_select">
									<i class="fa-solid fa-shapes field-icon-desktop desktop-only-element"></i>
									Category <span class="required-star">*</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<select name="CategoryID" id="category_select" class="form-select staggered-dropdown"
									required>
									<option value="">-- Select --</option>
									<?php foreach ($categories as $cat): ?>
										<option value="<?= $cat->CategoryID ?>" <?= selected($editing->CategoryID ?? '', $cat->CategoryID, false) ?>>
											<?= esc_html($cat->CategoryName) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- Device ID (Auto-generated) -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="device_id_input">
									<i class="fa-solid fa-fingerprint field-icon-desktop desktop-only-element"></i>
									Device ID <span class="auto-badge desktop-only-element">Auto</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<input type="text" name="DeviceID" value="<?= esc_attr($device_id) ?>" id="device_id_input"
									placeholder="Auto-generated" readonly>
							</div>
						</div>

						<!-- Brand Select + Add Brand Inline -->
						<div class="form-group modern-group">
							<div class="field-header d-flex justify-content-between align-items-center">
								<label for="brand-select" class="mb-0">
									<i class="fa-solid fa-building field-icon-desktop desktop-only-element"></i>
									Brand <span class="required-star">*</span>
								</label>
								<button type="button" id="btn-add-new-brand-link"
									class="btn-new-brand-text desktop-only-element" onclick="toggleNewBrandMode()">
									<i class="fa-solid fa-plus-circle"></i> New Brand
								</button>
							</div>

							<div id="brand-select-wrapper">
								<div class="field-input-wrap">
									<select name="BrandID" id="brand-select" required onchange="checkBrandSelection(this)">
										<option value="">-- Select --</option>
										<?php foreach ($brands as $brand): ?>
											<option value="<?= $brand->BrandID ?>" <?= selected($editing->BrandID ?? '', $brand->BrandID, false) ?>>
												<?= esc_html($brand->BrandName) ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<button type="button" id="btn-add-new-brand" class="btn w-100 mt-2 mobile-only-element"
									style="border: 1.5px dashed #cbd5e1; color: #475569; font-weight: 600; border-radius: 8px; padding: 10px; background: #ffffff; transition: all 0.2s; <?= (!empty($editing->BrandID)) ? 'display: none;' : '' ?>"
									onclick="toggleNewBrandMode()">
									<i class="fa-solid fa-plus me-1"></i> Add New Brand
								</button>
							</div>

							<!-- New Brand Input Wrapper (Animated Slide-in) -->
							<div id="new_brand_wrapper" class="new-brand-wrapper-box" style="display: none;">
								<div class="new-brand-top-bar">
									<span class="new-brand-title">
										<i class="fa-solid fa-sparkles text-primary me-1"></i> Create New Brand
									</span>
									<button type="button" class="btn-cancel-brand" onclick="cancelNewBrandMode()"
										title="Cancel">
										<i class="fa-solid fa-xmark"></i> Cancel
									</button>
								</div>
								<input type="text" name="new_brand_name" id="new_brand_name"
									placeholder="e.g. Razer, Anker, Dell, Apple..." class="new-brand-input-element">
							</div>
						</div>

						<!-- Model Input -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="model_input">
									<i class="fa-solid fa-tag field-icon-desktop desktop-only-element"></i>
									Model <span class="required-star">*</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<input type="text" name="Model" id="model_input"
									value="<?= esc_attr($editing->Model ?? '') ?>"
									placeholder="e.g. MacBook Pro M3 16&quot;" list="suggested_models" autocomplete="off"
									required>
								<datalist id="suggested_models"></datalist>
							</div>
						</div>

						<!-- Serial Number -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="serial_number_input">
									<i class="fa-solid fa-barcode field-icon-desktop desktop-only-element"></i>
									Serial No <span class="required-star">*</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<input type="text" name="SerialNumber" id="serial_number_input"
									value="<?= esc_attr($editing->SerialNumber ?? '') ?>" placeholder="e.g. C02G4589MD6R"
									autocomplete="off" required>
							</div>
						</div>

						<!-- Keyword Select -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="keyword_select">
									<i class="fa-solid fa-tags field-icon-desktop desktop-only-element"></i>
									Keyword <span class="required-star">*</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<select name="KeywordID" id="keyword_select" class="form-select staggered-dropdown"
									required>
									<option value="">-- Select --</option>
									<?php foreach ($keywords as $key): ?>
										<option value="<?= $key->KeywordID ?>" <?= selected($editing->KeywordID ?? '', $key->KeywordID, false) ?>>
											<?= esc_html($key->KeywordName) ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- Status (Preset to Available) -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label>
									<i class="fa-solid fa-circle-check field-icon-desktop desktop-only-element"></i>
									Status
								</label>
							</div>
							<div class="field-input-wrap">
								<input type="hidden" name="StatusID" value="<?= esc_attr($available_status_id) ?>">
								<div class="desktop-status-pill desktop-only-element">
									<span class="status-dot-active"></span>
									<span>Available</span>
								</div>
								<input type="text" value="Available" readonly class="mobile-only-element">
							</div>
						</div>

						<!-- Add Device Date -->
						<div class="form-group modern-group">
							<div class="field-header">
								<label for="add_device_date_input">
									<i class="fa-solid fa-calendar-day field-icon-desktop desktop-only-element"></i>
									Add Device Date <span class="required-star">*</span>
								</label>
							</div>
							<div class="field-input-wrap">
								<input type="date" name="AddDeviceDate" id="add_device_date_input"
									value="<?= esc_attr($dateValue) ?>"
									min="<?= esc_attr($editing->AddDeviceDate ?? '2020-01-01') ?>" required>
							</div>
						</div>
					</div>

					<!-- Form Actions (Cancel & Submit) -->
					<div class="form-actions modern-form-actions">
						<button type="button" onclick="history.back()"
							class="btn btn-danger btn-cancel-action border rounded-pill">
							<i class="fa-solid fa-arrow-left me-1"></i> Cancel
						</button>
						<button type="submit" class="btn btn-success btn-submit-action border rounded-pill"
							name="<?= $editing ? 'update_device' : 'add_device' ?>">
							<span class="btn-shine-effect desktop-only-element"></span>
							<i class="fa-solid <?= $editing ? 'fa-check' : 'fa-plus' ?> me-1"></i>
							<span><?= $editing ? 'Update' : 'Submit' ?></span>
						</button>
					</div>
				</div>

				<!-- Desktop Live Interactive Preview Card (Right Column) -->
				<div class="preview-card-column desktop-only-element">
					<div class="desktop-live-preview-card" id="live-preview-card">
						<div class="preview-ambient-glow"></div>

						<div class="preview-top-row">
							<div class="preview-cat-badge" id="preview-cat-badge">
								<i class="fa-solid fa-laptop" id="preview-cat-icon"></i>
								<span id="preview-cat-name">Laptop</span>
							</div>
							<div class="preview-live-status">
								<span class="pulse-indicator"></span>
								<span>Available</span>
							</div>
						</div>

						<!-- Center Animated Device Icon -->
						<div class="preview-device-center">
							<div class="preview-orb-icon">
								<i class="fa-solid fa-laptop" id="preview-stage-icon"></i>
							</div>
							<div class="preview-id-pill" id="preview-id-display">
								<i class="fa-solid fa-fingerprint me-1"></i>
								<span id="preview-id-text"><?= esc_html($device_id ?: 'ID: PENDING') ?></span>
							</div>
						</div>

						<!-- Specs Details -->
						<div class="preview-specs-box">
							<div class="preview-model-text" id="preview-model-text">
								<?= esc_html(!empty($editing->Model) ? $editing->Model : 'Device Model Name') ?>
							</div>
							<div class="preview-brand-text" id="preview-brand-text">
								Manufacturer Brand
							</div>

							<div class="preview-meta-grid">
								<div class="preview-meta-row">
									<span class="meta-label">Serial Number</span>
									<span class="meta-val font-monospace" id="preview-sn-text">
										<?= esc_html(!empty($editing->SerialNumber) ? $editing->SerialNumber : 'NO SERIAL YET') ?>
									</span>
								</div>
								<div class="preview-meta-row">
									<span class="meta-label">Keyword Tag</span>
									<span class="meta-val" id="preview-keyword-text">
										<?= esc_html(!empty($editing->KeywordID) ? 'Assigned' : 'Standard') ?>
									</span>
								</div>
								<div class="preview-meta-row">
									<span class="meta-label">Entry Date</span>
									<span class="meta-val" id="preview-date-text"><?= esc_html($dateValue) ?></span>
								</div>
							</div>
						</div>

						<!-- Card Footer -->
						<div class="preview-card-footer">
							<div class="d-flex align-items-center gap-1">
								<span class="radar-dot"></span>
								<span>Real-time Dynamic Preview</span>
							</div>
							<span class="text-muted"><i class="fa-solid fa-shield-halved"></i> Active</span>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

	<!-- Interactive Logic & Live Sync JavaScript -->
	<script>
		function checkBrandSelection(selectElem) {
			var addBtn = document.getElementById('btn-add-new-brand');
			if (addBtn) {
				addBtn.style.display = (selectElem.value !== '') ? 'none' : 'block';
			}
			updateLivePreview();
		}

		function toggleNewBrandMode() {
			var selectWrapper = document.getElementById('brand-select-wrapper');
			var newWrapper = document.getElementById('new_brand_wrapper');
			var input = document.getElementById('new_brand_name');
			var select = document.getElementById('brand-select');
			var linkBtn = document.getElementById('btn-add-new-brand-link');

			if (!selectWrapper || !newWrapper || !input || !select) return;

			selectWrapper.style.display = 'none';
			newWrapper.style.display = 'block';
			if (linkBtn) linkBtn.style.display = 'none';

			select.required = false;
			input.required = true;
			input.focus();
			select.value = '';
			updateLivePreview();
		}

		function cancelNewBrandMode() {
			var selectWrapper = document.getElementById('brand-select-wrapper');
			var newWrapper = document.getElementById('new_brand_wrapper');
			var input = document.getElementById('new_brand_name');
			var select = document.getElementById('brand-select');
			var linkBtn = document.getElementById('btn-add-new-brand-link');

			if (!selectWrapper || !newWrapper || !input || !select) return;

			selectWrapper.style.display = 'block';
			newWrapper.style.display = 'none';
			if (linkBtn) linkBtn.style.display = 'inline-flex';

			select.required = true;
			input.required = false;
			input.value = '';
			updateLivePreview();
		}

		document.addEventListener('DOMContentLoaded', function () {
			const categorySelect = document.getElementById('category_select');
			const brandSelect = document.getElementById('brand-select');
			const modelInput = document.getElementById('model_input');
			const serialInput = document.getElementById('serial_number_input');
			const keywordSelect = document.getElementById('keyword_select');
			const dateInput = document.getElementById('add_device_date_input');
			const newBrandInput = document.getElementById('new_brand_name');
			const deviceIdInput = document.getElementById('device_id_input');
			const modelList = document.getElementById('suggested_models');

			const iconMap = {
				'laptop': 'fa-laptop',
				'notebook': 'fa-laptop',
				'monitor': 'fa-desktop',
				'screen': 'fa-desktop',
				'display': 'fa-desktop',
				'pc': 'fa-computer',
				'desktop': 'fa-computer',
				'accessories': 'fa-keyboard',
				'keyboard': 'fa-keyboard',
				'mouse': 'fa-computer-mouse',
				'adapter': 'fa-plug',
				'power supply': 'fa-bolt',
				'ssd': 'fa-hard-drive'
			};

			window.updateLivePreview = function () {
				const catText = categorySelect && categorySelect.selectedIndex > 0
					? categorySelect.options[categorySelect.selectedIndex].text.trim()
					: 'Hardware';
				const catKey = catText.toLowerCase();

				let matchedIcon = 'fa-microchip';
				for (let key in iconMap) {
					if (catKey.includes(key)) {
						matchedIcon = iconMap[key];
						break;
					}
				}

				const catBadge = document.getElementById('preview-cat-name');
				const catIcon = document.getElementById('preview-cat-icon');
				const stageIcon = document.getElementById('preview-stage-icon');
				if (catBadge) catBadge.textContent = catText;
				if (catIcon) catIcon.className = 'fa-solid ' + matchedIcon;
				if (stageIcon) stageIcon.className = 'fa-solid ' + matchedIcon;

				const devId = (deviceIdInput && deviceIdInput.value.trim()) ? deviceIdInput.value.trim() : 'ID: PENDING';
				const idDisplay = document.getElementById('preview-id-text');
				if (idDisplay) idDisplay.textContent = devId;

				let brandName = '';
				if (newBrandInput && newBrandInput.value.trim()) {
					brandName = newBrandInput.value.trim();
				} else if (brandSelect && brandSelect.selectedIndex > 0) {
					brandName = brandSelect.options[brandSelect.selectedIndex].text.trim();
				}
				const brandDisplay = document.getElementById('preview-brand-text');
				if (brandDisplay) brandDisplay.textContent = brandName || 'Manufacturer Brand';

				const modelVal = modelInput && modelInput.value.trim() ? modelInput.value.trim() : 'Device Model Name';
				const modelDisplay = document.getElementById('preview-model-text');
				if (modelDisplay) modelDisplay.textContent = modelVal;

				const snVal = serialInput && serialInput.value.trim() ? serialInput.value.trim() : 'NO SERIAL YET';
				const snDisplay = document.getElementById('preview-sn-text');
				if (snDisplay) snDisplay.textContent = snVal;

				const kwText = keywordSelect && keywordSelect.selectedIndex > 0
					? keywordSelect.options[keywordSelect.selectedIndex].text.trim()
					: 'Standard';
				const kwDisplay = document.getElementById('preview-keyword-text');
				if (kwDisplay) kwDisplay.textContent = kwText;

				const dateVal = dateInput && dateInput.value ? dateInput.value : '<?= esc_js($dateValue) ?>';
				const dateDisplay = document.getElementById('preview-date-text');
				if (dateDisplay) dateDisplay.textContent = dateVal;
			};

			function fetchSuggestedModels() {
				const catId = categorySelect ? categorySelect.value : '';
				const brandId = brandSelect ? brandSelect.value : '';

				if (modelList) modelList.innerHTML = '';
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
						if (res.success && res.data.length > 0 && modelList) {
							res.data.forEach(modelName => {
								const option = document.createElement('option');
								option.value = modelName;
								modelList.appendChild(option);
							});
						}
					})
					.catch(err => console.error('Error fetching models:', err));
			}

			if (categorySelect) {
				categorySelect.addEventListener('change', () => {
					fetchSuggestedModels();
					setTimeout(updateLivePreview, 50);
				});
			}
			if (brandSelect) {
				brandSelect.addEventListener('change', () => {
					fetchSuggestedModels();
					updateLivePreview();
				});
			}
			if (modelInput) modelInput.addEventListener('input', updateLivePreview);
			if (serialInput) serialInput.addEventListener('input', updateLivePreview);
			if (keywordSelect) keywordSelect.addEventListener('change', updateLivePreview);
			if (dateInput) dateInput.addEventListener('change', updateLivePreview);
			if (newBrandInput) newBrandInput.addEventListener('input', updateLivePreview);
			if (deviceIdInput) deviceIdInput.addEventListener('input', updateLivePreview);

			// 3D Card Hover Tilt
			const card = document.getElementById('live-preview-card');
			if (card && window.innerWidth > 992) {
				card.addEventListener('mousemove', function (e) {
					const rect = card.getBoundingClientRect();
					const x = e.clientX - rect.left;
					const y = e.clientY - rect.top;
					const centerX = rect.width / 2;
					const centerY = rect.height / 2;
					const rotateX = ((y - centerY) / centerY) * -5;
					const rotateY = ((x - centerX) / centerX) * 5;
					card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-2px)`;
				});
				card.addEventListener('mouseleave', function () {
					card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)';
				});
			}

			updateLivePreview();
		});
	</script>

	<!-- Script js hooks -->
	<script>
		window.keywordMap = <?= json_encode($keyword_map); ?>;
		window.accessoryKeywordIDs = <?= json_encode($accessory_keyword_ids); ?>;
		window.siteUrl = "<?= esc_url(home_url('/')) ?>";
	</script>
	<script src="<?= get_stylesheet_directory_uri() ?>/js/category_keyword_filter.js?v=<?= time() ?>"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/sweetalert_check_serial.js"></script>
	<script src="<?= get_stylesheet_directory_uri(); ?>/js/alert_add_devices.js?v=<?= time() ?>"></script>

	<!-- Responsive Stylesheet (Expanded Single-Screen Desktop + Classic Clean Mobile) -->
	<style>
		/* =============================================================
						   DESKTOP STYLES (Screen > 768px): Spacious Single-Screen Bento
						   ============================================================= */
		@media (min-width: 769px) {

			.mobile-only-header,
			.mobile-only-element {
				display: none !important;
			}

			.desktop-only-element {
				display: flex !important;
			}

			.add-device-responsive-container {
				position: relative;
				width: 100%;
				max-width: 1280px;
				margin: 0 auto;
				padding: 0.5rem 1rem 2rem 1rem;
				font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
				box-sizing: border-box;
			}

			/* Background Glow Orbs */
			.bg-glow-orb {
				position: absolute;
				border-radius: 50%;
				filter: blur(80px);
				pointer-events: none;
				z-index: 0;
				opacity: 0.35;
			}

			.bg-glow-orb-1 {
				top: -20px;
				left: 15%;
				width: 300px;
				height: 300px;
				background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, transparent 70%);
			}

			.bg-glow-orb-2 {
				bottom: 20px;
				right: 10%;
				width: 320px;
				height: 320px;
				background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, transparent 70%);
			}

			/* Desktop Header Bar */
			.add-device-desktop-header {
				display: flex;
				align-items: center;
				justify-content: space-between;
				background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
				backdrop-filter: blur(12px);
				-webkit-backdrop-filter: blur(12px);
				border: 1px solid rgba(226, 232, 240, 0.9);
				border-radius: 18px;
				padding: 1rem 1.5rem;
				margin-bottom: 1.25rem;
				box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
				position: relative;
				z-index: 1;
				animation: slideDownFade 0.4s ease-out forwards;
			}

			.desktop-header-left {
				display: flex;
				align-items: center;
				gap: 14px;
			}

			.desktop-icon-badge {
				width: 44px;
				height: 44px;
				border-radius: 14px;
				background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
				display: flex;
				align-items: center;
				justify-content: center;
				color: #ffffff;
				font-size: 1.3rem;
				box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
				flex-shrink: 0;
			}



			.desktop-header-subtitle {
				margin: 3px 0 0 0;
				font-size: 0.85rem;
				color: #64748b;
			}

			.desktop-header-badge {
				display: inline-flex;
				align-items: center;
				gap: 7px;
				padding: 5px 14px;
				background: rgba(99, 102, 241, 0.08);
				color: #4f46e5;
				border-radius: 999px;
				font-size: 0.78rem;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.04em;
			}

			.pulse-dot-green {
				width: 7px;
				height: 7px;
				border-radius: 50%;
				background-color: #10b981;
				box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
				animation: dotPulse 2s infinite;
			}

			/* Desktop Layout Grid (2 Columns: Form + Preview) */
			.add-device-layout-grid {
				display: grid;
				grid-template-columns: 1.4fr 0.9fr;
				gap: 1.5rem;
				position: relative;
				z-index: 1;
				align-items: stretch;
			}

			.add-device-main-form {
				background: transparent !important;
				border: none !important;
				box-shadow: none !important;
				padding: 0 !important;
				margin: 0 !important;
			}

			.form-fields-wrapper {
				background: #ffffff;
				border: 1.5px solid #e2e8f0;
				border-radius: 24px;
				padding: 1.5rem 1.75rem;
				box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.05);
				display: flex;
				flex-direction: column;
				justify-content: space-between;
				animation: cardFadeIn 0.45s ease-out forwards;
			}

			.modern-grid {
				display: grid !important;
				grid-template-columns: 1fr 1fr !important;
				gap: 1rem 1.25rem !important;
			}

			.modern-group {
				display: flex;
				flex-direction: column;
				gap: 5px;
				margin-bottom: 0 !important;
			}

			.modern-group label {
				font-size: 0.85rem !important;
				font-weight: 700 !important;
				color: #334155 !important;
				display: flex !important;
				align-items: center !important;
				gap: 6px !important;
				margin: 0 !important;
				text-transform: none !important;
			}

			.field-icon-desktop {
				color: #6366f1;
				font-size: 0.9rem;
			}

			.required-star {
				color: #ef4444;
				font-weight: 700;
			}

			.auto-badge {
				font-size: 0.68rem;
				font-weight: 700;
				background: #eef2ff;
				color: #4f46e5;
				padding: 1px 6px;
				border-radius: 5px;
				margin-left: 4px;
				text-transform: uppercase;
			}

			.field-input-wrap {
				position: relative;
				width: 100%;
			}

			.modern-group input,
			.modern-group select {
				width: 100% !important;
				height: 44px !important;
				padding: 0 14px !important;
				font-size: 0.92rem !important;
				font-weight: 500 !important;
				color: #0f172a !important;
				background-color: #f8fafc !important;
				border: 1.5px solid #e2e8f0 !important;
				border-radius: 12px !important;
				transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
				box-sizing: border-box !important;
			}

			.modern-group select {
				padding-right: 36px !important;
				cursor: pointer !important;
			}

			.modern-group input:hover,
			.modern-group select:hover {
				border-color: #cbd5e1 !important;
				background-color: #ffffff !important;
			}

			.modern-group input:focus,
			.modern-group select:focus {
				background-color: #ffffff !important;
				border-color: #6366f1 !important;
				outline: none !important;
				box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.16) !important;
			}

			.modern-group input[readonly],
			.modern-group input:disabled,
			.modern-group select:disabled {
				background-color: #f1f5f9 !important;
				color: #0f172a !important;
				-webkit-text-fill-color: #0f172a !important;
				font-weight: 700 !important;
				border: 1.5px solid #cbd5e1 !important;
				cursor: not-allowed !important;
				opacity: 1 !important;
				background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 1a3 3 0 0 0-3 3v2H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1V4a3 3 0 0 0-3-3zm1 5V4a1 1 0 0 0-2 0v2h2z'/%3E%3C/svg%3E") !important;
				background-repeat: no-repeat !important;
				background-position: right 14px center !important;
				background-size: 14px 14px !important;
				padding-right: 38px !important;
			}

			.desktop-status-pill {
				display: flex;
				align-items: center;
				gap: 8px;
				height: 44px;
				padding: 0 14px;
				background: #ecfdf5;
				border: 1.5px solid #a7f3d0;
				border-radius: 12px;
				color: #065f46;
				font-weight: 600;
				font-size: 0.88rem;
			}

			.status-dot-active {
				width: 8px;
				height: 8px;
				border-radius: 50%;
				background-color: #10b981;
				box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
				animation: dotPulse 2s infinite;
			}

			.btn-new-brand-text {
				background: #eff6ff !important;
				border: 1px solid #bfdbfe !important;
				color: #2563eb !important;
				font-size: 0.72rem !important;
				font-weight: 700 !important;
				padding: 3px 10px !important;
				border-radius: 9999px !important;
				cursor: pointer !important;
				user-select: none !important;
				outline: none !important;
				display: inline-flex !important;
				align-items: center !important;
				gap: 5px !important;
				text-decoration: none !important;
				transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
				box-shadow: 0 1px 2px rgba(37, 99, 235, 0.06) !important;
			}

			.btn-new-brand-text i {
				font-size: 0.75rem !important;
				transition: transform 0.25s ease !important;
			}

			.btn-new-brand-text:hover {
				background: #2563eb !important;
				border-color: #2563eb !important;
				color: #ffffff !important;
				transform: translateY(-1px) !important;
				box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25) !important;
				text-decoration: none !important;
			}

			.btn-new-brand-text:hover i {
				transform: rotate(90deg) !important;
			}

			.btn-new-brand-text:active {
				transform: translateY(0) !important;
				box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2) !important;
			}

			.new-brand-wrapper-box {
				background: #f8fafc !important;
				border: 1.5px solid #c7d2fe !important;
				border-radius: 12px !important;
				padding: 8px 12px !important;
				margin-top: 6px !important;
				box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08) !important;
				animation: slideDownFade 0.2s ease-out forwards;
			}

			.new-brand-top-bar {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				margin-bottom: 6px !important;
				width: 100% !important;
			}

			.new-brand-title {
				font-size: 0.8rem !important;
				font-weight: 700 !important;
				color: #4338ca !important;
				white-space: nowrap !important;
				display: inline-flex !important;
				align-items: center !important;
			}

			.btn-cancel-brand {
				all: unset !important;
				display: inline-flex !important;
				align-items: center !important;
				gap: 4px !important;
				background: #fee2e2 !important;
				color: #dc2626 !important;
				border: 1px solid #fca5a5 !important;
				border-radius: 6px !important;
				padding: 3px 10px !important;
				font-size: 0.75rem !important;
				font-weight: 700 !important;
				line-height: 1.2 !important;
				cursor: pointer !important;
				transition: all 0.15s ease !important;
				box-sizing: border-box !important;
			}

			.btn-cancel-brand:hover {
				background: #fecaca !important;
				color: #b91c1c !important;
			}

			.new-brand-input-element {
				width: 100% !important;
				height: 40px !important;
				padding: 0 12px !important;
				font-size: 0.88rem !important;
				font-weight: 500 !important;
				color: #0f172a !important;
				background-color: #ffffff !important;
				border: 1.5px solid #cbd5e1 !important;
				border-radius: 8px !important;
				transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
				box-sizing: border-box !important;
			}

			.new-brand-input-element:focus {
				background-color: #ffffff !important;
				border-color: #6366f1 !important;
				outline: none !important;
				box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
			}

			/* Desktop Actions */
			.modern-form-actions {
				display: flex !important;
				align-items: center !important;
				justify-content: flex-end !important;
				gap: 1rem !important;
				margin-top: 1.25rem !important;
				padding-top: 1rem !important;
				border-top: 1.5px dashed #f1f5f9 !important;
			}

			.btn-cancel-action {
				padding: 0.6rem 1.75rem !important;
				font-size: 0.92rem !important;
				font-weight: 600 !important;
				background-color: #ffffff !important;
				border: 1.5px solid #e2e8f0 !important;
				color: #ffffff !important;
				transition: all 0.2s ease !important;
			}

			.btn-cancel-action:hover {
				background-color: #f1f5f9 !important;
				color: #0f172a !important;
				border-color: #cbd5e1 !important;
			}

			.btn-submit-action {
				position: relative;
				overflow: hidden;
				padding: 0.6rem 2.2rem !important;
				font-size: 0.92rem !important;
				font-weight: 700 !important;
				background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
				border: none !important;
				color: #ffffff !important;
				box-shadow: 0 6px 20px -4px rgba(79, 70, 229, 0.4) !important;
				transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
			}

			.btn-submit-action:hover {
				transform: translateY(-2px) !important;
				color: #0f172a !important;
				box-shadow: 0 8px 25px -4px rgba(79, 70, 229, 0.5) !important;
			}

			.btn-shine-effect {
				position: absolute;
				top: -50%;
				left: -60%;
				width: 40%;
				height: 200%;
				background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.35) 50%, rgba(255, 255, 255, 0) 100%);
				transform: rotate(25deg);
				animation: shineSweep 4s infinite;
			}

			/* Desktop Preview Card (Right Column) */
			.preview-card-column {
				display: flex;
				flex-direction: column;
			}

			.desktop-live-preview-card {
				position: relative;
				height: 100%;
				background: linear-gradient(165deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 245, 249, 0.98) 100%);
				border: 1.5px solid #e2e8f0;
				border-radius: 24px;
				padding: 1.5rem;
				box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.05);
				display: flex;
				flex-direction: column;
				justify-content: space-between;
				overflow: hidden;
				transition: transform 0.2s ease, box-shadow 0.2s ease;
				animation: cardFadeIn 0.5s ease-out forwards;
			}

			.preview-ambient-glow {
				position: absolute;
				top: -40px;
				right: -40px;
				width: 150px;
				height: 150px;
				border-radius: 50%;
				background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
				filter: blur(24px);
				pointer-events: none;
			}

			.preview-top-row {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			.preview-cat-badge {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				padding: 5px 12px;
				background: #eef2ff;
				color: #4338ca;
				border-radius: 999px;
				font-size: 0.78rem;
				font-weight: 700;
				border: 1px solid #c7d2fe;
			}

			.preview-live-status {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				font-size: 0.75rem;
				font-weight: 700;
				color: #059669;
				background: #ecfdf5;
				padding: 4px 10px;
				border-radius: 999px;
				border: 1px solid #a7f3d0;
			}

			.pulse-indicator {
				width: 6px;
				height: 6px;
				border-radius: 50%;
				background-color: #10b981;
				box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
				animation: dotPulse 1.5s infinite;
			}

			.preview-device-center {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				padding: 1rem 0;
				margin: 0.65rem 0;
				background: rgba(248, 250, 252, 0.85);
				border: 1px dashed #cbd5e1;
				border-radius: 16px;
			}

			.preview-orb-icon {
				width: 60px;
				height: 60px;
				border-radius: 18px;
				background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
				display: flex;
				align-items: center;
				justify-content: center;
				color: #ffffff;
				font-size: 1.8rem;
				box-shadow: 0 10px 22px -4px rgba(79, 70, 229, 0.35);
				margin-bottom: 10px;
				animation: iconFloat 4s ease-in-out infinite;
			}

			.preview-id-pill {
				font-family: monospace;
				font-size: 0.92rem;
				font-weight: 800;
				color: #1e293b;
				background: #ffffff;
				padding: 3px 12px;
				border-radius: 8px;
				border: 1px solid #e2e8f0;
			}

			.preview-specs-box {
				margin-bottom: 0.6rem;
			}

			.preview-model-text {
				font-size: 1.15rem;
				font-weight: 800;
				color: #0f172a;
				letter-spacing: -0.015em;
				line-height: 1.3;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.preview-brand-text {
				font-size: 0.82rem;
				font-weight: 600;
				color: #6366f1;
				margin-top: 2px;
				margin-bottom: 0.85rem;
			}

			.preview-meta-grid {
				display: flex;
				flex-direction: column;
				gap: 5px;
				background: #ffffff;
				border: 1px solid #f1f5f9;
				border-radius: 12px;
				padding: 10px 12px;
			}

			.preview-meta-row {
				display: flex;
				justify-content: space-between;
				align-items: center;
				font-size: 0.78rem;
			}

			.meta-label {
				color: #94a3b8;
				font-weight: 600;
			}

			.meta-val {
				color: #1e293b;
				font-weight: 700;
			}

			.preview-card-footer {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding-top: 0.75rem;
				border-top: 1px solid #e2e8f0;
				font-size: 0.75rem;
				color: #64748b;
			}

			.radar-dot {
				width: 6px;
				height: 6px;
				border-radius: 50%;
				background-color: #3b82f6;
				animation: dotPulse 2s infinite;
			}
		}

		/* =============================================================
						   MOBILE STYLES (Screen <= 768px): Reverted to Classic Mobile Look
						   ============================================================= */
		@media (max-width: 768px) {
			.desktop-only-element {
				display: none !important;
			}

			.mobile-only-element {
				display: block !important;
			}

			.mobile-only-header {
				display: block;
				text-align: center;
				margin-bottom: 15px;
			}

			.mobile-only-header h2 {
				font-size: 1.35rem !important;
				font-weight: 800 !important;
				color: #0f172a !important;
				letter-spacing: -0.02em !important;
				margin: 0 !important;
			}

			.add-device-responsive-container {
				width: 100%;
				padding: 0;
				margin: 0;
			}

			.add-device-layout-grid {
				display: block !important;
				width: 100% !important;
			}

			.form-fields-wrapper {
				background: transparent !important;
				border: none !important;
				padding: 0 !important;
				box-shadow: none !important;
			}

			.form-grid {
				display: flex !important;
				flex-direction: column !important;
				gap: 14px !important;
				width: 100% !important;
			}

			.form-group {
				background: #ffffff !important;
				border: 1.5px solid #e2e8f0 !important;
				border-radius: 20px !important;
				padding: 16px 18px !important;
				box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04) !important;
				margin-bottom: 0 !important;
			}

			.form-group label {
				font-size: 0.78rem !important;
				font-weight: 700 !important;
				text-transform: uppercase !important;
				letter-spacing: 0.06em !important;
				color: #475569 !important;
				margin-bottom: 8px !important;
				display: flex !important;
				align-items: center !important;
				gap: 6px !important;
			}

			.form-group input,
			.form-group select {
				width: 100% !important;
				box-sizing: border-box !important;
				height: 48px !important;
				border-radius: 14px !important;
				background-color: #f8fafc !important;
				border: 1.5px solid #cbd5e1 !important;
				padding: 0 16px !important;
				font-size: 16px !important;
				font-weight: 600 !important;
				color: #0f172a !important;
				box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02) !important;
			}

			.form-actions {
				display: flex !important;
				flex-direction: row !important;
				gap: 12px !important;
				margin-top: 24px !important;
				padding-top: 18px !important;
				border-top: 1.5px dashed #e2e8f0 !important;
				width: 100% !important;
			}

			.form-actions button {
				flex: 1 !important;
				height: 52px !important;
				border-radius: 9999px !important;
				font-size: 1rem !important;
				font-weight: 700 !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				gap: 8px !important;
			}

			.form-actions button[type="submit"] {
				background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
				color: #ffffff !important;
				border: none !important;
				box-shadow: 0 6px 18px rgba(37, 99, 235, 0.28) !important;
			}

			.form-actions button[type="button"] {
				background: #ffffff !important;
				border: 1.5px solid #e2e8f0 !important;
				color: #475569 !important;
			}
		}

		/* Shared Animations */
		@keyframes slideDownFade {
			from {
				opacity: 0;
				transform: translateY(-12px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		@keyframes cardFadeIn {
			from {
				opacity: 0;
				transform: translateY(12px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		@keyframes iconFloat {

			0%,
			100% {
				transform: translateY(0);
			}

			50% {
				transform: translateY(-4px);
			}
		}

		@keyframes dotPulse {
			0% {
				transform: scale(0.95);
				box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
			}

			70% {
				transform: scale(1.05);
				box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
			}

			100% {
				transform: scale(0.95);
				box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
			}
		}

		@keyframes shineSweep {
			0% {
				left: -60%;
			}

			20%,
			100% {
				left: 140%;
			}
		}
	</style>
	<?php
	return ob_get_clean();
}

add_shortcode('device_form', 'device_form');
?>