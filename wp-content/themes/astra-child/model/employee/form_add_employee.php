<?php
if (!defined('ABSPATH')) {
    exit;
}

function form_add_owner($editing = null)
{
    global $wpdb;
    $table_owner = 'Owners';

    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments ORDER BY DepartmentName ASC");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions ORDER BY PositionName ASC");
    $status_emp = $wpdb->get_results("SELECT StatusID, Status_name FROM Status_Employee");
    $active_status_id = (int) ($wpdb->get_var("SELECT StatusID FROM Status_Employee WHERE LOWER(Status_name) = 'active'") ?: 1);

    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['Nickname'])) {
        $owner_id = isset($_POST['OwnerID']) ? intval($_POST['OwnerID']) : null;
        $nickname = sanitize_text_field($_POST['Nickname'] ?? '');
        $status_id = !empty($_POST['StatusID']) ? intval($_POST['StatusID']) : $active_status_id;

        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email ?? 'system';
        $category_id = 1; // Employee

        $data = [
            'OwnerType' => 'User',
            'Nickname' => $nickname,
            'FirstName' => sanitize_text_field($_POST['FirstName'] ?? ''),
            'LastName' => sanitize_text_field($_POST['LastName'] ?? ''),
            'DepartmentID' => !empty($_POST['DepartmentID']) ? intval($_POST['DepartmentID']) : null,
            'PositionID' => !empty($_POST['PositionID']) ? intval($_POST['PositionID']) : null,
            'StatusID' => $status_id,
            'Email' => !empty($_POST['Email']) ? sanitize_email($_POST['Email']) : null,
            'user_email' => $user_email,
        ];

        if (!$owner_id && !empty($nickname)) {
            $inserted = $wpdb->insert($table_owner, $data);
            if ($inserted) {
                $description = "Added Employee: {$nickname}";

                $wpdb->insert('History_new', [
                    'DeviceID' => 0,
                    'Action' => 'Add Employee',
                    'Date' => current_time('mysql'),
                    'Description' => $description,
                    'user_email' => $user_email,
                    'CategoryID' => $category_id,
                    'Owner' => $nickname
                ]);

                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Add Employee Success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '" . esc_url(home_url('/Owner/')) . "';
                    });
                });
            </script>";
            } else {
                $db_err = esc_js($wpdb->last_error ?: 'Database insert error');
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Insert Failed',
                        text: 'Error: " . $db_err . "'
                    });
                });
            </script>";
            }
        }
    }
    ?>

    <!-- Clean Enterprise Add Employee Component -->
    <div id="add-employee-wrapper" class="employee-bento-wrapper">
        <!-- Desktop Sleek Header Bar -->
        <div class="emp-header-bar desktop-only-el">
            <div class="emp-header-left">
                <div class="emp-icon-badge">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div>
                    <p class="emp-header-subtitle">Create profile, assign department and set initial account status</p>
                </div>
            </div>
            <div class="emp-header-badge">
                <span class="emp-status-dot-static"></span>
                <span>STAFF RECORD</span>
            </div>
        </div>

        <form method="POST" action="" id="add-employee-form" class="emp-main-form">
            <?php wp_nonce_field('add_employee_nonce', '_add_emp_nonce'); ?>
            <?php if ($editing): ?>
                <input type="hidden" name="OwnerID" value="<?= esc_attr($editing->OwnerID) ?>">
            <?php endif; ?>

            <!-- Mobile Only Header -->
            <div class="emp-mobile-header mobile-only-el">
                <h2>Add Employee</h2>
            </div>

            <!-- Main 2-Column Layout -->
            <div class="emp-layout-grid">
                <!-- Left Column: Form Fields -->
                <div class="emp-form-card">
                    <div class="emp-fields-grid">

                        <!-- Row 1: Nickname & Status -->
                        <div class="emp-field-group">
                            <label for="emp_nickname">
                                <i class="fa-solid fa-signature emp-field-icon desktop-only-el"></i>
                                Nickname <span class="emp-req">*</span>
                            </label>
                            <input type="text" name="Nickname" id="emp_nickname"
                                value="<?= esc_attr($editing->Nickname ?? '') ?>" placeholder="e.g. Employee" required
                                autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_status">
                                <i class="fa-solid fa-circle-check emp-field-icon desktop-only-el"></i>
                                Status <i class="fa-solid fa-lock ms-1" style="font-size: 0.72rem; color: #94a3b8;"
                                    title="Status is locked to Active for new employees"></i>
                            </label>
                            <input type="hidden" name="StatusID" value="<?= esc_attr($active_status_id) ?>">
                            <select id="emp_status" class="emp-locked-select" disabled>
                                <option value="<?= esc_attr($active_status_id) ?>" selected>Active</option>
                            </select>
                        </div>

                        <!-- Row 2: First Name & Last Name -->
                        <div class="emp-field-group">
                            <label for="emp_firstname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                First Name
                            </label>
                            <input type="text" name="FirstName" id="emp_firstname"
                                value="<?= esc_attr($editing->FirstName ?? '') ?>" placeholder="First name"
                                autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_lastname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                Last Name
                            </label>
                            <input type="text" name="LastName" id="emp_lastname"
                                value="<?= esc_attr($editing->LastName ?? '') ?>" placeholder="Last name"
                                autocomplete="off">
                        </div>

                        <!-- Row 3: Email Address (Full Width Span 2) -->
                        <div class="emp-field-group emp-span-2">
                            <label for="emp_email">
                                <i class="fa-solid fa-envelope emp-field-icon desktop-only-el"></i>
                                Email Address
                            </label>
                            <input type="email" name="Email" id="emp_email" value="<?= esc_attr($editing->Email ?? '') ?>"
                                placeholder="name@company.com" autocomplete="off">
                        </div>

                        <!-- Row 4: Department & Position -->
                        <div class="emp-field-group">
                            <label for="emp_dept">
                                <i class="fa-solid fa-building emp-field-icon desktop-only-el"></i>
                                Department
                            </label>
                            <select name="DepartmentID" id="emp_dept">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dep): ?>
                                    <option value="<?= $dep->DepartmentID ?>" <?= selected($editing->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                                        <?= esc_html($dep->DepartmentName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_pos">
                                <i class="fa-solid fa-briefcase emp-field-icon desktop-only-el"></i>
                                Position
                            </label>
                            <select name="PositionID" id="emp_pos">
                                <option value="">-- Select Position --</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?= $position->PositionID ?>" <?= selected($editing->PositionID ?? '', $position->PositionID, false) ?>>
                                        <?= esc_html($position->PositionName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <!-- Form Actions (Cancel & Submit) -->
                    <div class="emp-form-actions">
                        <button type="button" onclick="window.location.href='<?= esc_url(home_url('/Owner/')) ?>';"
                            class="emp-btn-cancel">
                            <i class="fa-solid fa-arrow-left me-1"></i> Cancel
                        </button>
                        <button type="submit" class="emp-btn-submit">
                            <i class="fa-solid fa-check me-1"></i>
                            <span>Save Employee</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Clean Staff ID Card Preview (Desktop Only) -->
                <div class="emp-preview-column desktop-only-el">
                    <div class="emp-badge-card" id="interactive-preview-card">
                        <!-- Top Bar -->
                        <div class="emp-badge-top">
                            <span class="emp-badge-tag"><i class="fa-solid fa-id-badge me-1"></i> STAFF ID CARD</span>
                            <span class="emp-badge-status" id="preview-emp-status-badge">
                                <span class="emp-status-dot" id="preview-emp-status-dot"></span>
                                <span id="preview-emp-status">Active</span>
                            </span>
                        </div>

                        <!-- Avatar Visualizer -->
                        <div class="emp-avatar-box">
                            <div class="emp-avatar-circle" id="preview-emp-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="emp-id-pill" id="preview-emp-nickname">
                                <?= esc_html(($editing->Nickname ?? '') ?: 'Employee') ?>
                            </div>
                        </div>

                        <!-- Details Box -->
                        <div class="emp-details-box">
                            <div class="emp-preview-name" id="preview-emp-fullname">
                                <?= esc_html(trim(($editing->FirstName ?? '') . ' ' . ($editing->LastName ?? '')) ?: 'Staff Member') ?>
                            </div>
                            <div class="emp-preview-email" id="preview-emp-email">
                                <?= esc_html(($editing->Email ?? '') ?: 'name@company.com') ?>
                            </div>

                            <div class="emp-meta-grid">
                                <div class="emp-meta-item">
                                    <span class="emp-meta-label"><i class="fa-solid fa-building me-1"></i> DEPARTMENT</span>
                                    <span class="emp-meta-val" id="preview-emp-dept">
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
                                    <span class="emp-meta-val" id="preview-emp-pos">
                                        <?php
                                        $pName = '—';
                                        foreach ($positions as $p) {
                                            if ($p->PositionID == ($editing->PositionID ?? 0)) {
                                                $pName = $p->PositionName;
                                                break;
                                            }
                                        }
                                        echo esc_html($pName);
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Scoped Style Block: Clean Enterprise Theme -->
    <style>
        .page-add-owner .fl-builder-content,
        .page-add-owner .fl-row,
        .page-add-owner .fl-row-content,
        .page-add-owner .fl-col-group,
        .page-add-owner .fl-col,
        .page-add-owner .fl-col-content,
        .page-add-owner .ast-container,
        .page-add-owner #primary,
        .page-add-owner .entry-content {
            max-width: 100% !important;
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        #add-employee-wrapper {
            position: relative;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem 1rem 2.5rem 1rem;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "SF Pro", "Sukhumvit Set", "Thonburi", "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            box-sizing: border-box;
            color: #0f172a;
        }

        /* --- DESKTOP STYLES (Screen > 768px) --- */
        @media (min-width: 769px) {
            #add-employee-wrapper .mobile-only-el {
                display: none !important;
            }

            #add-employee-wrapper .desktop-only-el {
                display: flex !important;
            }

            #add-employee-wrapper .emp-header-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.25rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }

            #add-employee-wrapper .emp-header-left {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            #add-employee-wrapper .emp-icon-badge {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                background: #eff6ff;
                border: 1px solid #dbeafe;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #2563eb;
                font-size: 1.15rem;
                flex-shrink: 0;
            }

            #add-employee-wrapper .emp-header-title {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.3;
            }

            #add-employee-wrapper .emp-header-subtitle {
                margin: 2px 0 0 0;
                font-size: 0.82rem;
                color: #64748b;
                line-height: 1.4;
            }

            #add-employee-wrapper .emp-header-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 12px;
                background: #f8fafc;
                color: #475569;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            #add-employee-wrapper .emp-status-dot-static {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background-color: #2563eb;
            }

            #add-employee-wrapper .emp-main-form {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #add-employee-wrapper .emp-layout-grid {
                display: grid;
                grid-template-columns: 1.4fr 0.9fr;
                gap: 1.25rem;
                align-items: stretch;
            }

            #add-employee-wrapper .emp-form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1.5rem 1.75rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            #add-employee-wrapper .emp-fields-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1rem 1.25rem !important;
            }

            #add-employee-wrapper .emp-field-group {
                display: flex !important;
                flex-direction: column !important;
                margin-bottom: 0 !important;
            }

            #add-employee-wrapper .emp-span-2 {
                grid-column: span 2 !important;
            }

            #add-employee-wrapper .emp-field-group label {
                font-size: 0.82rem !important;
                font-weight: 600 !important;
                color: #334155 !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 5px !important;
            }

            #add-employee-wrapper .emp-field-icon {
                color: #64748b;
                font-size: 0.8rem;
            }

            #add-employee-wrapper .emp-req {
                color: #dc2626;
            }

            #add-employee-wrapper .emp-field-group input,
            #add-employee-wrapper .emp-field-group select {
                width: 100% !important;
                height: 42px !important;
                padding: 0 14px !important;
                font-size: 0.9rem !important;
                font-weight: 500 !important;
                color: #0f172a !important;
                background-color: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 8px !important;
                transition: border-color 0.12s ease, box-shadow 0.12s ease !important;
                box-sizing: border-box !important;
                appearance: none;
            }

            #add-employee-wrapper .emp-field-group select {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E") !important;
                background-position: right 12px center !important;
                background-repeat: no-repeat !important;
                background-size: 14px 14px !important;
                padding-right: 32px !important;
                cursor: pointer;
            }

            #add-employee-wrapper .emp-field-group select.emp-locked-select {
                background-color: #f1f5f9 !important;
                background-image: none !important;
                color: #059669 !important;
                font-weight: 600 !important;
                border-color: #cbd5e1 !important;
                cursor: not-allowed !important;
                opacity: 1 !important;
                -webkit-text-fill-color: #059669 !important;
            }

            #add-employee-wrapper .emp-field-group input:hover,
            #add-employee-wrapper .emp-field-group select:hover {
                border-color: #94a3b8 !important;
            }

            #add-employee-wrapper .emp-field-group input:focus,
            #add-employee-wrapper .emp-field-group select:focus {
                background-color: #ffffff !important;
                border-color: #2563eb !important;
                outline: none !important;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
            }

            #add-employee-wrapper .emp-form-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 10px !important;
                margin-top: 1.5rem !important;
                padding-top: 1.25rem !important;
                border-top: 1px solid #f1f5f9 !important;
            }

            #add-employee-wrapper .emp-btn-cancel {
                padding: 0.55rem 1.25rem !important;
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                color: #475569 !important;
                border-radius: 8px !important;
                transition: all 0.12s ease !important;
                cursor: pointer !important;
            }

            #add-employee-wrapper .emp-btn-cancel:hover {
                background: #f8fafc !important;
                color: #0f172a !important;
                border-color: #94a3b8 !important;
            }

            #add-employee-wrapper .emp-btn-submit {
                padding: 0.55rem 1.4rem !important;
                font-size: 0.875rem !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                border: 1px solid #1d4ed8 !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2) !important;
                transition: all 0.12s ease !important;
                cursor: pointer !important;
            }

            #add-employee-wrapper .emp-btn-submit:hover {
                background: #1d4ed8 !important;
                color: #ffffff !important;
                box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3) !important;
            }

            /* Preview Badge Column */
            #add-employee-wrapper .emp-preview-column {
                display: flex !important;
                flex-direction: column !important;
            }

            #add-employee-wrapper .emp-badge-card {
                height: 100%;
                background: #f8fafc;
                border-radius: 12px;
                padding: 1.25rem;
                color: #0f172a;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                border: 1px solid #e2e8f0;
                box-sizing: border-box;
            }

            #add-employee-wrapper .emp-badge-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            #add-employee-wrapper .emp-badge-tag {
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #475569;
                background: #ffffff;
                padding: 3px 8px;
                border-radius: 4px;
                border: 1px solid #e2e8f0;
            }

            #add-employee-wrapper .emp-badge-status {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 0.72rem;
                font-weight: 600;
                padding: 3px 10px;
                border-radius: 999px;
                color: #059669;
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
            }

            #add-employee-wrapper .emp-status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #10b981;
            }

            #add-employee-wrapper .emp-avatar-box {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1rem 0;
            }

            #add-employee-wrapper .emp-avatar-circle {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: #eff6ff;
                border: 2px solid #bfdbfe;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #2563eb;
                font-size: 1.6rem;
                margin-bottom: 0.5rem;
            }

            #add-employee-wrapper .emp-id-pill {
                font-size: 1rem;
                font-weight: 700;
                color: #0f172a;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                padding: 3px 14px;
                border-radius: 6px;
            }

            #add-employee-wrapper .emp-details-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 0.85rem 1rem;
            }

            #add-employee-wrapper .emp-preview-name {
                font-size: 1rem;
                font-weight: 700;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #add-employee-wrapper .emp-preview-email {
                font-size: 0.8rem;
                color: #64748b;
                font-weight: 500;
                margin-bottom: 0.6rem;
                word-break: break-all;
            }

            #add-employee-wrapper .emp-meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                border-top: 1px solid #f1f5f9;
                padding-top: 8px;
            }

            #add-employee-wrapper .emp-meta-item {
                display: flex;
                flex-direction: column;
            }

            #add-employee-wrapper .emp-meta-label {
                font-size: 0.65rem;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                margin-bottom: 1px;
                font-weight: 600;
            }

            #add-employee-wrapper .emp-meta-val {
                font-size: 0.82rem;
                font-weight: 600;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* --- MOBILE STYLES (Screen <= 768px) --- */
        @media (max-width: 768px) {
            #add-employee-wrapper {
                padding: 0.5rem 0.5rem 2rem 0.5rem !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            #add-employee-wrapper .desktop-only-el {
                display: none !important;
            }

            #add-employee-wrapper .mobile-only-el {
                display: block !important;
            }

            #add-employee-wrapper .emp-mobile-header {
                margin-bottom: 1rem !important;
                text-align: left !important;
            }

            #add-employee-wrapper .emp-mobile-header h2 {
                font-size: 1.35rem !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                margin: 0 !important;
            }

            #add-employee-wrapper .emp-layout-grid {
                display: block !important;
                width: 100% !important;
            }

            #add-employee-wrapper .emp-form-card {
                background: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
                border-radius: 12px !important;
                padding: 1.25rem 1rem !important;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
            }

            #add-employee-wrapper .emp-fields-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
                width: 100% !important;
            }

            #add-employee-wrapper .emp-field-group {
                display: flex !important;
                flex-direction: column !important;
                margin-bottom: 0 !important;
            }

            #add-employee-wrapper .emp-field-group label {
                font-size: 0.8rem !important;
                font-weight: 600 !important;
                color: #334155 !important;
                margin-bottom: 4px !important;
            }

            #add-employee-wrapper .emp-field-group input,
            #add-employee-wrapper .emp-field-group select {
                width: 100% !important;
                box-sizing: border-box !important;
                height: 44px !important;
                border-radius: 8px !important;
                background-color: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                padding: 0 12px !important;
                font-size: 0.9rem !important;
                font-weight: 500 !important;
                color: #0f172a !important;
            }

            #add-employee-wrapper .emp-form-actions {
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                margin-top: 16px !important;
                padding-top: 14px !important;
                border-top: 1px solid #f1f5f9 !important;
            }

            #add-employee-wrapper .emp-btn-cancel,
            #add-employee-wrapper .emp-btn-submit {
                flex: 1 1 0 !important;
                height: 44px !important;
                font-size: 0.9rem !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            #add-employee-wrapper .emp-btn-cancel {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                color: #475569 !important;
            }

            #add-employee-wrapper .emp-btn-submit {
                background: #2563eb !important;
                border: 1px solid #1d4ed8 !important;
                color: #ffffff !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nicknameInput = document.getElementById('emp_nickname');
            const firstnameInput = document.getElementById('emp_firstname');
            const lastnameInput = document.getElementById('emp_lastname');
            const emailInput = document.getElementById('emp_email');
            const deptSelect = document.getElementById('emp_dept');
            const posSelect = document.getElementById('emp_pos');
            const statusSelect = document.getElementById('emp_status');

            const pNickname = document.getElementById('preview-emp-nickname');
            const pFullname = document.getElementById('preview-emp-fullname');
            const pEmail = document.getElementById('preview-emp-email');
            const pDept = document.getElementById('preview-emp-dept');
            const pPos = document.getElementById('preview-emp-pos');
            const pStatus = document.getElementById('preview-emp-status');
            const pStatusBadge = document.getElementById('preview-emp-status-badge');
            const pStatusDot = document.getElementById('preview-emp-status-dot');

            const statusStyles = {
                'active': { color: '#059669', bg: '#ecfdf5', border: '#a7f3d0', dot: '#10b981' },
                'resigned': { color: '#d97706', bg: '#fffbeb', border: '#fde68a', dot: '#f59e0b' }
            };

            function syncEmployeePreview() {
                if (pNickname && nicknameInput) {
                    pNickname.textContent = nicknameInput.value.trim() || 'Employee';
                }
                if (pFullname) {
                    const fn = (firstnameInput ? firstnameInput.value.trim() : '');
                    const ln = (lastnameInput ? lastnameInput.value.trim() : '');
                    const full = [fn, ln].filter(Boolean).join(' ');
                    pFullname.textContent = full || 'Staff Member';
                }
                if (pEmail && emailInput) {
                    pEmail.textContent = emailInput.value.trim() || 'name@company.com';
                }
                if (pDept && deptSelect) {
                    pDept.textContent = (deptSelect.selectedIndex > 0) ? deptSelect.options[deptSelect.selectedIndex].text : '—';
                }
                if (pPos && posSelect) {
                    pPos.textContent = (posSelect.selectedIndex > 0) ? posSelect.options[posSelect.selectedIndex].text : '—';
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

            if (nicknameInput) nicknameInput.addEventListener('input', syncEmployeePreview);
            if (firstnameInput) firstnameInput.addEventListener('input', syncEmployeePreview);
            if (lastnameInput) lastnameInput.addEventListener('input', syncEmployeePreview);
            if (emailInput) emailInput.addEventListener('input', syncEmployeePreview);
            if (deptSelect) deptSelect.addEventListener('change', syncEmployeePreview);
            if (posSelect) posSelect.addEventListener('change', syncEmployeePreview);
            if (statusSelect) statusSelect.addEventListener('change', syncEmployeePreview);

            syncEmployeePreview();
        });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('form_add_owner', 'form_add_owner');