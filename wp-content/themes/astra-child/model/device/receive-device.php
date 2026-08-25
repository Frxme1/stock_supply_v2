<?php
if (!defined('ABSPATH')) {
    exit;
}

function receive_device($device = null)
{
    ob_start();
    global $wpdb;

    $table_devices = 'Devices';
    $table_owners = 'Owners';
    $table_history = 'History_new';

    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments ORDER BY DepartmentName ASC");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions ORDER BY PositionName ASC");

    $owners_data = $wpdb->get_results("
        SELECT o.OwnerID, o.Nickname, o.FirstName, o.LastName, o.DepartmentID, o.PositionID, d.DepartmentName, p.PositionName
        FROM $table_owners o
        LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
        LEFT JOIN Positions p ON o.PositionID = p.PositionID
        WHERE (o.StatusID = 1 OR o.StatusID IS NULL OR o.StatusID = (SELECT StatusID FROM Status_employee WHERE Status_name = 'Active' LIMIT 1))
        ORDER BY o.Nickname ASC
    ");

    $device_data = null;
    $date_value = date('Y-m-d');

    // 1. Check if passed via controller parameter
    if (is_object($device) && !empty($device->DeviceID)) {
        $device_data = $device;
    } elseif (is_string($device) && !empty($device)) {
        $device_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", sanitize_text_field($device)));
    }

    // 2. Check query string parameters (?receive=, ?DeviceID=, ?device_id=, ?id=)
    if (!$device_data) {
        $target_device_id = '';
        if (!empty($_GET['receive'])) {
            $target_device_id = sanitize_text_field($_GET['receive']);
        } elseif (!empty($_GET['DeviceID'])) {
            $target_device_id = sanitize_text_field($_GET['DeviceID']);
        } elseif (!empty($_GET['device_id'])) {
            $target_device_id = sanitize_text_field($_GET['device_id']);
        } elseif (!empty($_GET['id'])) {
            $target_device_id = sanitize_text_field($_GET['id']);
        }

        if (!empty($target_device_id)) {
            $device_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $target_device_id));
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_device']) || isset($_POST['_rcv_nonce']))) {
        if (!is_user_logged_in() || !isset($_POST['_rcv_nonce']) || !wp_verify_nonce($_POST['_rcv_nonce'], 'receive_device_nonce')) {
            echo '<p style="color:red;">Security check failed.</p>';
            return ob_get_clean();
        }

        $device_id = sanitize_text_field($_POST['DeviceID'] ?? '');
        $owner_id = intval($_POST['OwnerID'] ?? 0);
        $receive_date = !empty($_POST['ReceiveDate']) ? sanitize_text_field($_POST['ReceiveDate']) : current_time('Y-m-d');

        if (empty($device_id) || empty($owner_id)) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Required Fields',
                        text: 'Please select both a Device and an Employee to assign.',
                        confirmButtonColor: '#0f172a'
                    });
                });
            </script>";
        } else {
            $owner_row = $wpdb->get_row($wpdb->prepare("SELECT DepartmentID, PositionID, Nickname FROM $table_owners WHERE OwnerID = %d", $owner_id));
            $department_id = $owner_row ? $owner_row->DepartmentID : null;
            $position_id = $owner_row ? $owner_row->PositionID : null;
            $owner_nickname = $owner_row ? $owner_row->Nickname : ('Employee #' . $owner_id);

            $in_use_status_id = intval($wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'"));

            // Photo upload handling
            $photo_url = '';
            if (!empty($_FILES['photo']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $attachment_id = media_handle_upload('photo', 0);
                if (!is_wp_error($attachment_id)) {
                    $photo_url = wp_get_attachment_url($attachment_id);
                }
            }

            $update_data = [
                'OwnerID' => $owner_id,
                'DepartmentID' => $department_id,
                'PositionID' => $position_id,
                'ReceiveDate' => $receive_date,
                'StatusID' => $in_use_status_id,
                'ReturnDate' => null,
                'RepairDate' => null,
                'UpdatedAt' => current_time('mysql'),
            ];

            $updated = $wpdb->update($table_devices, $update_data, ['DeviceID' => $device_id]);

            if ($updated !== false) {
                $dev_info = $wpdb->get_row($wpdb->prepare("SELECT CategoryID FROM $table_devices WHERE DeviceID = %s", $device_id));

                $desc = "Device assigned to " . ($owner_nickname ?: 'Employee #' . $owner_id);
                if ($photo_url) {
                    $desc .= " | Photo: " . $photo_url;
                }

                $current_user = wp_get_current_user();
                $wpdb->insert($table_history, [
                    'DeviceID' => $device_id,
                    'Action' => 'Assign Device',
                    'Date' => current_time('mysql'),
                    'Description' => $desc,
                    'user_email' => $current_user->user_email ?? '',
                    'CategoryID' => $dev_info->CategoryID ?? null,
                    'Owner' => $owner_nickname ?: '-'
                ]);

                $redirect_url = home_url('/home/?view=' . urlencode($device_id));

                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Device Assigned Successfully!',
                            text: 'Device " . esc_js($device_id) . " assigned to " . esc_js($owner_nickname) . "',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = '" . esc_url($redirect_url) . "';
                        });
                    });
                </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Assignment Failed',
                            text: '" . esc_js($wpdb->last_error) . "'
                        });
                    });
                </script>";
            }

            $device_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id));
        }
    }

    $device_model_name = '';
    $category_name_info = '';
    $brand_name_info = '';
    $available_count = 0;

    if (!empty($device_data->DeviceID)) {
        $info = $wpdb->get_row($wpdb->prepare("
            SELECT d.DeviceID, d.Model, d.CategoryID, d.BrandID, c.CategoryName, b.BrandName
            FROM Devices d
            LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
            LEFT JOIN Brands b ON d.BrandID = b.BrandID
            WHERE d.DeviceID = %s
        ", $device_data->DeviceID));

        if ($info) {
            $device_model_name = $info->Model ?? '';
            $category_name_info = $info->CategoryName ?? '';
            $brand_name_info = $info->BrandName ?? '';

            $avail_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");

            if (!empty($info->BrandID) && !empty($info->CategoryID)) {
                $available_count = (int) $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM Devices 
                    WHERE CategoryID = %d AND BrandID = %d AND (StatusID = %d OR StatusID IS NULL)
                ", $info->CategoryID, $info->BrandID, $avail_status_id));
            } elseif (!empty($info->BrandID)) {
                $available_count = (int) $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM Devices 
                    WHERE BrandID = %d AND (StatusID = %d OR StatusID IS NULL)
                ", $info->BrandID, $avail_status_id));
            }
        }
    }
    $after_borrow_count = max(0, $available_count - 1);

    $cat_slug = !empty($category_name_info) ? sanitize_title($category_name_info) : 'home';
    if (!in_array($cat_slug, ['laptop', 'monitor', 'accessories'])) {
        $cat_slug = 'home';
    }
    $fallback_cancel_url = home_url('/' . $cat_slug . '/');
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Main Assign Device Container -->
    <div class="add-device-responsive-container">
        <!-- Desktop Ambient Glows -->
        <div class="bg-glow-orb bg-glow-orb-1 desktop-only-element"></div>
        <div class="bg-glow-orb bg-glow-orb-2 desktop-only-element"></div>

        <!-- Desktop Sleek Header Bar -->
        <div class="add-device-desktop-header desktop-only-element">
            <div class="desktop-header-left">
                <div class="desktop-icon-badge">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                </div>
                <div>
                    <br>
                    <p class="desktop-header-subtitle">
                        Assign device to an employee and update records
                    </p>
                </div>
            </div>
            <div class="desktop-header-badge">
                <span class="pulse-dot-green"></span>
                <span>Assign Device</span>
            </div>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" id="receive-device-form"
            class="edit-data-form add-device-main-form">
            <?php wp_nonce_field('receive_device_nonce', '_rcv_nonce'); ?>
            <input type="hidden" name="update_device" value="1">
            <?php if (!empty($device_data->DeviceID)): ?>
                <input type="hidden" name="DeviceID" id="hidden_device_id" value="<?= esc_attr($device_data->DeviceID) ?>">
            <?php endif; ?>

            <!-- Mobile Only Header -->
            <div class="mobile-only-header">
                <h2>Assign Device</h2>
            </div>

            <!-- Desktop & Mobile Layout Grid -->
            <div class="add-device-layout-grid">
                <!-- Form Fields Side -->
                <div class="form-fields-wrapper">
                    <div class="form-grid modern-grid">

                        <!-- DeviceID (Read-only) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-fingerprint field-icon-desktop desktop-only-element"></i>
                                    Device ID
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="text" value="<?= esc_attr($device_data->DeviceID ?? '') ?>" readonly
                                    class="input-locked">
                            </div>
                        </div>

                        <!-- Owner (Search & Select) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-user field-icon-desktop desktop-only-element"></i>
                                    Assignee (Employee) <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap" id="website_owner_search_wrap" style="position: relative;">
                                <div style="position: relative;">
                                    <i class="fa-solid fa-magnifying-glass"
                                        style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; pointer-events: none;"></i>
                                    <input type="text" id="owner_search_input" placeholder="Search employee nickname..."
                                        autocomplete="off" style="padding-left: 36px; padding-right: 32px;"
                                        onfocus="this.select(); openOwnerSearchPopup()"
                                        oninput="onOwnerInputChanged(this.value)">
                                    <i class="fa-solid fa-chevron-down"
                                        style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem; pointer-events: none;"></i>
                                </div>

                                <!-- Live Floating Results Popup -->
                                <div id="owner_search_popup"
                                    style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); z-index: 99999; padding: 4px;">
                                </div>
                            </div>
                            <input type="hidden" name="OwnerID" id="OwnerID" value="">
                        </div>

                        <!-- Department (Auto-filled) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-building field-icon-desktop desktop-only-element"></i>
                                    Department
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <select name="DepartmentID" id="DepartmentID" disabled tabindex="-1" class="input-locked">
                                    <option value="">-- Auto-filled --</option>
                                    <?php foreach ($departments as $dep): ?>
                                        <option value="<?= $dep->DepartmentID ?>" <?= selected($device_data->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                                            <?= esc_html($dep->DepartmentName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Position (Auto-filled) -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-briefcase field-icon-desktop desktop-only-element"></i>
                                    Position
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <select name="PositionID" id="PositionID" disabled tabindex="-1" class="input-locked">
                                    <option value="">-- Auto-filled --</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?= $pos->PositionID ?>" <?= selected($device_data->PositionID ?? '', $pos->PositionID, false) ?>>
                                            <?= esc_html($pos->PositionName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Assign Date -->
                        <div class="form-group modern-group">
                            <div class="field-header">
                                <label for="ReceiveDate">
                                    <i class="fa-solid fa-calendar-check field-icon-desktop desktop-only-element"></i>
                                    Assign Date <span class="required-star">*</span>
                                </label>
                            </div>
                            <div class="field-input-wrap">
                                <input type="date" name="ReceiveDate" id="ReceiveDate" value="<?= esc_attr($date_value) ?>"
                                    min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <!-- Condition Photo Upload -->
                        <div class="form-group modern-group" style="grid-column: span 2;">
                            <div class="field-header">
                                <label>
                                    <i class="fa-solid fa-camera field-icon-desktop desktop-only-element"></i>
                                    Device Photo (Optional)
                                </label>
                            </div>
                            <div class="field-input-wrap rcv-photo-upload-wrap">
                                <input type="file" name="photo" id="rcv_photo" accept="image/*" capture="environment"
                                    style="padding: 8px 12px;">
                                <div id="rcv_photo_wrap" class="rcv-photo-preview-box"
                                    style="display:none; margin-top:8px;">
                                    <div class="rcv-photo-preview-inner" style="position: relative; display: inline-block;">
                                        <img id="rcv_photo_img" src="" alt="Condition Photo Preview"
                                            onclick="openRcvPhotoModal(this.src)" title="Click to enlarge image">
                                        <span class="rcv-photo-preview-badge">Attached</span>
                                        <button type="button" class="rcv-photo-clear-btn" onclick="clearRcvPhoto()"
                                            title="Clear photo">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Form Actions (Cancel & Assign) -->
                    <div class="form-actions modern-form-actions">
                        <button type="button" onclick="handleCancelReceive()"
                            class="btn btn-danger btn-cancel-action border rounded-pill">
                            <i class="fa-solid fa-arrow-left me-1"></i> Cancel
                        </button>
                        <button type="submit" name="update_device" id="btn-confirm-assignment"
                            class="btn btn-success btn-submit-action border rounded-pill">
                            <span class="btn-shine-effect desktop-only-element"></span>
                            <i class="fa-solid fa-check me-1"></i>
                            <span>Confirm Assignment</span>
                        </button>
                    </div>
                </div>

                <!-- Right Side: Device & Stock Summary Card (Desktop Only) -->
                <div class="preview-panel-column desktop-only-element">
                    <div class="live-preview-card">
                        <div class="preview-glow-bg"></div>

                        <!-- Top Bar -->
                        <div class="preview-top-bar">
                            <span class="preview-tag"><i class="fa-solid fa-laptop me-1"></i> Selected Device</span>
                            <span class="preview-status-badge">
                                <span class="preview-status-dot"></span>
                                <span>Ready to Assign</span>
                            </span>
                        </div>

                        <!-- Visual Visualizer -->
                        <div class="preview-visual-box">
                            <div class="preview-icon-halo"></div>
                            <div class="preview-icon-main">
                                <i
                                    class="fa-solid <?= ($category_name_info === 'Monitor') ? 'fa-desktop' : (($category_name_info === 'Accessories') ? 'fa-plug' : 'fa-laptop') ?>"></i>
                            </div>
                            <div class="preview-id-pill">
                                <?= esc_html($device_data->DeviceID ?? 'DEV000') ?>
                            </div>
                        </div>

                        <!-- Stock Projection Box -->
                        <div class="preview-specs-box">
                            <div class="preview-device-name">
                                <?= esc_html($brand_name_info ?: 'Unspecified Brand') ?>
                                <?= esc_html($device_model_name ? '- ' . $device_model_name : '') ?>
                            </div>
                            <div class="preview-brand-tag">
                                Category: <?= esc_html($category_name_info ?: 'Hardware') ?>
                            </div>

                            <div class="preview-meta-grid">
                                <div class="preview-meta-item">
                                    <span class="meta-label"><i class="fa-solid fa-boxes-stacked me-1"></i> Available
                                        Now</span>
                                    <span class="meta-value font-mono"
                                        style="color: #34d399; font-size: 1.05rem;"><?= $available_count ?> Units</span>
                                </div>
                                <div class="preview-meta-item">
                                    <span class="meta-label"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>
                                        Remaining After</span>
                                    <span class="meta-value font-mono"
                                        style="color: <?= ($after_borrow_count < 3) ? '#f87171' : '#60a5fa' ?>; font-size: 1.05rem;"><?= $after_borrow_count ?>
                                        Units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <!-- Custom Image Lightbox Modal -->
        <div id="rcv_image_lightbox" class="rcv-image-lightbox" style="display:none;" onclick="closeRcvLightbox(event)">
            <div class="rcv-lightbox-dialog" onclick="event.stopPropagation()">
                <button type="button" class="rcv-lightbox-close-btn" onclick="closeRcvLightbox(event)" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img id="rcv_lightbox_img" src="" alt="Full Condition Photo">
            </div>
        </div>
    </div>

    <!-- Responsive Stylesheet (Expanded Single-Screen Desktop + Classic Clean Mobile) -->
    <style>
        #owner_search_input {
            padding-left: 42px !important;
            padding-right: 36px !important;
        }

        /* --- SHARED PHOTO UPLOAD & PREVIEW STYLES --- */
        .rcv-photo-upload-wrap {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .rcv-photo-preview-box {
            position: relative;
            display: inline-block;
            margin-top: 8px;
            width: fit-content;
        }

        .rcv-photo-preview-inner {
            position: relative;
            display: inline-block;
            border-radius: 12px;
        }

        .rcv-photo-preview-box img {
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

        .rcv-photo-preview-box img:hover {
            transform: scale(1.03);
        }

        .rcv-photo-preview-badge {
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

        .rcv-photo-clear-btn {
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

        .rcv-photo-clear-btn:hover {
            background: #dc2626 !important;
            transform: scale(1.15) !important;
        }

        .rcv-photo-clear-btn i {
            font-size: 0.75rem !important;
            color: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
        }

        /* Custom Fullscreen Image Lightbox */
        .rcv-image-lightbox {
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

        .rcv-image-lightbox.is-open {
            display: flex !important;
            animation: rcvLightFadeIn 0.2s ease-out;
        }

        .rcv-lightbox-dialog {
            position: relative !important;
            max-width: 92vw !important;
            max-height: 90vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            animation: rcvLightZoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .rcv-lightbox-dialog img {
            max-width: 92vw !important;
            max-height: 85vh !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            border-radius: 16px !important;
            box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.15) !important;
            display: block !important;
        }

        .rcv-lightbox-close-btn {
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

        .rcv-lightbox-close-btn:hover {
            background: #ef4444 !important;
            transform: scale(1.1) !important;
        }

        @keyframes rcvLightFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes rcvLightZoomIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

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
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 1.5rem 1.75rem;
                box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
                animation: cardFadeIn 0.45s ease-out forwards;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
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

            .field-input-wrap {
                position: relative;
                width: 100%;
            }

            .field-input-wrap input,
            .field-input-wrap select {
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

            .field-input-wrap select {
                padding-right: 36px !important;
                cursor: pointer !important;
            }

            .field-input-wrap input:hover,
            .field-input-wrap select:hover {
                border-color: #cbd5e1 !important;
                background-color: #ffffff !important;
            }

            .field-input-wrap input:focus,
            .field-input-wrap select:focus {
                background-color: #ffffff !important;
                border-color: #6366f1 !important;
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
                border-radius: 10px !important;
                color: #f1f5f9 !important;
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
                border-radius: 10px !important;
                color: #ffffff !important;
                box-shadow: 0 6px 20px -4px rgba(79, 70, 229, 0.4) !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }

            .btn-submit-action:hover {
                transform: translateY(-2px) !important;
                color: #ffffff !important;
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
                color: #34d399;
                background: rgba(52, 211, 153, 0.15);
                padding: 4px 12px;
                border-radius: 999px;
            }

            .preview-status-dot {
                width: 6px;
                height: 6px;
                background: #34d399;
                border-radius: 50%;
                box-shadow: 0 0 8px #34d399;
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
            }
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            .desktop-only-element {
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
        const ownerDataList = [
            <?php foreach ($owners_data as $o): ?>
                                        {
                    id: <?= intval($o->OwnerID) ?>,
                    name: <?= json_encode(trim($o->Nickname)) ?>,
                    deptId: <?= json_encode(!empty($o->DepartmentID) ? strval($o->DepartmentID) : '') ?>,
                    posId: <?= json_encode(!empty($o->PositionID) ? strval($o->PositionID) : '') ?>,
                    deptName: <?= json_encode($o->DepartmentName ?? '') ?>,
                    posName: <?= json_encode($o->PositionName ?? '') ?>
                },
            <?php endforeach; ?>
        ];

        function openOwnerSearchPopup() {
            const input = document.getElementById('owner_search_input');
            if (input) filterOwnerSearchPopup(input.value);
        }

        function onOwnerInputChanged(val) {
            filterOwnerSearchPopup(val);
            if (!val.trim()) {
                const hiddenInput = document.getElementById('OwnerID');
                if (hiddenInput) {
                    hiddenInput.value = '';
                    handleOwnerChange();
                }
            }
        }

        function filterOwnerSearchPopup(query) {
            const popup = document.getElementById('owner_search_popup');
            if (!popup) return;
            const term = query.toLowerCase().trim();

            const filtered = ownerDataList.filter(o => {
                return !term || o.name.toLowerCase().includes(term) || o.deptName.toLowerCase().includes(term) || o.posName.toLowerCase().includes(term);
            });

            if (filtered.length === 0) {
                popup.innerHTML = `<div style="padding: 10px 14px; color: #94a3b8; font-size: 0.85rem; text-align: center;">❌ No matching employee found</div>`;
            } else {
                let html = '';
                filtered.forEach(o => {
                    const deptBadge = o.deptName ? `<span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-weight: 600; margin-right: 4px;">${o.deptName}</span>` : '';
                    const posBadge = o.posName ? `<span style="font-size: 0.75rem; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 6px; font-weight: 600;">${o.posName}</span>` : '';
                    const displayName = o.name + (o.deptName || o.posName ? ` (${[o.deptName, o.posName].filter(Boolean).join(' • ')})` : '');
                    html += `
                        <div class="owner-item-row" onclick="selectOwnerItem(${o.id}, '${displayName.replace(/'/g, "\\'")}')" style="padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='transparent';">
                            <span style="font-weight: 600; color: #0f172a;"><i class="fa-solid fa-user me-2" style="color: #64748b; font-size: 0.8rem;"></i>${o.name}</span>
                            <div>
                                ${deptBadge}
                                ${posBadge}
                            </div>
                        </div>
                    `;
            });
            popup.innerHTML = html;
        }
        popup.style.display = 'block';
    }

    function selectOwnerItem(id, displayName) {
        const input = document.getElementById('owner_search_input');
        const hiddenInput = document.getElementById('OwnerID');
        const popup = document.getElementById('owner_search_popup');

        if (input) input.value = displayName;
        if (hiddenInput) {
            hiddenInput.value = id;
            handleOwnerChange();
        }
        if (popup) popup.style.display = 'none';
    }

    function handleOwnerChange() {
        const ownerId = document.getElementById('OwnerID').value;
        const deptSelect = document.getElementById('DepartmentID');
        const posSelect = document.getElementById('PositionID');

        const found = ownerDataList.find(o => String(o.id) === String(ownerId));
        if (found) {
            if (deptSelect) {
                deptSelect.value = (found.deptId !== null && found.deptId !== undefined && found.deptId !== '') ? String(found.deptId) : '';
                if (typeof window.flashAutoFillGlow === 'function') {
                    window.flashAutoFillGlow(deptSelect);
                }
            }
            if (posSelect) {
                posSelect.value = (found.posId !== null && found.posId !== undefined && found.posId !== '') ? String(found.posId) : '';
                if (typeof window.flashAutoFillGlow === 'function') {
                    window.flashAutoFillGlow(posSelect);
                }
            }
        } else {
            if (deptSelect) deptSelect.value = '';
            if (posSelect) posSelect.value = '';
        }
    }

    document.addEventListener('click', function (e) {
        const wrap = document.getElementById('website_owner_search_wrap');
        const popup = document.getElementById('owner_search_popup');
        if (wrap && popup && !wrap.contains(e.target)) {
            popup.style.display = 'none';
        }
    });

    // Stock Data Constants for Warning
    const afterBorrowCount = <?= intval($after_borrow_count) ?>;
    const availableCount = <?= intval($available_count) ?>;
    const currentModelName = <?= json_encode(trim(($brand_name_info ? $brand_name_info . ' ' : '') . ($device_model_name ?: 'Device'))) ?>;
    const currentDeviceId = <?= json_encode($device_data->DeviceID ?? '') ?>;

    // Smart Cancel Navigation (Reliable even after Page Refresh)
    window.handleCancelReceive = function () {
        const ref = document.referrer;
        if (ref && ref.includes(window.location.host) && !ref.includes('receive=') && !ref.includes('edit=') && !ref.includes('add=')) {
            window.location.href = ref;
        } else {
            window.location.href = '<?= esc_url($fallback_cancel_url) ?>';
        }
    };

    // Form submit validation & Low Stock Alert
    function initReceiveDeviceValidation() {
        const rcvForm = document.getElementById('receive-device-form');
        if (!rcvForm || rcvForm.dataset.validationAttached === 'true') return;
        rcvForm.dataset.validationAttached = 'true';

        rcvForm.addEventListener('submit', function (e) {
            if (rcvForm.dataset.confirmedLowStock === 'true') {
                return true;
            }

            const ownerInput = document.getElementById('OwnerID');
            const hiddenDev = document.getElementById('hidden_device_id');
            const selectDev = document.getElementById('select_device_id');
            const deviceVal = hiddenDev ? hiddenDev.value.trim() : (selectDev ? selectDev.value.trim() : '');
            const ownerVal = ownerInput ? ownerInput.value.trim() : '';

            if (!deviceVal) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Device Required',
                        text: 'Please choose an available device to assign.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    alert('Please choose an available device to assign.');
                }
                return false;
            }

            if (!ownerVal) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Assignee Required',
                        text: 'Please search and select an employee from the list.',
                        confirmButtonColor: '#2563eb'
                    });
                } else {
                    alert('Please search and select an employee.');
                }
                const searchInput = document.getElementById('owner_search_input');
                if (searchInput) {
                    searchInput.focus();
                    openOwnerSearchPopup();
                }
                return false;
            }

            // Low Stock Alert (Triggers when remaining available stock <= 3)
            if (afterBorrowCount <= 3) {
                e.preventDefault();

                const ownerSearchInput = document.getElementById('owner_search_input');
                const ownerDisplayName = ownerSearchInput ? ownerSearchInput.value.trim() : 'the employee';

                let stockBadge = '';
                if (afterBorrowCount === 0) {
                    stockBadge = `<span style="color: #ef4444; font-weight: 800; background: #fee2e2; padding: 4px 12px; border-radius: 8px; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-triangle-exclamation"></i> 0 Units Left (Out of Stock!)</span>`;
                } else {
                    stockBadge = `<span style="color: #d97706; font-weight: 800; background: #fef3c7; padding: 4px 12px; border-radius: 8px; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 6px;"><i class="fa-solid fa-boxes-stacked"></i> Only ${afterBorrowCount} Unit${afterBorrowCount > 1 ? 's' : ''} Remaining</span>`;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: '⚠️ Low Stock Warning',
                        html: `
                                <div style="text-align: left; background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 14px; padding: 14px 18px; margin: 12px 0 16px 0;">
                                    <div style="font-weight: 700; color: #92400e; font-size: 0.95rem; margin-bottom: 6px;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Stock Threshold Alert
                                    </div>
                                    <div style="color: #78350f; font-size: 0.88rem; line-height: 1.5;">
                                        Assigning <strong>${currentModelName}</strong> (<code>${currentDeviceId}</code>) will reduce remaining available inventory to:
                                        <div style="margin-top: 8px; font-size: 0.92rem;">${stockBadge}</div>
                                    </div>
                                </div>
                                <p style="margin: 0; color: #475569; font-size: 0.92rem; text-align: left;">
                                    Do you want to proceed with assigning this hardware asset to <strong style="color: #0f172a;">${ownerDisplayName}</strong>?
                                </p>`,
                        showConfirmButton: true,
                        showCancelButton: true,
                        showDenyButton: false,
                        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Confirm & Assign',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#64748b',
                        reverseButtons: true
                    }).then((res) => {
                        if (res.isConfirmed) {
                            rcvForm.dataset.confirmedLowStock = 'true';
                            rcvForm.submit();
                        }
                    });
                } else {
                    if (confirm(`Low Stock Alert: Remaining units will be ${afterBorrowCount}. Confirm assignment?`)) {
                        rcvForm.dataset.confirmedLowStock = 'true';
                        rcvForm.submit();
                    }
                }
                return false;
            }
        });
    }

    window.clearRcvPhoto = function () {
        const photoInput = document.getElementById('rcv_photo');
        if (photoInput) {
            photoInput.value = '';
        }
        const prevWrap = document.getElementById('rcv_photo_wrap');
        const prevImg = document.getElementById('rcv_photo_img');
        if (prevImg) {
            prevImg.src = '';
        }
        if (prevWrap) {
            prevWrap.style.display = 'none';
        }
    };

    window.openRcvPhotoModal = function (src) {
        if (!src) return;
        const box = document.getElementById('rcv_image_lightbox');
        const img = document.getElementById('rcv_lightbox_img');
        if (box && img) {
            img.src = src;
            box.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeRcvLightbox = function (e) {
        if (e) e.stopPropagation();
        const box = document.getElementById('rcv_image_lightbox');
        if (box) {
            box.classList.remove('is-open');
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.closeRcvLightbox();
        }
    });

    function initReceiveDevicePhoto() {
        const rcvPhotoInput = document.getElementById('rcv_photo');
        if (rcvPhotoInput) {
            rcvPhotoInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const prevWrap = document.getElementById('rcv_photo_wrap');
                        const prevImg = document.getElementById('rcv_photo_img');
                        if (prevImg) {
                            prevImg.src = e.target.result;
                        }
                        if (prevWrap) {
                            prevWrap.style.display = 'inline-block';
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    window.clearRcvPhoto();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initReceiveDeviceValidation();
            initReceiveDevicePhoto();
        });
    } else {
        initReceiveDeviceValidation();
        initReceiveDevicePhoto();
    }
</script>

<?php
        return ob_get_clean();
}

add_shortcode('receive_device', 'receive_device');
