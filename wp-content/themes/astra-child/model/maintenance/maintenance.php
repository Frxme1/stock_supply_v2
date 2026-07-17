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



    // section search
    $search = isset($_GET['device_search']) ? trim($_GET['device_search']) : '';
    $where_sql = "WHERE Status = 'Maintenance'";

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_sql .= $wpdb->prepare(
            " AND (Brand LIKE %s OR DeviceID LIKE %s OR RepairDate LIKE %s OR Details LIKE %s OR Model  LIKE %s OR SerialNumber LIKE %s OR Owner LIKE %s)",
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }


    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_mainten $where_sql");
    $total_pages = ceil($total_items / $page);

    $rows = $wpdb->get_results("SELECT * FROM $table_mainten $where_sql 
    ORDER BY RepairDate DESC 
    LIMIT $page OFFSET $offset");

    $suggestions = $wpdb->get_col("SELECT DISTINCT Brand FROM $table_mainten ORDER BY Category LIMIT 50");


    if (!function_exists('formatName')) {
        function formatName($el)
        {
            $el = trim($el);
            $el = preg_replace('/\(\s*\)/', '', $el);
            return htmlspecialchars($el ?: '-');
        }
    }
?>


    <div class="container-fluid">
        <form method="GET" action="">
<?php
foreach ($_GET as $key => $value) {
    if (!in_array($key, ['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged'])) {
        if (is_array($value)) {
            foreach ($value as $v) { echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">'; }
        } else {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }
}
?>
            <div class="row align-items-center g-2">
                <label class="col-auto col-form-label">Device</label>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <input type="text" name="device_search" list="search_suggestions" class="form-control" placeholder="Search Device..." value="<?= esc_attr($search) ?>" />
                    <datalist id="search_suggestions">
                        <?php foreach ($suggestions as $suggest): ?>
                            <option value="<?= esc_attr($suggest) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <button class="btn btn-info rounded-pill" style="width: 7rem;" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </div>
            </div>
        </form>


        <br>
        <div class="table-responsive-xl rounded">
            <table class="table table-bordered table-sm">
                <thead class="table-secondary">
                    <tr>
                        <th class="text-nowrap py-3 text-start" style="width: 10%;">ID</th>
                        <th class="text-nowrap py-3 text-start" style="width: 40%;">Device Info</th>
                        <th class="text-nowrap py-3 text-start" style="width: 25%;">Owner</th>
                        <th class="text-nowrap py-3 text-start" style="width: 15%;">Status</th>
                        <th class="text-nowrap py-3 text-center" style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td class="align-middle text-start"><?= $row->DeviceID ?></td>
                        <td class="text-start align-middle">
                            <strong><?= $row->Brand ?> <?= !empty($row->Model) ? $row->Model : '' ?></strong><br>
                            <small class="text-muted"><?= $row->Category ?> | SN: <?= !empty($row->SerialNumber) ? $row->SerialNumber : '-' ?></small>
                        </td>
                        <td class="align-middle text-start">
                            <strong><?= formatName($row->Owner) ?></strong><br>
                            <small class="text-muted"><?= formatName($row->Department) ?></small>
                        </td>

                        <td class="align-middle text-start">
                            <?php
                            $status = $row->Status;
                            $emoji = '';
                            if (strcasecmp($status, 'Available') === 0) {
                                $emoji = '<i class="fa-solid fa-circle text-success" style="font-size:12px;"></i>';
                            } elseif (strcasecmp($status, 'In Use') === 0) {
                                $emoji = '<i class="fa-solid fa-circle text-danger" style="font-size:12px;"></i>';
                            } elseif (strcasecmp($status, 'Maintenance') === 0) {
                                $emoji = '<i class="fa-solid fa-circle text-warning" style="font-size:12px;"></i>';
                            } elseif (strcasecmp($status, 'Retired') === 0) {
                                $emoji = '<i class="fa-solid fa-circle text-dark" style="font-size:12px;"></i>';
                            }
                            echo $emoji . ' ' . esc_html($status);
                            ?>
                        </td>

                        <td class="align-middle text-center">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-<?= $row->DeviceID ?>" onclick="toggleRow('<?= $row->DeviceID ?>')">▼</button>
                                <div class="dropdown action-menu mb-0 text-center">
                                    <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        ...
                                    </button>
                                    <div class="dropdown-menu action-dropdown text-start" style="z-index: 10000;">
                                        <div class="action-dropdown-header">Actions</div>
                                        <div class="action-dropdown-separator"></div>
                                        <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                                            <a href="?maintenance=<?= $row->DeviceID ?>"><i class="fa-solid fa-gear"></i> Edit</a>
                                        <?php else: ?>
                                            <a href="?edit=<?= $row->DeviceID ?>"><i class="fa-solid fa-gear"></i> Edit</a>
                                        <?php endif; ?>
                                        <a href="?view=<?= $row->DeviceID ?>"><i class="fa-solid fa-magnifying-glass"></i> View Details</a>
                                        <?php if ($row->Status == 'Available'): ?>
                                            <a href="?receive=<?= $row->DeviceID ?>"><i class="fa-solid fa-box"></i> Receive</a>
                                            <a href="?maintenance=<?= $row->DeviceID ?>"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'In Use'): ?>
                                            <a href="?return=<?= $row->DeviceID ?>"><i class="fa-solid fa-rotate-left"></i> Return</a>
                                            <a href="?maintenance=<?= $row->DeviceID ?>"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'Maintenance'): ?>
                                            <a href="?available=<?= $row->DeviceID ?>"><i class="fa-solid fa-circle text-success"></i> Available</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'Retired'): ?>
                                            <a href="?available=<?= $row->DeviceID ?>"><i class="fa-solid fa-circle text-success"></i> Available</a>
                                        <?php endif; ?>
                                        <a href="#" onclick="confirmDelete('<?= $row->DeviceID ?>')"><i class="fa-solid fa-trash-can"></i> Delete</a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr id="details-<?= $row->DeviceID ?>" style="display: none;">
                        <td colspan="5" class="p-0 border-0">
                            <div class="collapse-content" id="content-<?= $row->DeviceID ?>">
                                <div class="p-3 bg-light text-start m-2 rounded border">
                                    <div class="row">
                                        <div class="col-sm-3 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Repair Date</span>
                                            <strong><?= formatName($row->RepairDate) ?></strong>
                                        </div>
                                        <div class="col-sm-9 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Maintenance Reason / Details</span>
                                            <strong class="text-danger"><?= formatName($row->Details) ?></strong>
                                        </div>
                                        <?php if (strcasecmp($row->Status, 'Retired') === 0): ?>
                                            <div class="col-sm-12 mt-2">
                                                <?php
                                                $r_details = $wpdb->get_var($wpdb->prepare(
                                                    "SELECT Description FROM History_new WHERE DeviceID = %s AND (Action = 'Retired' OR (Action = 'Update Device' AND Description LIKE '%%| Reason:%%')) ORDER BY HistoryID DESC LIMIT 1", 
                                                    $row->DeviceID
                                                ));
                                                $r_reason = '-';
                                                if ($r_details) {
                                                    if (preg_match('/\|\s*Reason:\s*(.*)$/i', $r_details, $matches)) {
                                                        $r_reason = trim($matches[1]);
                                                    }
                                                }
                                                ?>
                                                <span class="text-muted d-block" style="font-size: 0.85em;">Retired Reason</span>
                                                <strong class="text-danger"><?= formatName($r_reason) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <style>
            .collapse-content {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
        </style>
        <script>
            function toggleRow(id) {
                const allContents = document.querySelectorAll('.collapse-content');
                allContents.forEach(content => {
                    if (content.id !== 'content-' + id && content.style.maxHeight !== '0px' && content.style.maxHeight !== '') {
                        content.style.maxHeight = '0px';
                        const otherBtn = document.getElementById('btn-' + content.id.replace('content-', ''));
                        if (otherBtn) otherBtn.innerHTML = '▼';
                        setTimeout(() => {
                            const tr = document.getElementById('details-' + content.id.replace('content-', ''));
                            if (tr) tr.style.display = 'none';
                        }, 300);
                    }
                });

                const row = document.getElementById('details-' + id);
                const content = document.getElementById('content-' + id);
                const btn = document.getElementById('btn-' + id);

                if (row.style.display === 'none' || row.style.display === '') {
                    row.style.display = 'table-row';
                    setTimeout(() => {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    }, 10);
                    if (btn) btn.innerHTML = '▲';
                } else {
                    content.style.maxHeight = '0px';
                    if (btn) btn.innerHTML = '▼';
                    setTimeout(() => {
                        row.style.display = 'none';
                    }, 300);
                }
            }
        </script>
        
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

<?php

    return ob_get_clean();
}
add_shortcode('device_crud_mainten', 'device_crud_maintenance');
