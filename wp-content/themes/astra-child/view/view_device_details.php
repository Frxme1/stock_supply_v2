<?php
if (!defined('ABSPATH')) {
    exit;
}

function device_view_details($device_id = null)
{
    global $wpdb;

    $table_device = 'Devices';
    $table_brand = 'Brands';
    $table_cat = 'Categories';
    $table_status = 'Statuses';
    $table_history = 'History_new';

    if (!function_exists('formatName')) {
        function formatName($el)
        {
            $el = trim($el ?? '');
            $el = preg_replace('/\(\s*\)/', '', $el);
            return htmlspecialchars($el ?: '-');
        }
    }

    ob_start();

    if (isset($_GET['delete'])) {
        if (!is_user_logged_in() || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_history_nonce')) {
            return '<p>Security check failed.</p>';
        }
        $history_id = sanitize_text_field($_GET['delete']);
        $wpdb->delete($table_history, ['HistoryID' => $history_id], ['%s']);
    }

    $device_id = $device_id ?: ($_GET['view'] ?? '');
    if (empty($device_id)) {
        return '<p>No Device ID provided.</p>';
    }

    // Query Device details with Category, Brand, Status, Keyword, Owner, and Department
    $device = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, b.BrandName, c.CategoryName, s.StatusName, kw.KeywordName,
               o.Nickname AS OwnerNickname,
               TRIM(CONCAT(COALESCE(o.FirstName, ''), ' ', COALESCE(o.LastName, ''))) AS OwnerFullname,
               dept.DepartmentName
        FROM {$table_device} d
        LEFT JOIN {$table_brand} b ON d.BrandID = b.BrandID
        LEFT JOIN {$table_cat} c ON d.CategoryID = c.CategoryID
        LEFT JOIN {$table_status} s ON d.StatusID = s.StatusID
        LEFT JOIN Keywords kw ON d.KeywordID = kw.KeywordID
        LEFT JOIN Owners o ON d.OwnerID = o.OwnerID
        LEFT JOIN Departments dept ON o.DepartmentID = dept.DepartmentID
        WHERE d.DeviceID = %s
    ", $device_id));

    if (!$device) {
        return '<p>Device not found.</p>';
    }

    // Query Active / Latest Maintenance Record
    $active_maintenance = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM Maintenance
        WHERE DeviceID = %s
        ORDER BY MaintenanceID DESC
        LIMIT 1
    ", $device_id));

    // Query Total Maintenance Count
    $maint_count = intval($wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM Maintenance
        WHERE DeviceID = %s
    ", $device_id)));

    $is_in_maintenance = (strcasecmp($device->StatusName ?? '', 'Maintenance') === 0);

    // Calculate Days in Repair if in maintenance
    $days_in_repair_text = '';
    $repair_date_raw = '';
    if ($active_maintenance) {
        $repair_date_raw = (!empty($active_maintenance->RepairDate) && $active_maintenance->RepairDate !== '0000-00-00')
            ? $active_maintenance->RepairDate
            : $active_maintenance->CreatedAt;

        if (!empty($repair_date_raw)) {
            $ts = strtotime($repair_date_raw);
            if ($ts !== false) {
                $diff_days = floor((time() - $ts) / 86400);
                if ($diff_days <= 0) {
                    $days_in_repair_text = 'Sent today';
                } elseif ($diff_days == 1) {
                    $days_in_repair_text = '1 day in repair';
                } else {
                    $days_in_repair_text = "{$diff_days} days in repair";
                }
            }
        }
    }

    $dev_action_nonce = wp_create_nonce('device_action_nonce');

    // History Log Pagination & Query
    $search = isset($_GET['history_search']) ? stock_supply_parse_search_query($_GET['history_search']) : '';
    $where = "WHERE CAST(h.DeviceID AS CHAR) = %s AND h.DeviceID IS NOT NULL AND h.DeviceID != '' AND h.DeviceID != '0'";
    $params = [(string) $device_id];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= " AND (
            h.Action LIKE %s OR 
            h.Date LIKE %s OR 
            h.Description LIKE %s OR 
            h.user_email LIKE %s OR 
            h.Owner LIKE %s
        )";
        $params = array_merge($params, array_fill(0, 5, $like));
    }

    $per_page = 15;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    $sql_count = $wpdb->prepare("SELECT COUNT(*) FROM {$table_history} h $where", ...$params);
    $total_items = $wpdb->get_var($sql_count);
    $total_pages = ceil($total_items / $per_page);

    $sql_rows = $wpdb->prepare(
        "SELECT * FROM {$table_history} h $where ORDER BY h.Date DESC LIMIT %d OFFSET %d",
        ...array_merge($params, [$per_page, $offset])
    );
    $rows = $wpdb->get_results($sql_rows);

    $cat_slug = !empty($device->CategoryName) ? sanitize_title($device->CategoryName) : 'home';
    if (!in_array($cat_slug, ['laptop', 'monitor', 'accessories'])) {
        $cat_slug = 'home';
    }
    $category_table_url = home_url('/' . $cat_slug . '/');
    ?>

    <style>
        /* Base Modern Styling */
        .view-details-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
            padding-bottom: 2.5rem;
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Top Header */
        .vd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
            gap: 1rem;
            animation: fadeDown 0.35s ease-out;
        }

        .vd-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .vd-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .vd-id-badge {
            background: #f1f5f9;
            color: #1e40af;
            border: 1.5px solid #cbd5e1;
            font-size: 0.95rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .vd-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
            margin-top: 0.35rem;
        }

        /* Action Buttons */
        .vd-actions-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .vd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            border-radius: 9999px;
            padding: 0.55rem 1.3rem;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .vd-btn:hover {
            transform: translateY(-1px);
        }

        .vd-btn-done-return {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            font-weight: 700;
        }

        .vd-btn-done-return:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
            color: #ffffff !important;
        }

        /* ===================================================
           ACTIVE MAINTENANCE CASE BANNER (HIGH PRIORITY)
           =================================================== */
        .vd-maint-active-banner {
            background: linear-gradient(145deg, #ffffff 0%, #fffbeb 100%);
            border: 2px solid #f59e0b;
            border-radius: 20px;
            padding: 22px 24px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.12);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
        }

        .vd-maint-top-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #eab308);
        }

        .vd-maint-banner-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1.5px dashed #fde68a;
        }

        .vd-maint-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vd-maint-badge {
            background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
            color: #ffffff;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(234, 88, 12, 0.25);
        }

        .vd-maint-pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #ffffff;
            border-radius: 50%;
            animation: maintDotBlink 1.5s infinite ease-in-out;
        }

        .vd-maint-duration-pill {
            font-size: 0.85rem;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fde68a;
            padding: 5px 12px;
            border-radius: 9999px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Bento Grid inside Active Maintenance */
        .vd-maint-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
        }

        @media (max-width: 860px) {
            .vd-maint-grid {
                grid-template-columns: 1fr;
            }
        }

        .vd-maint-issue-card {
            background: #ffffff;
            border: 1.5px solid #fed7aa;
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .vd-maint-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
            color: #b45309;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .vd-maint-issue-content {
            font-size: 1.05rem;
            font-weight: 700;
            color: #7c2d12;
            line-height: 1.45;
            word-break: break-word;
        }

        .vd-maint-meta-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .vd-maint-meta-item {
            background: #ffffff;
            border: 1.5px solid #fed7aa;
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .vd-maint-meta-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .vd-maint-meta-val {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .vd-maint-photo-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .vd-maint-photo-thumb {
            width: 48px;
            height: 38px;
            object-fit: cover;
            border-radius: 6px;
            border: 1.5px solid #cbd5e1;
            transition: transform 0.2s;
        }

        .vd-maint-photo-thumb:hover {
            transform: scale(1.08);
        }

        /* Banner Bottom Footer */
        .vd-maint-footer {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed #fde68a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .vd-maint-footer-hint {
            font-size: 0.82rem;
            color: #92400e;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===================================================
           DEVICE SPECIFICATIONS & IDENTITY INFO GRID
           =================================================== */
        .vd-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.25rem;
            animation: fadeIn 0.45s ease-out;
        }

        .vd-info-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.2rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
            transition: all 0.22s ease;
            position: relative;
        }

        .vd-info-card:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .vd-info-label {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .vd-info-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        /* Status Badges */
        .vd-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .vd-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .vd-status-Available {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .vd-status-Available .vd-status-dot { background: #10b981; }

        .vd-status-InUse {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .vd-status-InUse .vd-status-dot { background: #3b82f6; }

        .vd-status-Maintenance {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .vd-status-Maintenance .vd-status-dot { background: #f59e0b; }

        .vd-status-Retired {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .vd-status-Retired .vd-status-dot { background: #94a3b8; }

        /* Copy Button inside info card */
        .vd-copy-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px 6px;
            font-size: 0.8rem;
            transition: color 0.15s;
        }
        .vd-copy-btn:hover { color: #2563eb; }

        /* History Section */
        .vd-history-section {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
            margin-top: 1.5rem;
        }

        .vd-history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1.5px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 12px;
        }

        .vd-history-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vd-maint-count-badge {
            font-size: 0.8rem;
            background: #f1f5f9;
            color: #475569;
            padding: 4px 12px;
            border-radius: 9999px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
        }

        /* Animations */
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes maintDotBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        /* ===================================================
           MOBILE INTERFACE ENHANCEMENTS (Screen <= 768px)
           =================================================== */
        @media (max-width: 768px) {
            .view-details-container {
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                margin-top: 0.5rem !important;
                padding-bottom: 2rem !important;
            }

            /* Sleek Mobile App Navigation Header */
            .vd-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
                margin-bottom: 1rem !important;
                background: #ffffff;
                border: 1.5px solid #e2e8f0;
                border-radius: 18px;
                padding: 14px 14px;
                box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            }

            .vd-title-wrap {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 8px !important;
                width: 100% !important;
            }

            .vd-title {
                font-size: 1.22rem !important;
                font-weight: 800 !important;
                letter-spacing: -0.02em !important;
            }

            .vd-id-badge {
                font-size: 0.8rem !important;
                font-weight: 800 !important;
                padding: 3px 8px !important;
                border-radius: 6px !important;
            }

            .vd-subtitle {
                font-size: 0.84rem !important;
                color: #475569 !important;
                margin-top: 4px !important;
                line-height: 1.4 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 4px 6px !important;
            }

            /* Responsive Touch Action Buttons */
            .vd-actions-bar {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)) !important;
                gap: 8px !important;
                width: 100% !important;
                padding-top: 10px !important;
                border-top: 1px dashed #e2e8f0 !important;
            }

            .vd-btn {
                width: 100% !important;
                height: 40px !important;
                padding: 0 10px !important;
                font-size: 0.82rem !important;
                font-weight: 700 !important;
                border-radius: 10px !important;
                white-space: nowrap !important;
            }

            /* Active Maintenance Banner on Mobile */
            .vd-maint-active-banner {
                padding: 14px 14px !important;
                border-radius: 16px !important;
                margin-bottom: 1rem !important;
                box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12) !important;
            }

            .vd-maint-banner-header {
                margin-bottom: 10px !important;
                padding-bottom: 8px !important;
            }

            .vd-maint-badge {
                font-size: 0.74rem !important;
                padding: 4px 10px !important;
            }

            .vd-maint-duration-pill {
                font-size: 0.72rem !important;
                padding: 3px 8px !important;
            }

            .vd-maint-issue-card {
                padding: 12px 12px !important;
                border-radius: 12px !important;
            }

            .vd-maint-label {
                font-size: 0.7rem !important;
            }

            .vd-maint-issue-content {
                font-size: 0.92rem !important;
                line-height: 1.4 !important;
            }

            .vd-maint-meta-item {
                padding: 8px 10px !important;
                border-radius: 10px !important;
            }

            .vd-maint-meta-label,
            .vd-maint-meta-val {
                font-size: 0.78rem !important;
            }

            /* 2-Column Balanced Bento Grid for Mobile Specs */
            .vd-info-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 8px !important;
                margin-bottom: 1rem !important;
            }

            .vd-info-card {
                padding: 10px 12px !important;
                border-radius: 14px !important;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03) !important;
            }

            .vd-card-span-2 {
                grid-column: span 2 !important;
            }

            .vd-info-label {
                font-size: 0.68rem !important;
                font-weight: 700 !important;
                margin-bottom: 0.25rem !important;
                letter-spacing: 0.04em !important;
            }

            .vd-info-value {
                font-size: 0.88rem !important;
                font-weight: 700 !important;
                line-height: 1.3 !important;
            }

            .vd-status-badge {
                padding: 3px 8px !important;
                font-size: 0.74rem !important;
                font-weight: 700 !important;
            }

            /* Equipment History Section on Mobile */
            .vd-history-section {
                padding: 16px 12px !important;
                border-radius: 18px !important;
                margin-top: 1rem !important;
            }

            .vd-history-header {
                margin-bottom: 1rem !important;
                padding-bottom: 0.75rem !important;
            }

            .vd-history-title {
                font-size: 1.05rem !important;
                gap: 6px !important;
            }

            .vd-maint-count-badge {
                font-size: 0.74rem !important;
                padding: 3px 8px !important;
            }

            /* Touch Friendly Filter Chips Horizontal Scroll */
            .dtl-stats {
                display: flex !important;
                overflow-x: auto !important;
                flex-wrap: nowrap !important;
                -webkit-overflow-scrolling: touch !important;
                scrollbar-width: none !important;
                padding-bottom: 6px !important;
                gap: 6px !important;
            }

            .dtl-stats::-webkit-scrollbar {
                display: none !important;
            }

            .dtl-stat-chip {
                white-space: nowrap !important;
                flex-shrink: 0 !important;
                font-size: 0.74rem !important;
                padding: 5px 10px !important;
            }

            /* Timeline Nodes on Mobile */
            .dtl-timeline {
                padding-left: 26px !important;
            }

            .dtl-timeline::before {
                left: 9px !important;
            }

            .dtl-dot {
                left: -22px !important;
                top: 14px !important;
                width: 11px !important;
                height: 11px !important;
            }

            .dtl-date-header {
                font-size: 0.75rem !important;
                padding: 4px 12px !important;
                margin-bottom: 1rem !important;
            }

            .dtl-card {
                padding: 10px 12px !important;
                border-radius: 12px !important;
            }

            .dtl-card-head {
                gap: 6px !important;
            }

            .dtl-action-badge {
                font-size: 0.75rem !important;
                padding: 3px 8px !important;
            }

            .dtl-time {
                font-size: 0.72rem !important;
                margin-left: 0 !important;
            }

            .dtl-detail-row {
                font-size: 0.8rem !important;
                padding: 0.25rem 0 !important;
            }

            .dtl-detail-label {
                min-width: 60px !important;
                font-size: 0.7rem !important;
            }

            .dtl-detail-value {
                font-size: 0.8rem !important;
            }
        }
    </style>

    <div class="view-details-container px-3 mt-3">
        <!-- Top Header Navigation & Action Bar -->
        <div class="vd-header">
            <div>
                <div class="vd-title-wrap">
                    <h2 class="vd-title">Device Details</h2>
                    <span class="vd-id-badge"><i class="fa-solid fa-barcode"></i> <?= esc_html($device->DeviceID) ?></span>
                    <?php if (!empty($device->CategoryName)): ?>
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size:0.75rem; font-weight:700; text-transform:uppercase;">
                            <?= esc_html($device->CategoryName) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="vd-subtitle">
                    <?= esc_html($device->BrandName ?? '') ?> <?= esc_html($device->Model ?? '') ?>
                    <?php if (!empty($device->SerialNumber)): ?>
                        &nbsp;&bull;&nbsp; SN: <strong><?= esc_html($device->SerialNumber) ?></strong>
                    <?php endif; ?>
                </div>
            </div>

            <div class="vd-actions-bar">
                <button type="button" class="btn btn-outline-secondary vd-btn" onclick="
                    const ref = document.referrer;
                    const unsafeParams = ['return_to_owner=', 'return=', 'available=', 'retired=', 'receive=', 'edit=', 'maintenance=', 'action=', 'add=', 'delete=', 'lost=', '_wpnonce=', 'view='];
                    const isUnsafe = !ref || !ref.includes(window.location.host) || unsafeParams.some(p => ref.includes(p));
                    if (!isUnsafe) {
                        window.location.href = ref;
                    } else {
                        window.location.href = '<?= esc_url($category_table_url) ?>';
                    }
                ">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>

                <!-- Primary Action: Done & Return (Only when in Maintenance) -->
                <?php if ($is_in_maintenance): ?>
                    <button type="button"
                        class="vd-btn vd-btn-done-return"
                        data-device="<?= esc_attr(wp_json_encode([
                            'id'           => $device->DeviceID,
                            'brand'        => $device->BrandName ?? '',
                            'model'        => $device->Model ?? '',
                            'category'     => $device->CategoryName ?? '',
                            'serialNumber' => $device->SerialNumber ?? '',
                            'owner'        => $device->OwnerNickname ?: ($device->OwnerFullname ?? ''),
                            'department'   => $device->DepartmentName ?? '',
                            'details'      => $active_maintenance->Details ?? '',
                            'repairDate'   => $repair_date_raw,
                            'nonce'        => $dev_action_nonce
                        ])) ?>"
                        onclick="handleReturnMaintenanceClick(this)">
                        <i class="fa-solid fa-circle-check"></i> Done & Return
                    </button>
                <?php endif; ?>

                <button type="button" class="btn btn-dark vd-btn"
                    onclick="printDeviceLabels([{ id: '<?= esc_js($device->DeviceID) ?>', sn: '<?= esc_js($device->SerialNumber) ?>' }])">
                    <i class="fa-solid fa-print"></i> Print Label
                </button>
            </div>
        </div>

        <!-- =========================================================
             ACTIVE MAINTENANCE CASE BANNER (SHOWN WHEN IN MAINTENANCE)
             ========================================================= -->
        <?php if ($is_in_maintenance && $active_maintenance): ?>
            <div class="vd-maint-active-banner">
                <div class="vd-maint-top-accent"></div>

                <!-- Banner Header -->
                <div class="vd-maint-banner-header">
                    <div class="vd-maint-title-group">
                        <span class="vd-maint-badge">
                            <span class="vd-maint-pulse-dot"></span>
                            <i class="fa-solid fa-screwdriver-wrench"></i> Under Maintenance
                        </span>
                        <?php if (!empty($days_in_repair_text)): ?>
                            <span class="vd-maint-duration-pill">
                                <i class="fa-regular fa-clock"></i> <?= esc_html($days_in_repair_text) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span style="font-size: 0.85rem; font-weight: 700; color: #92400e;">
                            Case ID: #<?= esc_html($active_maintenance->MaintenanceID) ?>
                        </span>
                    </div>
                </div>

                <!-- Banner Body Grid -->
                <div class="vd-maint-grid">
                    <!-- Left: Diagnostic Issue -->
                    <div class="vd-maint-issue-card">
                        <div>
                            <div class="vd-maint-label">
                                <i class="fa-solid fa-triangle-exclamation"></i> Reported Issue
                            </div>
                            <div class="vd-maint-issue-content">
                                <?= esc_html($active_maintenance->Details ?: 'No issue details provided.') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Metadata (Sent Date, Owner, Photo) -->
                    <div class="vd-maint-meta-list">
                        <div class="vd-maint-meta-item">
                            <span class="vd-maint-meta-label">
                                <i class="fa-regular fa-calendar-check text-warning"></i> Sent to Repair
                            </span>
                            <span class="vd-maint-meta-val text-warning">
                                <?= !empty($repair_date_raw) ? esc_html(date('d M Y', strtotime($repair_date_raw))) : '-' ?>
                            </span>
                        </div>

                        <div class="vd-maint-meta-item">
                            <span class="vd-maint-meta-label">
                                <i class="fa-solid fa-user text-indigo-500"></i> Assigned Owner
                            </span>
                            <span class="vd-maint-meta-val">
                                <?= esc_html(formatName(stock_supply_format_owner_with_dept($device->OwnerNickname ?: $device->OwnerFullname, $device->DepartmentName))) ?>
                            </span>
                        </div>

                        <?php if (!empty($active_maintenance->Photo)): ?>
                            <div class="vd-maint-meta-item">
                                <span class="vd-maint-meta-label">
                                    <i class="fa-solid fa-camera text-success"></i> Condition Photo
                                </span>
                                <span class="vd-maint-meta-val">
                                    <span class="vd-maint-photo-box" onclick="window.openPhotoModal('<?= esc_url($active_maintenance->Photo) ?>')">
                                        <img src="<?= esc_url($active_maintenance->Photo) ?>" class="vd-maint-photo-thumb" alt="Condition Photo">
                                        <span style="font-size:0.8rem; color:#0284c7; text-decoration:underline;">View Photo</span>
                                    </span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- =========================================================
             DEVICE SPECIFICATIONS & IDENTITY INFO GRID
             ========================================================= -->
        <div class="vd-info-grid">
            <!-- Device ID -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-hashtag text-primary"></i> Device ID
                </div>
                <div class="vd-info-value d-flex align-items-center justify-content-between">
                    <span><?= esc_html($device->DeviceID) ?></span>
                    <button type="button" class="vd-copy-btn" title="Copy Device ID" onclick="navigator.clipboard.writeText('<?= esc_js($device->DeviceID) ?>'); this.innerHTML='<i class=\'fa-solid fa-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fa-regular fa-copy\'></i>', 1500);">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Current Status -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-circle-info text-amber-500"></i> Status
                </div>
                <div class="vd-info-value">
                    <?php
                    $statusClass = 'vd-status-Retired';
                    $statusName = $device->StatusName ?? 'Unknown';
                    if (strcasecmp($statusName, 'Available') === 0)
                        $statusClass = 'vd-status-Available';
                    elseif (strcasecmp($statusName, 'In Use') === 0)
                        $statusClass = 'vd-status-InUse';
                    elseif (strcasecmp($statusName, 'Maintenance') === 0)
                        $statusClass = 'vd-status-Maintenance';
                    ?>
                    <div class="vd-status-badge <?= $statusClass ?>">
                        <div class="vd-status-dot"></div>
                        <?= esc_html($statusName) ?>
                    </div>
                </div>
            </div>

            <!-- Category -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-layer-group text-info"></i> Category
                </div>
                <div class="vd-info-value"><?= esc_html($device->CategoryName ?: '-') ?></div>
            </div>

            <!-- Brand -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-tag text-secondary"></i> Brand
                </div>
                <div class="vd-info-value"><?= esc_html($device->BrandName ?: '-') ?></div>
            </div>

            <!-- Model -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-cube text-primary"></i> Model
                </div>
                <div class="vd-info-value"><?= esc_html($device->Model ?: '-') ?></div>
            </div>

            <!-- Serial Number -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-barcode text-slate-500"></i> Serial Number
                </div>
                <div class="vd-info-value d-flex align-items-center justify-content-between">
                    <span><?= !empty($device->SerialNumber) ? esc_html($device->SerialNumber) : '<span class="text-muted">-</span>' ?></span>
                    <?php if (!empty($device->SerialNumber)): ?>
                        <button type="button" class="vd-copy-btn" title="Copy Serial Number" onclick="navigator.clipboard.writeText('<?= esc_js($device->SerialNumber) ?>'); this.innerHTML='<i class=\'fa-solid fa-check text-success\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fa-regular fa-copy\'></i>', 1500);">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Keyword / Specs Profile -->
            <div class="vd-info-card">
                <div class="vd-info-label">
                    <i class="fa-solid fa-microchip text-emerald-500"></i> Hardware Profile
                </div>
                <div class="vd-info-value">
                    <?= !empty($device->KeywordName) ? esc_html($device->KeywordName) : '<span class="text-muted">-</span>' ?>
                </div>
            </div>

            <!-- Current Owner -->
            <div class="vd-info-card vd-card-span-2">
                <div class="vd-info-label">
                    <i class="fa-solid fa-user text-indigo-500"></i> Current Owner
                </div>
                <div class="vd-info-value">
                    <?= esc_html(formatName(stock_supply_format_owner_with_dept($device->OwnerNickname ?: $device->OwnerFullname, $device->DepartmentName))) ?>
                </div>
            </div>
        </div>

        <!-- =========================================================
             HISTORY & MAINTENANCE TIMELINE SECTION
             ========================================================= -->
        <div class="vd-history-section dtl-show-search">
            <div class="vd-history-header">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h3 class="vd-history-title">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i> Device History
                    </h3>
                    <?php if ($maint_count > 0): ?>
                        <span class="vd-maint-count-badge">
                            <i class="fa-solid fa-wrench text-warning me-1"></i> Total <?= $maint_count ?> Repair<?= $maint_count > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Timeline Container -->
            <?php
            $action_map = [
                'receive'     => ['class' => 'dtl-action-receive', 'icon' => 'fa-box-open'],
                'add'         => ['class' => 'dtl-action-receive', 'icon' => 'fa-plus-circle'],
                'create'      => ['class' => 'dtl-action-receive', 'icon' => 'fa-plus-circle'],
                'edit'        => ['class' => 'dtl-action-edit', 'icon' => 'fa-pen'],
                'update'      => ['class' => 'dtl-action-edit', 'icon' => 'fa-pen-to-square'],
                'maintenance' => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-wrench'],
                'repair'      => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-screwdriver-wrench'],
                'transfer'    => ['class' => 'dtl-action-transfer', 'icon' => 'fa-right-left'],
                'move'        => ['class' => 'dtl-action-transfer', 'icon' => 'fa-truck'],
                'delete'      => ['class' => 'dtl-action-delete', 'icon' => 'fa-trash'],
                'remove'      => ['class' => 'dtl-action-delete', 'icon' => 'fa-circle-minus'],
                'status'      => ['class' => 'dtl-action-status', 'icon' => 'fa-circle-dot'],
                'change'      => ['class' => 'dtl-action-status', 'icon' => 'fa-arrows-rotate'],
            ];

            function dtl_get_action_info($action, $map)
            {
                $lower = strtolower(trim($action));
                foreach ($map as $key => $info) {
                    if (strpos($lower, $key) !== false) {
                        return $info;
                    }
                }
                return ['class' => 'dtl-action-default', 'icon' => 'fa-circle'];
            }

            $grouped = [];
            foreach ($rows as $row) {
                $dt = new DateTime($row->Date);
                $key = $dt->format('F Y');
                $grouped[$key][] = $row;
            }

            $action_counts = [];
            foreach ($rows as $row) {
                $a = trim($row->Action);
                if (!isset($action_counts[$a]))
                    $action_counts[$a] = 0;
                $action_counts[$a]++;
            }
            ?>

            <div class="dtl-timeline-wrap active">
                <?php if (!empty($action_counts)): ?>
                    <div class="dtl-stats">
                        <div class="dtl-stat-chip active" data-filter-action="all" role="button" tabindex="0">
                            <i class="fa-solid fa-list-check"></i> Total
                            <span class="dtl-stat-count"><?= count($rows) ?></span>
                        </div>
                        <?php foreach ($action_counts as $act => $cnt): ?>
                            <div class="dtl-stat-chip" data-filter-action="<?= esc_attr(strtolower(trim($act))) ?>" role="button" tabindex="0">
                                <?= esc_html($act) ?>
                                <span class="dtl-stat-count"><?= $cnt ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                    <div class="dtl-empty">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <p>No history logs found.</p>
                    </div>
                <?php else: ?>
                    <?php
                    $item_idx = 0;
                    foreach ($grouped as $month => $items): ?>
                        <div class="dtl-date-group">
                            <div class="dtl-date-header">
                                <i class="fa-regular fa-calendar"></i> <?= esc_html($month) ?>
                            </div>
                            <div class="dtl-timeline">
                                <?php foreach ($items as $row):
                                    $info = dtl_get_action_info($row->Action, $action_map);
                                    $dt = new DateTime($row->Date);
                                    $isOpen = ($item_idx === 0);
                                    $item_idx++;
                                    ?>
                                    <div class="dtl-node <?= $info['class'] ?> dtl-visible" data-action="<?= esc_attr(strtolower(trim($row->Action))) ?>">
                                        <div class="dtl-dot"></div>
                                        <div class="dtl-card <?= $isOpen ? 'dtl-open' : '' ?>">
                                            <div class="dtl-card-head">
                                                <span class="dtl-action-badge">
                                                    <i class="fa-solid <?= $info['icon'] ?>"></i>
                                                    <?= esc_html($row->Action) ?>
                                                </span>
                                                <span class="dtl-time">
                                                    <i class="fa-regular fa-clock"></i>
                                                    <?= esc_html($dt->format('d M Y, H:i')) ?>
                                                </span>
                                                <i class="fa-solid fa-chevron-down dtl-expand-icon"></i>
                                            </div>
                                            <div class="dtl-card-body <?= $isOpen ? 'dtl-expanded' : '' ?>">
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">Details</span>
                                                    <span class="dtl-detail-value">
                                                        <?= wp_kses_post(stock_supply_format_history_description($row->Description, $row->Action, $device->DeviceID)) ?>
                                                    </span>
                                                </div>
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">User</span>
                                                    <span class="dtl-detail-value"><?= esc_html($row->user_email ?: '-') ?></span>
                                                </div>
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">Owner</span>
                                                    <span class="dtl-detail-value"><?= esc_html(stock_supply_format_owner_with_dept($row->Owner ?? '', $row->Department ?? '')) ?></span>
                                                </div>
                                                <?php if (!empty($row->Photo)): ?>
                                                    <div class="dtl-detail-row">
                                                        <span class="dtl-detail-label">Photo</span>
                                                        <span class="dtl-detail-value">
                                                            <img src="<?= esc_url($row->Photo) ?>" class="dtl-photo-thumb"
                                                                onclick="event.stopPropagation(); window.openPhotoModal('<?= esc_url($row->Photo) ?>');"
                                                                title="Click to view full photo">
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4 mb-2">
                <ul class="pagination pagination-sm">
                    <?php
                    if ($total_pages > 1) {
                        $query_str = http_build_query(array_merge($_GET, ['paged' => null]));
                        $range = 2;
                        $start = max(1, $current_page - $range);
                        $end = min($total_pages, $current_page + $range);

                        echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
                        echo '<a class="page-link" href="?' . $query_str . '&paged=' . ($current_page - 1) . '"><i class="fa-solid fa-chevron-left"></i></a>';
                        echo '</li>';

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=1">1</a></li>';
                            if ($start > 2)
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $current_page ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            echo '<a class="page-link" href="?' . $query_str . '&paged=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1)
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }

                        echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
                        echo '<a class="page-link" href="?' . $query_str . '&paged=' . ($current_page + 1) . '"><i class="fa-solid fa-chevron-right"></i></a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- SweetAlert & Return Action Helper -->
    <script>
    function handleReturnMaintenanceClick(btn) {
        if (!btn) return;
        try {
            const raw = btn.getAttribute('data-device');
            const data = JSON.parse(raw);
            if (typeof confirmReturnFromMaintenance === 'function') {
                confirmReturnFromMaintenance(data, data.nonce || '');
            } else {
                if (confirm('Confirm mark repair completed and return device ' + (data.id || '') + '?')) {
                    const targetAction = data.owner && data.owner !== '-' ? 'return_to_owner' : 'available';
                    const url = new URL(window.location.href);
                    url.searchParams.set(targetAction, data.id);
                    if (data.nonce) url.searchParams.set('_wpnonce', data.nonce);
                    window.location.href = url.toString();
                }
            }
        } catch(err) {
            console.error('Error parsing device data:', err);
        }
    }

    window.openPhotoModal = function (imgUrl) {
        if (!imgUrl) return;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<i class="fa-solid fa-camera" style="color:#6366f1; margin-right:8px;"></i> Device Photo',
                imageUrl: imgUrl,
                imageAlt: 'Device Photo',
                showCloseButton: false,
                confirmButtonColor: '#6366f1',
                confirmButtonText: '<i class="fa-solid fa-xmark"></i> Close',
                customClass: { popup: 'dash-scan-popup dtl-photo-modal' }
            });
        } else {
            let overlay = document.getElementById('photo_lightbox_overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'photo_lightbox_overlay';
                overlay.innerHTML = `
                    <div class="photo-lightbox-card">
                        <button class="photo-lightbox-close" title="Close">&times;</button>
                        <img id="photo_lightbox_img" class="photo-lightbox-img" src="" alt="Device Photo">
                    </div>
                `;
                const closeBtn = overlay.querySelector('.photo-lightbox-close');
                const closeModal = function () {
                    overlay.classList.remove('active');
                };
                closeBtn.onclick = closeModal;
                overlay.onclick = function (e) {
                    if (e.target === overlay) closeModal();
                };
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && overlay.classList.contains('active')) {
                        closeModal();
                    }
                });
                document.body.appendChild(overlay);
            }
            document.getElementById('photo_lightbox_img').src = imgUrl;
            overlay.classList.add('active');
        }
    };
    </script>

    <link rel="stylesheet" href="<?= get_stylesheet_directory_uri() ?>/css/device_timeline.css?v=<?= time() ?>">
    <script src="<?= get_stylesheet_directory_uri() ?>/js/device_timeline.js?v=<?= time() ?>"></script>
    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>
    <?php
    return ob_get_clean();
}

add_shortcode('device_view_details', 'device_view_details');
?>