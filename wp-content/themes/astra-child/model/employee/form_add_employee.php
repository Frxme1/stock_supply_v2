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
            'OwnerType'    => 'User',
            'Nickname'     => $nickname,
            'FirstName'    => sanitize_text_field($_POST['FirstName'] ?? ''),
            'LastName'     => sanitize_text_field($_POST['LastName'] ?? ''),
            'DepartmentID' => !empty($_POST['DepartmentID']) ? intval($_POST['DepartmentID']) : null,
            'PositionID'   => !empty($_POST['PositionID']) ? intval($_POST['PositionID']) : null,
            'StatusID'     => $status_id,
            'Email'        => !empty($_POST['Email']) ? sanitize_email($_POST['Email']) : null,
            'user_email'   => $user_email,
        ];

        if (!$owner_id && !empty($nickname)) {
            $inserted = $wpdb->insert($table_owner, $data);
            if ($inserted) {
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

    <!-- Fully Isolated Add Employee Component -->
    <div id="add-employee-wrapper" class="employee-bento-wrapper">
        <!-- Desktop Ambient Glows -->
        <div class="emp-glow-orb emp-glow-1 desktop-only-el"></div>
        <div class="emp-glow-orb emp-glow-2 desktop-only-el"></div>

        <!-- Desktop Sleek Header Bar -->
        <div class="emp-header-bar desktop-only-el">
            <div class="emp-header-left">
                <div class="emp-icon-badge">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <br>
                    <p class="emp-header-subtitle">
                        Update Staff Profile Information, Role & Department Assignments
                    </p>
                </div>
            </div>
            <div class="emp-header-badge">
                <span class="emp-pulse-dot"></span>
                <span>EMPLOYEE PROFILE UPDATE</span>
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

            <!-- Main 2-Column Bento Layout -->
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
                                value="<?= esc_attr($editing->Nickname ?? '') ?>" placeholder="e.g. Yoru" required
                                autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_status">
                                <i class="fa-solid fa-circle-check emp-field-icon desktop-only-el"></i>
                                Status
                            </label>
                            <select name="StatusID" id="emp_status" required>
                                <?php foreach ($status_emp as $status): ?>
                                    <option value="<?= $status->StatusID ?>" <?= selected($editing->StatusID ?? $active_status_id, $status->StatusID, false) ?>>
                                        <?= esc_html($status->Status_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 2: First Name & Last Name -->
                        <div class="emp-field-group">
                            <label for="emp_firstname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                First Name
                            </label>
                            <input type="text" name="FirstName" id="emp_firstname"
                                value="<?= esc_attr($editing->FirstName ?? '') ?>" autocomplete="off">
                        </div>

                        <div class="emp-field-group">
                            <label for="emp_lastname">
                                <i class="fa-solid fa-user emp-field-icon desktop-only-el"></i>
                                Last Name
                            </label>
                            <input type="text" name="LastName" id="emp_lastname"
                                value="<?= esc_attr($editing->LastName ?? '') ?>" autocomplete="off">
                        </div>

                        <!-- Row 3: Email Address (Full Width Span 2) -->
                        <div class="emp-field-group emp-span-2">
                            <label for="emp_email">
                                <i class="fa-solid fa-envelope emp-field-icon desktop-only-el"></i>
                                Email Address
                            </label>
                            <input type="email" name="Email" id="emp_email"
                                value="<?= esc_attr($editing->Email ?? '') ?>"
                                placeholder="e.g. robertnotlikeus@gmail.com" autocomplete="off">
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
                            <span class="emp-shine desktop-only-el"></span>
                            <i class="fa-solid fa-check me-1"></i>
                            <span>Submit</span>
                        </button>
                    </div>
                </div>

                <!-- Right Column: Live Interactive Staff ID Badge Card (Desktop Only) -->
                <div class="emp-preview-column desktop-only-el">
                    <div class="emp-badge-card" id="interactive-preview-card">
                        <div class="emp-badge-glow"></div>

                        <!-- Top Bar -->
                        <div class="emp-badge-top">
                            <span class="emp-badge-tag"><i class="fa-solid fa-id-badge me-1"></i> STAFF ID BADGE</span>
                            <span class="emp-badge-status" id="preview-emp-status-badge">
                                <span class="emp-status-dot" id="preview-emp-status-dot"></span>
                                <span id="preview-emp-status">Active</span>
                            </span>
                        </div>

                        <!-- Avatar Visualizer -->
                        <div class="emp-avatar-box">
                            <div class="emp-avatar-halo"></div>
                            <div class="emp-avatar-circle" id="preview-emp-avatar">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="emp-id-pill" id="preview-emp-nickname">
                                <?= esc_html($editing->Nickname ?: 'Yoru') ?>
                            </div>
                        </div>

                        <!-- Details Box -->
                        <div class="emp-details-box">
                            <div class="emp-preview-name" id="preview-emp-fullname">
                                <?= esc_html(trim(($editing->FirstName ?? '') . ' ' . ($editing->LastName ?? '')) ?: 'Staff Member') ?>
                            </div>
                            <div class="emp-preview-email" id="preview-emp-email">
                                <?= esc_html($editing->Email ?: 'email@company.com') ?>
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

    <!-- Scoped Style Block: Zero Conflict With Other Forms -->
    <style>
        /* Unbind Beaver Builder & Astra container restrictions on Add Owner page */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.5rem 1rem 2rem 1rem;
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            box-sizing: border-box;
        }

        /* --- DESKTOP STYLES (Screen > 768px) --- */
        @media (min-width: 769px) {
            #add-employee-wrapper .mobile-only-el {
                display: none !important;
            }

            #add-employee-wrapper .desktop-only-el {
                display: flex !important;
            }

            #add-employee-wrapper .emp-glow-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                pointer-events: none;
                z-index: 0;
                opacity: 0.35;
            }

            #add-employee-wrapper .emp-glow-1 {
                top: -20px;
                left: 15%;
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.22) 0%, transparent 70%);
            }

            #add-employee-wrapper .emp-glow-2 {
                bottom: 20px;
                right: 10%;
                width: 320px;
                height: 320px;
                background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, transparent 70%);
            }

            #add-employee-wrapper .emp-header-bar {
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
                animation: empSlideDown 0.4s ease-out forwards;
            }

            #add-employee-wrapper .emp-header-left {
                display: flex;
                align-items: center;
                gap: 14px;
            }

            #add-employee-wrapper .emp-icon-badge {
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

            #add-employee-wrapper .emp-header-subtitle {
                margin: 3px 0 0 0;
                font-size: 0.85rem;
                color: #64748b;
            }

            #add-employee-wrapper .emp-header-badge {
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

            #add-employee-wrapper .emp-pulse-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background-color: #10b981;
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
                animation: empDotPulse 2s infinite;
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
                grid-template-columns: 1.35fr 0.95fr;
                gap: 1.75rem;
                position: relative;
                z-index: 1;
                align-items: stretch;
            }

            #add-employee-wrapper .emp-form-card {
                background: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-radius: 20px;
                padding: 1.75rem 2rem;
                box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
                animation: empCardFadeIn 0.4s ease-out forwards;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            #add-employee-wrapper .emp-fields-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1.1rem 1.25rem !important;
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
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                color: #1e293b !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 6px !important;
                letter-spacing: -0.01em !important;
            }

            #add-employee-wrapper .emp-field-icon {
                color: #4f46e5;
                font-size: 0.85rem;
            }

            #add-employee-wrapper .emp-req {
                color: #ef4444;
                font-weight: bold;
            }

            #add-employee-wrapper .emp-field-group input,
            #add-employee-wrapper .emp-field-group select {
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

            #add-employee-wrapper .emp-field-group select {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
                background-position: right 0.85rem center !important;
                background-repeat: no-repeat !important;
                background-size: 1.1em 1.1em !important;
                padding-right: 2.4rem !important;
                cursor: pointer;
            }

            #add-employee-wrapper .emp-field-group input:hover,
            #add-employee-wrapper .emp-field-group select:hover {
                border-color: #94a3b8 !important;
            }

            #add-employee-wrapper .emp-field-group input:focus,
            #add-employee-wrapper .emp-field-group select:focus {
                background-color: #ffffff !important;
                border-color: #4f46e5 !important;
                outline: none !important;
                box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.16) !important;
            }

            #add-employee-wrapper .emp-field-group input[readonly],
            #add-employee-wrapper .emp-field-group input:disabled,
            #add-employee-wrapper .emp-field-group select:disabled,
            #add-employee-wrapper .input-locked {
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

            #add-employee-wrapper .emp-form-actions {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 1.5rem !important;
                margin-top: 1.75rem !important;
                padding-top: 1.5rem !important;
                border-top: 1.5px dashed #f1f5f9 !important;
            }

            #add-employee-wrapper .emp-btn-cancel {
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

            #add-employee-wrapper .emp-btn-cancel:hover {
                background: #b91c1c !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 18px rgba(220, 38, 38, 0.45) !important;
                color: #ffffff !important;
            }

            #add-employee-wrapper .emp-btn-submit {
                position: relative;
                overflow: hidden;
                padding: 0.65rem 2.4rem !important;
                font-size: 0.95rem !important;
                font-weight: 700 !important;
                background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
                border: none !important;
                color: #ffffff !important;
                border-radius: 9999px !important;
                box-shadow: 0 6px 20px -4px rgba(79, 70, 229, 0.4) !important;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
                cursor: pointer !important;
            }

            #add-employee-wrapper .emp-btn-submit:hover {
                transform: translateY(-2px) !important;
                color: #ffffff !important;
                box-shadow: 0 8px 25px -4px rgba(79, 70, 229, 0.5) !important;
            }

            #add-employee-wrapper .emp-shine {
                position: absolute;
                top: -50%;
                left: -60%;
                width: 40%;
                height: 200%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                transform: rotate(25deg);
                animation: empShineSweep 4s infinite cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Preview Badge Column */
            #add-employee-wrapper .emp-preview-column {
                display: flex !important;
                flex-direction: column !important;
                animation: empCardFadeIn 0.5s ease-out forwards;
            }

            #add-employee-wrapper .emp-badge-card {
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

            #add-employee-wrapper .emp-badge-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 1;
            }

            #add-employee-wrapper .emp-badge-tag {
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

            #add-employee-wrapper .emp-badge-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                font-weight: 700;
                padding: 4px 12px;
                border-radius: 999px;
                color: #34d399;
                background: rgba(16, 185, 129, 0.15);
                border: 1px solid rgba(16, 185, 129, 0.35);
            }

            #add-employee-wrapper .emp-status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #34d399;
                box-shadow: 0 0 8px #34d399;
            }

            #add-employee-wrapper .emp-avatar-box {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.25rem 0;
                z-index: 1;
            }

            #add-employee-wrapper .emp-avatar-halo {
                position: absolute;
                width: 110px;
                height: 110px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.4) 0%, transparent 70%);
                filter: blur(14px);
                z-index: 0;
            }

            #add-employee-wrapper .emp-avatar-circle {
                width: 72px;
                height: 72px;
                border-radius: 50%;
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                box-shadow: 0 0 30px rgba(99, 102, 241, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 2rem;
                margin-bottom: 0.5rem;
                z-index: 1;
            }

            #add-employee-wrapper .emp-id-pill {
                font-size: 1.1rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                color: #e0e7ff;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.15);
                padding: 4px 18px;
                border-radius: 10px;
                backdrop-filter: blur(8px);
                z-index: 1;
            }

            #add-employee-wrapper .emp-details-box {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 14px;
                padding: 1rem 1.25rem;
                backdrop-filter: blur(12px);
                z-index: 1;
            }

            #add-employee-wrapper .emp-preview-name {
                font-size: 1.15rem;
                font-weight: 800;
                color: #ffffff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #add-employee-wrapper .emp-preview-email {
                font-size: 0.82rem;
                color: #818cf8;
                font-weight: 600;
                margin-bottom: 0.75rem;
                word-break: break-all;
            }

            #add-employee-wrapper .emp-meta-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding-top: 8px;
            }

            #add-employee-wrapper .emp-meta-item {
                display: flex;
                flex-direction: column;
            }

            #add-employee-wrapper .emp-meta-label {
                font-size: 0.68rem;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                margin-bottom: 2px;
            }

            #add-employee-wrapper .emp-meta-val {
                font-size: 0.85rem;
                font-weight: 700;
                color: #f1f5f9;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* --- MOBILE STYLES (Screen <= 768px) --- */
        @media (max-width: 768px) {
            #add-employee-wrapper {
                padding: 0 4px 2rem 4px !important;
                width: 100% !important;
                max-width: 100% !important;
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
                text-align: center !important;
            }

            #add-employee-wrapper .emp-mobile-header h2 {
                font-size: 1.5rem !important;
                font-weight: 800 !important;
                color: #0f172a !important;
                margin: 0 !important;
            }

            #add-employee-wrapper .emp-layout-grid {
                display: block !important;
                width: 100% !important;
            }

            #add-employee-wrapper .emp-form-card {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            #add-employee-wrapper .emp-fields-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 14px !important;
                width: 100% !important;
            }

            #add-employee-wrapper .emp-field-group {
                background: #ffffff !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 20px !important;
                padding: 16px 18px !important;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04) !important;
                margin-bottom: 0 !important;
            }

            #add-employee-wrapper .emp-field-group label {
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

            #add-employee-wrapper .emp-field-group input,
            #add-employee-wrapper .emp-field-group select {
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

            #add-employee-wrapper .emp-form-actions {
                display: flex !important;
                flex-direction: row !important;
                gap: 12px !important;
                margin-top: 24px !important;
                padding-top: 18px !important;
                border-top: 1px solid #f1f5f9 !important;
            }

            #add-employee-wrapper .emp-btn-cancel,
            #add-employee-wrapper .emp-btn-submit {
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

            #add-employee-wrapper .emp-btn-cancel {
                background: #dc2626 !important;
            }

            #add-employee-wrapper .emp-btn-submit {
                background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            }
        }

        /* Animations */
        @keyframes empSlideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes empCardFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes empDotPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        @keyframes empShineSweep {
            0% { left: -60%; }
            20%, 100% { left: 140%; }
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
                'active': { color: '#34d399', bg: 'rgba(16, 185, 129, 0.15)', border: 'rgba(16, 185, 129, 0.35)', dot: '#34d399' },
                'resigned': { color: '#f87171', bg: 'rgba(239, 68, 68, 0.15)', border: 'rgba(239, 68, 68, 0.35)', dot: '#f87171' }
            };

            function syncEmployeePreview() {
                if (pNickname && nicknameInput) {
                    pNickname.textContent = nicknameInput.value.trim() || 'Yoru';
                }
                if (pFullname) {
                    const fn = (firstnameInput ? firstnameInput.value.trim() : '');
                    const ln = (lastnameInput ? lastnameInput.value.trim() : '');
                    const full = [fn, ln].filter(Boolean).join(' ');
                    pFullname.textContent = full || 'Staff Member';
                }
                if (pEmail && emailInput) {
                    pEmail.textContent = emailInput.value.trim() || 'email@company.com';
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
                        pStatusDot.style.boxShadow = `0 0 8px ${st.dot}`;
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