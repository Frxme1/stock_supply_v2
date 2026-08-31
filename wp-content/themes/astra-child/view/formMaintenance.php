<?php
if (!defined('ABSPATH')) {
    exit;
}

function form_maintenance($editing = null)
{
    if (!is_object($editing)) {
        $editing = null;
    }

    global $wpdb;
    $table_device = 'Devices';
    $table_maintenance = 'Maintenance';
    $table_statuses = 'Statuses';

    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // Alert function
    if (!function_exists('show_alert')) {
        function show_alert($icon, $title, $html = '', $redirect = '')
        {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: '$icon',
                        title: '$title',
                        html: `" . $html . "` ,
                        showConfirmButton: " . ($redirect ? "false" : "true") . ",
                        timer: " . ($redirect ? "1500" : "null") . "
                    })" . ($redirect ? ".then(() => { window.location.href = '$redirect'; })" : "") . ";
                });
            </script>";
        }
    }

    // Handle form submit
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_device'])) {
        if (!is_user_logged_in() || !isset($_POST['_maint_nonce']) || !wp_verify_nonce($_POST['_maint_nonce'], 'update_maintenance_nonce')) {
            show_alert('error', 'Unauthorized', 'Security check failed.');
            return ob_get_clean();
        }
        $DeviceID = sanitize_text_field($_POST['DeviceID'] ?? '');
        $RepairDate = sanitize_text_field($_POST['RepairDate'] ?? current_time('Y-m-d'));
        $SelectedDetails = sanitize_text_field($_POST['Details'] ?? '');
        $OtherDetails = sanitize_text_field($_POST['OtherDetails'] ?? '');

        if (!empty($SelectedDetails) && !empty($OtherDetails)) {
            $Details = $SelectedDetails . ' - ' . $OtherDetails;
        } elseif (!empty($OtherDetails)) {
            $Details = $OtherDetails;
        } else {
            $Details = $SelectedDetails;
        }

        if (empty($Details)) {
            show_alert('error', 'Incomplete Form', 'Please select a maintenance reason or enter details.');
            return ob_get_clean();
        }

        $device_info = $wpdb->get_row($wpdb->prepare(
            "SELECT user_email, CategoryID, OwnerID, Model, BrandID FROM $table_device WHERE DeviceID = %s",
            $DeviceID
        ));

        if (!$device_info) {
            show_alert('error', 'Error!', 'Device not found.');
            return ob_get_clean();
        }

        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email ?: $device_info->user_email;

        // Process photo upload if provided
        $photo_url = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploaded = wp_handle_upload($_FILES['photo'], array('test_form' => false));
            if ($uploaded && !isset($uploaded['error'])) {
                $photo_url = $uploaded['url'];
            }
        }

        // Insert into Maintenance
        $inserted = $wpdb->insert(
            $table_maintenance,
            [
                'DeviceID' => $DeviceID,
                'RepairDate' => $RepairDate,
                'Details' => $Details,
                'user_email' => $user_email,
                'Photo' => $photo_url,
                'CreatedAt' => current_time('mysql'),
                'UpdatedAt' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted || $wpdb->last_error) {
            show_alert('error', 'Database Error', 'An error occurred while creating the maintenance record.');
            return ob_get_clean();
        }

        // Get StatusID for Maintenance
        $status_id = $wpdb->get_var($wpdb->prepare("SELECT StatusID FROM $table_statuses WHERE StatusName = %s", 'Maintenance'));
        if (!$status_id) {
            show_alert('error', 'Error!', 'Status "Maintenance" not found.');
            return ob_get_clean();
        }

        // Update device status
        $updated = $wpdb->update(
            $table_device,
            [
                'StatusID' => $status_id,
                'RepairDate' => $RepairDate
            ],
            ['DeviceID' => $DeviceID],
            ['%d', '%s'],
            ['%s']
        );

        if ($updated === false || $wpdb->last_error) {
            show_alert('error', 'Database Error', 'An error occurred while updating the device status.');
            return ob_get_clean();
        }

        // Get Owner nickname
        $owner_nickname = '-';
        if (!empty($device_info->OwnerID)) {
            $owner_info = $wpdb->get_var($wpdb->prepare(
                "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                $device_info->OwnerID
            ));
            if ($owner_info) {
                $owner_nickname = $owner_info;
            }
        }

        // Insert history
        $wpdb->insert('History_new', [
            'DeviceID' => $DeviceID,
            'Action' => 'Maintenance',
            'Date' => current_time('mysql'),
            'Description' => "Device ID {$DeviceID} set to Maintenance. Reason: {$Details}",
            'user_email' => $user_email,
            'CategoryID' => $device_info->CategoryID ?? null,
            'Owner' => $owner_nickname,
            'Photo' => $photo_url
        ]);

        // Send email notification
        if (function_exists('stock_supply_send_email') && !empty($device_info->OwnerID)) {
            stock_supply_send_email('Maintenance', $DeviceID, $device_info->OwnerID, $Details);
        }

        show_alert('success', 'Sent to Repair', 'The device has been placed in maintenance.', home_url('/maintenance/'));
        return ob_get_clean();
    }

    // Load data for form
    if (!$editing) {
        $req_device_id = sanitize_text_field($_GET['maintenance'] ?? $_GET['DeviceID'] ?? $_GET['id'] ?? '');
        if ($req_device_id) {
            $editing = $wpdb->get_row($wpdb->prepare("
                SELECT d.*, c.CategoryName, b.BrandName, m.RepairDate, m.Details
                FROM {$table_device} d
                LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
                LEFT JOIN Brands b ON d.BrandID = b.BrandID
                LEFT JOIN Maintenance m ON d.DeviceID = m.DeviceID
                WHERE d.DeviceID = %s
                ORDER BY m.MaintenanceID DESC
                LIMIT 1
            ", $req_device_id));
        }
    } elseif ($editing && !isset($editing->CategoryName)) {
        $editing = $wpdb->get_row($wpdb->prepare("
            SELECT d.*, c.CategoryName, b.BrandName
            FROM {$table_device} d
            LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
            LEFT JOIN Brands b ON d.BrandID = b.BrandID
            WHERE d.DeviceID = %s
        ", $editing->DeviceID));
    }

    $maint_status_id = (int) ($wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Maintenance'") ?: 3);
    $is_in_maintenance = ($editing && isset($editing->StatusID) && (int) $editing->StatusID === $maint_status_id);
    $dateValue = ($is_in_maintenance && !empty($editing->RepairDate)) ? date('Y-m-d', strtotime($editing->RepairDate)) : current_time('Y-m-d');
    $details_val = $is_in_maintenance ? ($editing->Details ?? '') : '';
    $other_text = '';
    $known_options = [
        'Screen Issue',
        'Battery Issue',
        'Power Issue',
        'Keyboard / Mouse Issue',
        'Hardware Upgrade',
        'Software Issue'
    ];

    if (!empty($details_val) && !in_array($details_val, $known_options)) {
        if (strpos($details_val, 'Others - ') === 0) {
            $other_text = substr($details_val, strlen('Others - '));
        } elseif ($details_val !== 'Others') {
            $other_text = $details_val;
        }
    }
    ?>

    <!-- Main Responsive Maintenance Form Component -->
    <div id="maintenance-form-wrapper" class="maintenance-bento-wrapper">
        <!-- Desktop Ambient Glow Orbs -->
        <div class="maint-glow-orb maint-glow-1 desktop-only-el"></div>
        <div class="maint-glow-orb maint-glow-2 desktop-only-el"></div>

        <!-- Desktop Sleek Header Bar -->
        <div class="maint-header-bar desktop-only-el">
            <div class="maint-header-left">
                <div class="maint-icon-badge">
                    <i class="fa-solid fa-wrench"></i>
                </div>
                <div>
                    <br>
                    <p class="maint-header-subtitle">
                        Send device for repair and record issue details
                    </p>
                </div>
            </div>
            <div class="maint-header-badge">
                <span class="maint-pulse-dot"></span>
                <span>Send to Repair</span>
            </div>
        </div>

        <?php
        $qr_details_only = true;
        include(get_stylesheet_directory() . '/model/shared/qr_scanner_bar.php');
        ?>

        <form method="POST" action="" id="maintenance-device-form" class="maint-main-form" enctype="multipart/form-data">
            <?php wp_nonce_field('update_maintenance_nonce', '_maint_nonce'); ?>
            <input type="hidden" name="DeviceID" value="<?= esc_attr($editing->DeviceID ?? '') ?>">

            <!-- Mobile Sleek Header Bar (Mobile Only) -->
            <div class="maint-mobile-header-bar mobile-only-el">
                <div class="maint-mobile-header-top">
                    <div class="maint-icon-badge-mob">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                    <div class="maint-mobile-title-wrap">
                        <h2>Send to Repair</h2>
                        <p class="maint-mobile-subtitle">Record device issue and service details</p>
                    </div>
                </div>
            </div>

            <!-- Mobile Hero Device Ticket Card (All-in-One Device Specs + Real-time Sync) -->
            <div class="maint-mobile-hero-card mobile-only-el">
                <div class="maint-badge-glow"></div>

                <!-- Top Badges Row -->
                <div class="maint-mob-hero-top">
                    <div class="maint-mob-id-pill">
                        <i class="fa-solid fa-fingerprint me-1"></i> <?= esc_html($editing->DeviceID ?? 'DEV-XXXX') ?>
                    </div>
                    <div class="maint-mob-status-pill">
                        <span class="maint-pulse-dot"></span>
                        <span>Maintenance</span>
                    </div>
                </div>

                <!-- Center Device Info -->
                <div class="maint-mob-hero-main">
                    <div class="maint-mob-avatar-box">
                        <div class="maint-mob-avatar-icon" id="preview-maint-icon-mob">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <img id="preview-maint-uploaded-img-mob" src="" onclick="openPhotoModal(this.src)"
                            title="Click to enlarge image"
                            style="display:none; width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 2px solid #f59e0b; cursor: zoom-in;">
                    </div>
                    <div class="maint-mob-hero-text">
                        <div class="maint-mob-hero-name">
                            <?= esc_html(!empty($editing->Model) ? $editing->Model : 'Hardware Equipment') ?></div>
                        <div class="maint-mob-hero-tags">
                            <?php if (!empty($editing->BrandName)): ?>
                                <span class="maint-tag-pill brand"><?= esc_html($editing->BrandName) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($editing->CategoryName)): ?>
                                <span class="maint-tag-pill cat"><?= esc_html($editing->CategoryName) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($editing->SerialNumber) && $editing->SerialNumber !== '—'): ?>
                                <span class="maint-tag-pill sn"><i
                                        class="fa-solid fa-barcode me-1"></i><?= esc_html($editing->SerialNumber) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Live Dynamic Ticket Preview Bar -->
                <div class="maint-mob-live-strip">
                    <div class="maint-mob-strip-item">
                        <span class="maint-mob-strip-label"><i class="fa-solid fa-calendar-day me-1"></i> SERVICE
                            DATE</span>
                        <span class="maint-mob-strip-val"
                            id="preview-maint-date-mob"><?= esc_html($dateValue ?: date('Y-m-d')) ?></span>
                    </div>
                    <div class="maint-mob-strip-item strip-reason">
                        <span class="maint-mob-strip-label"><i class="fa-solid fa-triangle-exclamation me-1"></i> ISSUE /
                            REASON</span>
                        <span class="maint-mob-strip-val text-amber"
                            id="preview-maint-reason-mob"><?= esc_html($details_val ?: 'Select or enter reason') ?></span>
                    </div>
                </div>
            </div>

            <!-- Desktop & Mobile 2-Column Bento Layout Grid -->
            <div class="maint-layout-grid">
                <!-- Left Column: Form Fields -->
                <div class="maint-form-card">

                    <!-- Section 1: Device Information (Desktop Only) -->
                    <div class="maint-section-divider desktop-only-el">
                        <span class="maint-section-title"><i class="fa-solid fa-circle-info me-1"></i> Device
                            Information</span>
                    </div>

                    <div class="maint-fields-grid desktop-only-el">
                        <!-- Device ID -->
                        <div class="maint-field-group">
                            <label for="maint_device_id">
                                <i class="fa-solid fa-fingerprint maint-field-icon"></i>
                                Device ID
                            </label>
                            <input type="text" id="maint_device_id" value="<?= esc_attr($editing->DeviceID ?? '') ?>"
                                required readonly class="input-readonly">
                        </div>

                        <!-- Brand -->
                        <div class="maint-field-group">
                            <label for="maint_brand">
                                <i class="fa-solid fa-building maint-field-icon"></i>
                                Brand
                            </label>
                            <input type="text" id="maint_brand" value="<?= esc_attr($editing->BrandName ?? '—') ?>" readonly
                                disabled class="input-readonly">
                        </div>

                        <!-- Category -->
                        <div class="maint-field-group">
                            <label for="maint_category">
                                <i class="fa-solid fa-shapes maint-field-icon"></i>
                                Category
                            </label>
                            <input type="text" id="maint_category" value="<?= esc_attr($editing->CategoryName ?? '—') ?>"
                                readonly disabled class="input-readonly">
                        </div>

                        <!-- Model -->
                        <div class="maint-field-group">
                            <label for="maint_model">
                                <i class="fa-solid fa-laptop maint-field-icon"></i>
                                Model
                            </label>
                            <input type="text" id="maint_model" value="<?= esc_attr($editing->Model ?? '—') ?>" readonly
                                disabled class="input-readonly">
                        </div>

                        <!-- Serial Number (Full Width Span 2) -->
                        <div class="maint-field-group maint-span-2">
                            <label for="maint_sn">
                                <i class="fa-solid fa-barcode maint-field-icon"></i>
                                Serial Number
                            </label>
                            <input type="text" id="maint_sn" value="<?= esc_attr($editing->SerialNumber ?? '—') ?>" readonly
                                disabled class="input-readonly">
                        </div>
                    </div>

                    <!-- Section 2: Maintenance Details -->
                    <div class="maint-section-divider mt-4">
                        <span class="maint-section-title"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Repair
                            Information</span>
                    </div>

                    <div class="maint-fields-grid">
                        <!-- Repair Date -->
                        <div class="maint-field-group">
                            <label for="maint_date">
                                <i class="fa-solid fa-calendar-day maint-field-icon"></i>
                                Repair Date <span class="maint-req">*</span>
                            </label>
                            <input type="date" name="RepairDate" id="maint_date" value="<?= esc_attr($dateValue) ?>"
                                required>
                        </div>

                        <!-- Maintenance Reason Select -->
                        <div class="maint-field-group">
                            <label for="maint_reason_select">
                                <i class="fa-solid fa-triangle-exclamation maint-field-icon"></i>
                                Reason / Issue <span class="maint-req">*</span>
                            </label>
                            <select name="Details" id="maint_reason_select" required>
                                <option value="">-- Select Maintenance Reason --</option>
                                <option value="Screen Issue" <?= (strpos($details_val, 'Screen Issue') !== false) ? 'selected' : '' ?>>Screen Issue</option>
                                <option value="Battery Issue" <?= (strpos($details_val, 'Battery Issue') !== false) ? 'selected' : '' ?>>Battery Issue</option>
                                <option value="Power Issue" <?= (strpos($details_val, 'Power Issue') !== false) ? 'selected' : '' ?>>Power Issue</option>
                                <option value="Keyboard / Mouse Issue" <?= (strpos($details_val, 'Keyboard') !== false || strpos($details_val, 'Mouse') !== false) ? 'selected' : '' ?>>Keyboard / Mouse Issue
                                </option>
                                <option value="Hardware Upgrade" <?= (strpos($details_val, 'Hardware Upgrade') !== false) ? 'selected' : '' ?>>Hardware Upgrade</option>
                                <option value="Software Issue" <?= (strpos($details_val, 'Software Issue') !== false) ? 'selected' : '' ?>>Software Issue</option>
                                <option value="Other Issue" <?= ($other_text || strpos($details_val, 'Other') !== false) ? 'selected' : '' ?>>Other Issue (Custom)</option>
                            </select>
                        </div>

                        <!-- Additional Details (Full Width Span 2) -->
                        <div class="maint-field-group maint-span-2" id="other-details-group">
                            <label for="OtherDetails">
                                <i class="fa-solid fa-comment-dots maint-field-icon"></i>
                                Additional Notes / Custom Reason
                            </label>

                            <!-- Quick Clickable Symptom Chips (Compact & Clean - Image 1 Style) -->
                            <div class="maint-quick-chips-bar">
                                <div class="maint-chips-header">
                                    <div class="maint-chips-title">
                                        <i class="fa-solid fa-bolt text-warning"></i>
                                        <span>Quick Symptoms:</span>
                                    </div>
                                </div>
                                <div class="maint-chips-grid" id="maint_symptom_chips">
                                    <button type="button" class="maint-chip-btn" data-symptom="Cracked / Defective Screen"
                                        data-main-reason="Screen Issue">
                                        <i class="fa-solid fa-desktop text-primary"></i>
                                        <span>Screen</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn" data-symptom="Battery Degraded / Swollen"
                                        data-main-reason="Battery Issue">
                                        <i class="fa-solid fa-battery-half text-success"></i>
                                        <span>Battery</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn" data-symptom="No Power / Auto Shutdown"
                                        data-main-reason="Power Issue">
                                        <i class="fa-solid fa-power-off text-danger"></i>
                                        <span>Power</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn" data-symptom="Keyboard / Touchpad Failure"
                                        data-main-reason="Keyboard / Mouse Issue">
                                        <i class="fa-solid fa-keyboard text-secondary"></i>
                                        <span>Keyboard</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn"
                                        data-symptom="OS Reinstall / Software Glitch" data-main-reason="Software Issue">
                                        <i class="fa-solid fa-arrows-rotate text-info"></i>
                                        <span>OS</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn" data-symptom="Upgrade RAM / Storage SSD"
                                        data-main-reason="Hardware Upgrade">
                                        <i class="fa-solid fa-microchip text-primary"></i>
                                        <span>Upgrade</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn"
                                        data-symptom="Faulty Charger / Damaged Port" data-main-reason="Power Issue">
                                        <i class="fa-solid fa-plug text-warning"></i>
                                        <span>Charger</span>
                                    </button>
                                    <button type="button" class="maint-chip-btn" data-symptom="Overheating / Loud Fan Noise"
                                        data-main-reason="Other Issue">
                                        <i class="fa-solid fa-fan text-muted"></i>
                                        <span>Fan / Heat</span>
                                    </button>
                                </div>
                            </div>

                            <div class="maint-custom-reason-input-wrap">
                                <input type="text" name="OtherDetails" id="OtherDetails"
                                    placeholder="e.g. Broken Screen, Battery Degraded..."
                                    value="<?= esc_attr($other_text) ?>" autocomplete="off">
                            </div>
                        </div>

                        <!-- Condition Photo Upload (Full Width Span 2) -->
                        <div class="maint-field-group maint-span-2">
                            <label for="maint_photo">
                                <i class="fa-solid fa-camera maint-field-icon"></i>
                                Condition Photo (Camera / Upload)
                            </label>
                            <div class="maint-photo-upload-wrap">
                                <input type="file" name="photo" id="maint_photo" accept="image/*" capture="environment">
                                <div id="maint_photo_preview_box" class="maint-photo-preview-box" style="display:none;">
                                    <div class="maint-photo-preview-inner"
                                        style="position: relative; display: inline-block;">
                                        <img id="maint_photo_img" src="" alt="Condition Photo Preview"
                                            onclick="openPhotoModal(this.src)" title="Click to enlarge image"
                                            style="cursor: zoom-in;">
                                        <span class="maint-photo-preview-badge"><i
                                                class="fa-solid fa-check me-1"></i>Attached</span>
                                        <button type="button" class="maint-photo-clear-btn" onclick="clearMaintPhoto()"
                                            title="Clear photo">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="maint-form-actions">
                        <button type="button" onclick="window.history.back();" class="maint-btn-cancel">
                            <i class="fa-solid fa-arrow-left me-1"></i> Cancel
                        </button>
                        <button type="submit" name="update_device" class="maint-btn-submit">
                            <span class="maint-shine desktop-only-el"></span>
                            <i class="fa-solid fa-wrench me-1"></i>
                            <span>Save Maintenance</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Live Interactive Hardware Maintenance Badge Card (Desktop Only) -->
                <div class="maint-preview-column desktop-only-el">
                    <div class="maint-badge-card" id="interactive-preview-card">
                        <div class="maint-badge-glow"></div>

                        <!-- Top Bar -->
                        <div class="maint-badge-top">
                            <span class="maint-badge-tag"><i class="fa-solid fa-wrench me-1"></i> SERVICE TICKET</span>
                            <span class="maint-badge-status" id="preview-maint-status-badge">
                                <span class="maint-status-dot" id="preview-maint-status-dot"></span>
                                <span>Maintenance</span>
                            </span>
                        </div>

                        <!-- Device Avatar / Photo Visualizer -->
                        <div class="maint-avatar-box">
                            <div class="maint-avatar-halo"></div>
                            <div class="maint-avatar-circle" id="preview-maint-icon">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                            <img id="preview-maint-uploaded-img" src="" onclick="openPhotoModal(this.src)"
                                title="Click to enlarge image"
                                style="display:none; width: 80px; height: 80px; border-radius: 16px; object-fit: cover; border: 2px solid rgba(245, 158, 11, 0.6); box-shadow: 0 0 20px rgba(245, 158, 11, 0.4); z-index: 1; margin-bottom: 0.5rem; cursor: zoom-in;">
                            <div class="maint-id-pill" id="preview-maint-device-id">
                                <?= esc_html($editing->DeviceID ?? 'DEV-XXXX') ?>
                            </div>
                        </div>

                        <!-- Details Specs Box -->
                        <div class="maint-details-box">
                            <div class="maint-preview-name" id="preview-maint-model">
                                <?= esc_html(!empty($editing->Model) ? $editing->Model : 'Hardware Equipment') ?>
                            </div>
                            <div class="maint-preview-brand" id="preview-maint-brand">
                                <?= esc_html(trim(($editing->BrandName ?? '') . ' ' . ($editing->CategoryName ?? '')) ?: 'Device Details') ?>
                            </div>

                            <div class="maint-meta-grid">
                                <div class="maint-meta-item">
                                    <span class="maint-meta-label"><i class="fa-solid fa-calendar-day me-1"></i> SERVICE
                                        DATE</span>
                                    <span class="maint-meta-val" id="preview-maint-date">
                                        <?= esc_html($dateValue ?: date('Y-m-d')) ?>
                                    </span>
                                </div>
                                <div class="maint-meta-item">
                                    <span class="maint-meta-label"><i class="fa-solid fa-barcode me-1"></i> SERIAL
                                        NO.</span>
                                    <span class="maint-meta-val" id="preview-maint-sn">
                                        <?= esc_html(!empty($editing->SerialNumber) ? $editing->SerialNumber : '—') ?>
                                    </span>
                                </div>
                                <div class="maint-meta-item maint-span-2"
                                    style="border-top: 1px dashed rgba(255, 255, 255, 0.1); padding-top: 6px; margin-top: 4px;">
                                    <span class="maint-meta-label"><i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        ISSUE / REASON</span>
                                    <span class="maint-meta-val" id="preview-maint-reason"
                                        style="color: #fbbf24; font-weight: 700;">
                                        <?= esc_html($details_val ?: 'Select or enter reason') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <!-- Custom Image Lightbox Modal -->
        <div id="maint_image_lightbox" class="maint-image-lightbox" style="display:none;"
            onclick="closeMaintLightbox(event)">
            <div class="maint-lightbox-dialog" onclick="event.stopPropagation()">
                <button type="button" class="maint-lightbox-close-btn" onclick="closeMaintLightbox(event)" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img id="maint_lightbox_img" src="" alt="Full Condition Photo">
            </div>
        </div>
    </div>

    <!-- Scoped Modern Stylesheet -->
    <style>
        /* Unbind Page Container Constraints */
        .page-maintenance .fl-builder-content,
        .page-maintenance .fl-row,
        .page-maintenance .fl-row-content,
        .page-maintenance .fl-col-group,
        .page-maintenance .fl-col,
        .page-maintenance .fl-col-content,
        .page-maintenance .ast-container,
        .page-maintenance #primary,
        .page-maintenance .entry-content {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        #maintenance-form-wrapper {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.5rem 1rem 2rem 1rem;
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-sizing: border-box;
        }

        /* --- SHARED PHOTO UPLOAD & PREVIEW STYLES (Both Desktop & Mobile) --- */
        #maintenance-form-wrapper .maint-photo-upload-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        #maintenance-form-wrapper .maint-photo-preview-box {
            position: relative;
            display: inline-block;
            margin-top: 8px;
            width: fit-content;
        }

        #maintenance-form-wrapper .maint-photo-preview-inner {
            position: relative;
            display: inline-block;
            border-radius: 12px;
        }

        #maintenance-form-wrapper .maint-photo-preview-box img {
            width: 140px !important;
            height: 95px !important;
            object-fit: cover !important;
            border-radius: 12px !important;
            border: 1.5px solid #cbd5e1 !important;
            display: block !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08) !important;
            cursor: zoom-in !important;
            transition: transform 0.2s ease !important;
        }

        #maintenance-form-wrapper .maint-photo-preview-box img:hover {
            transform: scale(1.03);
        }

        #maintenance-form-wrapper .maint-photo-preview-badge {
            position: absolute !important;
            bottom: 6px !important;
            left: 6px !important;
            background: rgba(16, 185, 129, 0.95) !important;
            color: #ffffff !important;
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            padding: 2px 8px !important;
            border-radius: 6px !important;
            backdrop-filter: blur(4px) !important;
            pointer-events: none !important;
            z-index: 5 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
            letter-spacing: 0.02em !important;
        }

        #maintenance-form-wrapper .maint-photo-clear-btn {
            position: absolute !important;
            top: -8px !important;
            right: -8px !important;
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            max-width: 26px !important;
            padding: 0 !important;
            margin: 0 !important;
            background: #ef4444 !important;
            color: #ffffff !important;
            border: 2px solid #ffffff !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.78rem !important;
            line-height: 1 !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.45) !important;
            transition: all 0.2s ease !important;
            z-index: 20 !important;
        }

        #maintenance-form-wrapper .maint-photo-clear-btn:hover {
            background: #dc2626 !important;
            transform: scale(1.15) !important;
        }

        #maintenance-form-wrapper .maint-photo-clear-btn i {
            font-size: 0.75rem !important;
            color: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
        }

        /* Photo Zoom Hover & Lightbox Styles */
        #maintenance-form-wrapper #preview-maint-uploaded-img,
        #maintenance-form-wrapper #maint_photo_img {
            cursor: zoom-in !important;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease !important;
        }

        #maintenance-form-wrapper #preview-maint-uploaded-img:hover {
            transform: scale(1.08);
            box-shadow: 0 0 28px rgba(245, 158, 11, 0.75) !important;
            border-color: #f59e0b !important;
        }

        /* --- SHARED QUICK SYMPTOM CHIPS & INPUT ST        /* --- SHARED QUICK SYMPTOM CHIPS & INPUT STYLES (IMAGE 1 STYLE) --- */
        #maintenance-form-wrapper .maint-quick-chips-bar {
            background: #fffdf5 !important;
            border: 1.5px solid #fde68a !important;
            border-radius: 16px !important;
            padding: 12px 14px !important;
            margin-bottom: 12px !important;
            box-sizing: border-box !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }

        #maintenance-form-wrapper .maint-chips-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 10px !important;
            width: 100% !important;
        }

        #maintenance-form-wrapper .maint-chips-title {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 0.82rem !important;
            color: #92400e !important;
            font-weight: 700 !important;
        }

        #maintenance-form-wrapper .maint-chips-hint {
            font-size: 0.72rem !important;
            color: #b45309 !important;
            font-weight: 700 !important;
            text-decoration: underline !important;
            background: transparent !important;
            padding: 0 !important;
            border: none !important;
        }

        #maintenance-form-wrapper .maint-chips-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 6px 8px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        #maintenance-form-wrapper .maint-chip-btn {
            all: unset !important;
            box-sizing: border-box !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            padding: 6px 10px !important;
            background: #ffffff !important;
            border: 1.5px solid #fde68a !important;
            border-radius: 10px !important;
            cursor: pointer !important;
            font-weight: 700 !important;
            font-size: 0.8rem !important;
            color: #78350f !important;
            transition: all 0.15s ease !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
            user-select: none !important;
            width: 100% !important;
            min-width: 0 !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
            height: 36px !important;
        }

        #maintenance-form-wrapper .maint-chip-btn:hover {
            background: #fef9c3 !important;
            border-color: #f59e0b !important;
            transform: translateY(-1px) !important;
        }

        #maintenance-form-wrapper .maint-chip-btn:active {
            transform: scale(0.97) !important;
        }

        /* Selected State */
        #maintenance-form-wrapper .maint-chip-btn.is-selected {
            background: #fef3c7 !important;
            border-color: #f59e0b !important;
            color: #78350f !important;
            font-weight: 800 !important;
            box-shadow: 0 0 0 1.5px #f59e0b, 0 2px 6px rgba(245, 158, 11, 0.2) !important;
        }

        #maintenance-form-wrapper .maint-chip-btn i {
            font-size: 0.85rem !important;
            flex-shrink: 0 !important;
        }

        #maintenance-form-wrapper .maint-chip-btn span {
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        /* Custom Reason Input Wrap */
        #maintenance-form-wrapper .maint-custom-reason-input-wrap {
            position: relative !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        #maintenance-form-wrapper .maint-custom-reason-input-wrap input {
            width: 100% !important;
            height: 46px !important;
            border-radius: 14px !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 0 16px !important;
            font-size: 0.88rem !important;
            color: #0f172a !important;
            background: #ffffff !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02) !important;
        }

        #maintenance-form-wrapper .maint-custom-reason-input-wrap input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
            outline: none !important;
        }

        /* Custom Fullscreen Image Lightbox */
        .maint-image-lightbox {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.88) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            display: none;
            align-items: center !important;
            justify-content: center !important;
            z-index: 999999 !important;
            padding: 1.25rem !important;
            box-sizing: border-box !important;
        }

        .maint-image-lightbox.is-open {
            display: flex !important;
            animation: maintLightFadeIn 0.2s ease-out;
        }

        .maint-lightbox-dialog {
            position: relative !important;
            max-width: 92vw !important;
            max-height: 90vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            animation: maintLightZoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .maint-lightbox-dialog img {
            max-width: 92vw !important;
            max-height: 85vh !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            border-radius: 16px !important;
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.15) !important;
            display: block !important;
        }

        .maint-lightbox-close-btn {
            position: absolute !important;
            top: -14px !important;
            right: -14px !important;
            width: 38px !important;
            height: 38px !important;
            min-width: 38px !important;
            max-width: 38px !important;
            border-radius: 50% !important;
            background: #0f172a !important;
            color: #ffffff !important;
            border: 2px solid rgba(255, 255, 255, 0.9) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.1rem !important;
            cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6) !important;
            transition: all 0.2s ease !important;
            z-index: 1000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .maint-lightbox-close-btn:hover {
            background: #ef4444 !important;
            transform: scale(1.1) !important;
        }

        @keyframes maintLightFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes maintLightZoomIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* --- DESKTOP STYLES (Screen > 768px) --- */
        @media (min-width: 769px) {
            #maintenance-form-wrapper .mobile-only-el {
                display: none !important;
            }

            #maintenance-form-wrapper .desktop-only-el {
                display: flex !important;
            }

            #maintenance-form-wrapper .maint-glow-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                pointer-events: none;
                z-index: 0;
                opacity: 0.35;
            }

            #maintenance-form-wrapper .maint-glow-1 {
                top: -20px;
                left: 15%;
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(245, 158, 11, 0.22) 0%, transparent 70%);
            }

            #maintenance-form-wrapper .maint-glow-2 {
                bottom: 20px;
                right: 10%;
                width: 320px;
                height: 320px;
                background: radial-gradient(circle, rgba(234, 88, 12, 0.18) 0%, transparent 70%);
            }

            #maintenance-form-wrapper .maint-header-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(226, 232, 240, 0.9);
                border-radius: 18px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.25rem;
                box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
                position: relative;
                z-index: 1;
                animation: maintSlideDown 0.4s ease-out forwards;
            }

            #maintenance-form-wrapper .maint-header-left {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            #maintenance-form-wrapper .maint-icon-badge {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.3rem;
                box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
                flex-shrink: 0;
            }

            #maintenance-form-wrapper .maint-header-subtitle {
                margin: 3px 0 0 0;
                font-size: 0.85rem;
                color: #64748b;
            }

            #maintenance-form-wrapper .maint-header-badge {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 5px 14px;
                background: rgba(245, 158, 11, 0.1);
                color: #d97706;
                border-radius: 999px;
                font-size: 0.78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            #maintenance-form-wrapper .maint-pulse-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background-color: #f59e0b;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
                animation: maintDotPulse 2s infinite;
            }

            #maintenance-form-wrapper .maint-main-form {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #maintenance-form-wrapper .maint-layout-grid {
                display: grid;
                grid-template-columns: 1.35fr 0.95fr;
                gap: 1.75rem;
                position: relative;
                z-index: 1;
                align-items: start;
            }

            #maintenance-form-wrapper .maint-form-card {
                background: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-radius: 20px;
                padding: 1.75rem 2rem;
                box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
                animation: maintCardFadeIn 0.4s ease-out forwards;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            #maintenance-form-wrapper .maint-section-divider {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
                padding-bottom: 0.4rem;
                border-bottom: 1.5px solid #f1f5f9;
            }

            #maintenance-form-wrapper .maint-section-title {
                font-size: 0.88rem;
                font-weight: 800;
                color: #334155;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            #maintenance-form-wrapper .maint-fields-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1.1rem 1.25rem !important;
            }

            #maintenance-form-wrapper .maint-field-group {
                display: flex !important;
                flex-direction: column !important;
                margin-bottom: 0 !important;
            }

            #maintenance-form-wrapper .maint-span-2 {
                grid-column: span 2 !important;
            }

            #maintenance-form-wrapper .maint-field-group label {
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #1e293b !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 6px !important;
                letter-spacing: -0.01em !important;
            }

            #maintenance-form-wrapper .maint-field-icon {
                color: #ea580c;
                font-size: 0.85rem;
            }

            #maintenance-form-wrapper .maint-req {
                color: #ef4444;
                font-weight: bold;
            }

            #maintenance-form-wrapper .maint-field-group input,
            #maintenance-form-wrapper .maint-field-group select {
                width: 100% !important;
                height: 46px !important;
                padding: 0 16px !important;
                font-size: 0.95rem !important;
                font-weight: 500 !important;
                color: #0f172a !important;
                background-color: #ffffff !important;
                border: 1.5px solid #cbd5e1 !important;
                border-radius: 12px !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                box-sizing: border-box !important;
                appearance: none;
            }

            #maintenance-form-wrapper .input-readonly,
            #maintenance-form-wrapper input[readonly],
            #maintenance-form-wrapper input:disabled,
            #maintenance-form-wrapper select:disabled {
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

            #maintenance-form-wrapper .maint-field-group select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
                background-position: right 0.85rem center !important;
                background-repeat: no-repeat !important;
                background-size: 1.1em 1.1em !important;
                padding-right: 2.4rem !important;
                cursor: pointer;
            }

            #maintenance-form-wrapper .maint-field-group input:hover:not([readonly]):not([disabled]),
            #maintenance-form-wrapper .maint-field-group select:hover:not([readonly]):not([disabled]) {
                border-color: #94a3b8 !important;
            }

            #maintenance-form-wrapper .maint-field-group input:focus:not([readonly]):not([disabled]),
            #maintenance-form-wrapper .maint-field-group select:focus:not([readonly]):not([disabled]) {
                background-color: #ffffff !important;
                border-color: #ea580c !important;
                outline: none !important;
                box-shadow: 0 0 0 3.5px rgba(234, 88, 12, 0.16) !important;
            }

            #maintenance-form-wrapper .maint-form-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 1.5rem !important;
                margin-top: 1.75rem !important;
                padding-top: 1.5rem !important;
                border-top: 1.5px dashed #f1f5f9 !important;
            }

            #maintenance-form-wrapper .maint-btn-cancel {
                padding: 0.65rem 2.2rem !important;
                font-size: 0.95rem !important;
                font-weight: 700 !important;
                background: #dc2626 !important;
                border: none !important;
                color: #ffffff !important;
                border-radius: 9999px !important;
                box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35) !important;
                transition: all 0.2s ease !important;
                cursor: pointer !important;
            }

            #maintenance-form-wrapper .maint-btn-cancel:hover {
                background: #b91c1c !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 18px rgba(220, 38, 38, 0.45) !important;
                color: #ffffff !important;
            }

            #maintenance-form-wrapper .maint-btn-submit {
                position: relative;
                overflow: hidden;
                padding: 0.65rem 2.4rem !important;
                font-size: 0.95rem !important;
                font-weight: 700 !important;
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%) !important;
                border: none !important;
                color: #ffffff !important;
                border-radius: 9999px !important;
                box-shadow: 0 6px 20px -4px rgba(234, 88, 12, 0.4) !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                cursor: pointer !important;
            }

            #maintenance-form-wrapper .maint-btn-submit:hover {
                transform: translateY(-2px) !important;
                color: #ffffff !important;
                box-shadow: 0 8px 25px -4px rgba(234, 88, 12, 0.5) !important;
            }

            #maintenance-form-wrapper .maint-shine {
                position: absolute;
                top: -50%;
                left: -60%;
                width: 40%;
                height: 200%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                transform: rotate(25deg);
                animation: maintShineSweep 4s infinite cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Preview Badge Column (Sticky & Proportional Spacing) */
            #maintenance-form-wrapper .maint-preview-column {
                display: flex !important;
                flex-direction: column !important;
                position: sticky !important;
                top: 24px !important;
                animation: maintCardFadeIn 0.5s ease-out forwards;
            }

            #maintenance-form-wrapper .maint-badge-card {
                position: relative;
                background: linear-gradient(145deg, #0f172a 0%, #1c1917 100%);
                border-radius: 20px;
                padding: 1.5rem;
                color: #ffffff;
                box-shadow: 0 16px 36px -8px rgba(15, 23, 42, 0.35);
                display: flex;
                flex-direction: column;
                gap: 1.25rem;
                overflow: hidden;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            #maintenance-form-wrapper .maint-badge-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 1;
            }

            #maintenance-form-wrapper .maint-badge-tag {
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #fdba74;
                background: rgba(253, 186, 116, 0.12);
                padding: 4px 10px;
                border-radius: 6px;
                border: 1px solid rgba(253, 186, 116, 0.25);
            }

            #maintenance-form-wrapper .maint-badge-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                font-weight: 700;
                padding: 4px 12px;
                border-radius: 999px;
                color: #fbbf24;
                background: rgba(245, 158, 11, 0.15);
                border: 1px solid rgba(245, 158, 11, 0.35);
            }

            #maintenance-form-wrapper .maint-status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #f59e0b;
                box-shadow: 0 0 8px #f59e0b;
            }

            #maintenance-form-wrapper .maint-avatar-box {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1rem 0 0.5rem 0;
                z-index: 1;
            }

            #maintenance-form-wrapper .maint-avatar-halo {
                position: absolute;
                width: 130px;
                height: 130px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(234, 88, 12, 0.4) 0%, transparent 70%);
                filter: blur(16px);
                z-index: 0;
            }

            #maintenance-form-wrapper .maint-avatar-circle {
                width: 78px;
                height: 78px;
                border-radius: 50%;
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
                box-shadow: 0 0 32px rgba(234, 88, 12, 0.45);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 2.1rem;
                margin-bottom: 0.6rem;
                z-index: 1;
            }

            #maintenance-form-wrapper .maint-id-pill {
                font-size: 1.1rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                color: #fed7aa;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.15);
                padding: 4px 18px;
                border-radius: 10px;
                backdrop-filter: blur(8px);
                z-index: 1;
            }

            #maintenance-form-wrapper .maint-details-box {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 14px;
                padding: 1rem 1.25rem;
                backdrop-filter: blur(12px);
                z-index: 1;
            }

            #maintenance-form-wrapper .maint-preview-name {
                font-size: 1.15rem;
                font-weight: 800;
                color: #ffffff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #maintenance-form-wrapper .maint-preview-brand {
                font-size: 0.82rem;
                color: #fb923c;
                font-weight: 600;
                margin-bottom: 0.75rem;
            }

            #maintenance-form-wrapper .maint-meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 8px;
            }

            #maintenance-form-wrapper .maint-meta-item {
                display: flex;
                flex-direction: column;
            }

            #maintenance-form-wrapper .maint-meta-label {
                font-size: 0.68rem;
                color: #a8a29e;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                margin-bottom: 2px;
            }

            #maintenance-form-wrapper .maint-meta-val {
                font-size: 0.85rem;
                font-weight: 700;
                color: #f5f5f4;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* --- MOBILE STYLES (Screen <= 768px) --- */
        @media (max-width: 768px) {
            #maintenance-form-wrapper {
                padding: 0 4px 2rem 4px !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
            }

            #maintenance-form-wrapper .desktop-only-el {
                display: none !important;
            }

            #maintenance-form-wrapper .mobile-only-el {
                display: block !important;
            }

            /* Mobile Header Bar */
            #maintenance-form-wrapper .maint-mobile-header-bar {
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border: 1.5px solid #e2e8f0;
                border-radius: 18px;
                padding: 14px 16px;
                margin-bottom: 12px;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            }

            #maintenance-form-wrapper .maint-mobile-header-top {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            #maintenance-form-wrapper .maint-icon-badge-mob {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.25rem;
                box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
                flex-shrink: 0;
            }

            #maintenance-form-wrapper .maint-mobile-title-wrap h2 {
                font-size: 1.25rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                margin: 0 !important;
                line-height: 1.2 !important;
            }

            #maintenance-form-wrapper .maint-mobile-subtitle {
                font-size: 0.8rem !important;
                color: #64748b !important;
                margin: 2px 0 0 0 !important;
            }

            /* Mobile Hero Device Ticket Card */
            #maintenance-form-wrapper .maint-mobile-hero-card {
                position: relative;
                background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
                border: 1.5px solid rgba(245, 158, 11, 0.35);
                border-radius: 20px;
                padding: 16px;
                margin-bottom: 16px;
                color: #ffffff;
                box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25), 0 0 20px rgba(245, 158, 11, 0.12);
                overflow: hidden;
            }

            #maintenance-form-wrapper .maint-mob-hero-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
            }

            #maintenance-form-wrapper .maint-mob-id-pill {
                font-size: 0.85rem;
                font-weight: 800;
                color: #fed7aa;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.18);
                padding: 3px 12px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                font-family: 'SFMono-Regular', Consolas, Menlo, monospace;
            }

            #maintenance-form-wrapper .maint-mob-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.72rem;
                font-weight: 700;
                color: #f59e0b;
                background: rgba(245, 158, 11, 0.15);
                border: 1px solid rgba(245, 158, 11, 0.3);
                padding: 3px 10px;
                border-radius: 999px;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            #maintenance-form-wrapper .maint-mob-hero-main {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 12px;
                padding-bottom: 12px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            #maintenance-form-wrapper .maint-mob-avatar-box {
                position: relative;
                flex-shrink: 0;
            }

            #maintenance-form-wrapper .maint-mob-avatar-icon {
                width: 56px;
                height: 56px;
                border-radius: 14px;
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.4rem;
                box-shadow: 0 4px 14px rgba(234, 88, 12, 0.4);
            }

            #maintenance-form-wrapper .maint-mob-hero-text {
                flex: 1;
                min-width: 0;
            }

            #maintenance-form-wrapper .maint-mob-hero-name {
                font-size: 1.05rem;
                font-weight: 800;
                color: #ffffff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 4px;
            }

            #maintenance-form-wrapper .maint-mob-hero-tags {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            #maintenance-form-wrapper .maint-tag-pill {
                font-size: 0.72rem;
                padding: 2px 8px;
                border-radius: 6px;
                font-weight: 700;
            }

            #maintenance-form-wrapper .maint-tag-pill.brand {
                background: rgba(251, 191, 36, 0.15);
                color: #fbbf24;
                border: 1px solid rgba(251, 191, 36, 0.3);
            }

            #maintenance-form-wrapper .maint-tag-pill.cat {
                background: rgba(99, 102, 241, 0.15);
                color: #a5b4fc;
                border: 1px solid rgba(99, 102, 241, 0.3);
            }

            #maintenance-form-wrapper .maint-tag-pill.sn {
                background: rgba(255, 255, 255, 0.08);
                color: #cbd5e1;
                border: 1px solid rgba(255, 255, 255, 0.12);
                font-family: monospace;
            }

            #maintenance-form-wrapper .maint-mob-live-strip {
                display: grid;
                grid-template-columns: 1fr 1.35fr;
                gap: 10px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 12px;
                padding: 8px 12px;
            }

            #maintenance-form-wrapper .maint-mob-strip-item {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }

            #maintenance-form-wrapper .maint-mob-strip-label {
                font-size: 0.65rem;
                color: #94a3b8;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                margin-bottom: 2px;
            }

            #maintenance-form-wrapper .maint-mob-strip-val {
                font-size: 0.82rem;
                font-weight: 700;
                color: #f1f5f9;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #maintenance-form-wrapper .maint-mob-strip-val.text-amber {
                color: #fbbf24 !important;
            }

            #maintenance-form-wrapper .maint-layout-grid {
                display: block !important;
                width: 100% !important;
            }

            #maintenance-form-wrapper .maint-form-card {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            #maintenance-form-wrapper .maint-section-divider {
                display: flex;
                align-items: center;
                margin: 1.25rem 0 0.75rem 0;
                padding-bottom: 0.4rem;
                border-bottom: 1.5px solid #e2e8f0;
            }

            #maintenance-form-wrapper .maint-section-title {
                font-size: 0.85rem;
                font-weight: 800;
                color: #475569;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            #maintenance-form-wrapper .maint-fields-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 14px !important;
                width: 100% !important;
            }

            #maintenance-form-wrapper .maint-field-group {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 18px !important;
                padding: 16px 18px !important;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04) !important;
                margin-bottom: 0 !important;
            }

            #maintenance-form-wrapper .maint-field-group label {
                font-size: 0.8rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.04em !important;
                color: #334155 !important;
                margin-bottom: 8px !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }

            #maintenance-form-wrapper .maint-field-group input,
            #maintenance-form-wrapper .maint-field-group select {
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

            #maintenance-form-wrapper .maint-field-group input:focus,
            #maintenance-form-wrapper .maint-field-group select:focus {
                border-color: #ea580c !important;
                background-color: #ffffff !important;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15) !important;
            }

            #maintenance-form-wrapper .maint-photo-upload-wrap input[type="file"] {
                padding: 10px !important;
                height: auto !important;
                border-radius: 12px !important;
                background: #f8fafc !important;
                border: 1.5px dashed #cbd5e1 !important;
            }

            #maintenance-form-wrapper .maint-form-actions {
                display: flex !important;
                flex-direction: row !important;
                gap: 12px !important;
                margin-top: 24px !important;
                padding-top: 18px !important;
                border-top: 1px solid #e2e8f0 !important;
            }

            #maintenance-form-wrapper .maint-btn-cancel,
            #maintenance-form-wrapper .maint-btn-submit {
                flex: 1 1 0 !important;
                width: 100% !important;
                height: 50px !important;
                font-size: 16px !important;
                font-weight: 700 !important;
                border-radius: 9999px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                border: none !important;
                color: #ffffff !important;
            }

            #maintenance-form-wrapper .maint-btn-cancel {
                background: #ef4444 !important;
            }

            #maintenance-form-wrapper .maint-btn-submit {
                background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%) !important;
            }

            /* =========================================
                   Quick Symptom Tags & Custom Reason (Image 1 Style)
                   ========================================= */
            #maintenance-form-wrapper .maint-quick-chips-bar {
                background: #fffdf5 !important;
                border: 1.5px solid #fde68a !important;
                border-radius: 16px !important;
                padding: 12px 14px !important;
                margin-bottom: 12px !important;
                box-sizing: border-box !important;
                width: 100% !important;
                max-width: 100% !important;
                overflow: hidden !important;
            }

            #maintenance-form-wrapper .maint-chips-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                margin-bottom: 10px !important;
                width: 100% !important;
            }

            #maintenance-form-wrapper .maint-chips-title {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                font-size: 0.82rem !important;
                color: #92400e !important;
                font-weight: 700 !important;
            }

            #maintenance-form-wrapper .maint-chips-hint {
                font-size: 0.72rem !important;
                color: #b45309 !important;
                font-weight: 700 !important;
                text-decoration: underline !important;
                background: transparent !important;
                padding: 0 !important;
                border: none !important;
            }

            #maintenance-form-wrapper .maint-chips-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6px 8px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #maintenance-form-wrapper .maint-chip-btn {
                all: unset !important;
                box-sizing: border-box !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 6px !important;
                padding: 6px 10px !important;
                background: #ffffff !important;
                border: 1.5px solid #fde68a !important;
                border-radius: 10px !important;
                cursor: pointer !important;
                font-weight: 700 !important;
                font-size: 0.8rem !important;
                color: #78350f !important;
                transition: all 0.15s ease !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
                user-select: none !important;
                width: 100% !important;
                min-width: 0 !important;
                overflow: hidden !important;
                white-space: nowrap !important;
                text-overflow: ellipsis !important;
                height: 36px !important;
            }

            #maintenance-form-wrapper .maint-chip-btn:hover {
                background: #fef9c3 !important;
                border-color: #f59e0b !important;
                transform: translateY(-1px) !important;
            }

            #maintenance-form-wrapper .maint-chip-btn:active {
                transform: scale(0.97) !important;
            }

            /* Selected State */
            #maintenance-form-wrapper .maint-chip-btn.is-selected {
                background: #fef3c7 !important;
                border-color: #f59e0b !important;
                color: #78350f !important;
                font-weight: 800 !important;
                box-shadow: 0 0 0 1.5px #f59e0b, 0 2px 6px rgba(245, 158, 11, 0.2) !important;
            }

            #maintenance-form-wrapper .maint-chip-btn i {
                font-size: 0.85rem !important;
                flex-shrink: 0 !important;
            }

            #maintenance-form-wrapper .maint-chip-btn span {
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
            }

            /* Custom Reason Input Wrap */
            #maintenance-form-wrapper .maint-custom-reason-input-wrap {
                position: relative !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #maintenance-form-wrapper .maint-custom-reason-input-wrap input {
                width: 100% !important;
                height: 46px !important;
                border-radius: 14px !important;
                border: 1.5px solid #cbd5e1 !important;
                padding: 0 16px !important;
                font-size: 0.88rem !important;
                color: #0f172a !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
                transition: all 0.2s ease !important;
                box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.02) !important;
            }

            #maintenance-form-wrapper .maint-custom-reason-input-wrap input:focus {
                border-color: #3b82f6 !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
                outline: none !important;
            }

            #maintenance-form-wrapper .maint-input-inner-icon {
                position: absolute !important;
                left: 15px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                color: #94a3b8 !important;
                font-size: 0.85rem !important;
                pointer-events: none !important;
            }
        }

        /* Animations */
        @keyframes maintSlideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes maintCardFadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes maintDotPulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 4px rgba(245, 158, 11, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }

        @keyframes maintShineSweep {
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
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.getElementById('maint_date');
            const reasonSelect = document.getElementById('maint_reason_select');
            const otherInput = document.getElementById('OtherDetails');
            const photoInput = document.getElementById('maint_photo');
            const chipButtons = document.querySelectorAll('.maint-chip-btn');

            // Desktop preview elements
            const pDate = document.getElementById('preview-maint-date');
            const pReason = document.getElementById('preview-maint-reason');
            const pPhotoImg = document.getElementById('preview-maint-uploaded-img');
            const pIcon = document.getElementById('preview-maint-icon');

            // Mobile preview elements
            const pDateMob = document.getElementById('preview-maint-date-mob');
            const pReasonMob = document.getElementById('preview-maint-reason-mob');
            const pPhotoImgMob = document.getElementById('preview-maint-uploaded-img-mob');
            const pIconMob = document.getElementById('preview-maint-icon-mob');

            function syncMaintenancePreview() {
                const dateVal = (dateInput ? dateInput.value : '') || '<?= esc_js($dateValue) ?>';
                let sel = (reasonSelect && reasonSelect.selectedIndex > 0) ? reasonSelect.options[reasonSelect.selectedIndex].text : '';
                let oth = (otherInput ? otherInput.value.trim() : '');
                let fullReason = '';
                if (sel && oth) {
                    fullReason = sel + ' - ' + oth;
                } else if (oth) {
                    fullReason = oth;
                } else if (sel) {
                    fullReason = sel;
                } else {
                    fullReason = 'Select or enter reason';
                }

                // Desktop sync
                if (pDate) pDate.textContent = dateVal;
                if (pReason) pReason.textContent = fullReason;

                // Mobile sync
                if (pDateMob) pDateMob.textContent = dateVal;
                if (pReasonMob) pReasonMob.textContent = fullReason;

                syncChipsActiveState();
            }

            function syncChipsActiveState() {
                const currentVal = otherInput ? otherInput.value : '';
                chipButtons.forEach(btn => {
                    const symptom = btn.getAttribute('data-symptom');
                    if (symptom && currentVal.includes(symptom)) {
                        btn.classList.add('is-selected');
                    } else {
                        btn.classList.remove('is-selected');
                    }
                });
            }

            // Bind Quick Clickable Symptom Chips
            chipButtons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!otherInput) return;

                    const symptom = this.getAttribute('data-symptom');
                    const mainReason = this.getAttribute('data-main-reason');
                    let currentVal = otherInput.value.trim();

                    if (this.classList.contains('is-selected')) {
                        // Deselect: remove this symptom
                        let parts = currentVal.split(',').map(s => s.trim()).filter(Boolean);
                        parts = parts.filter(p => p !== symptom);
                        otherInput.value = parts.join(', ');
                        this.classList.remove('is-selected');
                    } else {
                        // Select: append or set symptom
                        if (!currentVal) {
                            otherInput.value = symptom;
                        } else {
                            let parts = currentVal.split(',').map(s => s.trim()).filter(Boolean);
                            if (!parts.includes(symptom)) {
                                parts.push(symptom);
                            }
                            otherInput.value = parts.join(', ');
                        }
                        this.classList.add('is-selected');

                        // Auto-select corresponding category in Reason dropdown if available
                        if (reasonSelect && mainReason) {
                            for (let i = 0; i < reasonSelect.options.length; i++) {
                                if (reasonSelect.options[i].value === mainReason) {
                                    reasonSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }

                        if (typeof window.flashAutoFillGlow === 'function') {
                            window.flashAutoFillGlow(otherInput);
                        }
                    }

                    syncMaintenancePreview();
                });
            });

            if (reasonSelect) {
                reasonSelect.addEventListener('change', function () {
                    if (otherInput) {
                        if (this.value === 'Other Issue') {
                            otherInput.placeholder = 'Please specify the custom maintenance issue details...';
                            otherInput.focus();
                        } else if (this.value) {
                            otherInput.placeholder = 'Specify additional details regarding ' + this.value + ' (optional)...';
                        } else {
                            otherInput.placeholder = 'Enter issue details or select from tags above...';
                        }
                    }
                    syncMaintenancePreview();
                });
            }

            if (otherInput) otherInput.addEventListener('input', syncMaintenancePreview);
            if (dateInput) dateInput.addEventListener('change', syncMaintenancePreview);

            window.openPhotoModal = function (src) {
                if (!src) return;
                const box = document.getElementById('maint_image_lightbox');
                const img = document.getElementById('maint_lightbox_img');
                if (box && img) {
                    img.src = src;
                    box.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeMaintLightbox = function (e) {
                if (e) e.stopPropagation();
                const box = document.getElementById('maint_image_lightbox');
                if (box) {
                    box.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            };

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    window.closeMaintLightbox();
                }
            });

            window.clearMaintPhoto = function () {
                if (photoInput) {
                    photoInput.value = '';
                }
                const prevWrap = document.getElementById('maint_photo_preview_box');
                const prevImg = document.getElementById('maint_photo_img');
                if (prevImg) {
                    prevImg.src = '';
                }
                if (prevWrap) {
                    prevWrap.style.display = 'none';
                }
                if (pPhotoImg && pIcon) {
                    pPhotoImg.src = '';
                    pPhotoImg.style.display = 'none';
                    pIcon.style.display = 'flex';
                }
                if (pPhotoImgMob && pIconMob) {
                    pPhotoImgMob.src = '';
                    pPhotoImgMob.style.display = 'none';
                    pIconMob.style.display = 'flex';
                }
            };

            if (photoInput) {
                photoInput.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const prevWrap = document.getElementById('maint_photo_preview_box');
                            const prevImg = document.getElementById('maint_photo_img');
                            if (prevWrap && prevImg) {
                                prevImg.src = e.target.result;
                                prevWrap.style.display = 'inline-block';
                            }
                            if (pPhotoImg && pIcon) {
                                pPhotoImg.src = e.target.result;
                                pPhotoImg.style.display = 'block';
                                pIcon.style.display = 'none';
                            }
                            if (pPhotoImgMob && pIconMob) {
                                pPhotoImgMob.src = e.target.result;
                                pPhotoImgMob.style.display = 'block';
                                pIconMob.style.display = 'none';
                            }
                        };
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        window.clearMaintPhoto();
                    }
                });
            }

            syncMaintenancePreview();
        });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('form_maintenance', 'form_maintenance');
