<?php
/**
 * View Employee Details — Standalone Component
 * 
 * Provides an enterprise-grade profile view of an employee including:
 * - Profile header & staff metadata
 * - Real-time assigned hardware stats (Total, Laptops, Monitors, Accessories)
 * - Interactive assigned equipment grid with direct device detail links
 * - Employee historical device activity timeline
 */

if (!defined('ABSPATH')) {
    exit;
}

function view_employee_details($owner_id = null)
{
    global $wpdb;

    $owner_id = $owner_id ? intval($owner_id) : intval($_GET['view'] ?? 0);
    if ($owner_id <= 0) {
        return '<div class="ved-error-card"><i class="fa-solid fa-triangle-exclamation"></i> Invalid Employee ID.</div>';
    }

    $table_owner = 'Owners';
    $table_dept = 'Departments';
    $table_pos = 'Positions';
    $table_status = 'Status_Employee';
    $table_history = 'History_new';

    // 1. Query Owner Details
    $owner = $wpdb->get_row($wpdb->prepare("
        SELECT o.*, 
               d.DepartmentName, 
               p.PositionName, 
               se.Status_name AS StatusName
        FROM {$table_owner} o
        LEFT JOIN {$table_dept} d ON o.DepartmentID = d.DepartmentID
        LEFT JOIN {$table_pos} p ON o.PositionID = p.PositionID
        LEFT JOIN {$table_status} se ON o.StatusID = se.StatusID
        WHERE o.OwnerID = %d
    ", $owner_id));

    if (!$owner) {
        return '<div class="ved-error-card"><i class="fa-solid fa-user-slash"></i> Employee record not found.</div>';
    }

    $nickname = trim($owner->Nickname ?? '');
    $firstname = trim($owner->FirstName ?? '');
    $lastname = trim($owner->LastName ?? '');
    $fullname = trim($firstname . ' ' . $lastname);
    $display_name = stock_supply_format_nickname_with_initial($nickname, $firstname, $lastname);
    $status_name = $owner->StatusName ?: 'Active';
    $is_active = (strcasecmp($status_name, 'Active') === 0);

    // 2. Query Currently Assigned Devices
    $assigned_devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, 
               c.CategoryName, 
               b.BrandName, 
               d.Model, 
               d.SerialNumber, 
               s.StatusName, 
               kw.KeywordName, 
               d.ReceiveDate,
               d.CreatedAt
        FROM Devices d
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Statuses s ON d.StatusID = s.StatusID
        LEFT JOIN Keywords kw ON d.KeywordID = kw.KeywordID
        WHERE d.OwnerID = %d
        ORDER BY d.CategoryID ASC, d.DeviceID ASC
    ", $owner_id));

    // Stats breakdown
    $total_dev = count($assigned_devices);
    $laptop_count = 0;
    $monitor_count = 0;
    $acc_count = 0;

    foreach ($assigned_devices as $dev) {
        $c = strtolower($dev->CategoryName ?? '');
        if (strpos($c, 'laptop') !== false) {
            $laptop_count++;
        } elseif (strpos($c, 'monitor') !== false) {
            $monitor_count++;
        } else {
            $acc_count++;
        }
    }

    // 3. Query History Timeline for this employee
    $history_names = array_unique(array_filter([$nickname, $fullname, $display_name]));
    $history_rows = [];
    if (!empty($history_names)) {
        $placeholders = implode(',', array_fill(0, count($history_names), '%s'));
        $history_sql = "
            SELECT h.*, c.CategoryName 
            FROM {$table_history} h
            LEFT JOIN Categories c ON h.CategoryID = c.CategoryID
            WHERE h.Owner IN ($placeholders)
            ORDER BY h.Date DESC, h.HistoryID DESC
            LIMIT 30
        ";
        $history_rows = $wpdb->get_results($wpdb->prepare($history_sql, ...$history_names));
    }

    $initial_char = strtoupper(mb_substr($nickname ?: ($firstname ?: 'E'), 0, 1));

    ob_start();
?>
    <!-- FontAwesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <div id="view-employee-wrapper" class="ved-wrapper">

        <!-- Top Action & Navigation Bar -->
        <div class="ved-top-bar">
            <div class="ved-breadcrumb-wrap">
                <a href="<?= esc_url(home_url('/Owner/')) ?>" class="ved-back-btn">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Employees</span>
                </a>
                <span class="ved-bread-sep">/</span>
                <span class="ved-bread-current"><?= esc_html($display_name) ?></span>
            </div>
            <div class="ved-top-actions">
                <a href="<?= esc_url(home_url('/Owner/?edit=' . $owner->OwnerID)) ?>" class="ved-edit-btn">
                    <i class="fa-solid fa-user-pen"></i>
                    <span>Edit Profile</span>
                </a>
            </div>
        </div>

        <!-- Hero Profile Bento Card -->
        <div class="ved-hero-card">
            <div class="ved-hero-main">
                <div class="ved-avatar-wrap">
                    <div class="ved-avatar-circle">
                        <span><?= esc_html($initial_char) ?></span>
                    </div>
                </div>
                <div class="ved-hero-info">
                    <div class="ved-hero-title-row">
                        <h1 class="ved-hero-name"><?= esc_html($display_name) ?></h1>
                        <span class="ved-status-pill <?= $is_active ? 'active' : 'resigned' ?>">
                            <span class="ved-status-dot"></span>
                            <?= esc_html($status_name) ?>
                        </span>
                    </div>
                    <?php if ($fullname && $fullname !== $nickname): ?>
                        <div class="ved-hero-fullname">
                            <i class="fa-solid fa-id-card me-1"></i>
                            <?= esc_html($fullname) ?>
                        </div>
                    <?php endif; ?>

                    <div class="ved-hero-chips">
                        <div class="ved-chip">
                            <i class="fa-solid fa-building"></i>
                            <span><?= esc_html($owner->DepartmentName ?: 'No Department') ?></span>
                        </div>
                        <?php 
                        $isIntern = !empty($owner->PositionName) && stripos($owner->PositionName, 'intern') !== false;
                        ?>
                        <div class="ved-chip <?= $isIntern ? 'ved-chip-intern' : 'ved-chip-fulltime' ?>">
                            <i class="fa-solid <?= $isIntern ? 'fa-user-graduate' : 'fa-briefcase' ?>"></i>
                            <span><?= esc_html($owner->PositionName ?: 'No Position') ?></span>
                        </div>
                        <?php if (!empty($owner->Email)): ?>
                            <a href="mailto:<?= esc_attr(strtolower($owner->Email)) ?>" class="ved-chip ved-chip-link" title="Send email" style="text-transform: lowercase !important;">
                                <i class="fa-solid fa-envelope"></i>
                                <span><?= esc_html(strtolower($owner->Email)) ?></span>
                            </a>
                        <?php endif; ?>
                        <div class="ved-chip ved-chip-mono">
                            <i class="fa-solid fa-hashtag"></i>
                            <span>ID: <?= esc_html($owner->OwnerID) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hardware KPI Counters Strip -->
        <div class="ved-kpi-grid">
            <div class="ved-kpi-card">
                <div class="ved-kpi-icon-wrap" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div class="ved-kpi-content">
                    <span class="ved-kpi-val"><?= $total_dev ?></span>
                    <span class="ved-kpi-label">Total Assigned Devices</span>
                </div>
            </div>

            <div class="ved-kpi-card">
                <div class="ved-kpi-icon-wrap" style="background: rgba(21, 165, 218, 0.1); color: #15A5DA;">
                    <i class="fa-solid fa-laptop"></i>
                </div>
                <div class="ved-kpi-content">
                    <span class="ved-kpi-val"><?= $laptop_count ?></span>
                    <span class="ved-kpi-label">Laptops</span>
                </div>
            </div>

            <div class="ved-kpi-card">
                <div class="ved-kpi-icon-wrap" style="background: rgba(253, 184, 64, 0.1); color: #FDB840;">
                    <i class="fa-solid fa-desktop"></i>
                </div>
                <div class="ved-kpi-content">
                    <span class="ved-kpi-val"><?= $monitor_count ?></span>
                    <span class="ved-kpi-label">Monitors</span>
                </div>
            </div>

            <div class="ved-kpi-card">
                <div class="ved-kpi-icon-wrap" style="background: rgba(106, 191, 87, 0.1); color: #6ABF57;">
                    <i class="fa-solid fa-plug"></i>
                </div>
                <div class="ved-kpi-content">
                    <span class="ved-kpi-val"><?= $acc_count ?></span>
                    <span class="ved-kpi-label">Accessories</span>
                </div>
            </div>
        </div>

        <!-- Main Section: Assigned Equipment -->
        <div class="ved-section-card">
            <div class="ved-section-header">
                <div class="ved-section-title-wrap">
                    <div class="ved-section-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                        <i class="fa-solid fa-laptop-file"></i>
                    </div>
                    <div>
                        <h2 class="ved-section-title">Assigned Equipment</h2>
                        <p class="ved-section-subtitle">Hardware units currently deployed and in use by this employee</p>
                    </div>
                </div>
                <div class="ved-section-badge">
                    <i class="fa-solid fa-layer-group me-1"></i>
                    <span><?= $total_dev ?> <?= $total_dev === 1 ? 'Unit' : 'Units' ?></span>
                </div>
            </div>

            <?php if (!empty($assigned_devices)): ?>
                <div class="ved-devices-grid">
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
                        <div class="ved-device-card">
                            <div class="ved-dev-card-top">
                                <span class="ved-cat-pill" style="background: <?= $cat_color ?>15; color: <?= $cat_color ?>; border: 1px solid <?= $cat_color ?>30;">
                                    <i class="fa-solid <?= $cat_icon ?> me-1"></i> <?= esc_html($cat) ?>
                                </span>
                                <span class="ved-id-pill"><?= esc_html($dev->DeviceID) ?></span>
                            </div>

                            <div class="ved-dev-body">
                                <h3 class="ved-dev-title">
                                    <?= esc_html($dev->BrandName) ?> <?= esc_html($dev->Model) ?>
                                </h3>

                                <div class="ved-dev-meta-list">
                                    <div class="ved-meta-row">
                                        <span class="ved-meta-k"><i class="fa-solid fa-barcode me-1"></i> Serial No:</span>
                                        <span class="ved-meta-v font-monospace"><?= esc_html($dev->SerialNumber ?: '-') ?></span>
                                    </div>
                                    <?php if (!empty($dev->KeywordName)): ?>
                                        <div class="ved-meta-row">
                                            <span class="ved-meta-k"><i class="fa-solid fa-microchip me-1"></i> Type / Profile:</span>
                                            <span class="ved-meta-v"><?= esc_html($dev->KeywordName) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($dev->ReceiveDate) && $dev->ReceiveDate !== '0000-00-00'): ?>
                                        <div class="ved-meta-row">
                                            <span class="ved-meta-k"><i class="fa-solid fa-calendar-check me-1"></i> Handover Date:</span>
                                            <span class="ved-meta-v"><?= esc_html(date_i18n('d M Y', strtotime($dev->ReceiveDate))) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ved-dev-footer">
                                <span class="status-badge status-inuse">
                                    <span class="status-dot"></span>
                                    <?= esc_html($dev->StatusName ?: 'In Use') ?>
                                </span>
                                <a href="<?= esc_url(home_url('/home/?view=' . $dev->DeviceID)) ?>" class="ved-view-dev-btn" title="View device full specs & maintenance history">
                                    <span>Device Details</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ved-empty-state">
                    <div class="ved-empty-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3>No Devices Currently Assigned</h3>
                    <p>This staff member does not have any active laptops, monitors, or accessories assigned right now.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Activity & History Timeline -->
        <div class="ved-section-card mt-4">
            <div class="ved-section-header">
                <div class="ved-section-title-wrap">
                    <div class="ved-section-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h2 class="ved-section-title">Equipment Activity Timeline</h2>
                        <p class="ved-section-subtitle">Audit logs and past handover history involving this employee</p>
                    </div>
                </div>
                <div class="ved-section-badge">
                    <i class="fa-solid fa-list-check me-1"></i>
                    <span><?= count($history_rows) ?> Events</span>
                </div>
            </div>

            <?php if (!empty($history_rows)): ?>
                <div class="ved-timeline-wrap">
                    <?php foreach ($history_rows as $idx => $h): 
                        $action = $h->Action ?? 'Action';
                        $act_class = 'info';
                        $act_icon = 'fa-arrow-right-arrow-left';
                        if (stripos($action, 'Receive') !== false || stripos($action, 'Assign') !== false || stripos($action, 'Add') !== false) {
                            $act_class = 'success';
                            $act_icon = 'fa-circle-check';
                        } elseif (stripos($action, 'Return') !== false || stripos($action, 'Offboard') !== false) {
                            $act_class = 'warning';
                            $act_icon = 'fa-rotate-left';
                        } elseif (stripos($action, 'Repair') !== false || stripos($action, 'Maintenance') !== false) {
                            $act_class = 'danger';
                            $act_icon = 'fa-wrench';
                        }
                    ?>
                        <div class="ved-timeline-item">
                            <div class="ved-timeline-dot <?= $act_class ?>">
                                <i class="fa-solid <?= $act_icon ?>"></i>
                            </div>
                            <div class="ved-timeline-card">
                                <div class="ved-timeline-head">
                                    <div class="ved-timeline-act-group">
                                        <span class="ved-action-badge <?= $act_class ?>"><?= esc_html($action) ?></span>
                                        <?php if (!empty($h->DeviceID) && $h->DeviceID !== '0'): ?>
                                            <a href="<?= esc_url(home_url('/home/?view=' . $h->DeviceID)) ?>" class="ved-timeline-dev-link">
                                                <code><?= esc_html($h->DeviceID) ?></code>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <span class="ved-timeline-time">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?= esc_html(date_i18n('d M Y, H:i', strtotime($h->Date))) ?>
                                    </span>
                                </div>
                                <div class="ved-timeline-desc">
                                    <?= esc_html($h->Description ?: 'No details recorded') ?>
                                </div>
                                <?php if (!empty($h->user_email)): ?>
                                    <div class="ved-timeline-meta">
                                        <i class="fa-solid fa-user-gear me-1"></i>
                                        <span>Logged by: <span style="text-transform: lowercase;"><?= esc_html(strtolower($h->user_email)) ?></span></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ved-empty-state">
                    <div class="ved-empty-icon">
                        <i class="fa-solid fa-timeline"></i>
                    </div>
                    <h3>No Activity History Found</h3>
                    <p>There are no historical equipment handover or return logs recorded for this employee yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Scoped Styles for Standalone View Employee Details -->
    <style>
        .ved-wrapper {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0.5rem 1rem 3rem 1rem;
            font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #0f172a;
            box-sizing: border-box;
        }

        /* Top Bar */
        .ved-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ved-breadcrumb-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.92rem;
        }

        .ved-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            padding: 7px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        }

        .ved-back-btn:hover {
            color: #0f172a;
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateX(-2px);
        }

        .ved-bread-sep {
            color: #cbd5e1;
            font-weight: 400;
        }

        .ved-bread-current {
            color: #0f172a;
            font-weight: 700;
        }

        .ved-edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
        }

        .ved-edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
            color: #ffffff !important;
        }

        /* Hero Profile Card */
        .ved-hero-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1.5px solid #e2e8f0;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.05);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .ved-hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #3b82f6, #06b6d4);
        }

        .ved-hero-main {
            display: flex;
            align-items: center;
            gap: 1.75rem;
            flex-wrap: wrap;
        }

        .ved-avatar-wrap {
            flex-shrink: 0;
        }

        .ved-avatar-circle {
            width: 86px;
            height: 86px;
            border-radius: 24px;
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: 800;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.4);
        }

        .ved-hero-info {
            flex: 1;
            min-width: 260px;
        }

        .ved-hero-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .ved-hero-name {
            margin: 0;
            font-size: 1.85rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .ved-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .ved-status-pill.active {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .ved-status-pill.resigned {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .ved-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .ved-hero-fullname {
            margin-top: 4px;
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
        }

        .ved-hero-chips {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .ved-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            color: #334155;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .ved-chip i {
            color: #6366f1;
            font-size: 0.8rem;
        }

        .ved-chip-link {
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .ved-chip-link:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
            color: #0f172a;
        }

        .ved-chip-mono {
            font-family: monospace;
            color: #64748b;
        }

        /* KPI Strip */
        .ved-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ved-kpi-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 1.25rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .ved-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.08);
        }

        .ved-kpi-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .ved-kpi-content {
            display: flex;
            flex-direction: column;
        }

        .ved-kpi-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .ved-kpi-label {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
            margin-top: 3px;
        }

        /* Section Cards */
        .ved-section-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 24px;
            padding: 1.75rem 2rem;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        }

        .ved-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .ved-section-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ved-section-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .ved-section-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        .ved-section-subtitle {
            margin: 2px 0 0 0;
            font-size: 0.84rem;
            color: #64748b;
        }

        .ved-section-badge {
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

        /* Devices Grid */
        .ved-devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        .ved-device-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.22s ease;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }

        .ved-device-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.09);
        }

        .ved-dev-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .ved-cat-pill {
            font-size: 0.74rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .ved-id-pill {
            font-size: 0.82rem;
            font-weight: 800;
            color: #1e40af;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 3px 9px;
            border-radius: 7px;
            letter-spacing: 0.02em;
        }

        .ved-dev-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
            line-height: 1.35;
        }

        .ved-dev-meta-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.82rem;
        }

        .ved-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .ved-meta-k {
            color: #94a3b8;
            font-weight: 500;
        }

        .ved-meta-v {
            color: #334155;
            font-weight: 600;
            text-align: right;
        }

        .ved-dev-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed #e2e8f0;
        }

        .ved-view-dev-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            padding: 5px 12px;
            border-radius: 8px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            transition: all 0.15s ease;
        }

        .ved-view-dev-btn:hover {
            background: #4f46e5;
            color: #ffffff;
            border-color: #4f46e5;
        }

        /* Timeline Styles */
        .ved-timeline-wrap {
            position: relative;
            padding-left: 20px;
        }

        .ved-timeline-wrap::before {
            content: '';
            position: absolute;
            top: 10px;
            bottom: 10px;
            left: 31px;
            width: 2px;
            background: #e2e8f0;
        }

        .ved-timeline-item {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 18px;
        }

        .ved-timeline-dot {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
            z-index: 1;
            box-shadow: 0 0 0 4px #ffffff;
        }

        .ved-timeline-dot.success { background: #10b981; color: #ffffff; }
        .ved-timeline-dot.warning { background: #f59e0b; color: #ffffff; }
        .ved-timeline-dot.danger  { background: #ef4444; color: #ffffff; }
        .ved-timeline-dot.info    { background: #3b82f6; color: #ffffff; }

        .ved-timeline-card {
            flex: 1;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 16px;
        }

        .ved-timeline-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 6px;
        }

        .ved-timeline-act-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ved-action-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .ved-action-badge.success { background: #ecfdf5; color: #059669; }
        .ved-action-badge.warning { background: #fffbeb; color: #d97706; }
        .ved-action-badge.danger  { background: #fef2f2; color: #dc2626; }
        .ved-action-badge.info    { background: #eff6ff; color: #2563eb; }

        .ved-timeline-dev-link code {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1e40af;
            background: #dbeafe;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .ved-timeline-time {
            font-size: 0.76rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .ved-timeline-desc {
            font-size: 0.86rem;
            color: #334155;
            font-weight: 500;
            line-height: 1.4;
        }

        .ved-timeline-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 6px;
        }

        /* Empty State */
        .ved-empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }

        .ved-empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        .ved-empty-state h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #334155;
            margin: 0 0 4px 0;
        }

        .ved-empty-state p {
            font-size: 0.88rem;
            color: #94a3b8;
            margin: 0;
        }

        .ved-error-card {
            background: #fff1f2;
            border: 1.5px solid #fecdd3;
            color: #be123c;
            padding: 1.5rem;
            border-radius: 16px;
            font-weight: 600;
            text-align: center;
            margin: 2rem auto;
            max-width: 600px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .ved-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .ved-wrapper {
                padding: 0.5rem 0.5rem 2rem 0.5rem;
            }

            .ved-hero-card {
                padding: 1.25rem;
                border-radius: 18px;
            }

            .ved-hero-name {
                font-size: 1.4rem;
            }

            .ved-avatar-circle {
                width: 64px;
                height: 64px;
                font-size: 1.6rem;
                border-radius: 18px;
            }

            .ved-kpi-grid {
                grid-template-columns: 1fr;
            }

            .ved-section-card {
                padding: 1.25rem;
                border-radius: 18px;
            }

            .ved-devices-grid {
                grid-template-columns: 1fr;
            }

            .ved-timeline-wrap {
                padding-left: 0;
            }

            .ved-timeline-wrap::before {
                display: none;
            }

            .ved-timeline-dot {
                display: none;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
