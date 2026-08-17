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

    if (isset($_GET['delete'])) {
        if (!is_user_logged_in() || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_history_nonce')) {
            return '<p>Security check failed.</p>';
        }
        $history_id = sanitize_text_field($_GET['delete']);
        $wpdb->delete($table_history, ['HistoryID' => $history_id], ['%s']);
    }

    $device_id = $device_id ?: ($_GET['view'] ?? '');
    if (empty($device_id))
        return '<p>No Device ID provided.</p>';

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

        // Exclude CategoryName if not present
        $params = array_merge($params, array_fill(0, 5, $like));
    }

    $per_page = 15;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Count total items
    $sql_count = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_history} h $where",
        ...$params
    );
    $total_items = $wpdb->get_var($sql_count);
    $total_pages = ceil($total_items / $per_page);

    // Fetch rows
    $sql_rows = $wpdb->prepare(
        "SELECT * FROM {$table_history} h $where ORDER BY h.Date DESC LIMIT %d OFFSET %d",
        ...array_merge($params, [$per_page, $offset])
    );
    $rows = $wpdb->get_results($sql_rows);

    // Fetch suggestions
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
            margin-top: -30px;
            color: #111827;
        }

        @media (max-width: 768px) {
            .vd-title {
                padding-top: 30px;
            }
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

        .vd-status-Available {
            background: #ecfdf5;
            color: #059669;
        }

        .vd-status-Available .vd-status-dot {
            background: #10b981;
        }

        .vd-status-InUse {
            background: #fef2f2;
            color: #dc2626;
        }

        .vd-status-InUse .vd-status-dot {
            background: #ef4444;
        }

        .vd-status-Maintenance {
            background: #fffbeb;
            color: #d97706;
        }

        .vd-status-Maintenance .vd-status-dot {
            background: #f59e0b;
        }

        .vd-status-Retired {
            background: #f3f4f6;
            color: #374151;
        }

        .vd-status-Retired .vd-status-dot {
            background: #6b7280;
        }

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
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
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
        @keyframes fadeDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <?php
    $cat_slug = !empty($device->CategoryName) ? sanitize_title($device->CategoryName) : 'formdevice';
    if (!in_array($cat_slug, ['laptop', 'monitor', 'accessories', 'formdevice'])) {
        $cat_slug = 'formdevice';
    }
    $category_table_url = home_url('/' . $cat_slug . '/');
    ?>

    <div class="view-details-container px-3 mt-4">
        <!-- Header -->
        <div class="vd-header">
            <div>
                <h2 class="vd-title">Device Details</h2>
                <div class="vd-subtitle">ID: <?= esc_html($device->DeviceID) ?> &nbsp;&bull;&nbsp;
                    <?= esc_html($device->Model) ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary vd-btn" onclick="
                        const ref = document.referrer;
                        if (ref && ref.includes(window.location.host) && !ref.includes('receive=') && !ref.includes('edit=') && !ref.includes('action=') && !ref.includes('add=') && !ref.includes('delete=')) {
                            window.location.href = ref;
                        } else {
                            window.location.href = '<?= esc_url($category_table_url) ?>';
                        }
                    ">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-dark vd-btn"
                    onclick="printDeviceLabels([{ id: '<?= esc_js($device->DeviceID) ?>', sn: '<?= esc_js($device->SerialNumber) ?>' }])">
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
                <div class="vd-info-value">
                    <?= !empty($device->SerialNumber) ? esc_html($device->SerialNumber) : '<span class="text-muted">-</span>' ?>
                </div>
            </div>
            <div class="vd-info-card">
                <div class="vd-info-label"><i class="fa-solid fa-circle-info"></i> Status</div>
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
        </div>

        <!-- History Section -->
        <div class="vd-history-section dtl-show-search">
            <div class="vd-history-header">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h3 class="vd-history-title"><i class="fa-solid fa-clock-rotate-left text-muted"></i> History Log</h3>
                </div>
            </div>

            <!-- ========== TIMELINE VIEW ========== -->
            <?php
            // Build action-to-class map
            $action_map = [
                'receive' => ['class' => 'dtl-action-receive', 'icon' => 'fa-box-open'],
                'add' => ['class' => 'dtl-action-receive', 'icon' => 'fa-plus-circle'],
                'create' => ['class' => 'dtl-action-receive', 'icon' => 'fa-plus-circle'],
                'edit' => ['class' => 'dtl-action-edit', 'icon' => 'fa-pen'],
                'update' => ['class' => 'dtl-action-edit', 'icon' => 'fa-pen-to-square'],
                'maintenance' => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-wrench'],
                'repair' => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-screwdriver-wrench'],
                'transfer' => ['class' => 'dtl-action-transfer', 'icon' => 'fa-right-left'],
                'move' => ['class' => 'dtl-action-transfer', 'icon' => 'fa-truck'],
                'delete' => ['class' => 'dtl-action-delete', 'icon' => 'fa-trash'],
                'remove' => ['class' => 'dtl-action-delete', 'icon' => 'fa-circle-minus'],
                'status' => ['class' => 'dtl-action-status', 'icon' => 'fa-circle-dot'],
                'change' => ['class' => 'dtl-action-status', 'icon' => 'fa-arrows-rotate'],
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

            // Group rows by month/year
            $grouped = [];
            foreach ($rows as $row) {
                $dt = new DateTime($row->Date);
                $key = $dt->format('F Y');
                $grouped[$key][] = $row;
            }

            // Count actions for stats
            $action_counts = [];
            foreach ($rows as $row) {
                $a = trim($row->Action);
                if (!isset($action_counts[$a]))
                    $action_counts[$a] = 0;
                $action_counts[$a]++;
            }
            ?>
            <div class="dtl-timeline-wrap active">
                <!-- Stats -->
                <!-- Stats -->
                <?php if (!empty($action_counts)): ?>
                    <div class="dtl-stats">
                        <div class="dtl-stat-chip active" data-filter-action="all" role="button" tabindex="0">
                            <i class="fa-solid fa-list-check"></i> Total
                            <span class="dtl-stat-count"><?= count($rows) ?></span>
                        </div>
                        <?php foreach ($action_counts as $act => $cnt): ?>
                            <div class="dtl-stat-chip" data-filter-action="<?= esc_attr(strtolower(trim($act))) ?>" role="button"
                                tabindex="0">
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
                                    <div class="dtl-node <?= $info['class'] ?> dtl-visible"
                                        data-action="<?= esc_attr(strtolower(trim($row->Action))) ?>">
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
                                                    <span
                                                        class="dtl-detail-value"><?= esc_html(stock_supply_format_owner_with_dept($row->Owner ?? '', $row->Department ?? '')) ?></span>
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

            <!-- Photo Lightbox Modal Script -->

            <script>
                window.openPhotoModal = function (imgUrl) {
                    if (!imgUrl) return;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '<i class="fa-solid fa-camera" style="color:#6366f1; margin-right:8px;"></i> Equipment Condition Photo',
                            imageUrl: imgUrl,
                            imageAlt: 'Equipment Condition Photo',
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
                                    <img id="photo_lightbox_img" class="photo-lightbox-img" src="" alt="Equipment Condition Photo">
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

    <link rel="stylesheet" href="<?= get_stylesheet_directory_uri() ?>/css/device_timeline.css?v=<?= time() ?>">
    <script src="<?= get_stylesheet_directory_uri() ?>/js/device_timeline.js?v=<?= time() ?>"></script>
    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>
    <?php
    return ob_get_clean();
}

add_shortcode('device_view_details', 'device_view_details');
?>