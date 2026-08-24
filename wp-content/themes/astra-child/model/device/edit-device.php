<?php
if (!defined('ABSPATH')) {
    exit;
}

function edit_device_form($editing = null)
{
    ob_start();
    global $wpdb;

    $table_devices = 'Devices';

    $brands = $wpdb->get_results("SELECT BrandID, BrandName FROM Brands WHERE LOWER(BrandName) != 'other' ORDER BY BrandName ASC");
    $statuses = $wpdb->get_results("SELECT StatusID, StatusName FROM Statuses");
    $keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords");
    $categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories WHERE LOWER(CategoryName) != 'other' ORDER BY CategoryName ASC");
    $owners = $wpdb->get_results("SELECT o.OwnerID, o.Nickname, o.FirstName, o.LastName, d.DepartmentName FROM Owners o LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID ORDER BY o.Nickname ASC");

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if (!$editing && !empty($_GET['device'])) {
        $DeviceID = sanitize_text_field($_GET['device']);
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['DeviceID'])) {
        if (!is_user_logged_in() || !isset($_POST['_edit_dev_nonce']) || !wp_verify_nonce($_POST['_edit_dev_nonce'], 'edit_device_nonce')) {
            echo '<p style="color:red;">Security check failed.</p>';
            return ob_get_clean();
        }
        $DeviceID = sanitize_text_field($_POST['DeviceID']);
        $Model = sanitize_text_field($_POST['Model']);
        $SerialNumber = sanitize_text_field($_POST['SerialNumber']);

        $raw_brand_id = trim($_POST['BrandID'] ?? '');
        $brand_name_in = trim($_POST['BrandName'] ?? '');
        $new_brand_name = trim($_POST['new_brand_name'] ?? '');

        $brand_string = !empty($new_brand_name) ? $new_brand_name : (!empty($brand_name_in) ? $brand_name_in : $raw_brand_id);

        if (is_numeric($brand_string) && intval($brand_string) > 0) {
            $BrandID = intval($brand_string);
        } elseif (!empty($brand_string)) {
            $existing_brand_id = $wpdb->get_var($wpdb->prepare("SELECT BrandID FROM Brands WHERE LOWER(BrandName) = LOWER(%s)", $brand_string));
            if ($existing_brand_id) {
                $BrandID = intval($existing_brand_id);
            } else {
                $inserted = $wpdb->insert('Brands', ['BrandName' => ucfirst($brand_string)]);
                if (!$inserted) {
                    $next_brand_id = intval($wpdb->get_var("SELECT MAX(BrandID) FROM Brands")) + 1;
                    $wpdb->query($wpdb->prepare("INSERT INTO Brands (BrandID, BrandName) VALUES (%d, %s)", $next_brand_id, ucfirst($brand_string)));
                }
                $BrandID = intval($wpdb->get_var($wpdb->prepare("SELECT BrandID FROM Brands WHERE LOWER(BrandName) = LOWER(%s)", $brand_string)));
            }
        } else {
            $BrandID = intval($raw_brand_id);
        }

        $StatusID = intval($_POST['StatusID']);
        $KeywordID = intval($_POST['KeywordID'] ?? ($editing->KeywordID ?? 0));
        $OwnerID = !empty($_POST['OwnerID']) ? intval($_POST['OwnerID']) : null;
        $AddDeviceDate_edit = sanitize_text_field($_POST['AddDeviceDate']);
        $AddDeviceDate = date('Y-m-d', strtotime($AddDeviceDate_edit));
        $Reason = !empty($_POST['Reason']) ? sanitize_text_field($_POST['Reason']) : '';

        // Validate IDs
        $valid_brand = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM Brands WHERE BrandID = %d", $BrandID));
        $valid_status = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM Statuses WHERE StatusID = %d", $StatusID));

        if (!$valid_brand || !$valid_status) {
            $message = !$valid_brand ? 'Invalid Brand selected!' : 'Invalid Status selected!';
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: '{$message}',
                    showConfirmButton: true
                });
            </script>";
            return ob_get_clean();
        }

        // Security & Integrity check for Status
        $selected_status_name = strtolower($wpdb->get_var($wpdb->prepare("SELECT StatusName FROM Statuses WHERE StatusID = %d", $StatusID)) ?? '');
        if ($selected_status_name === 'available' || $selected_status_name === 'retired') {
            $OwnerID = null; // Auto-clear owner to prevent ghost assignment on Available/Retired
        }

        $data = [
            'Model' => $Model,
            'SerialNumber' => $SerialNumber,
            'BrandID' => $BrandID,
            'StatusID' => $StatusID,
            'KeywordID' => $KeywordID,
            'OwnerID' => $OwnerID,
            'AddDeviceDate' => $AddDeviceDate,
            'UpdatedAt' => current_time('mysql'),
        ];

        $format = ['%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s'];
        $where = ['DeviceID' => $DeviceID];
        $where_format = ['%s'];

        // Get previous info for history
        $device_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));

        $updated = $wpdb->update($table_devices, $data, $where, $format, $where_format);

        // Get category slug
        $category_slug = '';
        if ($device_info && isset($device_info->CategoryID)) {
            $category_name = $wpdb->get_var($wpdb->prepare(
                "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                $device_info->CategoryID
            ));
            if ($category_name) {
                $category_slug = sanitize_title($category_name);
            }
        }
        $redirect_url = $category_slug ? home_url('/' . $category_slug . '/?view=' . urlencode($DeviceID)) : home_url('/?view=' . urlencode($DeviceID));

        $owner_nickname = '-';
        if (!empty($device_info->OwnerID)) {
            $owner_info = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device_info->OwnerID));
            if ($owner_info) {
                $owner_nickname = $owner_info;
            }
        }

        if ($updated === false) {
            error_log('Update failed: ' . $wpdb->last_error);
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Update failed',
                    text: '" . esc_js($wpdb->last_error) . "',
                    showConfirmButton: true
                });
            </script>";
        } elseif ($updated === 0) {
            echo "<script>
                Swal.fire({
                    icon: 'info',
                    title: 'No changes detected',
                    showConfirmButton: true
                });
            </script>";
        } else {
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? '';

            $old_status_name = isset($device_info->StatusID) ? $wpdb->get_var($wpdb->prepare("SELECT StatusName FROM Statuses WHERE StatusID = %d", $device_info->StatusID)) : '';
            $new_status_name = $wpdb->get_var($wpdb->prepare("SELECT StatusName FROM Statuses WHERE StatusID = %d", $StatusID)) ?? '';

            $history_description = "Device ID {$DeviceID} information updated";
            if ($old_status_name && $new_status_name && $old_status_name !== $new_status_name) {
                $history_description .= " (Status: {$old_status_name} -> {$new_status_name})";
            }
            if ($Reason !== '') {
                $history_description .= " | Reason/Note: " . $Reason;
            }

            $wpdb->insert('History_new', [
                'DeviceID' => $DeviceID,
                'Action' => ($old_status_name !== $new_status_name ? 'Update Status' : 'Update Device'),
                'Date' => current_time('mysql'),
                'Description' => $history_description,
                'user_email' => $user_email,
                'CategoryID' => $device_info->CategoryID ?? null,
                'Owner' => $owner_nickname
            ]);

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Device updated!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.replace('{$redirect_url}');
                });
            </script>";
        }

        // Refresh after update
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));
    }

    $hideKeyword = false;
    $currentCategoryName = 'Device';
    foreach ($categories as $c) {
        if (isset($editing->CategoryID) && $c->CategoryID == $editing->CategoryID) {
            $currentCategoryName = $c->CategoryName;
            $cName = strtolower(trim($c->CategoryName));
            if ($cName === 'monitor' || $cName === 'laptop') {
                $hideKeyword = true;
            }
            break;
        }
    }

    $current_keyword_name = '—';
    foreach ($keywords as $k) {
        if (isset($editing->KeywordID) && $k->KeywordID == $editing->KeywordID) {
            $current_keyword_name = $k->KeywordName;
            break;
        }
    }

    $initial_status_name = 'Available';
    $initial_status_raw = 'available';
    foreach ($statuses as $s) {
        if (isset($editing->StatusID) && $s->StatusID == $editing->StatusID) {
            $initial_status_name = $s->StatusName;
            $initial_status_raw = strtolower(trim($s->StatusName));
            break;
        }
    }
    $status_color_map = [
        'available' => ['color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.15)', 'border' => 'rgba(16, 185, 129, 0.35)', 'dot' => '#10b981'],
        'in use' => ['color' => '#f43f5e', 'bg' => 'rgba(244, 63, 94, 0.15)', 'border' => 'rgba(244, 63, 94, 0.35)', 'dot' => '#f43f5e'],
        'inuse' => ['color' => '#f43f5e', 'bg' => 'rgba(244, 63, 94, 0.15)', 'border' => 'rgba(244, 63, 94, 0.35)', 'dot' => '#f43f5e'],
        'maintenance' => ['color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.15)', 'border' => 'rgba(245, 158, 11, 0.35)', 'dot' => '#f59e0b'],
        'repair' => ['color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.15)', 'border' => 'rgba(245, 158, 11, 0.35)', 'dot' => '#f59e0b'],
        'retired' => ['color' => '#94a3b8', 'bg' => 'rgba(148, 163, 184, 0.15)', 'border' => 'rgba(148, 163, 184, 0.35)', 'dot' => '#94a3b8'],
    ];
    $init_conf = $status_color_map[$initial_status_raw] ?? ['color' => '#818cf8', 'bg' => 'rgba(129, 140, 248, 0.15)', 'border' => 'rgba(129, 140, 248, 0.35)', 'dot' => '#818cf8'];

    $current_brand_name = '';
    if (!empty($editing->BrandID)) {
        foreach ($brands as $b) {
            if ($b->BrandID == $editing->BrandID) {
                $current_brand_name = $b->BrandName;
                break;
            }
        }
    }
    ?>

    <!-- Main Edit Device Container -->
    <div class="add-device-responsive-container">
        <!-- Desktop Ambient Glows -->
        <div class="bg-glow-orb bg-glow-orb-1 desktop-only-element"></div>
        <div class="bg-glow-orb bg-glow-orb-2 desktop-only-element"></div>

        <!-- Desktop Sleek Header Bar -->
        <div class="add-device-desktop-header desktop-only-element">
            <div class="desktop-header-left">
                <div class="desktop-icon-badge">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <br>
                    <p class="desktop-header-subtitle">
                        Update hardware parameters, ownership assignment, and asset status
                    </p>
                </div>
            </div>
            <div class="desktop-header-badge">
                <span class="pulse-dot-green"></span>
                <span>Asset Specification Update</span>
            </div>
        </div>

        <!-- HTML Form -->
        <form method="POST" action="" id="edit-device-form" class="edit-data-form add-device-main-form">
            <?php wp_nonce_field('edit_device_nonce', '_edit_dev_nonce'); ?>
            <input type="hidden" name="DeviceID" value="<?= esc_attr($editing->DeviceID ?? '') ?>">

            <!-- Mobile Only Header -->
            <div class="mobile-only-header">
                <h2>Edit Device</h2>
            </div>

            <!-- Desktop & Mobile Layout Grid -->
            <div class="add-device-layout-grid">
                <!-- Form Fields Side -->
                <div class="form-fields-wrapper">
                    <div class="form-grid modern-grid">

                        <!-- Category (Read-only) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-shapes field-icon-desktop desktop-only-element"></i>
                                    Category
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" value="<?= esc_attr($currentCategoryName) ?>" readonly
                                    class="input-locked">
                            </div>
                        </div>

                        <!-- DeviceID (Read-only) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-fingerprint field-icon-desktop desktop-only-element"></i>
                                    Device ID
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" value="<?= esc_attr($editing->DeviceID ?? '') ?>" readonly
                                    class="input-locked">
                            </div>
                        </div>

                        <!-- Brand Select (Standard Dropdown) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label for="edit-brand-select">
                                    <i class="fa-solid fa-building field-icon-desktop desktop-only-element"></i>
                                    Brand <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <select name="BrandID" id="edit-brand-select" required onchange="updateLivePreview()">
                                    <option value="">-- Select Brand --</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?= esc_attr($b->BrandID) ?>" <?= selected($editing->BrandID ?? '', $b->BrandID, false) ?>>
                                            <?= esc_html($b->BrandName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                                    value="<?= esc_attr($editing->Model ?? '') ?>" required>
                            </div>
                        </div>

                        <!-- Serial Number -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label for="serial_number_input">
                                    <i class="fa-solid fa-barcode field-icon-desktop desktop-only-element"></i>
                                    Serial Number
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" name="SerialNumber" id="serial_number_input"
                                    value="<?= esc_attr($editing->SerialNumber ?? '') ?>">
                                <div id="edit_serial_duplicate_warning"></div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label for="StatusID">
                                    <i class="fa-solid fa-circle-check field-icon-desktop desktop-only-element"></i>
                                    Status <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <select name="StatusID" id="StatusID" required>
                                    <option value="">-- Select Status --</option>
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?= esc_attr($s->StatusID) ?>"
                                            data-name="<?= esc_attr(strtolower($s->StatusName)) ?>"
                                            <?= selected($editing->StatusID ?? '', $s->StatusID, false) ?>>
                                            <?= esc_html($s->StatusName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Keyword (Locked / Read-only in Edit Form) -->
                        <div class="form-group modern-group" id="keyword_form_group" <?= $hideKeyword ? 'style="display: none;"' : '' ?>>
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-key field-icon-desktop desktop-only-element"></i>
                                    Keyword <span
                                        style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">(Locked)</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" value="<?= esc_attr($current_keyword_name) ?>" readonly
                                    class="input-locked">
                                <input type="hidden" name="KeywordID" id="KeywordID"
                                    value="<?= esc_attr($editing->KeywordID ?? 0) ?>">
                            </div>
                        </div>

                        <!-- Owner (Search & Popup) -->
                        <div class="form-group modern-group" id="owner-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-user field-icon-desktop desktop-only-element"></i>
                                    Owner (Assigned To)
                                </label>
                            </div>
                            <!-- Desktop Custom Searchable Dropdown (Desktop Only) -->
                            <div class="field-input-wrap desktop-only-element" id="website_edit_owner_search_wrap" style="position: relative;">
                                <div style="position: relative; width: 100%;">
                                    <i class="fa-solid fa-magnifying-glass"
                                        style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; pointer-events: none; z-index: 2;"></i>
                                    <?php
                                    $current_owner_name = '';
                                    if (!empty($editing->OwnerID)) {
                                        foreach ($owners as $o) {
                                            if ($o->OwnerID == $editing->OwnerID) {
                                                $current_owner_name = trim($o->Nickname) . ($o->DepartmentName ? ' (' . $o->DepartmentName . ')' : '');
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <input type="text" id="edit_owner_search_input"
                                        value="<?= esc_attr($current_owner_name) ?>"
                                        placeholder="Type or select employee..." autocomplete="off"
                                        onfocus="this.select(); openEditOwnerSearchPopup()"
                                        oninput="onEditOwnerInputChanged(this.value)">
                                    <i class="fa-solid fa-chevron-down"
                                        style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem; pointer-events: none; z-index: 2;"></i>
                                </div>

                                <!-- Live Floating Results Popup -->
                                <div id="edit_owner_search_popup"
                                    style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); z-index: 99999; padding: 4px;">
                                </div>
                            </div>

                            <!-- Mobile Native System Select (Mobile Only) -->
                            <div class="field-input-wrap mobile-only-element">
                                <select id="mobile_owner_select" onchange="syncMobileOwnerSelect(this.value)">
                                    <option value="">-- No Owner --</option>
                                    <?php foreach ($owners as $o): ?>
                                        <?php $opt_label = trim($o->Nickname) . ($o->DepartmentName ? ' (' . $o->DepartmentName . ')' : ''); ?>
                                        <option value="<?= esc_attr($o->OwnerID) ?>" <?= selected($editing->OwnerID ?? '', $o->OwnerID, false) ?>>
                                            <?= esc_html($opt_label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="OwnerID" id="OwnerID"
                                value="<?= esc_attr($editing->OwnerID ?? '') ?>">
                        </div>

                        <!-- Add Device Date -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label for="AddDeviceDate">
                                    <i class="fa-solid fa-calendar-days field-icon-desktop desktop-only-element"></i>
                                    Add Device Date <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="date" name="AddDeviceDate" id="AddDeviceDate"
                                    value="<?= esc_attr($editing->AddDeviceDate ?? '') ?>"
                                    min="<?= esc_attr($editing->AddDeviceDate ?? '2020-01-01') ?>" required>
                            </div>
                        </div>

                        <!-- Reason (Conditional for Retired / Maintenance) -->
                        <div class="form-group modern-group" id="reason-group" style="display: none; grid-column: span 2;">
                            <div class="field-header">
                                <label for="Reason">
                                    <i class="fa-solid fa-circle-exclamation field-icon-desktop desktop-only-element"></i>
                                    Reason / Notes <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" name="Reason" id="Reason"
                                    placeholder="Please enter reason (Required for Retired)">
                            </div>
                        </div>

                    </div>

                    <!-- Form Actions (Cancel & Update) -->
                    <div class="form-actions modern-form-actions">
                        <button type="button" onclick="handleCancelEditDevice()"
                            class="btn btn-danger btn-cancel-action border rounded-pill">
                            <i class="fa-solid fa-arrow-left me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-submit-action border rounded-pill">
                            <span class="btn-shine-effect desktop-only-element"></span>
                            <i class="fa-solid fa-check me-1"></i>
                            <span>Update</span>
                        </button>
                    </div>
                </div>

                <!-- Right Side: Live Holographic Asset Preview Card (Desktop Only) -->
                <div class="preview-panel-column desktop-only-element">
                    <div class="live-preview-card" id="interactive-preview-card">
                        <div class="preview-glow-bg"></div>

                        <!-- Card Top Bar -->
                        <div class="preview-top-bar">
                            <span class="preview-tag"><i class="fa-solid fa-cube me-1"></i> Asset Summary</span>
                            <span class="preview-status-badge" id="preview-status-pill"
                                style="color: <?= $init_conf['color'] ?>; background: <?= $init_conf['bg'] ?>; border: 1px solid <?= $init_conf['border'] ?>;">
                                <span class="preview-status-dot" id="preview-status-dot"
                                    style="background: <?= $init_conf['dot'] ?>; box-shadow: 0 0 8px <?= $init_conf['dot'] ?>;"></span>
                                <span id="preview-status-text"><?= esc_html($initial_status_name) ?></span>
                            </span>
                        </div>

                        <!-- Dynamic Animated Category Visualizer -->
                        <div class="preview-visual-box">
                            <div class="preview-icon-halo"></div>
                            <div class="preview-icon-main" id="preview-category-icon">
                                <i
                                    class="fa-solid <?= (strtolower($currentCategoryName) === 'monitor') ? 'fa-desktop' : ((strtolower($currentCategoryName) === 'accessories') ? 'fa-plug' : 'fa-laptop') ?>"></i>
                            </div>
                            <div class="preview-id-pill" id="preview-device-id-badge">
                                <?= esc_html($editing->DeviceID ?? 'DEV000') ?>
                            </div>
                        </div>

                        <!-- Card Metadata Details -->
                        <div class="preview-specs-box">
                            <div class="preview-device-name" id="preview-model-text">
                                <?= esc_html($editing->Model ?: 'Hardware Asset') ?>
                            </div>
                            <div class="preview-brand-tag" id="preview-brand-text">
                                <?php
                                $bName = 'Brand';
                                foreach ($brands as $b) {
                                    if ($b->BrandID == ($editing->BrandID ?? 0)) {
                                        $bName = $b->BrandName;
                                        break;
                                    }
                                }
                                echo esc_html($bName);
                                ?>
                            </div>

                            <div class="preview-meta-grid">
                                <div class="preview-meta-item">
                                    <span class="meta-label"><i class="fa-solid fa-barcode me-1"></i> Serial No</span>
                                    <span class="meta-value font-mono"
                                        id="preview-serial-text"><?= esc_html($editing->SerialNumber ?: '—') ?></span>
                                </div>
                                <div class="preview-meta-item">
                                    <span class="meta-label"><i class="fa-solid fa-user me-1"></i> Assigned To</span>
                                    <span class="meta-value"
                                        id="preview-owner-text"><?= esc_html($current_owner_name ?: 'None') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Responsive Stylesheet (Expanded Single-Screen Desktop + Classic Clean Mobile) -->
    <style>
        /* Magnifying Glass Overlay & Padding Fix */
        #edit_owner_search_input,
        #owner_search_input {
            padding-left: 42px !important;
            padding-right: 36px !important;
        }

        .brand-search-box-wrap {
            position: relative;
            width: 100%;
        }

        .brand-search-box-wrap .position-relative {
            display: flex;
            align-items: center;
            position: relative;
        }

        .brand-search-icon {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 0.8rem;
            pointer-events: none;
            z-index: 2;
        }

        .brand-search-filter-input {
            width: 100% !important;
            height: 38px !important;
            padding-left: 32px !important;
            padding-right: 30px !important;
            font-size: 0.84rem !important;
            font-weight: 500 !important;
            background-color: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            color: #0f172a !important;
            transition: all 0.15s ease !important;
            box-sizing: border-box !important;
        }

        .brand-search-filter-input:focus {
            background-color: #ffffff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12) !important;
            outline: none !important;
        }

        .brand-search-clear-btn {
            position: absolute;
            right: 8px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 0.78rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-search-clear-btn:hover {
            color: #475569;
        }

        /* Default / Mobile-First: Hide Desktop Exclusive Elements */
        .desktop-only-element,
        .preview-panel-column,
        .add-device-desktop-header,
        .bg-glow-orb {
            display: none !important;
        }

        .mobile-only-header,
        .mobile-only-element {
            display: block !important;
        }

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

            .field-input-wrap.desktop-only-element {
                display: block !important;
                width: 100% !important;
            }

            .preview-panel-column {
                display: flex !important;
            }

            .add-device-desktop-header {
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
                border-radius: 20px;
                padding: 1.75rem;
                box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
                animation: cardFadeIn 0.4s ease-out forwards;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .modern-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1rem 1.25rem !important;
            }

            .modern-group {
                display: flex !important;
                flex-direction: column !important;
                margin-bottom: 0 !important;
            }

            .modern-group .field-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-bottom: 5px !important;
            }

            .modern-group .field-header label {
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #1e293b !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 0 !important;
                letter-spacing: -0.01em !important;
            }

            .field-icon-desktop {
                color: #4f46e5;
                font-size: 0.85rem;
            }

            .required-star {
                color: #ef4444;
                font-weight: bold;
            }

            .field-input-wrap input,
            .field-input-wrap select {
                width: 100% !important;
                height: 44px !important;
                padding: 0 14px !important;
                font-size: 0.92rem !important;
                font-weight: 500 !important;
                color: #0f172a !important;
                background-color: #ffffff !important;
                border: 1.5px solid #cbd5e1 !important;
                border-radius: 10px !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                box-sizing: border-box !important;
                appearance: none;
            }

            .field-input-wrap select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
                background-position: right 0.75rem center !important;
                background-repeat: no-repeat !important;
                background-size: 1.1em 1.1em !important;
                padding-right: 2.2rem !important;
                cursor: pointer;
            }

            .field-input-wrap input:hover:not([readonly]):not([disabled]),
            .field-input-wrap select:hover:not([readonly]):not([disabled]) {
                border-color: #94a3b8 !important;
            }

            .field-input-wrap input:focus:not([readonly]):not([disabled]),
            .field-input-wrap select:focus:not([readonly]):not([disabled]) {
                background-color: #ffffff !important;
                border-color: #4f46e5 !important;
                outline: none !important;
                box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.16) !important;
            }

            .field-input-wrap input[readonly],
            .field-input-wrap input:disabled,
            .field-input-wrap select:disabled,
            .input-locked {
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
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                    transform: rotate(25deg);
                    animation: shineSweep 4s infinite cubic-bezier(0.4, 0, 0.2, 1);
                }

                /* Preview Column (Right Side) */
                .preview-panel-column {
                    display: flex !important;
                    flex-direction: column !important;
                    animation: cardFadeIn 0.5s ease-out forwards;
                }

                .live-preview-card {
                    position: relative;
                    height: 100%;
                    background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 100%);
                    border-radius: 20px;
                    padding: 1.5rem;
                    color: #ffffff;
                    box-shadow: 0 16px 36px -8px rgba(15, 23, 42, 0.35);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    overflow: hidden;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }

                .preview-top-bar {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    z-index: 1;
                }

                .preview-tag {
                    font-size: 0.72rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                    color: #a5b4fc;
                    background: rgba(165, 180, 252, 0.12);
                    padding: 4px 10px;
                    border-radius: 6px;
                    border: 1px solid rgba(165, 180, 252, 0.2);
                }

                .preview-status-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 0.75rem;
                    font-weight: 700;
                    color: #10b981;
                    background: rgba(16, 185, 129, 0.15);
                    border: 1px solid rgba(16, 185, 129, 0.3);
                    padding: 4px 12px;
                    border-radius: 999px;
                    transition: all 0.3s ease;
                }

                .preview-status-dot {
                    width: 6px;
                    height: 6px;
                    background: #10b981;
                    border-radius: 50%;
                    box-shadow: 0 0 8px #10b981;
                    transition: all 0.3s ease;
                }

                .preview-visual-box {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 1.25rem 0;
                    z-index: 1;
                }

                .preview-icon-halo {
                    position: absolute;
                    width: 110px;
                    height: 110px;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, transparent 70%);
                    filter: blur(14px);
                    z-index: 0;
                }

                .preview-icon-main {
                    font-size: 3.5rem;
                    color: #ffffff;
                    text-shadow: 0 4px 20px rgba(99, 102, 241, 0.6);
                    animation: iconFloat 3.5s ease-in-out infinite;
                    z-index: 1;
                    margin-bottom: 0.75rem;
                }

                .preview-id-pill {
                    font-size: 1.1rem;
                    font-weight: 800;
                    letter-spacing: 0.05em;
                    color: #e0e7ff;
                    background: rgba(255, 255, 255, 0.08);
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    padding: 4px 16px;
                    border-radius: 12px;
                    backdrop-filter: blur(8px);
                    z-index: 1;
                    font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
                }

                .preview-specs-box {
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 14px;
                    padding: 1rem;
                    backdrop-filter: blur(12px);
                    z-index: 1;
                }

                .preview-device-name {
                    font-size: 1.15rem;
                    font-weight: 800;
                    color: #ffffff;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .preview-brand-tag {
                    font-size: 0.8rem;
                    color: #818cf8;
                    font-weight: 600;
                    margin-bottom: 0.75rem;
                }

                .preview-meta-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px;
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                    padding-top: 8px;
                }

                .preview-meta-item {
                    display: flex;
                    flex-direction: column;
                }

                .preview-meta-item .meta-label {
                    font-size: 0.68rem;
                    color: #94a3b8;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .preview-meta-item .meta-value {
                    font-size: 0.82rem;
                    font-weight: 600;
                    color: #f1f5f9;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
            }

            /* =============================================================
                           MOBILE STYLES (Screen <= 768px): Reverted to Classic Mobile Look
                           ============================================================= */
            @media (max-width: 768px) {

                .desktop-only-element,
                .preview-panel-column,
                .add-device-desktop-header,
                .bg-glow-orb {
                    display: none !important;
                }

                .mobile-only-header {
                    display: block !important;
                    margin-bottom: 1rem !important;
                    text-align: center !important;
                }

                .mobile-only-header h2 {
                    font-size: 1.5rem !important;
                    font-weight: 800 !important;
                    color: #0f172a !important;
                    margin: 0 !important;
                }

                .add-device-responsive-container {
                    padding: 0 4px 2rem 4px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    box-sizing: border-box !important;
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
                    border-top: 1px solid #f1f5f9 !important;
                }

                .form-actions .btn {
                    flex: 1 1 0 !important;
                    width: 100% !important;
                    height: 50px !important;
                    font-size: 16px !important;
                    font-weight: 700 !important;
                    border-radius: 9999px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
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

    <script>
        const editOwnerDataList = [
            { id: '', name: '-- No Owner --', deptName: '' },
            <?php foreach ($owners as $o): ?>
                                                {
                    id: <?= intval($o->OwnerID) ?>,
                    name: <?= json_encode(trim($o->Nickname)) ?>,
                    deptName: <?= json_encode($o->DepartmentName ?? '') ?>
                },
            <?php endforeach; ?>
        ];

        const statusConfigMap = {
            'available': {
                color: '#10b981',
                bg: 'rgba(16, 185, 129, 0.15)',
                border: 'rgba(16, 185, 129, 0.35)',
                dot: '#10b981'
            },
            'in use': {
                color: '#f43f5e',
                bg: 'rgba(244, 63, 94, 0.15)',
                border: 'rgba(244, 63, 94, 0.35)',
                dot: '#f43f5e'
            },
            'inuse': {
                color: '#f43f5e',
                bg: 'rgba(244, 63, 94, 0.15)',
                border: 'rgba(244, 63, 94, 0.35)',
                dot: '#f43f5e'
            },
            'maintenance': {
                color: '#f59e0b',
                bg: 'rgba(245, 158, 11, 0.15)',
                border: 'rgba(245, 158, 11, 0.35)',
                dot: '#f59e0b'
            },
            'repair': {
                color: '#f59e0b',
                bg: 'rgba(245, 158, 11, 0.15)',
                border: 'rgba(245, 158, 11, 0.35)',
                dot: '#f59e0b'
            },
            'retired': {
                color: '#94a3b8',
                bg: 'rgba(148, 163, 184, 0.15)',
                border: 'rgba(148, 163, 184, 0.35)',
                dot: '#94a3b8'
            }
        };

        function openEditOwnerSearchPopup() {
            const input = document.getElementById('edit_owner_search_input');
            if (input) filterEditOwnerSearchPopup(input.value);
        }

        function onEditOwnerInputChanged(val) {
            filterEditOwnerSearchPopup(val);
            if (!val.trim()) {
                const select = document.getElementById('OwnerID');
                if (select) select.value = '';
                updateLivePreview();
            }
        }

        function filterEditOwnerSearchPopup(query) {
            const popup = document.getElementById('edit_owner_search_popup');
            if (!popup) return;
            const term = query.toLowerCase().trim();

            const filtered = editOwnerDataList.filter(o => {
                return !term || o.name.toLowerCase().includes(term) || o.deptName.toLowerCase().includes(term);
            });

            if (filtered.length === 0) {
                popup.innerHTML = `<div style="padding: 10px 14px; color: #94a3b8; font-size: 0.85rem; text-align: center;">❌ No matching employee found</div>`;
            } else {
                let html = '';
                filtered.forEach(o => {
                    const deptBadge = o.deptName ? `<span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-weight: 600;">${o.deptName}</span>` : '';
                    html += `
                        <div class="owner-item-row" onclick="selectEditOwnerItem(${o.id}, '${o.name.replace(/'/g, "\\'")}', '${(o.deptName || '').replace(/'/g, "\\'")}')" onmousedown="event.preventDefault(); selectEditOwnerItem(${o.id}, '${o.name.replace(/'/g, "\\'")}', '${(o.deptName || '').replace(/'/g, "\\'")}')" ontouchstart="event.preventDefault(); selectEditOwnerItem(${o.id}, '${o.name.replace(/'/g, "\\'")}', '${(o.deptName || '').replace(/'/g, "\\'")}')" style="padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='transparent';">
                            <span style="font-weight: 600; color: #0f172a;"><i class="fa-solid fa-user me-2" style="color: #64748b; font-size: 0.8rem;"></i>${o.name}</span>
                            ${deptBadge}
                        </div>
                    `;
                });
                popup.innerHTML = html;
            }
            popup.style.display = 'block';
        }

        function selectEditOwnerItem(id, name, dept) {
            const input = document.getElementById('edit_owner_search_input');
            const select = document.getElementById('OwnerID');
            const popup = document.getElementById('edit_owner_search_popup');

            if (input) input.value = (id === '' ? '' : (dept ? `${name} (${dept})` : name));
            if (select) select.value = id;
            if (popup) popup.style.display = 'none';
            updateLivePreview();
        }

        document.addEventListener('click', function (e) {
            const wrap = document.getElementById('website_edit_owner_search_wrap');
            const popup = document.getElementById('edit_owner_search_popup');
            if (wrap && popup && !wrap.contains(e.target)) {
                popup.style.display = 'none';
            }
        });

        function updateLivePreview() {
            const modelInput = document.getElementById('model_input');
            const brandSelect = document.getElementById('edit-brand-select');
            const serialInput = document.getElementById('serial_number_input');
            const statusSelect = document.getElementById('StatusID');
            const ownerInput = document.getElementById('edit_owner_search_input');

            const pModel = document.getElementById('preview-model-text');
            const pBrand = document.getElementById('preview-brand-text');
            const pSerial = document.getElementById('preview-serial-text');
            const pOwner = document.getElementById('preview-owner-text');

            const pStatus = document.getElementById('preview-status-text');
            const pStatusPill = document.getElementById('preview-status-pill');
            const pStatusDot = document.getElementById('preview-status-dot');

            if (pModel && modelInput) {
                pModel.textContent = modelInput.value.trim() || 'Hardware Asset';
            }
            if (pBrand) {
                if (brandSelect && brandSelect.selectedIndex >= 0) {
                    const opt = brandSelect.options[brandSelect.selectedIndex];
                    pBrand.textContent = (opt && brandSelect.value) ? opt.text : 'Manufacturer Brand';
                } else {
                    pBrand.textContent = 'Manufacturer Brand';
                }
            }
            if (pSerial && serialInput) {
                pSerial.textContent = serialInput.value.trim() || '—';
            }
            if (pOwner && ownerInput) {
                pOwner.textContent = ownerInput.value.trim() || 'None';
            }

            if (statusSelect && statusSelect.selectedIndex >= 0) {
                const opt = statusSelect.options[statusSelect.selectedIndex];
                const rawName = (opt ? (opt.getAttribute('data-name') || opt.text) : '').toLowerCase().trim();
                const conf = statusConfigMap[rawName] || {
                    color: '#818cf8',
                    bg: 'rgba(129, 140, 248, 0.15)',
                    border: 'rgba(129, 140, 248, 0.35)',
                    dot: '#818cf8'
                };

                if (pStatus) pStatus.textContent = opt ? opt.text : 'Status';
                if (pStatusPill) {
                    pStatusPill.style.color = conf.color;
                    pStatusPill.style.backgroundColor = conf.bg;
                    pStatusPill.style.borderColor = conf.border;
                }
                if (pStatusDot) {
                    pStatusDot.style.backgroundColor = conf.dot;
                    pStatusDot.style.boxShadow = `0 0 8px ${conf.dot}`;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const statusSelect = document.getElementById('StatusID');
            const ownerGroup = document.getElementById('owner-group');
            const ownerSelect = document.getElementById('OwnerID');
            const dateField = document.getElementById('AddDeviceDate');

            function handleStatusChange() {
                if (!statusSelect) return;
                const selectedOption = statusSelect.options[statusSelect.selectedIndex];
                const statusName = selectedOption ? (selectedOption.getAttribute('data-name') || '').toLowerCase() : '';
                const reasonGroup = document.getElementById('reason-group');
                const reasonInput = document.getElementById('Reason');

                if (statusName === 'retired') {
                    if (reasonGroup) reasonGroup.style.display = 'flex';
                    if (reasonInput) {
                        reasonInput.required = true;
                        reasonInput.placeholder = 'Please enter reason (Required for Retired)';
                    }
                } else if (statusName === 'maintenance') {
                    if (reasonGroup) reasonGroup.style.display = 'flex';
                    if (reasonInput) {
                        reasonInput.required = false;
                        reasonInput.placeholder = 'Please enter maintenance details / reason (Optional)';
                    }
                } else {
                    if (reasonGroup) reasonGroup.style.display = 'none';
                    if (reasonInput) {
                        reasonInput.required = false;
                        reasonInput.value = '';
                    }
                }

                if (statusName === 'retired' || statusName === 'available') {
                    if (ownerGroup) ownerGroup.style.display = 'none';
                    if (ownerSelect) ownerSelect.value = '';
                    const ownerInput = document.getElementById('edit_owner_search_input');
                    if (ownerInput) ownerInput.value = '';
                } else {
                    if (ownerGroup) ownerGroup.style.display = 'flex';
                }

                if (statusName === 'retired') {
                    if (dateField) {
                        if (!dateField.value) {
                            dateField.value = new Date().toISOString().split('T')[0];
                        }
                        dateField.readOnly = true;
                        dateField.style.backgroundColor = '#f8fafc';
                        dateField.style.color = '#64748b';
                        dateField.style.cursor = 'not-allowed';
                    }
                } else {
                    if (dateField) {
                        dateField.readOnly = false;
                        dateField.style.backgroundColor = '';
                        dateField.style.color = '';
                        dateField.style.cursor = '';
                    }
                }
                updateLivePreview();
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', handleStatusChange);
                handleStatusChange();
            }

            const modelInput = document.getElementById('model_input');
            const serialInput = document.getElementById('serial_number_input');
            const brandInput = document.getElementById('edit_brand_input');
            const newBrandInput = document.getElementById('edit_new_brand_name');

            if (brandInput) {
                brandInput.addEventListener('focus', function () {
                    filterEditBrandItems(this.value);
                    openEditBrandDropdown();
                });
                brandInput.addEventListener('input', syncEditBrandSelection);
                brandInput.addEventListener('change', syncEditBrandSelection);
            }

            if (modelInput) modelInput.addEventListener('input', updateLivePreview);
            if (serialInput) serialInput.addEventListener('input', updateLivePreview);
            if (newBrandInput) newBrandInput.addEventListener('input', updateLivePreview);

            // Form Submit Validation for Brand
            const editForm = document.getElementById('edit-device-form');
            if (editForm) {
                editForm.addEventListener('submit', function (e) {
                    const newBrandWrap = document.getElementById('edit_new_brand_wrapper');
                    const isNewMode = (newBrandWrap && newBrandWrap.style.display !== 'none');

                    if (isNewMode) {
                        const newName = newBrandInput ? newBrandInput.value.trim() : '';
                        if (!newName) {
                            e.preventDefault();
                            Swal.fire({
                                icon: 'warning',
                                title: '⚠️ Brand Name Required',
                                text: 'Please enter a name for the new brand.',
                                confirmButtonColor: '#2563eb'
                            });
                            return false;
                        }
                    } else {
                        const typed = brandInput ? brandInput.value.trim() : '';
                        if (!typed) {
                            e.preventDefault();
                            Swal.fire({
                                icon: 'warning',
                                title: '⚠️ Brand Required',
                                text: 'Please choose or enter a brand name.',
                                confirmButtonColor: '#2563eb'
                            });
                            return false;
                        }

                        // Check if brand exists in system
                        let found = isEditBrandInList(typed);

                        if (!found) {
                            e.preventDefault();
                            e.stopPropagation();

                            Swal.fire({
                                icon: 'error',
                                title: '❌ Brand Not Found',
                                html: `Brand "<strong>${typed}</strong>" is not found in the inventory.<br><br>Please select an existing brand from the list or click <strong>+ Add as New Brand</strong> to create it.`,
                                showCancelButton: true,
                                confirmButtonText: '<i class="fa-solid fa-plus-circle me-1"></i> Add as New Brand',
                                cancelButtonText: 'Select Existing Brand',
                                confirmButtonColor: '#2563eb',
                                cancelButtonColor: '#64748b'
                            }).then((res) => {
                                if (res.isConfirmed) {
                                    toggleEditNewBrandMode(typed);
                                } else {
                                    openEditBrandDropdown();
                                    if (brandInput) brandInput.focus();
                                }
                            });
                            return false;
                        }
                    }
                });
            }

            // Smart Cancel Navigation (Reliable even after Page Refresh)
            window.handleCancelEditDevice = function () {
                const ref = document.referrer;
                if (ref && ref.includes(window.location.host) && !ref.includes('edit=') && !ref.includes('receive=') && !ref.includes('add=')) {
                    window.location.href = ref;
                } else {
                    window.location.href = '<?= esc_url(home_url('/home/')) ?>';
                }
            };

            // Initial preview sync
            updateLivePreview();
        });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('edit_device', 'edit_device_form');
