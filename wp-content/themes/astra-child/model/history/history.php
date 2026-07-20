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




    $search = isset($_GET['device_search']) ? trim($_GET['device_search']) : '';
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
        SELECT H.*, C.CategoryName, S.StatusName AS Status
        FROM $table_history AS H
        LEFT JOIN $table_category AS C ON H.CategoryID = C.CategoryID
        LEFT JOIN Devices AS D ON H.DeviceID = D.DeviceID
        LEFT JOIN Statuses AS S ON D.StatusID = S.StatusID
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
            <?php
            foreach ($_GET as $key => $value) {
                if (!in_array($key, ['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged'])) {
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
            <div class="row align-items-center g-2">

                <label class="col-auto col-form-label">History</label>

                <div class="col-12 col-sm-6 col-md-auto" style="width: 200px;">
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

                <div class="col-12 col-sm-6 col-md-auto" style="width: 200px;">
                    <button class="btn-filter-modern flex-grow-1" type="submit"><i class="fa-solid fa-filter"></i>
                        Filter</button>
                    <?php $reset_url = remove_query_arg(['device_search', 'paged']); ?>
                    <a href="<?= esc_url($reset_url) ?>" class="btn-reset-modern">Reset</a>
                </div>

            </div>
        </form>
        <br>

        <div class="table-responsive-xl rounded">
            <table class="table table-bordered table-sm">
                <thead class="table-secondary">
                    <tr>
                        <th class="text-nowrap py-3 text-start" style="width: 10%;">Action</th>
                        <th class="text-nowrap py-3 text-start" style="width: 15%;">Date</th>
                        <th class="text-nowrap py-3 text-start" style="width: 25%;">Description</th>
                        <th class="text-nowrap py-3 text-start" style="width: 15%;">User</th>
                        <th class="text-nowrap py-3 text-start" style="width: 10%;">Category</th>
                        <th class="text-nowrap py-3 text-start" style="width: 15%;">Owner</th>
                        <th class="text-nowrap py-3 text-center" style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php
                        $date = new DateTime($row->Date, new DateTimeZone('UTC'));
                        $date->setTimezone(new DateTimeZone('Asia/Bangkok'));
                        ?>
                        <tr>
                            <td class="text-start align-middle"><?= $row->Action ?></td>
                            <td class="text-start align-middle"><?= $date->format("d/m/Y H:i:s") ?></td>
                            <td class="text-start align-middle"><?= $row->Description ?></td>
                            <td class="text-start align-middle"><?= $row->user_email ?></td>
                            <td class="text-start align-middle">
                                <?= $row->Action === 'Add Employee' || $row->Action === 'Update Employee' || $row->Action === 'Delete Employee' ? 'Employee' : $row->CategoryName ?>
                            </td>
                            <td class="text-start align-middle"><?= $row->Owner ?></td>
                            <td class="text-center align-middle">
                                <div class="dropdown action-menu text-center">
                                    <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                        ...
                                    </button>
                                    <div class="dropdown-menu action-dropdown text-start">
                                        <div class="action-dropdown-header">Actions</div>
                                        <div class="action-dropdown-separator"></div>
                                        <a href="?view=<?= $row->DeviceID ?>"><i class="fa-solid fa-magnifying-glass"></i> View
                                            Details</a>
                                        <?php if ($row->Status == 'Available'): ?>
                                            <a href="?receive=<?= $row->DeviceID ?>"><i class="fa-solid fa-box"></i> Receive</a>
                                            <a href="?maintenance=<?= $row->DeviceID ?>"><i
                                                    class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i
                                                    class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'In Use'): ?>
                                            <a href="?return=<?= $row->DeviceID ?>"><i class="fa-solid fa-rotate-left"></i>
                                                Return</a>
                                            <a href="?maintenance=<?= $row->DeviceID ?>"><i
                                                    class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i
                                                    class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'Maintenance'): ?>
                                            <a href="?available=<?= $row->DeviceID ?>"><i
                                                    class="fa-solid fa-circle text-success"></i> Available</a>
                                            <a href="#" onclick="confirmRetire('<?= $row->DeviceID ?>'); return false;"><i
                                                    class="fa-solid fa-circle text-dark"></i> Retired</a>
                                        <?php elseif ($row->Status == 'Retired'): ?>
                                            <a href="?available=<?= $row->DeviceID ?>"><i
                                                    class="fa-solid fa-circle text-success"></i> Available</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
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
