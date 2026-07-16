<?php

function form_history()
{
    global $wpdb;

    $table_history = 'History_new';
    $table_category = 'Categories';


    $action_result = handle_device_actions();
    if ($action_result) {
        echo $action_result;
        return;
    }

    // delte data > 12 month
    $wpdb->query("
    DELETE FROM $table_history
    WHERE Date < DATE_SUB(NOW(), INTERVAL 12 MONTH)
");


    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;




    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $params = [];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_sql = "WHERE H.Description LIKE %s OR H.Action LIKE %s OR H.user_email LIKE %s OR H.Owner LIKE %s OR C.CategoryName LIKE %s";
        $params = array_fill(0, 5, $like);
    } else {
        $search_sql = '';
    }

    //  COUNT จำนวนข้อมูล
    $total_items = $wpdb->get_var(
        $wpdb->prepare("
        SELECT COUNT(*) FROM $table_history AS H
        LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
        $search_sql
    ", ...$params)
    );
    $total_pages = ceil($total_items / $page);

    // get results
    $rows = $wpdb->get_results(
        $wpdb->prepare("
        SELECT H.*, C.CategoryName
        FROM $table_history AS H
        LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
        $search_sql
        ORDER BY H.HistoryID DESC
        LIMIT %d OFFSET %d
    ", ...array_merge($params, [$page, $offset]))
    );

    // Suggestion
    $suggestions = $wpdb->get_col("SELECT DISTINCT Action FROM $table_history LIMIT 50");
    ob_start();

?>

    <div class="container-fluid px-3">
        <form method="GET" action="">
            <div class="row align-items-center g-2">

                <label class="col-auto col-form-label">History</label>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <input type="text" name="search" list="search_suggestions" class="form-control" placeholder="Search History..." value="<?= esc_attr($search) ?>" />
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
                    <th class="py-3">Action</th>
                    <th class="py-3">Date</th>
                    <th class="py-3">Description</th>
                    <th class="py-3">User</th>
                    <th class="py-3">Category</th>
                    <th class="py-3">Owner</th>
                    <th class="py-3">Action</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $date = new DateTime($row->Date, new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone('Asia/Bangkok'));
                    ?>
                    <tr>
                        <td><?= $row->Action ?></td>
                        <td><?= $date->format("d/m/Y H:i:s") ?></td>
                        <td><?= $row->Description ?></td>
                        <td><?= $row->user_email ?></td>
                        <td>
                            <?= $row->Action === 'Add Employee' || $row->Action === 'Update Employee' || $row->Action === 'Delete Employee' ? 'Employee' : $row->CategoryName ?>
                        </td>
                        <td><?= $row->Owner ?></td>
                        <td>
                            <div class="action-menu">
                                <div style="text-align: center;">
                                    <button class="action-btn" style="background-color: #8bd8f4;">⋮</button>
                                </div>
                                <div class="action-dropdown">
                                    <a href="?view=<?= $row->DeviceID ?>">🔍 View Details</a>
                                    <?php if ($row->Status == 'Available'): ?>
                                        <a href="?receive=<?= $row->DeviceID ?>">📦 Receive</a>
                                        <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                        <a href="?retire=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'In Use'): ?>
                                        <a href="?return=<?= $row->DeviceID ?>">↩️ Return</a>
                                        <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                        <a href="?retire=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'Maintenance'): ?>
                                        <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                        <a href="?retire=<?= $row->DeviceID ?>">⚫ Retired</a>
                                    <?php elseif ($row->Status == 'Retired'): ?>
                                        <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                    <?php endif; ?>
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

add_shortcode('form_history', 'form_history');
