<?php
if (!defined('ABSPATH')) {
    exit;
}

function form_edit_owner($editing = null)
{
    global $wpdb;
    $table_owner = 'Owners';

    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments ORDER BY DepartmentName ASC");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions ORDER BY PositionName ASC");
    $status_emp = $wpdb->get_results("SELECT StatusID, Status_name FROM Status_Employee");
    $resigned_status_id = (int) ($wpdb->get_var("SELECT StatusID FROM Status_Employee WHERE LOWER(Status_name) = 'resigned'") ?: 2);
    $available_status_id = (int) ($wpdb->get_var("SELECT StatusID FROM Statuses WHERE LOWER(StatusName) = 'available'") ?: 1);

    // Query active devices currently assigned to this employee
    $owner_id = intval($editing->OwnerID ?? 0);
    $assigned_devices = [];
    if ($owner_id > 0) {
        $assigned_devices = $wpdb->get_results($wpdb->prepare("
            SELECT d.DeviceID, c.CategoryName, b.BrandName, d.Model, d.SerialNumber, s.StatusName, kw.KeywordName, d.ReceiveDate
            FROM Devices d
            LEFT JOIN Brands b ON d.BrandID = b.BrandID
            LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
            LEFT JOIN Statuses s ON d.StatusID = s.StatusID
            LEFT JOIN Keywords kw ON d.KeywordID = kw.KeywordID
            WHERE d.OwnerID = %d
            ORDER BY d.CategoryID ASC, d.DeviceID ASC
        ", $owner_id));
    }

    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['OwnerID'])) {
        if (!is_user_logged_in() || !isset($_POST['_edit_emp_nonce']) || !wp_verify_nonce($_POST['_edit_emp_nonce'], 'edit_employee_nonce')) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Session Expired',
                        text: 'Security token expired. Please refresh and try again.'
                    });
                });
            </script>";
            return ob_get_clean();
        }

        $owner_id = intval($_POST['OwnerID']);
        $nickname = sanitize_text_field($_POST['Nickname'] ?? '');
        $firstname = sanitize_text_field($_POST['FirstName'] ?? '');
        $lastname = sanitize_text_field($_POST['LastName'] ?? '');
        $departmentID = !empty($_POST['DepartmentID']) ? intval($_POST['DepartmentID']) : null;
        $positionID = !empty($_POST['PositionID']) ? intval($_POST['PositionID']) : null;
        $statusID = !empty($_POST['StatusID']) ? intval($_POST['StatusID']) : null;
        $email = !empty($_POST['Email']) ? strtolower(sanitize_email($_POST['Email'])) : null;

        $updated = $wpdb->update(
            $table_owner,
            [
                'Nickname' => $nickname,
                'FirstName' => $firstname,
                'LastName' => $lastname,
                'Email' => $email,
                'DepartmentID' => $departmentID,
                'PositionID' => $positionID,
                'StatusID' => $statusID
            ],
            ['OwnerID' => $owner_id]
        );

        if ($updated !== false) {
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? 'system';

            if ($statusID === $resigned_status_id) {
                $assigned_devices = $wpdb->get_results(
                    $wpdb->prepare("SELECT DeviceID, CategoryID, Model FROM Devices WHERE OwnerID = %d", $owner_id)
                );

                if (!empty($assigned_devices)) {
                    $wpdb->update(
                        'Devices',
                        [
                            'StatusID' => $available_status_id,
                            'OwnerID' => null,
                            'DepartmentID' => null,
                            'ReceiveDate' => null,
                            'ReturnDate' => null,
                            'RepairDate' => null,
                            'PositionID' => null,
                            'ExpectedReturnDate' => null,
                            'LastNotifiedDate' => null,
                        ],
                        ['OwnerID' => $owner_id]
                    );

                    foreach ($assigned_devices as $dev) {
                        $safe_category_id = !empty($dev->CategoryID) ? $dev->CategoryID : 1;
                        $wpdb->insert('History_new', [
                            'DeviceID' => $dev->DeviceID,
                            'Action' => 'Offboard Return',
                            'Date' => current_time('mysql'),
                            'Description' => "Offboard: Device {$dev->DeviceID} ({$dev->Model}) returned to stock from {$nickname}",
                            'user_email' => $user_email,
                            'CategoryID' => $safe_category_id,
                            'Owner' => $nickname,
                        ]);
                    }
                }
            }

            $firstname = trim($firstname);
            $lastname = trim($lastname);

            if ($firstname === '' && $lastname === '') {
                $description = "Updated Employee: {$nickname}";
            } else {
                $description = "Updated Employee: {$firstname} {$lastname} ({$nickname})";
            }

            $action_label = ($statusID === $resigned_status_id) ? 'Employee Resigned' : 'Update Employee';
            $action_desc = ($statusID === $resigned_status_id) ? "Employee resigned: {$nickname}" : $description;

            $wpdb->insert('History_new', [
                'DeviceID' => 0,
                'Action' => $action_label,
                'Date' => current_time('mysql'),
                'Description' => $action_desc,
                'user_email' => $user_email,
                'CategoryID' => 1,
                'Owner' => $nickname
            ]);

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Employee Updated',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '" . esc_url(home_url('/Owner/')) . "';
                    });
                });
            </script>";
        } else {
            $db_err = esc_js($wpdb->last_error ?: 'Unknown database error');
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Update Employee',
                        text: 'Error: " . $db_err . "'
                    });
                });
            </script>";
        }
    }

    if (!$editing) {
        $owner_id = isset($_GET['OwnerID']) ? intval($_GET['OwnerID']) : 0;
        if ($owner_id) {
            $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner WHERE OwnerID = %d", $owner_id));
        }
    }
    ?>

    <!-- Clean Enterprise Edit Employee Component -->
    <div id="edit-employee-wrapper" class="employee-bento-wrapper">
        <!-- Desktop Sleek Header Bar -->
        <div class="emp-header-bar desktop-only-el">
            <div class="emp-header-left">
                <div class="emp-icon-badge">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <p class="emp-header-subtitle">Update employee details, role, and department</p>
                </div>
            </div>
            <div class="emp-header-badge">
                <span class="emp-status-dot-static"></span>
                <span>Edit Employee</span>
            </div>
        </div>

        <form method="POST" action="" id="edit-employee-form" class="emp-main-form">
            <?php wp_nonce_field('edit_employee_nonce', '_edit_emp_nonce'); ?>
            <input type="hidden" name="OwnerID" value="<?= esc_attr($editing->OwnerID ?? '') ?>">

            <!-- Mobile Only Header -->
            <div class="emp-mobile-header mobile-only-el">
                <h2>Edit Employee</h2>
            </div>

            <!-- Main 2-Column Layout -->
            <div class="emp-layout-grid">
                <!-- Left Column: Form Fields -->
                <div class="emp-form-card">
                    <div class="emp-fields-grid">

                        <!-- Row 1: Nickname & Status -->
                        <div class="emp-field-group">
                            <label for="emp_edit_nickname">
                                <i class="fa-solid fa-signature emp-field-icon desktop-only-el"></i>
                                Nickname <span class="emp-req">*</span>
                            </label>
                            <input type="text" name="Nickname" id="emp_edit_nickname"
                                value="<?= esc_attr($editing->Nickname ?? '') ?>" required autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_edit_status">
                                <i class="fa-solid fa-circle-check emp-field-icon desktop-only-el"></i>
                                Status
                            </label>
                            <select name="StatusID" id="emp_edit_status">
                                <option value="">-- Select Status --</option>
                                <?php foreach ($status_emp as $status): ?>
                                    <option value="<?= $status->StatusID ?>" <?= selected($editing->StatusID ?? '', $status->StatusID, false) ?>>
                                        <?= esc_html($status->Status_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 2: First Name & Last Name -->
                        <div class="emp-field-group">
                            <label for="emp_edit_firstname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                First Name
                            </label>
                            <input type="text" name="FirstName" id="emp_edit_firstname"
                                value="<?= esc_attr($editing->FirstName ?? '') ?>" placeholder="First name"
                                autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_edit_lastname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                Last Name
                            </label>
                            <input type="text" name="LastName" id="emp_edit_lastname"
                                value="<?= esc_attr($editing->LastName ?? '') ?>" placeholder="Last name"
                                autocomplete="off">
                        </div>

                        <!-- Row 3: Email Address (Full Width Span 2) -->
                        <div class="emp-field-group emp-span-2">
                            <label for="emp_edit_email">
                                <i class="fa-solid fa-envelope emp-field-icon desktop-only-el"></i>
                                Email Address
                            </label>
                            <input type="email" name="Email" id="emp_edit_email"
                                value="<?= esc_attr(strtolower($editing->Email ?? '')) ?>" placeholder="name@company.com"
                                autocomplete="off" style="text-transform: lowercase !important;">
                        </div>

                        <!-- Row 4: Department & Position -->
                        <div class="emp-field-group">
                            <label for="emp_edit_dept">
                                <i class="fa-solid fa-building emp-field-icon desktop-only-el"></i>
                                Department
                            </label>
                            <select name="DepartmentID" id="emp_edit_dept">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dep): ?>
                                    <option value="<?= $dep->DepartmentID ?>" <?= selected($editing->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                                        <?= esc_html($dep->DepartmentName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_edit_pos">
                                <i class="fa-solid fa-briefcase emp-field-icon desktop-only-el"></i>
                                Position
                            </label>
                            <select name="PositionID" id="emp_edit_pos" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?= $position->PositionID ?>" <?= selected($editing->PositionID ?? '', $position->PositionID, false) ?>>
                                        <?= esc_html($position->PositionName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <!-- Form Actions (Cancel & Update) -->
                    <div class="emp-form-actions">
                        <button type="button" onclick="history.back()" class="emp-btn-cancel">
                            <i class="fa-solid fa-arrow-left me-1"></i> Cancel
                        </button>
                        <button type="submit" class="emp-btn-submit">
                            <i class="fa-solid fa-check me-1"></i>
                            <span>Update Employee</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Clean Staff ID Card Preview (Desktop Only) -->
                <div class="emp-preview-column desktop-only-el">
                    <div class="emp-badge-card">
                        <!-- Top Bar -->
                        <div class="emp-badge-top">
                            <span class="emp-badge-tag"><i class="fa-solid fa-id-badge me-1"></i> STAFF ID CARD</span>
                            <span class="emp-badge-status" id="preview-edit-status-badge">
                                <span class="emp-status-dot" id="preview-edit-status-dot"></span>
                                <span id="preview-edit-status">Active</span>
                            </span>
                        </div>

                        <!-- Avatar Visualizer -->
                        <div class="emp-avatar-box">
                            <div class="emp-avatar-circle">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="emp-id-pill" id="preview-edit-nickname">
                                <?= esc_html($editing->Nickname ?: 'Staff Member') ?>
                            </div>
                        </div>

                        <!-- Details Box -->
                        <div class="emp-details-box">
                            <div class="emp-preview-name" id="preview-edit-fullname">
                                <?= esc_html(trim(($editing->FirstName ?? '') . ' ' . ($editing->LastName ?? '')) ?: 'Staff Member') ?>
                            </div>
                            <div class="emp-preview-email" id="preview-edit-email" style="text-transform: lowercase !important;">
                                <?= esc_html(strtolower($editing->Email ?: 'name@company.com')) ?>
                            </div>

                            <div class="emp-meta-grid">
                                <div class="emp-meta-item">
                                    <span class="emp-meta-label"><i class="fa-solid fa-building me-1"></i> DEPARTMENT</span>
                                    <span class="emp-meta-val" id="preview-edit-dept">
                                        <?php
                                        $dName = '—';
                                        foreach ($departments as $d) {
                                            if ($d->DepartmentID == ($editing->DepartmentID ?? 0)) {
                                                $dName = $d->DepartmentName;
                                                break;
                                            }
                                        }
                                        echo esc_html($dName);
                                        ?>
                                    </span>
                                </div>
                                <div class="emp-meta-item">
                                    <span class="emp-meta-label"><i class="fa-solid fa-briefcase me-1"></i> POSITION</span>
                                    <?php
                                    $pName = '—';
                                    $isIntern = false;
                                    foreach ($positions as $p) {
                                        if ($p->PositionID == ($editing->PositionID ?? 0)) {
                                            $pName = $p->PositionName;
                                            $isIntern = stripos($pName, 'intern') !== false;
                                            break;
                                        }
                                    }
                                    $posClass = !empty($editing->PositionID) ? ($isIntern ? 'pos-badge-intern' : 'pos-badge-fulltime') : '';
                                    ?>
                                    <span class="emp-meta-val <?= $posClass ?>" id="preview-edit-pos">
                                        <?= esc_html($pName) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>

        <!-- Assigned Equipment Section -->
        <div class="emp-assigned-section mt-4">
            <div class="emp-assigned-header">
                <div class="emp-assigned-title-wrap">
                    <div class="emp-assigned-icon">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <div>
                        <h3 class="emp-assigned-title">Assigned Equipment</h3>
                        <p class="emp-assigned-subtitle">Devices currently issued to this staff member</p>
                    </div>
                </div>
                <div class="emp-assigned-badge">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    <span><?= count($assigned_devices) ?> <?= count($assigned_devices) === 1 ? 'Device' : 'Devices' ?></span>
                </div>
            </div>

            <?php if (!empty($assigned_devices)): ?>
                <div class="emp-assigned-grid">
                    <?php foreach ($assigned_devices as $dev): 
                        $cat = $dev->CategoryName ?? 'Device';
                        $cat_icon = 'fa-plug';
                        $cat_color = '#6ABF57';
                        if (strcasecmp($cat, 'Laptop') === 0) {
                            $cat_icon = 'fa-laptop';
                            $cat_color = '#15A5DA';
                        } elseif (strcasecmp($cat, 'Monitor') === 0) {
                            $cat_icon = 'fa-desktop';
                            $cat_color = '#FDB840';
                        }
                    ?>
                        <div class="emp-dev-card">
                            <div class="emp-dev-card-header">
                                <div class="emp-dev-cat-badge" style="background: <?= $cat_color ?>18; color: <?= $cat_color ?>;">
                                    <i class="fa-solid <?= $cat_icon ?> me-1"></i> <?= esc_html($cat) ?>
                                </div>
                                <span class="emp-dev-id-badge"><?= esc_html($dev->DeviceID) ?></span>
                            </div>
                            <div class="emp-dev-card-body">
                                <div class="emp-dev-title">
                                    <?= esc_html($dev->BrandName) ?> <?= esc_html($dev->Model) ?>
                                </div>
                                <div class="emp-dev-meta">
                                    <div class="emp-dev-meta-item">
                                        <span class="emp-dev-meta-label">Serial Number:</span>
                                        <span class="emp-dev-meta-val font-monospace"><?= esc_html($dev->SerialNumber ?: '-') ?></span>
                                    </div>
                                    <?php if (!empty($dev->KeywordName)): ?>
                                        <div class="emp-dev-meta-item">
                                            <span class="emp-dev-meta-label">Type/Profile:</span>
                                            <span class="emp-dev-meta-val"><?= esc_html($dev->KeywordName) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($dev->ReceiveDate) && $dev->ReceiveDate !== '0000-00-00'): ?>
                                        <div class="emp-dev-meta-item">
                                            <span class="emp-dev-meta-label">Issued Date:</span>
                                            <span class="emp-dev-meta-val"><?= esc_html(date_i18n('d M Y', strtotime($dev->ReceiveDate))) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="emp-dev-card-footer">
                                <span class="status-badge status-inuse" style="font-size: 0.72rem; padding: 3px 10px;">
                                    <span class="status-dot"></span> <?= esc_html($dev->StatusName ?: 'In Use') ?>
                                </span>
                                <a href="<?= esc_url(home_url('/home/?view=' . $dev->DeviceID)) ?>" class="emp-dev-link-btn" title="View device timeline">
                                    <span>Details</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="emp-assigned-empty">
                    <div class="emp-assigned-empty-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h4>No Equipment Assigned</h4>
                    <p>This staff member currently has no active laptops, monitors, or accessories assigned.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scoped Style Block: Clean Enterprise Theme -->
    <style>
        .page-edit-owner .fl-builder-content,
        .page-edit-owner .fl-row,
        .page-edit-owner .fl-row-content,
        .page-edit-owner .fl-col-group,
        .page-edit-owner .fl-col,
        .page-edit-owner .fl-col-content,
        .page-edit-owner .ast-container,
        .page-edit-owner #primary,
        .page-edit-owner .entry-content {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        #edit-employee-wrapper {
            position: relative;
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.5rem 1rem 2rem 1rem;
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-sizing: border-box;
            color: #0f172a;
        }

        /* --- DESKTOP STYLES (Screen > 768px) --- */
        @media (min-width: 769px) {
            #edit-employee-wrapper .mobile-only-el {
                display: none !important;
            }

            #edit-employee-wrapper .desktop-only-el {
                display: flex !important;
            }

            #edit-employee-wrapper .emp-header-bar {
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
            }

            #edit-employee-wrapper .emp-header-left {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            #edit-employee-wrapper .emp-icon-badge {
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

            #edit-employee-wrapper .emp-header-title {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.3;
            }

            #edit-employee-wrapper .emp-header-subtitle {
                margin: 3px 0 0 0;
                font-size: 0.85rem;
                color: #64748b;
                line-height: 1.4;
            }

            #edit-employee-wrapper .emp-header-badge {
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

            #edit-employee-wrapper .emp-status-dot-static {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background-color: #10b981;
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
            }

            #edit-employee-wrapper .emp-main-form {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #edit-employee-wrapper .emp-layout-grid {
                display: grid;
                grid-template-columns: 1.4fr 0.9fr;
                gap: 1.5rem;
                align-items: stretch;
            }

            #edit-employee-wrapper .emp-form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                padding: 1.5rem 1.75rem;
                box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                animation: cardFadeIn 0.45s ease-out forwards;
            }

            #edit-employee-wrapper .emp-fields-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 1rem 1.25rem !important;
            }

            #edit-employee-wrapper .emp-field-group {
                display: flex !important;
                flex-direction: column !important;
                gap: 5px !important;
                margin-bottom: 0 !important;
            }

            #edit-employee-wrapper .emp-span-2 {
                grid-column: span 2 !important;
            }

            #edit-employee-wrapper .emp-field-group label {
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #334155 !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin: 0 !important;
                text-transform: none !important;
            }

            #edit-employee-wrapper .emp-field-icon {
                color: #6366f1;
                font-size: 0.9rem;
            }

            #edit-employee-wrapper .emp-req {
                color: #ef4444;
                font-weight: 700;
            }

            #edit-employee-wrapper .emp-field-group input,
            #edit-employee-wrapper .emp-field-group select {
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

            #edit-employee-wrapper .emp-field-group select {
                padding-right: 36px !important;
                cursor: pointer !important;
            }

            #edit-employee-wrapper .emp-field-group select.emp-locked-select {
                background-color: #f1f5f9 !important;
                color: #059669 !important;
                font-weight: 700 !important;
                border: 1.5px solid #cbd5e1 !important;
                cursor: not-allowed !important;
                opacity: 1 !important;
                -webkit-text-fill-color: #059669 !important;
            }

            #edit-employee-wrapper .emp-field-group input:hover,
            #edit-employee-wrapper .emp-field-group select:hover {
                border-color: #cbd5e1 !important;
                background-color: #ffffff !important;
            }

            #edit-employee-wrapper .emp-field-group input:focus,
            #edit-employee-wrapper .emp-field-group select:focus {
                background-color: #ffffff !important;
                border-color: #6366f1 !important;
                outline: none !important;
                box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.16) !important;
            }

            #edit-employee-wrapper .emp-form-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 1rem !important;
                margin-top: 1.25rem !important;
                padding-top: 1rem !important;
                border-top: 1.5px dashed #f1f5f9 !important;
            }

            #edit-employee-wrapper .emp-btn-cancel {
                padding: 0.6rem 1.75rem !important;
                font-size: 0.92rem !important;
                font-weight: 600 !important;
                background-color: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 10px !important;
                color: #64748b !important;
                transition: all 0.2s ease !important;
                cursor: pointer !important;
            }

            #edit-employee-wrapper .emp-btn-cancel:hover {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                border-color: #cbd5e1 !important;
            }

            #edit-employee-wrapper .emp-btn-submit {
                padding: 0.6rem 2.2rem !important;
                font-size: 0.92rem !important;
                font-weight: 700 !important;
                background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
                border: none !important;
                border-radius: 10px !important;
                color: #ffffff !important;
                box-shadow: 0 6px 20px -4px rgba(79, 70, 229, 0.4) !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                cursor: pointer !important;
            }

            #edit-employee-wrapper .emp-btn-submit:hover {
                transform: translateY(-2px) !important;
                color: #ffffff !important;
                box-shadow: 0 8px 25px -4px rgba(79, 70, 229, 0.5) !important;
            }

            /* Preview Badge Column */
            #edit-employee-wrapper .emp-preview-column {
                display: flex !important;
                flex-direction: column !important;
                animation: cardFadeIn 0.5s ease-out forwards;
            }

            #edit-employee-wrapper .emp-badge-card {
                height: 100%;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 245, 249, 0.98) 100%);
                border-radius: 20px;
                padding: 1.5rem;
                color: #0f172a;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                border: 1.5px solid #e2e8f0;
                box-sizing: border-box;
                box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.05);
            }

            #edit-employee-wrapper .emp-badge-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            #edit-employee-wrapper .emp-badge-tag {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #4338ca;
                background: #eef2ff;
                padding: 4px 10px;
                border-radius: 999px;
                border: 1px solid #c7d2fe;
            }

            #edit-employee-wrapper .emp-badge-status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 0.72rem;
                font-weight: 600;
                padding: 4px 10px;
                border-radius: 999px;
                color: #059669;
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
            }

            #edit-employee-wrapper .emp-status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #10b981;
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
            }

            #edit-employee-wrapper .emp-avatar-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.25rem 0;
            }

            #edit-employee-wrapper .emp-avatar-circle {
                width: 68px;
                height: 68px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.8rem;
                margin-bottom: 0.75rem;
                box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
            }

            #edit-employee-wrapper .emp-id-pill {
                font-size: 1.05rem;
                font-weight: 700;
                color: #0f172a;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                padding: 4px 16px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            }

            #edit-employee-wrapper .emp-details-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1rem 1.15rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            }

            #edit-employee-wrapper .emp-preview-name {
                font-size: 1.05rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.3;
            }

            #edit-employee-wrapper .emp-preview-email {
                font-size: 0.82rem;
                color: #64748b;
                margin-bottom: 0.75rem;
                line-height: 1.3;
            }

            #edit-employee-wrapper .emp-meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                border-top: 1px solid #f1f5f9;
                padding-top: 8px;
            }

            #edit-employee-wrapper .emp-meta-item {
                display: flex;
                flex-direction: column;
            }

            #edit-employee-wrapper .emp-meta-label {
                font-size: 0.65rem;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                margin-bottom: 2px;
                font-weight: 700;
            }

            #edit-employee-wrapper .emp-meta-val {
                font-size: 0.84rem;
                font-weight: 600;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* --- MOBILE STYLES (Screen <= 768px) --- */
        @media (max-width: 768px) {
            #edit-employee-wrapper {
                padding: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #edit-employee-wrapper .desktop-only-el {
                display: none !important;
            }

            #edit-employee-wrapper .mobile-only-el {
                display: block !important;
            }

            #edit-employee-wrapper .emp-mobile-header {
                display: block;
                text-align: center;
                margin-bottom: 15px;
            }

            #edit-employee-wrapper .emp-mobile-header h2 {
                font-size: 1.35rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                letter-spacing: -0.02em !important;
                margin: 0 !important;
            }

            #edit-employee-wrapper .emp-layout-grid {
                display: block !important;
                width: 100% !important;
            }

            #edit-employee-wrapper .emp-form-card {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            #edit-employee-wrapper .emp-fields-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 14px !important;
                width: 100% !important;
            }

            #edit-employee-wrapper .emp-field-group {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 20px !important;
                padding: 16px 18px !important;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04) !important;
                margin-bottom: 0 !important;
            }

            #edit-employee-wrapper .emp-field-group label {
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

            #edit-employee-wrapper .emp-field-group input,
            #edit-employee-wrapper .emp-field-group select {
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

            #edit-employee-wrapper .emp-form-actions {
                display: flex !important;
                flex-direction: row !important;
                gap: 12px !important;
                margin-top: 24px !important;
                padding-top: 18px !important;
                border-top: 1.5px dashed #e2e8f0 !important;
                width: 100% !important;
            }

            #edit-employee-wrapper .emp-btn-cancel,
            #edit-employee-wrapper .emp-btn-submit {
                flex: 1 !important;
                height: 52px !important;
                border-radius: 9999px !important;
                font-size: 1rem !important;
                font-weight: 700 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #edit-employee-wrapper .emp-btn-cancel {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                color: #64748b !important;
            }

            #edit-employee-wrapper .emp-btn-submit {
                background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
                border: none !important;
                color: #ffffff !important;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35) !important;
            }
        }

        /* --- Assigned Equipment Section Styles --- */
        #edit-employee-wrapper .emp-assigned-section {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 24px;
            padding: 1.75rem;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
            box-sizing: border-box;
            width: 100%;
        }

        #edit-employee-wrapper .emp-assigned-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        #edit-employee-wrapper .emp-assigned-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #edit-employee-wrapper .emp-assigned-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        #edit-employee-wrapper .emp-assigned-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        #edit-employee-wrapper .emp-assigned-subtitle {
            margin: 2px 0 0 0;
            font-size: 0.84rem;
            color: #64748b;
        }

        #edit-employee-wrapper .emp-assigned-badge {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
        }

        #edit-employee-wrapper .emp-assigned-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 16px;
        }

        #edit-employee-wrapper .emp-dev-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.22s ease;
        }

        #edit-employee-wrapper .emp-dev-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -4px rgba(15, 23, 42, 0.08);
        }

        #edit-employee-wrapper .emp-dev-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        #edit-employee-wrapper .emp-dev-cat-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
        }

        #edit-employee-wrapper .emp-dev-id-badge {
            font-size: 0.82rem;
            font-weight: 800;
            color: #1e40af;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 2px 8px;
            border-radius: 6px;
            letter-spacing: 0.02em;
        }

        #edit-employee-wrapper .emp-dev-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        #edit-employee-wrapper .emp-dev-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 0.8rem;
            color: #64748b;
        }

        #edit-employee-wrapper .emp-dev-meta-item {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        #edit-employee-wrapper .emp-dev-meta-label {
            color: #94a3b8;
            font-weight: 500;
        }

        #edit-employee-wrapper .emp-dev-meta-val {
            color: #334155;
            font-weight: 600;
            text-align: right;
        }

        #edit-employee-wrapper .emp-dev-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
        }

        #edit-employee-wrapper .emp-dev-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 8px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            transition: all 0.15s ease;
        }

        #edit-employee-wrapper .emp-dev-link-btn:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }

        #edit-employee-wrapper .emp-assigned-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #64748b;
        }

        #edit-employee-wrapper .emp-assigned-empty-icon {
            font-size: 2.5rem;
            color: #cbd5e1;
            margin-bottom: 10px;
        }

        #edit-employee-wrapper .emp-assigned-empty h4 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #334155;
            margin: 0 0 4px 0;
        }

        #edit-employee-wrapper .emp-assigned-empty p {
            font-size: 0.85rem;
            margin: 0;
            color: #94a3b8;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nicknameInput = document.getElementById('emp_edit_nickname');
            const firstnameInput = document.getElementById('emp_edit_firstname');
            const lastnameInput = document.getElementById('emp_edit_lastname');
            const emailInput = document.getElementById('emp_edit_email');
            const deptSelect = document.getElementById('emp_edit_dept');
            const posSelect = document.getElementById('emp_edit_pos');
            const statusSelect = document.getElementById('emp_edit_status');

            const pNickname = document.getElementById('preview-edit-nickname');
            const pFullname = document.getElementById('preview-edit-fullname');
            const pEmail = document.getElementById('preview-edit-email');
            const pDept = document.getElementById('preview-edit-dept');
            const pPos = document.getElementById('preview-edit-pos');
            const pStatus = document.getElementById('preview-edit-status');
            const pStatusBadge = document.getElementById('preview-edit-status-badge');
            const pStatusDot = document.getElementById('preview-edit-status-dot');

            const statusStyles = {
                'active': { color: '#059669', bg: '#ecfdf5', border: '#a7f3d0', dot: '#10b981' },
                'resigned': { color: '#d97706', bg: '#fffbeb', border: '#fde68a', dot: '#f59e0b' }
            };

            function syncEmployeeEditPreview() {
                if (pNickname && nicknameInput) {
                    pNickname.textContent = nicknameInput.value.trim() || 'Staff Member';
                }
                if (pFullname) {
                    const fn = (firstnameInput ? firstnameInput.value.trim() : '');
                    const ln = (lastnameInput ? lastnameInput.value.trim() : '');
                    const full = [fn, ln].filter(Boolean).join(' ');
                    pFullname.textContent = full || 'Staff Member';
                }
                if (pEmail && emailInput) {
                    pEmail.textContent = (emailInput.value.trim() || 'name@company.com').toLowerCase();
                }
                if (pDept && deptSelect) {
                    pDept.textContent = (deptSelect.selectedIndex > 0) ? deptSelect.options[deptSelect.selectedIndex].text : '—';
                }
                if (pPos && posSelect) {
                    if (posSelect.selectedIndex > 0) {
                        const pText = posSelect.options[posSelect.selectedIndex].text;
                        pPos.textContent = pText;
                        const isInt = pText.toLowerCase().includes('intern');
                        pPos.className = 'emp-meta-val ' + (isInt ? 'pos-badge-intern' : 'pos-badge-fulltime');
                    } else {
                        pPos.textContent = '—';
                        pPos.className = 'emp-meta-val';
                    }
                }
                if (statusSelect && statusSelect.selectedIndex >= 0) {
                    const opt = statusSelect.options[statusSelect.selectedIndex];
                    const raw = (opt.text || '').toLowerCase().trim();
                    const st = statusStyles[raw] || statusStyles['active'];
                    if (pStatus) pStatus.textContent = opt.text;
                    if (pStatusBadge) {
                        pStatusBadge.style.color = st.color;
                        pStatusBadge.style.backgroundColor = st.bg;
                        pStatusBadge.style.borderColor = st.border;
                    }
                    if (pStatusDot) {
                        pStatusDot.style.backgroundColor = st.dot;
                    }
                }
            }

            if (nicknameInput) nicknameInput.addEventListener('input', syncEmployeeEditPreview);
            if (firstnameInput) firstnameInput.addEventListener('input', syncEmployeeEditPreview);
            if (lastnameInput) lastnameInput.addEventListener('input', syncEmployeeEditPreview);
            if (emailInput) emailInput.addEventListener('input', syncEmployeeEditPreview);
            if (deptSelect) deptSelect.addEventListener('change', syncEmployeeEditPreview);
            if (posSelect) posSelect.addEventListener('change', syncEmployeeEditPreview);
            if (statusSelect) statusSelect.addEventListener('change', syncEmployeeEditPreview);

            syncEmployeeEditPreview();

            // Offboard preview on form submit when status is Resigned
            const editForm = document.getElementById('edit-employee-form');
            const resignedStatusId = <?= json_encode((string) $resigned_status_id) ?>;
            const ajaxUrl = <?= json_encode(admin_url('admin-ajax.php')) ?>;
            const ajaxNonce = <?= json_encode(wp_create_nonce('stock_supply_ajax_nonce')) ?>;
            const currentOwnerId = <?= json_encode((int) ($editing->OwnerID ?? 0)) ?>;

            if (editForm) {
                editForm.addEventListener('submit', function (e) {
                    if (editForm.dataset.confirmedResign === 'true') {
                        return; // proceed with submit
                    }

                    const selectedStatusVal = statusSelect ? statusSelect.value : '';
                    const selectedStatusText = (statusSelect && statusSelect.selectedIndex >= 0)
                        ? statusSelect.options[statusSelect.selectedIndex].text.toLowerCase().trim()
                        : '';
                    const isResigned = (selectedStatusVal == resignedStatusId) || selectedStatusText.includes('resign');

                    if (!isResigned || !currentOwnerId) {
                        return; // normal submit
                    }

                    e.preventDefault();

                    const currentNick = (nicknameInput ? nicknameInput.value.trim() : '') || 'Staff Member';

                    // Show loading
                    Swal.fire({
                        title: 'Checking equipment...',
                        text: 'Fetching assigned devices for ' + currentNick,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        showCancelButton: false,
                        showDenyButton: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const fd = new FormData();
                    fd.append('action', 'get_employee_devices');
                    fd.append('nonce', ajaxNonce);
                    fd.append('owner_id', currentOwnerId);

                    fetch(ajaxUrl, { method: 'POST', body: fd })
                        .then(res => res.json())
                        .then(result => {
                            if (!result.success) {
                                // If error fetching, fallback to confirm prompt
                                Swal.fire({
                                    icon: 'warning',
                                    title: '⚠️ Confirm Resignation?',
                                    text: 'Status will be updated to Resigned.',
                                    showConfirmButton: true,
                                    showCancelButton: true,
                                    showDenyButton: false,
                                    confirmButtonText: 'Confirm & Save',
                                    cancelButtonText: 'Cancel',
                                    confirmButtonColor: '#2563eb',
                                    cancelButtonColor: '#64748b'
                                }).then(r => {
                                    if (r.isConfirmed) {
                                        editForm.dataset.confirmedResign = 'true';
                                        editForm.submit();
                                    }
                                });
                                return;
                            }

                            const devices = result.data.devices || [];
                            const owner = result.data.owner || {};
                            const count = result.data.count || 0;
                            const displayName = owner.full_name || currentNick;
                            const displayNick = owner.nickname || currentNick;
                            const displayDept = (deptSelect && deptSelect.selectedIndex > 0) ? deptSelect.options[deptSelect.selectedIndex].text : (owner.department || '-');

                            if (count === 0) {
                                const noDevHtml = `
                                <div class="offboard-modal-container">
                                    <div class="offboard-employee-info">
                                        <div class="offboard-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                            <i class="fa-solid fa-user-large"></i>
                                        </div>
                                        <div class="offboard-emp-details">
                                            <div class="offboard-emp-name">${displayName}</div>
                                            <div class="offboard-emp-nick">@${displayNick} • ${displayDept}</div>
                                        </div>
                                        <div class="offboard-device-count" style="border: 1px solid #e2e8f0;">
                                            <span class="offboard-count-num" style="color: #64748b;">0</span>
                                            <span class="offboard-count-label">devices</span>
                                        </div>
                                    </div>
                                    <div style="background: #f8fafc; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; margin-top: 10px;">
                                        <p style="margin: 0; color: #334155; font-size: 0.95rem; font-weight: 500;">
                                            This employee currently has <strong style="color: #0f172a;">no assigned devices</strong>.
                                        </p>
                                        <p style="margin: 8px 0 0; color: #64748b; font-size: 0.85rem;">
                                            Employee status will be saved as <span style="color: #d97706; font-weight: 700;">Resigned</span>.
                                        </p>
                                    </div>
                                </div>`;

                                Swal.fire({
                                    title: '⚠️ Confirm Resignation?',
                                    html: noDevHtml,
                                    width: 580,
                                    showConfirmButton: true,
                                    showCancelButton: true,
                                    showDenyButton: false,
                                    confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Confirm & Save as Resigned',
                                    cancelButtonText: 'Cancel',
                                    confirmButtonColor: '#2563eb',
                                    cancelButtonColor: '#64748b',
                                    customClass: {
                                        popup: 'offboard-swal-popup',
                                        confirmButton: 'offboard-confirm-btn',
                                    },
                                    reverseButtons: true
                                }).then(r => {
                                    if (r.isConfirmed) {
                                        editForm.dataset.confirmedResign = 'true';
                                        editForm.submit();
                                    }
                                });
                                return;
                            }

                            // 1+ devices
                            let rows = '';
                            devices.forEach((d, i) => {
                                rows += `
                                <tr class="offboard-row">
                                    <td class="offboard-td-num">${i + 1}</td>
                                    <td class="offboard-td-id">#${d.DeviceID}</td>
                                    <td class="offboard-td-model"><strong>${d.Model || '-'}</strong></td>
                                    <td class="offboard-td-cat">
                                        <span class="offboard-cat-badge">${d.CategoryName || '-'}</span>
                                    </td>
                                    <td class="offboard-td-sn">${d.SerialNumber || '-'}</td>
                                    <td class="offboard-td-status">
                                        <span class="offboard-status-badge">${d.StatusName || 'In Use'}</span>
                                    </td>
                                </tr>`;
                            });

                            const modalHtml = `
                            <div class="offboard-modal-container">
                                <div class="offboard-employee-info">
                                    <div class="offboard-avatar">
                                        <i class="fa-solid fa-user-large"></i>
                                    </div>
                                    <div class="offboard-emp-details">
                                        <div class="offboard-emp-name">${displayName}</div>
                                        <div class="offboard-emp-nick">@${displayNick} • ${displayDept}</div>
                                    </div>
                                    <div class="offboard-device-count">
                                        <span class="offboard-count-num">${count}</span>
                                        <span class="offboard-count-label">device${count > 1 ? 's' : ''}</span>
                                    </div>
                                </div>

                                <div class="offboard-warning-banner">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span>Saving as <strong>Resigned</strong> will automatically return all <strong>${count} assigned device(s)</strong> back to Available Stock.</span>
                                </div>

                                <div class="offboard-table-wrap">
                                    <table class="offboard-device-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Device ID</th>
                                                <th>Model</th>
                                                <th>Category</th>
                                                <th>Serial No</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>${rows}</tbody>
                                    </table>
                                </div>
                            </div>`;

                            Swal.fire({
                                title: '⚠️ Confirm Resignation & Offboard Devices',
                                html: modalHtml,
                                width: 780,
                                showConfirmButton: true,
                                showCancelButton: true,
                                showDenyButton: false,
                                confirmButtonText: '<i class="fa-solid fa-rotate-left me-1"></i> Confirm Resign & Return Devices',
                                cancelButtonText: 'Cancel',
                                confirmButtonColor: '#2563eb',
                                cancelButtonColor: '#64748b',
                                customClass: {
                                    popup: 'offboard-swal-popup',
                                    confirmButton: 'offboard-confirm-btn',
                                },
                                reverseButtons: true
                            }).then(confirmRes => {
                                if (confirmRes.isConfirmed) {
                                    editForm.dataset.confirmedResign = 'true';
                                    editForm.submit();
                                }
                            });
                        })
                        .catch(() => {
                            editForm.dataset.confirmedResign = 'true';
                            editForm.submit();
                        });
                });
            }
        });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('form_edit_owner', 'form_edit_owner');
