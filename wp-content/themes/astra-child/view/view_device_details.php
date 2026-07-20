<?php
function device_view_details($device_id = null)
{
    global $wpdb;

    $table_device  = 'Devices';
    $table_brand   = 'Brands';
    $table_cat     = 'Categories';
    $table_status  = 'Statuses';
    $table_history = 'History_new';

    if (isset($_GET['delete'])) {
        $history_id = $_GET['delete'];
        $wpdb->delete($table_history, ['HistoryID' => $history_id], ['%s']);
    }

    $device_id = $device_id ?: ($_GET['view'] ?? '');
    if (empty($device_id)) return '<p>No Device ID provided.</p>';

    $device = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, b.BrandName, c.CategoryName, s.StatusName
        FROM {$table_device} d
        LEFT JOIN {$table_brand} b ON d.BrandID = b.BrandID
        LEFT JOIN {$table_cat} c ON d.CategoryID = c.CategoryID
        LEFT JOIN {$table_status} s ON d.StatusID = s.StatusID
        WHERE d.DeviceID = %s
    ", $device_id));

    if (!$device) {
        return '<p>Device not found.</p>';
    }

    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;

    $search = isset($_GET['device_search']) ? trim($_GET['device_search']) : '';

    $where = "WHERE h.DeviceID = %s";
    $params = [$device_id];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= " AND (
        h.Action LIKE %s OR 
        h.Date LIKE %s OR 
        h.Description LIKE %s OR 
        h.user_email LIKE %s OR 
        h.Owner LIKE %s
    )";

        // ไม่รวม CategoryName ถ้าไม่มี
        $params = array_merge($params, array_fill(0, 5, $like));
    }

    $per_page = 15;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // นับจำนวนทั้งหมด
    $sql_count = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_history} h $where",
        ...$params
    );
    $total_items = $wpdb->get_var($sql_count);
    $total_pages = ceil($total_items / $per_page);

    // ดึงรายการข้อมูล
    $sql_rows = $wpdb->prepare(
        "SELECT * FROM {$table_history} h $where ORDER BY h.Date DESC LIMIT %d OFFSET %d",
        ...array_merge($params, [$per_page, $offset])
    );
    $rows = $wpdb->get_results($sql_rows);

    // ดึงคำแนะนำ
    $suggestions = $wpdb->get_col("SELECT DISTINCT StatusName FROM {$table_status} ORDER BY StatusName LIMIT 50");

    ob_start();
?>
    <style>
        /* Base modern styling */
        .view-details-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #111827;
            padding-bottom: 2rem;
        }

        /* Page Header */
        .vd-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            animation: fadeDown 0.4s ease-out;
        }
        .vd-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }
        .vd-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        /* Info Grid */
        .vd-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            animation: fadeIn 0.5s ease-out;
        }
        .vd-info-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .vd-info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        .vd-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .vd-info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            word-break: break-word;
        }

        /* Badges */
        .vd-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .vd-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .vd-status-Available { background: #ecfdf5; color: #059669; }
        .vd-status-Available .vd-status-dot { background: #10b981; }
        .vd-status-InUse { background: #fef2f2; color: #dc2626; }
        .vd-status-InUse .vd-status-dot { background: #ef4444; }
        .vd-status-Maintenance { background: #fffbeb; color: #d97706; }
        .vd-status-Maintenance .vd-status-dot { background: #f59e0b; }
        .vd-status-Retired { background: #f3f4f6; color: #374151; }
        .vd-status-Retired .vd-status-dot { background: #6b7280; }

        /* History Table */
        .vd-history-section {
            animation: slideUp 0.5s ease-out;
        }
        .vd-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .vd-history-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #111827;
        }
        
        .next-table-wrapper {
            background: #ffffff;
            border-radius: 20px;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .next-table {
            margin: 0;
            border-collapse: collapse;
            width: 100%;
        }
        .next-table thead {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        .next-table th {
            color: #6b7280;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border: none;
        }
        .next-table td {
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: middle;
        }
        .next-table tbody tr {
            transition: background-color 0.2s ease;
        }
        .next-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .next-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .vd-desc-text {
            color: #4b5563;
            font-size: 0.95rem;
        }
        
        .vd-date-text {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .vd-action-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            background: #f3f4f6;
            color: #374151;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Controls */
        .vd-search-bar {
            border-radius: 9999px;
            padding-left: 1rem;
            border: 1px solid #d1d5db;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
        }
        
        .vd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
            border-radius: 9999px;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s;
        }
        .vd-btn-icon {
            padding: 0.4rem 1rem;
        }
        
        /* Pagination */
        .pagination .page-link {
            color: #4b5563;
            border: none;
            margin: 0 2px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .pagination .page-item.active .page-link {
            background-color: #111827;
            color: #ffffff;
            font-weight: 600;
        }
        .pagination .page-link:hover:not(.active) {
            background-color: #f3f4f6;
            color: #111827;
        }
        .pagination .page-item.disabled .page-link {
            background-color: transparent;
            color: #9ca3af;
        }
        
        /* Animations */
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <div class="view-details-container px-3 mt-4">
        <!-- Header -->
        <div class="vd-header">
            <div>
                <h2 class="vd-title">Device Details</h2>
                <div class="vd-subtitle">ID: <?= esc_html($device->DeviceID) ?> &nbsp;&bull;&nbsp; <?= esc_html($device->Model) ?></div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary vd-btn" onclick="history.back()">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-dark vd-btn" onclick="printDeviceLabels([{ id: '<?= esc_js($device->DeviceID) ?>', sn: '<?= esc_js($device->SerialNumber) ?>' }])">
                    <i class="fa-solid fa-print"></i> Print Label
                </button>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="vd-info-grid">
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-hashtag"></i> Device ID</div>
                <div class="vd-info-value"><?= esc_html($device->DeviceID) ?></div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-laptop"></i> Category</div>
                <div class="vd-info-value"><?= esc_html($device->CategoryName) ?></div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-tag"></i> Brand</div>
                <div class="vd-info-value"><?= esc_html($device->BrandName) ?></div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-cube"></i> Model</div>
                <div class="vd-info-value"><?= esc_html($device->Model) ?></div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-barcode"></i> Serial Number</div>
                <div class="vd-info-value"><?= !empty($device->SerialNumber) ? esc_html($device->SerialNumber) : '<span class="text-muted">-</span>' ?></div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-circle-info"></i> Status</div>
                <div class="vd-info-value">
                    <?php
                    $statusClass = 'vd-status-Retired';
                    $statusName = $device->StatusName ?? 'Unknown';
                    if (strcasecmp($statusName, 'Available') === 0) $statusClass = 'vd-status-Available';
                    elseif (strcasecmp($statusName, 'In Use') === 0) $statusClass = 'vd-status-InUse';
                    elseif (strcasecmp($statusName, 'Maintenance') === 0) $statusClass = 'vd-status-Maintenance';
                    ?>
                    <div class="vd-status-badge <?= $statusClass ?>">
                        <div class="vd-status-dot"></div>
                        <?= esc_html($statusName) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="vd-history-section">
            <div class="vd-history-header">
                <h3 class="vd-history-title"><i class="fa-solid fa-clock-rotate-left text-muted"></i> History Log</h3>
                
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="view" value="<?= esc_attr($device->DeviceID) ?>">
                    <?php
                    foreach ($_GET as $key => $value) {
                        if (!in_array($key, ['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged', 'view'])) {
                            if (is_array($value)) {
                                foreach ($value as $v) { echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">'; }
                            } else {
                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                            }
                        }
                    }
                    ?>
                    <input type="text" name="device_search" list="search_suggestions"
                        class="form-control form-control-sm vd-search-bar"
                        style="max-width: 250px;"
                        placeholder="Search History..."
                        value="<?= esc_attr($search) ?>">
                    <button type="submit" class="btn btn-sm btn-info vd-btn vd-btn-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <?php $reset_url = remove_query_arg(['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged']); ?>
                    <a href="<?= esc_url($reset_url) ?>" class="btn btn-sm btn-outline-secondary vd-btn vd-btn-icon">Reset</a>
                    
                    <datalist id="search_suggestions">
                        <?php foreach ($suggestions as $suggest): ?>
                            <option value="<?= esc_attr($suggest) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </form>
            </div>

            <div class="table-responsive-xl next-table-wrapper">
                <table class="table next-table">
                    <thead>
                        <tr>
                            <th class="text-start">Action</th>
                            <th class="text-start">Date</th>
                            <th class="text-start">Description</th>
                            <th class="text-start">User</th>
                            <th class="text-start">Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No history logs found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php
                                $date = new DateTime($row->Date, new DateTimeZone('UTC'));
                                $date->setTimezone(new DateTimeZone('Asia/Bangkok'));
                                ?>
                                <tr>
                                    <td class="text-start"><span class="vd-action-pill"><?= esc_html($row->Action) ?></span></td>
                                    <td class="text-start vd-date-text"><?= esc_html($date->format("d/m/Y H:i")) ?></td>
                                    <td class="text-start vd-desc-text">
                                        <?php 
                                            $desc = $row->Description;
                                            $desc = preg_replace('/^Device ID [A-Za-z0-9_-]+\s*/i', '', $desc);
                                            $desc = ucfirst($desc);
                                            echo esc_html($desc);
                                        ?>
                                    </td>
                                    <td class="text-start font-medium"><?= esc_html($row->user_email) ?></td>
                                    <td class="text-start font-medium"><?= esc_html($row->Owner ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

                        // Previous Button
                        echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
                        echo '<a class="page-link" href="?' . $query_str . '&paged=' . ($current_page - 1) . '"><i class="fa-solid fa-chevron-left"></i></a>';
                        echo '</li>';

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=1">1</a></li>';
                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = $i === $current_page ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            echo '<a class="page-link" href="?' . $query_str . '&paged=' . $i . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=' . $total_pages . '">' . $total_pages . '</a></li>';
                        }

                        // Next Button
                        echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
                        echo '<a class="page-link" href="?' . $query_str . '&paged=' . ($current_page + 1) . '"><i class="fa-solid fa-chevron-right"></i></a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>

    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>
<?php
    return ob_get_clean();
}

add_shortcode('device_view_details', 'device_view_details');
?>
