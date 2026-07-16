<?php

function device_crud_maintenance()
{
    global $wpdb;
    $table_devices = 'Devices';
    $table_mainten = 'MaintenanceView';

    ob_start();

    $action_result = handle_device_actions();
    if ($action_result) {
        echo $action_result;
        return;
    }


    echo device_dashboard_maintenance();


    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;



    // section search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
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


    function formatName($el)
    {
        $el = trim($el);
        $el = preg_replace('/\(\s*\)/', '', $el);
        return htmlspecialchars($el ?: '-');
    }
?>


    <div class="container-fluid" style="margin: 0 -10px;">
        <form method="GET" action="">
            <div class="row align-items-center g-2">
                <label class="col-auto col-form-label">Device</label>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <input type="text" name="search" list="search_suggestions" class="form-control" placeholder="Search Device..." value="<?= esc_attr($search) ?>" />
                    <datalist id="search_suggestions">
                        <?php foreach ($suggestions as $suggest): ?>
                            <option value="<?= esc_attr($suggest) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <button class="btn btn-info rounded-pill" style="width: 7rem;" type="submit">🔍 Search</button>
                </div>
            </div>
        </form>


        <br>
        <div class="table-responsive-xl">
            <table class="table table-bordered table-sm text-center">
                <tr class="table-secondary">
                    <th class="py-3">ID</th>
                    <th class="py-3">Category</th>
                    <th class="py-3">Brand</th>
                    <th class="py-3">Model</th>
                    <th class="py-3">Serial No.</th>
                    <th class="py-3">Owner</th>
                    <th class="py-3">Department</th>
                    <!-- <th>KeywordID</th> -->
                    <th class="py-3">Status</th>
                    <th class="py-3">Repair Date</th>
                    <th class="py-3">Details</th>
                    <!-- <th>AddDeviceDate</th> -->
                    <th class="py-3">Action</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= $row->DeviceID ?></td>
                        <td><?= $row->Category ?></td>
                        <td><?= $row->Brand ?></td>
                        <td><?= $row->Model ?></td>
                        <td><?= !empty($row->SerialNumber) ? $row->SerialNumber : '-' ?></td>
                        <td style="min-width: 150px;">
                            <?= $row->Owner ?>
                        </td>
                        <td>
                            <?php
                            echo formatName($row->Department);
                            ?>
                        </td>

                        <td style="width: 10%; white-space: nowrap">
                            <?php
                            $status = $row->Status;
                            $emoji = '';
                            if (strcasecmp($status, 'Available') === 0) {
                                $emoji = '🟢';
                            } elseif (strcasecmp($status, 'In Use') === 0) {
                                $emoji = '🔴';
                            } elseif (strcasecmp($status, 'Maintenance') === 0) {
                                $emoji = '🟡';
                            } elseif (strcasecmp($status, 'Retired') === 0) {
                                $emoji = '⚫';
                            }
                            echo $emoji . ' ' . esc_html($status);
                            ?>
                        </td>

                        <td style="width: 8rem;"><?= $row->RepairDate ?></td>
                        <td><?= $row->Details ?></td>
                        <!-- <td>AddDeviceDate</td> -->
                        <td>
                            <div class="action-menu">
                                <div style="text-align: center;">
                                    <button class="action-btn" style="background-color: #8bd8f4;">⋮</button>
                                </div>
                                <div class="action-dropdown" style="text-align: start; z-index: 10000;">
                                    <a href="?edit=<?= $row->DeviceID ?>">⚙️ Edit</a>
                                    <a href="?view=<?= $row->DeviceID ?>">🔍 View Details</a>
                                    <?php if ($row->Status == 'Available'): ?>
                                        <a href="?receive=<?= $row->DeviceID ?>">📦 Receive</a>
                                        <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                        <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'In Use'): ?>
                                        <a href="?return=<?= $row->DeviceID ?>">↩️ Return</a>
                                        <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                        <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'Maintenance'): ?>
                                        <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                        <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'Retired'): ?>
                                        <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                    <?php endif; ?>
                                    <a href="#" onclick="confirmDelete('<?= $row->DeviceID ?>')">🗑 Delete</a>
                                </div>
                            </div>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
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

<?php

    return ob_get_clean();
}
add_shortcode('device_crud_mainten', 'device_crud_maintenance');
