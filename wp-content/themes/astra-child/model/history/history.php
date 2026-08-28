<?php

function form_history()
{
    global $wpdb;

    $table_history = 'History_new';
    $table_category = 'Categories';


    $action_result = handle_device_actions();
    if ($action_result) {
        return $action_result;
    }


    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;




    $search = isset($_GET['device_search']) ? stock_supply_parse_search_query($_GET['device_search']) : '';
    $filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';
    $filter_action = isset($_GET['filter_action']) ? trim($_GET['filter_action']) : '';

    $params = [];
    $where_clauses = [];

    // Base filters (search & category) for global action counts
    $base_params = [];
    $base_where_clauses = [];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_clause = "(H.Description LIKE %s OR H.Action LIKE %s OR H.user_email LIKE %s OR H.Owner LIKE %s OR C.CategoryName LIKE %s)";
        $where_clauses[] = $search_clause;
        $params = array_merge($params, array_fill(0, 5, $like));
        $base_where_clauses[] = $search_clause;
        $base_params = array_merge($base_params, array_fill(0, 5, $like));
    }
    if (!empty($filter_category)) {
        $where_clauses[] = "C.CategoryName = %s";
        $params[] = $filter_category;
        $base_where_clauses[] = "C.CategoryName = %s";
        $base_params[] = $filter_category;
    }
    if (!empty($filter_action) && strtolower($filter_action) !== 'all') {
        $where_clauses[] = "TRIM(H.Action) = %s";
        $params[] = $filter_action;
    }

    $search_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    $base_search_sql = !empty($base_where_clauses) ? 'WHERE ' . implode(' AND ', $base_where_clauses) : '';

    // Total base count (for Total chip)
    $total_base_items = $wpdb->get_var(
        $wpdb->prepare("
            SELECT COUNT(*) FROM $table_history AS H
            LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
            $base_search_sql
        ", ...$base_params)
    );

    // Global action counts across all records matching base search/category
    $action_counts_raw = $wpdb->get_results(
        $wpdb->prepare("
            SELECT TRIM(H.Action) as action_name, COUNT(*) as cnt 
            FROM $table_history AS H
            LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
            $base_search_sql
            GROUP BY TRIM(H.Action)
            ORDER BY cnt DESC
        ", ...$base_params)
    );

    $action_counts = [];
    foreach ($action_counts_raw as $ac) {
        if (!empty($ac->action_name)) {
            $action_counts[$ac->action_name] = intval($ac->cnt);
        }
    }

    // COUNT total items for current active filter (with action filter)
    $total_items = $wpdb->get_var(
        $wpdb->prepare("
            SELECT COUNT(*) FROM $table_history AS H
            LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
            $search_sql
        ", ...$params)
    );
    $total_pages = ceil($total_items / $page);

    // get results
    $order_sql = "ORDER BY H.HistoryID DESC";

    $rows = $wpdb->get_results(
        $wpdb->prepare("
        SELECT 
            H.HistoryID, H.DeviceID, H.Action, H.Date, H.Description, H.user_email, H.Owner, H.Photo,
            C.CategoryName, S.StatusName AS Status,
            dep.DepartmentName AS Dept, pos.PositionName AS Position,
            u_dep.DepartmentName AS UserDept
        FROM $table_history AS H
        LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
        LEFT JOIN Devices AS D ON H.DeviceID = D.DeviceID
        LEFT JOIN Statuses AS S ON D.StatusID = S.StatusID
        LEFT JOIN (
            SELECT Nickname, FirstName, LastName, DepartmentID, PositionID 
            FROM Owners 
            GROUP BY Nickname, FirstName, LastName, DepartmentID, PositionID
        ) AS o ON (H.Owner = o.Nickname OR CONCAT(o.FirstName, ' ', o.LastName) = H.Owner OR H.Owner = o.FirstName OR H.Owner LIKE CONCAT(o.Nickname, ' (%%'))
        LEFT JOIN Departments AS dep ON o.DepartmentID = dep.DepartmentID
        LEFT JOIN Positions AS pos ON o.PositionID = pos.PositionID
        LEFT JOIN (
            SELECT Email, user_email, DepartmentID 
            FROM Owners 
            WHERE Email != '' OR user_email != ''
            GROUP BY Email, user_email, DepartmentID
        ) AS u_o ON (H.user_email = u_o.Email OR H.user_email = u_o.user_email)
        LEFT JOIN Departments AS u_dep ON u_o.DepartmentID = u_dep.DepartmentID
        $search_sql
        GROUP BY H.HistoryID
        $order_sql
        LIMIT %d OFFSET %d
    ", ...array_merge($params, [$page, $offset]))
    );

    // Suggestion and Options
    $all_categories = $wpdb->get_col("SELECT DISTINCT CategoryName FROM $table_category WHERE CategoryName != '' ORDER BY CategoryName");
    $all_actions = $wpdb->get_col("SELECT DISTINCT TRIM(Action) FROM $table_history WHERE Action != '' ORDER BY Action");
    $suggestions = $wpdb->get_col("SELECT DISTINCT Action FROM $table_history LIMIT 50");
    ob_start();

    ?>

    <div class="container-fluid px-3">
        <style>
            .history-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }

            .history-header i {
                color: #6366f1;
                font-size: 1.5rem;
            }

            .history-header h2 {
                font-size: 1.5rem;
                font-weight: 700;
                color: #1e293b;
                margin: 0;
            }

            .table-wrapper {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.5);
                border-radius: 20px;
                padding: 20px;
                box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
                margin-bottom: 40px;
                overflow-x: auto;
            }

            .table-custom {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }

            .table-custom th {
                background: transparent;
                padding: 16px;
                text-align: left;
                font-size: 0.85rem;
                color: #64748b;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                border-bottom: 2px solid #e2e8f0;
            }

            .table-custom td {
                padding: 16px;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
                font-size: 0.95rem;
                transition: all 0.2s ease;
            }

            .table-custom tbody tr {
                transition: all 0.3s ease;
            }

            .table-custom tbody tr:hover {
                background: rgba(255, 255, 255, 0.9);
                transform: scale(1.005);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
                border-radius: 12px;
            }

            .table-custom tbody tr:hover td:first-child {
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
            }

            .table-custom tbody tr:hover td:last-child {
                border-top-right-radius: 12px;
                border-bottom-right-radius: 12px;
            }

            .badge-history {
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 0.85rem;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                white-space: normal;
                word-break: break-word;
                letter-spacing: 0.3px;
                border: 1px solid rgba(0, 0, 0, 0.05);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }

            .badge-history::before {
                content: '';
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .badge-add {
                background: #dcfce7;
                color: #166534;
            }

            .badge-add::before {
                background: #22c55e;
            }

            .badge-update {
                background: #fef3c7;
                color: #b45309;
            }

            .badge-update::before {
                background: #f59e0b;
            }

            .badge-delete {
                background: #fee2e2;
                color: #991b1b;
            }

            .badge-delete::before {
                background: #ef4444;
            }

            .badge-receive {
                background: #e0e7ff;
                color: #3730a3;
            }

            .badge-receive::before {
                background: #4f46e5;
            }

            .badge-maintenance {
                background: #ffedd5;
                color: #9a3412;
            }

            .badge-maintenance::before {
                background: #ea580c;
            }

            .badge-return {
                background: #f3e8ff;
                color: #6b21a8;
            }

            .badge-return::before {
                background: #9333ea;
            }

            .badge-default {
                background: #f1f5f9;
                color: #475569;
            }

            .badge-default::before {
                background: #94a3b8;
            }
        </style>

        <link rel="stylesheet" href="<?= get_stylesheet_directory_uri() ?>/css/device_timeline.css?v=<?= time() ?>">
        <script src="<?= get_stylesheet_directory_uri() ?>/js/device_timeline.js?v=<?= time() ?>"></script>

        <div class="history-header d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h2 class="m-0">System History</h2>
            </div>
        </div>
        <form method="GET" action="" id="advanced-filter-form">
            <?php
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['device_search', 'filter_category', 'filter_action', 'filter_status', 'filter_brand', 'filter_department', 'paged'])) {
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
                    $search_placeholder = 'Search History...';
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
                    <select name="filter_category" id="filter_category" class="form-select form-select-sm filter-select-custom staggered-dropdown" style="height: 38px; border-radius: 10px;">
                        <option value="">All Categories</option>
                        <?php foreach ($all_categories as $cat): ?>
                            <option value="<?= esc_attr($cat) ?>" <?= $filter_category == $cat ? 'selected' : '' ?>>
                                <?= esc_html($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-auto" style="width: 180px;">
                    <select name="filter_action" id="filter_action" class="form-select form-select-sm filter-select-custom staggered-dropdown" style="height: 38px; border-radius: 10px;">
                        <option value="">All Actions</option>
                        <?php foreach ($all_actions as $act): ?>
                            <option value="<?= esc_attr($act) ?>" <?= strtolower($filter_action) === strtolower($act) ? 'selected' : '' ?>>
                                <?= esc_html($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                    <button class="btn-filter-modern flex-grow-1" type="submit"><i class="fa-solid fa-filter"></i>
                        Filter</button>
                    <?php $reset_url = remove_query_arg(['device_search', 'filter_category', 'filter_action', 'paged']); ?>
                    <a href="<?= esc_url($reset_url) ?>" class="btn-reset-modern">Reset</a>
                </div>
            </div>
        </form>

        <div class="dtl-history-timeline-wrapper mt-4">
            <?php
            // Action map for timeline icons and colors
            $action_map = [
                'add' => ['class' => 'dtl-action-add', 'icon' => 'fa-plus'],
                'update' => ['class' => 'dtl-action-update', 'icon' => 'fa-pen'],
                'delete' => ['class' => 'dtl-action-delete', 'icon' => 'fa-trash'],
                'receive' => ['class' => 'dtl-action-receive', 'icon' => 'fa-box'],
                'return' => ['class' => 'dtl-action-return', 'icon' => 'fa-rotate-left'],
                'maintenance' => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-screwdriver-wrench'],
                'repair' => ['class' => 'dtl-action-maintenance', 'icon' => 'fa-wrench'],
                'retired' => ['class' => 'dtl-action-retired', 'icon' => 'fa-circle-xmark'],
                'audit' => ['class' => 'dtl-action-audit', 'icon' => 'fa-clipboard-check']
            ];

            if (!function_exists('dtl_get_action_info_history')) {
                function dtl_get_action_info_history($action, $map)
                {
                    $lower = strtolower($action);
                    foreach ($map as $key => $info) {
                        if (strpos($lower, $key) !== false)
                            return $info;
                    }
                    return ['class' => 'dtl-action-default', 'icon' => 'fa-circle'];
                }
            }

            // Group rows by month/year
            $grouped = [];
            foreach ($rows as $row) {
                $dt = new DateTime($row->Date);
                $key = $dt->format('F Y');
                $grouped[$key][] = $row;
            }
            ?>
            <div class="dtl-timeline-wrap active dtl-server-filtered">
                <!-- Stats -->
                <?php if (!empty($action_counts)): ?>
                    <div class="dtl-stats">
                        <?php 
                        $is_total_active = empty($filter_action) || strtolower($filter_action) === 'all';
                        $total_url = remove_query_arg('paged', add_query_arg(['filter_action' => null]));
                        ?>
                        <a href="<?= esc_url($total_url) ?>" class="dtl-stat-chip <?= $is_total_active ? 'active' : '' ?>" role="button">
                            <i class="fa-solid fa-list-check"></i> Total
                            <span class="dtl-stat-count"><?= number_format($total_base_items) ?></span>
                        </a>
                        <?php foreach ($action_counts as $act => $cnt): 
                            $is_act_active = (strtolower($filter_action) === strtolower($act));
                            $act_url = remove_query_arg('paged', add_query_arg(['filter_action' => $act]));
                        ?>
                            <a href="<?= esc_url($act_url) ?>" class="dtl-stat-chip <?= $is_act_active ? 'active' : '' ?>" role="button">
                                <?= esc_html($act) ?>
                                <span class="dtl-stat-count"><?= number_format($cnt) ?></span>
                            </a>
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
                                    $info = dtl_get_action_info_history($row->Action, $action_map);
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
                                                    <span class="dtl-detail-value fw-bold text-dark">
                                                        <?= wp_kses_post(stock_supply_format_history_description($row->Description, $row->Action, $row->DeviceID)) ?>
                                                    </span>
                                                </div>
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">User Action</span>
                                                    <span class="dtl-detail-value dtl-user-email" style="text-transform: lowercase !important;"><?= esc_html(strtolower($row->user_email ?: '-')) ?></span>
                                                </div>
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">Owner</span>
                                                    <span class="dtl-detail-value"><?= esc_html(stock_supply_format_nickname_with_initial('', '', '', $row->Owner)) ?></span>
                                                </div>
                                                <div class="dtl-detail-row">
                                                    <span class="dtl-detail-label">Department</span>
                                                    <span class="dtl-detail-value"><?= esc_html($row->Dept ?: ($row->UserDept ?: '-')) ?></span>
                                                </div>
                                                <?php if (!empty($row->Photo)): ?>
                                                    <div class="dtl-detail-row align-items-center">
                                                        <span class="dtl-detail-label">Photo</span>
                                                        <span class="dtl-detail-value">
                                                            <img src="<?= esc_url($row->Photo) ?>" class="dtl-photo-thumb"
                                                                onclick="event.stopPropagation(); window.openPhotoModal('<?= esc_url($row->Photo) ?>');"
                                                                title="Click to view full photo">
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="dtl-detail-row mt-2 border-0">
                                                    <a href="?view=<?= esc_attr($row->DeviceID) ?>" class="dtl-view-btn" style="text-decoration: none !important;">
                                                        <i class="fa-solid fa-eye"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <script>
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
        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            <ul class="pagination">
                <?php
                if ($total_pages > 1) {
                    $query_str = http_build_query(array_merge($_GET, ['paged' => null]));
                    $range = 2;
                    $start = max(1, $current_page - $range);
                    $end = min($total_pages, $current_page + $range);

                    // Previous Button
                    echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
                    echo '<a class="page-link" style="text-decoration: none;" href="?' . $query_str . '&paged=' . ($current_page - 1) . '">Previous</a>';
                    echo '</li>';

                    // First page
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=1">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    // Page numbers
                    for ($i = $start; $i <= $end; $i++) {
                        $active = $i === $current_page ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">';
                        echo '<a class="page-link" style="text-decoration: none;" href="?' . $query_str . '&paged=' . $i . '">' . $i . '</a>';
                        echo '</li>';
                    }

                    // Last page
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" style="text-decoration: none;" href="?' . $query_str . '&paged=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }

                    // Next Button
                    echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
                    echo '<a class="page-link" style="text-decoration: none;" href="?' . $query_str . '&paged=' . ($current_page + 1) . '">Next</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>



    <script>
        function toggleRow(id) {
            var detailsRow = document.getElementById('details-' + id);
            var btn = document.getElementById('btn-' + id);
            if (!detailsRow || !btn) return;
            if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                detailsRow.style.display = 'table-row';
                btn.textContent = '▲';
            } else {
                detailsRow.style.display = 'none';
                btn.textContent = '▼';
            }
        }
    </script>
    <?php

    return ob_get_clean();
}

add_shortcode('form_history', 'form_history');
