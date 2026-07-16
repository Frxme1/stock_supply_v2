<?php
function device_crud_monitor()
{
    global $wpdb; // Access the WordPress database object
    $table_device_wn = 'DevicesWithNames'; // Table name containing device data
    $page_size = 25; // Number of items to show per page

    ob_start(); // Start output buffering




    // Handle actions like edit, delete, etc. (if any)
    if ($action_result = handle_device_actions()) {
        echo $action_result;
        return;
    }

    echo device_dashboard_monitor();

    // Pagination Setup
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page_size;


    // --- ADVANCED FILTER LOGIC ---
    // Get filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
    $filter_brand = isset($_GET['filter_brand']) ? trim($_GET['filter_brand']) : '';
    $filter_department = isset($_GET['filter_department']) ? trim($_GET['filter_department']) : '';


    $where_sql = "WHERE Category = 'Monitor'"; // Filter only monitor devices

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_sql .= $wpdb->prepare(
            " AND (Brand LIKE %s OR DeviceID LIKE %s OR Department LIKE %s OR Status LIKE %s OR Model LIKE %s OR NickName LIKE %s OR SerialNumber LIKE %s OR Owner LIKE %s OR ReceiveDate LIKE %s OR ReturnDate LIKE %s OR RepairDate LIKE %s)",
            $like, $like, $like, $like, $like, $like, $like, $like, $like, $like, $like
        );
    }
    if (!empty($filter_status)) {
        $where_sql .= $wpdb->prepare(" AND Status = %s", $filter_status);
    }
    if (!empty($filter_brand)) {
        $where_sql .= $wpdb->prepare(" AND Brand = %s", $filter_brand);
    }
    if (!empty($filter_department)) {
        $where_sql .= $wpdb->prepare(" AND Department = %s", $filter_department);
    }

    // Fetch dropdown options
    $all_brands = $wpdb->get_col("SELECT DISTINCT Brand FROM $table_device_wn WHERE Category = 'Monitor' AND Brand != '' ORDER BY Brand");
    $all_departments = $wpdb->get_col("SELECT DISTINCT Department FROM $table_device_wn WHERE Category = 'Monitor' AND Department != '' ORDER BY Department");
    // -------------------------------------------


    //  Fetch Device Data 
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn $where_sql"); // Total filtered rows
    $total_pages = ceil($total_items / $page_size); // Calculate total pages


    // Get device rows with limit & offset for current page
    $rows = $wpdb->get_results("SELECT * FROM $table_device_wn $where_sql ORDER BY DeviceID DESC LIMIT $page_size OFFSET $offset");


    // Get distinct brand names for the search suggestion list
    $suggestions = $wpdb->get_col("SELECT DISTINCT Brand FROM $table_device_wn ORDER BY Category LIMIT 50");



    function formatName($el)
    {
        $el = trim($el);
        $el = preg_replace('/\(\s*\)/', '', $el);
        return htmlspecialchars($el ?: '-');
    }
?>

    <!-- Search Form -->
    <div class="container-fluid" style="margin: 0 -10px;">
        <div class="row mb-3 align-items-end">
            <div class="col-md-9">
                <form method="GET" action="" id="advanced-filter-form">
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Search Text</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= esc_attr($search) ?>" />
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Status</label>
                            <select name="filter_status" id="filter_status" class="form-select form-select-sm" onchange="toggleDepartment()">
                                <option value="">All Status</option>
                                <option value="Available" <?= $filter_status == 'Available' ? 'selected' : '' ?>>Available</option>
                                <option value="In Use" <?= $filter_status == 'In Use' ? 'selected' : '' ?>>In Use</option>
                                <option value="Maintenance" <?= $filter_status == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="Retired" <?= $filter_status == 'Retired' ? 'selected' : '' ?>>Retired</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Brand</label>
                            <select name="filter_brand" class="form-select form-select-sm">
                                <option value="">All Brands</option>
                                <?php foreach($all_brands as $brand): ?>
                                    <option value="<?= esc_attr($brand) ?>" <?= $filter_brand == $brand ? 'selected' : '' ?>><?= esc_html($brand) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2" id="department_wrapper">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Department</label>
                            <select name="filter_department" id="filter_department" class="form-select form-select-sm">
                                <option value="">All Depts</option>
                                <?php foreach($all_departments as $dept): ?>
                                    <option value="<?= esc_attr($dept) ?>" <?= $filter_department == $dept ? 'selected' : '' ?>><?= formatName($dept) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                            <button class="btn btn-sm btn-info flex-grow-1" type="submit">🔍 Filter</button>
                            <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </div>
            </div>
        </div>

        <script>
        function toggleDepartment() {
            var status = document.getElementById('filter_status').value;
            var deptWrapper = document.getElementById('department_wrapper');
            var deptSelect = document.getElementById('filter_department');
            if (status === 'Available') {
                deptWrapper.style.display = 'none';
                deptSelect.value = '';
            } else {
                deptWrapper.style.display = 'block';
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            toggleDepartment();
            
            // Check for import results
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('import_status')) {
                const successCount = urlParams.get('import_success') || 0;
                const errorCount = urlParams.get('import_error') || 0;
                
                let icon = 'success';
                let title = 'Import Complete';
                let text = `Successfully imported ${successCount} devices.`;
                
                if (errorCount > 0) {
                    if (successCount > 0) {
                        icon = 'warning';
                        text += `<br>Failed to import ${errorCount} rows (Skipped). Check format or missing Brands/Categories.`;
                    } else {
                        icon = 'error';
                        title = 'Import Failed';
                        text = `All ${errorCount} rows failed to import. Check format or missing Brands/Categories.`;
                    }
                }
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: icon,
                        title: title,
                        html: text,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#6ABF57'
                    }).then(() => {
                        // Remove params
                        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({path:newUrl}, '', newUrl);
                    });
                } else {
                    alert(text.replace(/<br>/g, "\n"));
                }
            }
        });
        </script>

        <!-- Import CSV Modal -->
        <div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importCsvModalLabel">📥 Import Monitors (CSV)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <form action="<?= esc_url(admin_url('admin-post.php')) ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import_device_csv">
                            <?php wp_nonce_field('import_device_csv_nonce', 'import_csv_nonce'); ?>
                            
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">Select CSV File</label>
                                <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                            </div>
                            
                            <div class="alert alert-info" style="font-size: 0.85em;">
                                <strong>Format Requirements:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Columns: <code>Brand, Category, Model, SerialNumber, AddDeviceDate, Keyword</code></li>
                                    <li>Make sure <code>Category</code> is set to <code>Monitor</code>.</li>
                                    <li>If Brand or Category does not exist, the row will be skipped (Error).</li>
                                    <li>Device IDs will be generated automatically.</li>
                                </ul>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-sm">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Table -->
        <form method="POST" action="" id="bulk-action-form-monitor">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <select name="bulk_action" class="form-select me-2" style="width: auto;">
                        <option value="">-- Bulk Actions --</option>
                        <option value="available">Set Available (🟢)</option>
                        <option value="retired">Set Retired (⚫)</option>
                        <option value="print_labels">Print Labels (🖨️)</option>
                        <option value="delete">Delete (🗑)</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-sm" onclick="handleBulkAction('monitor')">Apply</button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                        📥 Import CSV
                    </button>
                    <!-- Export button uses the same GET parameters for filtering -->
                    <a href="<?= esc_url(add_query_arg(['export_csv' => 'device', 'category' => 'Monitor'], $_SERVER['REQUEST_URI'])) ?>" class="btn btn-secondary btn-sm">
                        📤 Export CSV
                    </a>
                </div>
            </div>


        <div class="table-responsive-xxl">
            <table class="table table-bordered table-sm text-center rounded-3">
                <thead class="table-secondary">
                    <tr>
                        <th class="text-nowrap py-3" style="width: 40px;"><input type="checkbox" id="selectAll-monitor"></th>
                        <th class="text-nowrap py-3">ID</th>
                        <th class="text-nowrap py-3">Device Info</th>
                        <th class="text-nowrap py-3">Owner</th>
                        <th class="text-nowrap py-3">Status</th>
                        <th class="text-nowrap py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr class="text-nowrap py-2" style="white-space: nowrap;">
                            <td class="align-middle"><input type="checkbox" name="bulk_device_ids[]" value="<?= $row->DeviceID ?>" class="device-checkbox-monitor" data-sn="<?= esc_attr($row->SerialNumber ?? '') ?>"></td>
                            <td class="align-middle"><?= $row->DeviceID ?></td>
                            <td class="text-start align-middle">
                                <strong><?= $row->Brand ?> <?= !empty($row->Model) ? $row->Model : '' ?></strong><br>
                                <small class="text-muted"><?= $row->Category ?> | SN: <?= !empty($row->SerialNumber) ? $row->SerialNumber : '-' ?></small>
                            </td>
                            <td class="align-middle" style="min-width: 120px;">
                                <?php
                                $owner = trim($row->Owner ?? '');
                                $nickname = trim($row->Nickname ?? '');

                                if ($owner === '' && $nickname === '') {
                                    echo '-';
                                } else {
                                    if ($nickname !== '') {
                                        echo htmlspecialchars($nickname) . ' ';
                                    }
                                    if ($owner !== '') {
                                        preg_match('/\((.*?)\)$/', $owner, $matches);
                                        $position = $matches[1] ?? '';
                                        $nameOnly = trim(preg_replace('/\s*\(.*?\)$/', '', $owner));
                                        $nameParts = explode(' ', $nameOnly);
                                        if (count($nameParts) > 1) {
                                            $lastName = end($nameParts);
                                            $lastInitial = strtoupper(mb_substr($lastName, 0, 1)) . '.';
                                        } else {
                                            $lastInitial = '';
                                        }
                                        echo htmlspecialchars($lastInitial);
                                        if ($position !== '') {
                                            echo ' (' . htmlspecialchars($position) . ')';
                                        }
                                    }
                                }
                                ?>
                            </td>
                            <td class="align-middle" style="min-width: 130px;">
                                <?php
                                $status = $row->Status;
                                $emojis = [
                                    'Available'   => '🟢',
                                    'In Use'      => '🔴',
                                    'Maintenance' => '🟡',
                                    'Retired'     => '⚫',
                                ];
                                echo ($emojis[$status] ?? '') . ' ' . esc_html($status);
                                ?>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-<?= $row->DeviceID ?>" onclick="toggleRow('<?= $row->DeviceID ?>')">▼</button>
                                    <div class="action-menu mb-0 text-center">
                                        <button type="button" class="action-btn" style="background-color: #8bd8f4;">⋮</button>
                                        <div class="action-dropdown text-start" style="z-index: 10000;">
                                            <a href="?edit=<?= $row->DeviceID ?>">⚙️ Edit</a>
                                            <a href="?view=<?= $row->DeviceID ?>">🔍 View Details</a>
                                            <?php if ($status == 'Available'): ?>
                                                <a href="?receive=<?= $row->DeviceID ?>">📦 Receive</a>
                                                <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                                <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                            <?php elseif ($status == 'In Use'): ?>
                                                <a href="?return=<?= $row->DeviceID ?>">↩️ Return</a>
                                                <a href="?maintenance=<?= $row->DeviceID ?>">🛠 Maintenance</a>
                                                <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                            <?php elseif ($status == 'Maintenance'): ?>
                                                <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                                <a href="?retired=<?= $row->DeviceID ?>">⚫ Retired</a>
                                            <?php elseif ($status == 'Retired'): ?>
                                                <a href="?available=<?= $row->DeviceID ?>">🟢 Available</a>
                                            <?php endif; ?>
                                            <a href="#" onclick="confirmDelete('<?= $row->DeviceID ?>')">🗑 Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr id="details-<?= $row->DeviceID ?>" style="display: none;">
                            <td colspan="6" class="p-0 border-0">
                                <div class="collapse-content" id="content-<?= $row->DeviceID ?>">
                                    <div class="p-3 bg-light text-start m-2 rounded border">
                                        <div class="row">
                                        <div class="col-sm-3 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Department</span>
                                            <strong><?= formatName($row->Department) ?></strong>
                                        </div>
                                        <div class="col-sm-3 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Receive Date</span>
                                            <strong><?= formatName($row->ReceiveDate) ?></strong>
                                        </div>
                                        <div class="col-sm-3 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Return Date</span>
                                            <strong><?= formatName($row->ReturnDate) ?></strong>
                                        </div>
                                        <div class="col-sm-3 mb-2 mb-sm-0">
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Repair Date</span>
                                            <strong><?= formatName($row->RepairDate) ?></strong>
                                        </div>
                                        
                                        <?php if ($row->Status === 'Maintenance'): ?>
                                        <div class="col-sm-12 mt-2">
                                            <?php 
                                                // Fetch latest maintenance detail
                                                $m_details = $wpdb->get_var($wpdb->prepare("SELECT Details FROM Maintenance WHERE DeviceID = %s ORDER BY RepairDate DESC LIMIT 1", $row->DeviceID));
                                            ?>
                                            <span class="text-muted d-block" style="font-size: 0.85em;">Maintenance Reason</span>
                                            <strong class="text-danger"><?= formatName($m_details) ?></strong>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </form>

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

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            <ul class="pagination">
                <?php
                if ($total_pages > 1) {
                    $query_str = http_build_query(array_merge($_GET, ['paged' => null]));
                    $range = 2;
                    $start = max(1, $current_page - $range);
                    $end = min($total_pages, $current_page + $range);

                    // Prev
                    echo '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
                    echo '<a class="page-link" style="text-decoration: none;"  href="?' . $query_str . '&paged=' . ($current_page - 1) . '">Previous</a></li>';

                    // Start Ellipsis
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=1">1</a></li>';
                        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }

                    // Pages
                    for ($i = $start; $i <= $end; $i++) {
                        echo '<li class="page-item ' . ($i == $current_page ? 'active' : '') . '">';
                        echo '<a class="page-link" style="text-decoration: none;"  href="?' . $query_str . '&paged=' . $i . '">' . $i . '</a></li>';
                    }

                    // End Ellipsis
                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="?' . $query_str . '&paged=' . $total_pages . '">' . $total_pages . '</a></li>';
                    }

                    // Next
                    echo '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
                    echo '<a class="page-link" style="text-decoration: none;"  href="?' . $query_str . '&paged=' . ($current_page + 1) . '">Next</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
    
    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>
    <script>function handleBulkAction(t){try{let e="bulk-action-form",n=".device-checkbox";t&&"device"!==t&&(e="bulk-action-form-"+t,n=".device-checkbox-"+t);const o=document.getElementById(e);if(!o)return;const c=o.querySelector('select[name="bulk_action"]'),l=c?c.value:"",r=o.querySelectorAll(n+":checked");if(!l)return void alert("Please select a bulk action.");if(0===r.length)return void alert("Please select at least one device.");if("print_labels"!==l)confirm("Are you sure you want to apply this action to the selected devices?")&&o.submit();else{const t=[];for(let e=0;e<r.length;e++)t.push({id:r[e].value,sn:r[e].getAttribute("data-sn")||"-"});"function"==typeof printDeviceLabels?printDeviceLabels(t):alert("Print function not loaded. Please try hard refreshing (Ctrl+F5).")}}catch(t){console.error("Error in bulk action:",t),alert("An error occurred: "+t.message)}}document.addEventListener("change",function(t){if(t.target&&t.target.id&&t.target.id.startsWith("selectAll")){let e=t.target.id.replace("selectAll-","");"selectAll"===t.target.id&&(e="device");let n="device"===e?".device-checkbox":".device-checkbox-"+e;const o=document.querySelectorAll(n);for(let t=0;t<o.length;t++)o[t].checked=t.target.checked}});</script>

<?php
    return ob_get_clean();
}
add_shortcode('device_crud_monitor', 'device_crud_monitor');
// Allows using [device_crud_monitor] shortcode inside WordPress posts/pages