<?php

function device_crud_maintenance()
{
    global $wpdb;
    $table_devices = 'Devices';
    $table_mainten = 'MaintenanceView';

    ob_start();

    $action_result = handle_device_actions();
    if ($action_result) {
        return ob_get_clean() . $action_result;
    }

    echo device_dashboard_maintenance();

    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;

    // Search filter
    $search = isset($_GET['device_search']) ? stock_supply_parse_search_query($_GET['device_search']) : '';
    $filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';

    $where_clauses = ["s.StatusName = 'Maintenance'"];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_clauses[] = $wpdb->prepare(
            "(b.BrandName LIKE %s OR d.DeviceID LIKE %s OR COALESCE(m.RepairDate, d.RepairDate) LIKE %s OR m.Details LIKE %s OR d.Model LIKE %s OR d.SerialNumber LIKE %s OR o.Nickname LIKE %s)",
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }
    if (!empty($filter_category)) {
        $where_clauses[] = $wpdb->prepare("c.CategoryName = %s", $filter_category);
    }

    $where_sql = "WHERE " . implode(" AND ", $where_clauses);

    // Fetch all active maintenance devices matching query (Clean Deduplicated Query)
    $all_active_maintenance = $wpdb->get_results("
        SELECT d.DeviceID, c.CategoryName AS Category, b.BrandName AS Brand, d.Model, d.SerialNumber,
               COALESCE(m.RepairDate, d.RepairDate) AS RepairDate,
               m.MaintenanceID, m.CreatedAt, m.Details, m.Photo,
               s.StatusName AS Status,
               o.Nickname AS Owner, dept.DepartmentName AS Department
        FROM Devices d
        INNER JOIN Statuses s ON d.StatusID = s.StatusID
        LEFT JOIN (
            SELECT m1.*
            FROM Maintenance m1
            INNER JOIN (
                SELECT DeviceID, MAX(MaintenanceID) AS MaxID
                FROM Maintenance
                GROUP BY DeviceID
            ) m2 ON m1.MaintenanceID = m2.MaxID
        ) m ON d.DeviceID = m.DeviceID
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        LEFT JOIN Owners o ON d.OwnerID = o.OwnerID
        LEFT JOIN Departments dept ON o.DepartmentID = dept.DepartmentID
        $where_sql
        ORDER BY COALESCE(m.RepairDate, d.RepairDate) DESC, COALESCE(m.CreatedAt, '1970-01-01 00:00:00') DESC, d.DeviceID DESC
    ");

    $all_categories = $wpdb->get_col("SELECT DISTINCT CategoryName FROM Categories WHERE CategoryName != '' ORDER BY CategoryName ASC");
    $suggestions = $wpdb->get_col("SELECT DISTINCT BrandName FROM Brands WHERE BrandName != '' ORDER BY BrandName ASC LIMIT 50");

    if (!function_exists('formatName')) {
        function formatName($el)
        {
            $el = trim($el);
            $el = preg_replace('/\(\s*\)/', '', $el);
            return htmlspecialchars($el ?: '-');
        }
    }

    if (!function_exists('stock_supply_format_maint_date')) {
        function stock_supply_format_maint_date($repair_date, $created_at = null)
        {
            $date_str = (!empty($repair_date) && $repair_date !== '0000-00-00')
                ? $repair_date
                : ((!empty($created_at) && $created_at !== '0000-00-00 00:00:00') ? $created_at : null);

            if (!empty($date_str)) {
                $ts = strtotime($date_str);
                if ($ts !== false) {
                    return date('d M Y', $ts);
                }
            }
            return '-';
        }
    }
    ?>

    <div class="container-fluid">
        <form method="GET" action="" id="advanced-filter-form">
            <?php
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['device_search', 'filter_category', 'filter_status', 'filter_brand', 'filter_department', 'paged', 'sort', 'order'])) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                        }
                    } else {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
            }
            ?>
            <div class="row align-items-center g-2 mb-4">
                <div class="col-12 col-sm-6 col-md-auto" style="width: 250px;">
                    <?php
                    $search_placeholder = 'Search Maintenance Device...';
                    $search_list = 'search_suggestions';
                    include get_stylesheet_directory() . '/view/animated-search.php';
                    ?>
                    <datalist id="search_suggestions">
                        <?php foreach ($suggestions as $suggest): ?>
                            <option value="<?= esc_attr($suggest) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-12 col-sm-6 col-md-auto" style="width: 180px;">
                    <select name="filter_category" id="filter_category"
                        class="form-select form-select-sm filter-select-custom staggered-dropdown"
                        style="border-radius: 10px; height: 38px;">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?= esc_attr($cat) ?>" <?= $filter_category == $cat ? 'selected' : '' ?>>
                                <?= esc_html($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                    <button class="btn btn-primary" type="submit"
                        style="background: #1e40af; border-color: #1e40af; font-weight: 700; border-radius: 10px; padding: 8px 18px;"><i
                            class="fa-solid fa-magnifying-glass me-1"></i> Filter</button>
                    <?php $reset_url = remove_query_arg(['device_search', 'filter_category', 'paged', 'sort', 'order']); ?>
                    <a href="<?= esc_url($reset_url) ?>" class="btn btn-outline-secondary btn-reset-modern"
                        style="border-radius: 10px; font-weight: 600; padding: 8px 16px;">Reset</a>
                </div>
            </div>
        </form>

        <!-- ===== ACTIVE MAINTENANCE DEVICES PANEL ===== -->
        <div class="active-maintenance-panel slide-up mb-4"
            style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 24px; padding: 24px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);">

            <!-- Panel Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3 pb-3"
                style="border-bottom: 1.5px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-3">
                    <div
                        style="width: 46px; height: 46px; border-radius: 16px; background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 6px 18px rgba(30, 64, 175, 0.3);">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <h3
                            style="margin: 0; font-weight: 800; color: #0f172a; font-size: 1.25rem; letter-spacing: -0.02em;">
                            Active Maintenance Devices
                        </h3>
                        <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">
                            Total <strong style="color: #1e40af;"><?= count($all_active_maintenance) ?></strong>
                            item(s) currently in maintenance
                        </span>
                    </div>
                </div>
                <div>
                    <span class="badge d-inline-flex align-items-center gap-2"
                        style="background: #eff6ff; color: #1e40af; border: 1.5px solid #bfdbfe; font-size: 0.85rem; padding: 8px 18px; border-radius: 9999px; font-weight: 700; box-shadow: 0 2px 8px rgba(30, 64, 175, 0.06);">
                        <i class="fa-solid fa-circle-notch fa-spin text-primary"></i> In Maintenance
                        (<?= count($all_active_maintenance) ?>)
                    </span>
                </div>
            </div>

            <!-- Mobile Only View (3 Cards Initial + Load More +3 Cards) -->
            <div class="mobile-only-container">
                <?php if (!empty($all_active_maintenance)): ?>
                    <?php
                    $dev_action_nonce = wp_create_nonce('device_action_nonce');
                    foreach ($all_active_maintenance as $idx => $item):
                        $deptAbbr = stock_supply_get_dept_abbr($item->Department ?? '');
                        ?>
                        <div class="mobile-maintenance-card slide-up">
                            <!-- Header: Title, Category Pill & Status Badge -->
                            <div class="mobile-maint-header">
                                <div class="mobile-maint-title-area">
                                    <div class="mobile-maint-title">
                                        <?= esc_html($item->Brand) ?>             <?= esc_html(!empty($item->Model) ? $item->Model : '') ?>
                                    </div>
                                    <div class="mobile-maint-meta">
                                        <span class="maint-category-pill"><?= esc_html($item->Category) ?></span>
                                        <span>SN: <?= esc_html(!empty($item->SerialNumber) ? $item->SerialNumber : '-') ?></span>
                                    </div>
                                </div>
                                <div class="maint-status-badge">
                                    <i class="fa-solid fa-screwdriver-wrench"></i> Maintenance
                                </div>
                            </div>

                            <!-- Body: Owner, Device ID, Sent Date & Issue -->
                            <div class="mobile-maint-body">
                                <div class="maint-info-row">
                                    <span class="maint-info-label"><i class="fa-solid fa-user" style="color: #6366f1;"></i>
                                        Owner</span>
                                    <span
                                        class="maint-info-value"><?= esc_html(formatName(stock_supply_format_owner_with_dept($item->Owner, $item->Department))) ?></span>
                                </div>
                                <div class="maint-info-row">
                                    <span class="maint-info-label"><i class="fa-solid fa-barcode" style="color: #64748b;"></i>
                                        Device ID</span>
                                    <span class="maint-info-value"><code
                                            class="maint-dev-id"><?= esc_html($item->DeviceID) ?></code></span>
                                </div>
                                <div class="maint-info-row">
                                    <span class="maint-info-label"><i class="fa-regular fa-clock" style="color: #d97706;"></i> Sent
                                        to Repair</span>
                                    <span class="maint-info-value"
                                        style="color: #d97706;"><?= esc_html(stock_supply_format_maint_date($item->RepairDate, $item->CreatedAt ?? null)) ?></span>
                                </div>

                                <!-- Photo Preview if available -->
                                <?php if (!empty($item->Photo)): ?>
                                    <div class="maint-info-row" style="align-items: flex-start; margin-top: 8px;">
                                        <span class="maint-info-label"><i class="fa-solid fa-image" style="color: #10b981;"></i>
                                            Photo</span>
                                        <span class="maint-info-value">
                                            <a href="<?= esc_url($item->Photo) ?>" target="_blank">
                                                <img src="<?= esc_url($item->Photo) ?>"
                                                    style="width: 70px; height: 50px; object-fit: cover; border-radius: 8px; border: 1.5px solid #e2e8f0;">
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Issue Box -->
                                <div class="mobile-maint-issue-box">
                                    <div class="issue-box-title">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Issue / Repair Reason
                                    </div>
                                    <div class="issue-box-content">
                                        <?= esc_html($item->Details ?: 'No additional details specified') ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mobile-maint-actions">
                                <a href="?view=<?= esc_attr($item->DeviceID) ?>" class="maint-btn maint-btn-secondary">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Details
                                </a>
                                <button type="button" data-device="<?= esc_attr(wp_json_encode([
                                    'id' => $item->DeviceID,
                                    'brand' => $item->Brand ?? '',
                                    'model' => $item->Model ?? '',
                                    'category' => $item->Category ?? '',
                                    'serialNumber' => $item->SerialNumber ?? '',
                                    'owner' => $item->Owner ?? '',
                                    'department' => $item->Department ?? '',
                                    'details' => $item->Details ?? '',
                                    'repairDate' => $item->RepairDate ?? '',
                                    'nonce' => $dev_action_nonce
                                ])) ?>" onclick="handleReturnMaintenanceClick(this)"
                                    class="maint-btn maint-btn-primary" style="border: none; cursor: pointer;">
                                    <i class="fa-solid fa-circle-check me-1"></i> Done & Return
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Desktop View Grid of Maintenance Device Cards -->
            <div class="desktop-only-container">
                <?php if (!empty($all_active_maintenance)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
                        <?php
                        $dev_action_nonce = wp_create_nonce('device_action_nonce');
                        foreach ($all_active_maintenance as $idx => $item):
                            $is_latest = ($idx === 0 && empty($search));
                            $is_recent = !empty($item->RepairDate) && strtotime($item->RepairDate) >= strtotime('-7 days') && strtotime($item->RepairDate) <= (current_time('timestamp') + 86400);
                            $delay = min(0.6, $idx * 0.05);
                            ?>
                            <div class="maint-card slide-up"
                                style="animation-delay: <?= $delay ?>s; background: <?= ($is_latest || $is_recent) ? 'linear-gradient(180deg, #ffffff 0%, #fffbeb 100%)' : '#ffffff' ?>; border: <?= $is_latest ? '2px solid #ea580c' : ($is_recent ? '1.5px solid #f97316' : '1.5px solid #e2e8f0') ?>; border-radius: 20px; padding: 22px; box-shadow: <?= $is_latest ? '0 10px 28px rgba(234, 88, 12, 0.16)' : '0 4px 16px rgba(15, 23, 42, 0.04)' ?>; position: relative; transition: all 0.28s ease; overflow: hidden; display: flex; flex-direction: column; height: 100%; box-sizing: border-box;"
                                onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 14px 32px rgba(30, 64, 175, 0.12)';"
                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='<?= $is_latest ? '0 10px 28px rgba(234, 88, 12, 0.16)' : '0 4px 16px rgba(15, 23, 42, 0.04)' ?>';">

                                <!-- Top subtle accent bar -->
                                <div
                                    style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: <?= $is_latest ? 'linear-gradient(90deg, #ef4444, #ea580c)' : ($is_recent ? 'linear-gradient(90deg, #f97316, #f59e0b)' : 'linear-gradient(90deg, #1e40af, #3b82f6)') ?>;">
                                </div>

                                <!-- Badges for latest / recent -->
                                <?php if ($is_latest): ?>
                                    <div
                                        style="position: absolute; top: 14px; right: 16px; background: linear-gradient(135deg, #ef4444 0%, #ea580c 100%); color: #ffffff; font-size: 0.68rem; font-weight: 800; padding: 4px 12px; border-radius: 9999px; box-shadow: 0 3px 10px rgba(239, 68, 68, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-fire me-1"></i> Latest Sent
                                    </div>
                                <?php elseif ($is_recent): ?>
                                    <div
                                        style="position: absolute; top: 14px; right: 16px; background: linear-gradient(135deg, #f97316 0%, #d97706 100%); color: #ffffff; font-size: 0.68rem; font-weight: 800; padding: 4px 12px; border-radius: 9999px; box-shadow: 0 3px 8px rgba(249, 115, 22, 0.3);">
                                        <i class="fa-solid fa-sparkles me-1"></i> Sent to Repair This Week
                                    </div>
                                <?php endif; ?>

                                <!-- Category and Device ID -->
                                <div class="d-flex align-items-center gap-2 mb-2" style="margin-top: 4px;">
                                    <span class="badge bg-light text-secondary border px-2 py-1"
                                        style="font-size: 0.72rem; border-radius: 6px; font-weight: 700; text-transform: uppercase;">
                                        <?= esc_html($item->Category) ?>
                                    </span>
                                    <span style="font-weight: 800; color: #1e40af; font-size: 0.95rem; letter-spacing: 0.5px;">
                                        <?= esc_html($item->DeviceID) ?>
                                    </span>
                                </div>

                                <!-- Device Title (Brand + Model) -->
                                <h4
                                    style="font-weight: 800; color: #0f172a; margin-bottom: 4px; font-size: 1.15rem; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= esc_html($item->Brand) ?>             <?= esc_html(!empty($item->Model) ? $item->Model : '') ?>
                                </h4>

                                <!-- Serial Number -->
                                <?php if (!empty($item->SerialNumber)): ?>
                                    <div style="font-size: 0.82rem; color: #64748b; margin-bottom: 10px; font-weight: 500;">
                                        SN: <?= esc_html($item->SerialNumber) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Owner & Department -->
                                <div class="d-flex align-items-center gap-3 mb-2 flex-wrap"
                                    style="font-size: 0.86rem; color: #334155; font-weight: 500;">
                                    <?php if (!empty($item->Owner)): ?>
                                        <div>
                                            <i class="fa-solid fa-user me-1" style="color: #6366f1;"></i>
                                            <strong><?= esc_html(formatName(stock_supply_format_owner_with_dept($item->Owner, $item->Department))) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Repair Date -->
                                <div
                                    style="font-size: 0.83rem; font-weight: 600; color: #d97706; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-regular fa-clock"></i>
                                    Sent to Repair:
                                    <?= esc_html(stock_supply_format_maint_date($item->RepairDate, $item->CreatedAt ?? null)) ?>
                                </div>

                                <!-- Photo Preview if available -->
                                <?php if (!empty($item->Photo)): ?>
                                    <div style="margin-bottom: 12px;">
                                        <a href="<?= esc_url($item->Photo) ?>" target="_blank"
                                            style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #0284c7; text-decoration: none; font-weight: 600;">
                                            <img src="<?= esc_url($item->Photo) ?>"
                                                style="width: 50px; height: 38px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                            <span><i class="fa-solid fa-camera"></i> View Condition Photo</span>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <!-- Repair Reason / Details Box -->
                                <div
                                    style="font-size: 0.86rem; color: #92400e; background: #fffbeb; border: 1.5px solid #fde047; padding: 12px 14px; border-radius: 14px; font-weight: 600; line-height: 1.45; margin-bottom: 18px; flex-grow: 1;">
                                    <div
                                        style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; color: #b45309; margin-bottom: 2px; font-weight: 700;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Issue / Repair Reason
                                    </div>
                                    <?= esc_html($item->Details ?: 'No additional details specified') ?>
                                </div>

                                <!-- Action Buttons (Fixed / Pinned to bottom of card) -->
                                <div class="d-flex gap-2 justify-content-end pt-3 mt-auto"
                                    style="border-top: 1px dashed #e2e8f0; width: 100%;">
                                    <a href="?view=<?= esc_attr($item->DeviceID) ?>" class="btn btn-sm btn-outline-secondary"
                                        style="border-radius: 10px; font-weight: 600; font-size: 0.83rem; padding: 7px 14px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                        <i class="fa-solid fa-magnifying-glass"></i> Details
                                    </a>
                                    <button type="button" data-device="<?= esc_attr(wp_json_encode([
                                        'id' => $item->DeviceID,
                                        'brand' => $item->Brand ?? '',
                                        'model' => $item->Model ?? '',
                                        'category' => $item->Category ?? '',
                                        'serialNumber' => $item->SerialNumber ?? '',
                                        'owner' => $item->Owner ?? '',
                                        'department' => $item->Department ?? '',
                                        'details' => $item->Details ?? '',
                                        'repairDate' => $item->RepairDate ?? '',
                                        'nonce' => $dev_action_nonce
                                    ])) ?>" onclick="handleReturnMaintenanceClick(this)"
                                        class="btn btn-sm btn-success"
                                        style="border-radius: 10px; font-weight: 600; font-size: 0.83rem; padding: 7px 16px; display: inline-flex; align-items: center; gap: 6px; background: #16a34a; border-color: #16a34a; color: #ffffff; cursor: pointer;">
                                        <i class="fa-solid fa-circle-check"></i> Done & Return
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center py-5"
                        style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 20px; padding: 44px 20px;">
                        <div
                            style="width: 64px; height: 64px; border-radius: 50%; background: #eff6ff; color: #1e40af; display: inline-flex; align-items: center; justify-content: center; font-size: 1.85rem; margin-bottom: 16px;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h4 style="font-weight: 800; color: #0f172a; margin-bottom: 6px;">No Devices Currently in Maintenance
                        </h4>
                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 0;">
                            All devices in the system are currently available or actively in use.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function handleReturnMaintenanceClick(btn) {
            if (!btn) return;
            try {
                const raw = btn.getAttribute('data-device');
                const data = JSON.parse(raw);
                if (typeof confirmReturnFromMaintenance === 'function') {
                    confirmReturnFromMaintenance(data, data.nonce || '');
                } else {
                    console.error('confirmReturnFromMaintenance not ready');
                    if (confirm('Confirm return device ' + (data.id || '') + ' from maintenance?')) {
                        const targetAction = data.owner && data.owner !== '-' ? 'return_to_owner' : 'available';
                        const url = new URL(window.location.href);
                        url.searchParams.set(targetAction, data.id);
                        if (data.nonce) url.searchParams.set('_wpnonce', data.nonce);
                        window.location.href = url.toString();
                    }
                }
            } catch (err) {
                console.error('Error parsing device data:', err);
            }
        }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('device_crud_mainten', 'device_crud_maintenance');
