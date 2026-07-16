<?php
function device_view_details($device_id = null)
{
    global $wpdb;

    $table_device  = 'Devices';
    $table_brand   = 'Brands';
    $table_cat     = 'Categories';
    $table_status  = 'Statuses';
    $table_history = 'History_new';

    ob_start();

    if (isset($_GET['delete'])) {
        $history_id = $_GET['delete'];
        $wpdb->delete($table_history, ['HistoryID' => $history_id], ['%s']);
    }

    $device_id = $_GET['view'] ?? '';
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

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

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
    <div class="container-fluid px-3">
        <form method="GET" action="">
            <div class="d-flex flex-column flex-md-row align-items-start gap-2 gap-md-3">
                <input type="hidden" name="view" value="<?= esc_attr($device->DeviceID) ?>">

                <h2 class="mb-2 mb-md-0">
                    Device Details: <?= esc_html($device->DeviceID) ?> (<?= esc_html($device->Model) ?>)
                </h2>

                <!-- Section Search -->
                <input type="text" name="search" list="search_suggestions"
                    class="form-control w-100 w-md-auto"
                    style="max-width: 400px;"
                    placeholder="Search Details..."
                    value="<?= esc_attr($search) ?>">

                <!-- Button Search -->
                <button type="submit" class="btn btn-info rounded-pill ms-md-2">
                    🔍 Search
                </button>

                <!-- Button Print Label -->
                <button type="button" class="btn btn-secondary rounded-pill ms-md-2" onclick="printDeviceLabels([{ id: '<?= esc_js($device->DeviceID) ?>', sn: '<?= esc_js($device->SerialNumber) ?>' }])">
                    🖨️ Print Label
                </button>

                <!-- datalist suggestions -->
                <datalist id="search_suggestions">
                    <?php foreach ($suggestions as $suggest): ?>
                        <option value="<?= esc_attr($suggest) ?>"></option>
                    <?php endforeach; ?>
                </datalist>


                <div class="action-menu">
                    <button class="action-btn" style="background-color: #8bd8f4;">☰</button>
                    <div class="action-dropdown">
                        <a href="?edit=<?= esc_attr($device->DeviceID) ?>">⚙️ Edit</a>
                        <a href="?view=<?= esc_attr($device->DeviceID) ?>">🔍 View Details</a>
                        <?php if ($device->StatusName == 'Available'): ?>
                            <a href="?receive=<?= esc_attr($device->DeviceID) ?>">📦 Receive</a>
                            <a href="?maintenance=<?= esc_attr($device->DeviceID) ?>">🛠 Maintenance</a>
                            <a href="?retired=<?= esc_attr($device->DeviceID) ?>">⚫ Retired</a>
                        <?php elseif ($device->StatusName == 'In Use'): ?>
                            <a href="?return=<?= esc_attr($device->DeviceID) ?>">↩️ Return</a>
                            <a href="?maintenance=<?= esc_attr($device->DeviceID) ?>">🛠 Maintenance</a>
                            <a href="?retired=<?= esc_attr($device->DeviceID) ?>">⚫ Retired</a>
                        <?php elseif ($device->StatusName == 'Maintenance'): ?>
                            <a href="?available=<?= esc_attr($device->DeviceID) ?>">🟢 Available</a>
                            <a href="?retired=<?= esc_attr($device->DeviceID) ?>">⚫ Retired</a>
                        <?php elseif ($device->StatusName == 'Retired'): ?>
                            <a href="?available=<?= esc_attr($device->DeviceID) ?>">🟢 Available</a>
                        <?php endif; ?>
                        <a href="#" onclick="confirmDelete('<?= esc_attr($device->DeviceID) ?>')">🗑 Delete</a>
                    </div>
                </div>

            </div>
    </div>


    </form>




    <h3>📋 Device</h3>
    <div class="row mb-4">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between">
            <div class="d-flex flex-column gap-3" style="flex: 1; max-width: 30%;">
                <div>ID : <?= ($device->DeviceID) ?></div>
                <div>Category : <?= ($device->CategoryName) ?></div>
            </div>

            <div class="d-flex flex-column gap-3" style="flex: 1; max-width: 30%;">
                <div>Brand : <?= ($device->BrandName) ?></div>
                <div>Model : <?= ($device->Model) ?></div>
            </div>

            <div class="d-flex flex-column gap-3" style="flex: 1; max-width: 30%;">
                <div>SerialNumber : <?= ($device->SerialNumber) ?></div>
                <div>Status :
                    <?=
                    ($device->StatusName === 'Available' ? '🟢 Available' : ($device->StatusName === 'In Use' ? '🔴 In Use' : ($device->StatusName === 'Maintenance' ? '🟡 Maintenance' : ($device->StatusName === 'Retired' ? '⚫ Retired' : '❔'))))
                    ?>
                </div>
            </div>
        </div>
    </div>


    <br>
    <hr>
    <br>
    <h3>🕒 History</h3>

    <div class="table-responsive rounded">
        <table class="table table-bordered text-center mb-0">
            <thead class="table-secondary">
                <tr>
                    <th class="py-3">Action</th>
                    <th class="py-3">Date</th>
                    <th class="py-3">Description</th>
                    <th class="py-3">User</th>
                    <th class="py-3">Category</th>
                    <th class="py-3">Owner</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $date = new DateTime($row->Date, new DateTimeZone('UTC'));
                    $date->setTimezone(new DateTimeZone('Asia/Bangkok'));
                    ?>
                    <tr>
                        <td><?= esc_html($row->Action) ?></td>
                        <td><?= esc_html($date->format("d/m/Y H:i:s")) ?></td>
                        <td><?= esc_html($row->Description) ?></td>
                        <td><?= esc_html($row->user_email) ?></td>
                        <td><?= esc_html($device->CategoryName ?? '-') ?></td>
                        <td><?= esc_html($row->Owner) ?></td>
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

    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>
<?php
    return ob_get_clean();
}

add_shortcode('device_view_details', 'device_view_details');
?>