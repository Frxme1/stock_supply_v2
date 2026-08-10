<?php
if (!defined('ABSPATH')) {
    exit;
}

function device_crud()
{
    global $wpdb; // Access the WordPress database object
    $table_device_wn = 'DevicesWithNames'; // The database table containing device records
    ob_start(); // Start output buffering


    $action_result = handle_device_actions(); // Handle actions like edit, delete, etc.
    if ($action_result) {
        return ob_get_clean() . $action_result;
    }


    echo device_dashboard(); // Show dashboard information (summary or controls)


    // Set up pagination
    $page = 25; // Number of records per page
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;



    // --- ADVANCED FILTER LOGIC ---
    // Get filter parameters
    $search = isset($_GET['device_search']) ? stock_supply_parse_search_query($_GET['device_search']) : '';
    $filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';
    $filter_brand = isset($_GET['filter_brand']) ? trim($_GET['filter_brand']) : '';
    $filter_department = isset($_GET['filter_department']) ? trim($_GET['filter_department']) : '';


    $search_sql = "WHERE 1=1";

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_sql .= $wpdb->prepare(
            " AND (DeviceID LIKE %s OR Category LIKE %s OR Brand LIKE %s OR Model LIKE %s OR NickName LIKE %s OR SerialNumber LIKE %s OR Owner LIKE %s OR Status LIKE %s OR Department LIKE %s OR ReceiveDate LIKE %s OR ReturnDate LIKE %s OR RepairDate LIKE %s)",
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }
    if (!empty($filter_status)) {
        $search_sql .= $wpdb->prepare(" AND Status = %s", $filter_status);
    }
    if (!empty($filter_brand)) {
        $search_sql .= $wpdb->prepare(" AND Brand = %s", $filter_brand);
    }
    if (!empty($filter_department)) {
        $search_sql .= $wpdb->prepare(" AND Department = %s", $filter_department);
    }

    // Fetch dropdown options
    $all_brands = $wpdb->get_col("SELECT DISTINCT Brand FROM $table_device_wn WHERE Brand != '' ORDER BY Brand");
    $all_departments = $wpdb->get_col("SELECT DISTINCT Department FROM $table_device_wn WHERE Department != '' ORDER BY Department");
    // -------------------------------------------


    // Count total matching records for pagination
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_device_wn $search_sql");
    $total_pages = ceil($total_items / $page);


    // Fetch current page of device records (default by latest updated)
    $rows = $wpdb->get_results("SELECT * FROM $table_device_wn $search_sql ORDER BY UpdatedAt DESC LIMIT $page OFFSET $offset");




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
        <div class="row mb-3 align-items-end">
            <div class="col-md-12">
                <form method="GET" action="" id="advanced-filter-form">
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
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Search Text</label>
                            <?php
                            $search_placeholder = 'Search...';
                            include get_stylesheet_directory() . '/view/animated-search.php';
                            ?>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Status</label>
                            <select name="filter_status" id="filter_status"
                                class="form-select form-select-sm staggered-dropdown" onchange="toggleDepartment()">
                                <option value="">All Status</option>
                                <option value="Available" <?= $filter_status == 'Available' ? 'selected' : '' ?>>Available
                                </option>
                                <option value="In Use" <?= $filter_status == 'In Use' ? 'selected' : '' ?>>In Use</option>
                                <option value="Maintenance" <?= $filter_status == 'Maintenance' ? 'selected' : '' ?>>
                                    Maintenance</option>
                                <option value="Retired" <?= $filter_status == 'Retired' ? 'selected' : '' ?>>Retired</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Brand</label>
                            <select name="filter_brand" id="filter_brand"
                                class="form-select form-select-sm staggered-dropdown">
                                <option value="">All Brands</option>
                                <?php foreach ($all_brands as $brand): ?>
                                    <option value="<?= esc_attr($brand) ?>" <?= $filter_brand == $brand ? 'selected' : '' ?>>
                                        <?= esc_html($brand) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2" id="department_wrapper">
                            <label class="form-label mb-1 text-muted" style="font-size: 0.85em;">Department</label>
                            <select name="filter_department" id="filter_department"
                                class="form-select form-select-sm staggered-dropdown">
                                <option value="">All Depts</option>
                                <?php foreach ($all_departments as $dept): ?>
                                    <option value="<?= esc_attr($dept) ?>" <?= $filter_department == $dept ? 'selected' : '' ?>>
                                        <?= formatName($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex align-items-end gap-2" style="width: 200px;">
                            <button class="btn-filter-modern flex-grow-1" type="submit"><i class="fa-solid fa-filter"></i>
                                Filter</button>
                            <?php $reset_url = remove_query_arg(['device_search', 'filter_status', 'filter_brand', 'filter_department', 'paged']); ?>
                            <a href="<?= esc_url($reset_url) ?>#advanced-filter-form" class="btn-reset-modern">Reset</a>
                        </div>
                    </div>
                </form>
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
            document.addEventListener('DOMContentLoaded', function () {
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
                            window.history.replaceState({ path: newUrl }, '', newUrl);
                        });
                    } else {
                        alert(text.replace(/<br>/g, "\n"));
                    }
                }
            });
        </script>
        <br>





        <form method="POST" action="" id="bulk-action-form-device">
            <?php wp_nonce_field('bulk_device_action_nonce', 'bulk_action_nonce'); ?>
            <div class="d-flex align-items-center mb-3">
                <!-- Dropdown removed as per user request -->
                <button type="button" class="btn btn-primary btn-sm"
                    style="border-radius: 8px; font-weight: 600; padding: 6px 16px;" onclick="handleBulkAction('device')">
                    <i class="fa-solid fa-print"></i> Print Labels
                </button>
            </div>

            <div class="mobile-only-container">
                <!-- Mobile Header / Quick Actions -->
                <div class="quick-action-grid">
                    <a href="javascript:void(0);" onclick="openAddDeviceBottomSheet()" class="quick-action-card receive"
                        style="grid-column: span 2;">
                        <div class="quick-action-icon"><i class="fa-solid fa-plus"></i></div>
                        <div class="quick-action-title">Add Device</div>
                    </a>
                    <a href="<?= home_url('/maintenance/') ?>" class="quick-action-card swap">
                        <div class="quick-action-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                        <div class="quick-action-title">Maintenance</div>
                    </a>
                    <a href="<?= home_url('/history/') ?>" class="quick-action-card return">
                        <div class="quick-action-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div class="quick-action-title">History</div>
                    </a>
                </div>

                <!-- Mobile Filter Button -->
                <button type="button" class="mobile-filter-btn" onclick="openBottomSheet()">
                    <span><i class="fa-solid fa-filter"></i> Filters & Search</span>
                    <i class="fa-solid fa-chevron-right text-muted"></i>
                </button>
            </div>

            <div id="device_table" class="table-wrapper">
                <table class="table-custom" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="py-3 text-center" style="width: 45px;"><input type="checkbox" id="selectAll"></th>
                            <th class="py-3 text-start">ID</th>
                            <th class="py-3 text-start">Device Info</th>
                            <th class="py-3 text-start">Owner</th>
                            <th class="py-3 text-start">Status</th>
                            <th class="py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($rows as $index => $row): ?>
                            <tr class="next-table-row" style="animation-delay: <?= min($index * 0.05, 1) ?>s;">
                                <td class="align-middle text-center mobile-card-checkbox" style="width: 45px;">
                                    <input type="checkbox" name="bulk_device_ids[]" value="<?= $row->DeviceID ?>"
                                        class="device-checkbox" data-sn="<?= esc_attr($row->SerialNumber ?? '') ?>">
                                </td>
                                <td class="align-middle text-start font-medium text-gray-900" data-label="ID">
                                    <?php
                                    $is_new_device = !empty($row->CreatedAt) && strtotime($row->CreatedAt) >= strtotime('-7 days');
                                    if ($is_new_device): ?>
                                        <span class="new-device-badge">NEW</span>
                                    <?php endif; ?>
                                    <?= $row->DeviceID ?>
                                </td>
                                <td class="text-start align-middle" data-label="Device Info">
                                    <div class="device-title"><?= $row->Brand ?>         <?= !empty($row->Model) ? $row->Model : '' ?>
                                    </div>
                                    <div class="device-subtitle"><?= $row->Category ?> | SN:
                                        <?= !empty($row->SerialNumber) ? $row->SerialNumber : '-' ?>
                                    </div>
                                </td>
                                <td class="text-start align-middle" data-label="Owner">
                                    <?php
                                    $owner = trim($row->Owner ?? '');
                                    $nickname = trim($row->Nickname ?? '');

                                    if ($owner === '' && $nickname === '') {
                                        echo '<span class="text-muted">-</span>';
                                    } else {
                                        if ($nickname !== '') {
                                            echo '<span class="owner-name">' . htmlspecialchars($nickname) . '</span> ';
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

                                            echo '<span class="owner-name">' . htmlspecialchars($lastInitial) . '</span>';
                                        }

                                        $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? '');
                                        if (!empty($deptAbbr)) {
                                            echo ' <span class="owner-dept text-muted" style="font-size: 0.85em;">' . htmlspecialchars($deptAbbr) . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="text-start align-middle" data-label="Status">
                                    <?php
                                    $status = $row->Status;
                                    $statusClass = '';
                                    if (strcasecmp($status, 'Available') === 0) {
                                        $statusClass = 'status-available';
                                    } elseif (strcasecmp($status, 'In Use') === 0) {
                                        $statusClass = 'status-inuse';
                                    } elseif (strcasecmp($status, 'Maintenance') === 0) {
                                        $statusClass = 'status-maintenance';
                                    } elseif (strcasecmp($status, 'Retired') === 0) {
                                        $statusClass = 'status-retired';
                                    }
                                    ?>
                                    <div class="status-badge <?= $statusClass ?>">
                                        <span class="status-dot"></span>
                                        <?= esc_html($status) ?>
                                    </div>
                                </td>
                                <td class="align-middle text-center" data-label="Action">
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="btn-<?= $row->DeviceID ?>"
                                            onclick="toggleRow('<?= $row->DeviceID ?>')">▼</button>
                                        <div class="dropdown action-menu mb-0 text-center">
                                            <button type="button" class="action-btn" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                ...
                                            </button>
                                            <div class="dropdown-menu action-dropdown text-start" style="z-index: 10000;">
                                                <div class="action-dropdown-header">Actions</div>
                                                <div class="action-dropdown-separator"></div>
                                                <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                                                    <a href="?maintenance=<?= $row->DeviceID ?>"><i class="fa-solid fa-gear"></i>
                                                        Edit</a>
                                                <?php else: ?>
                                                    <a href="?edit=<?= $row->DeviceID ?>"><i class="fa-solid fa-gear"></i> Edit</a>
                                                <?php endif; ?>
                                                <?php $dev_action_nonce = wp_create_nonce('device_action_nonce'); ?>
                                                <a href="?view=<?= esc_attr($row->DeviceID) ?>"><i
                                                        class="fa-solid fa-magnifying-glass"></i> View Details</a>
                                                <a
                                                    href="<?= home_url('/history/?device_search=') ?><?= esc_attr($row->DeviceID) ?>"><i
                                                        class="fa-solid fa-clock-rotate-left"></i> History</a>
                                                <?php if ($status == 'Available'): ?>
                                                    <a href="?receive=<?= esc_attr($row->DeviceID) ?>"><i
                                                            class="fa-solid fa-box"></i>
                                                        Receive</a>
                                                    <a href="?maintenance=<?= esc_attr($row->DeviceID) ?>"><i
                                                            class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                                    <a href="#"
                                                        onclick="confirmRetire('<?= esc_js($row->DeviceID) ?>', 'retired', '<?= $dev_action_nonce ?>'); return false;"><i
                                                            class="fa-solid fa-circle text-dark"></i> Retired</a>
                                                <?php elseif ($status == 'In Use'): ?>
                                                    <a href="#"
                                                        onclick="quickSwapDevice('<?= esc_js($row->DeviceID) ?>'); return false;"
                                                        class="text-warning"><i class="fa-solid fa-arrows-rotate"></i> Quick
                                                        Swap</a>
                                                    <a
                                                        href="?return=<?= esc_attr($row->DeviceID) ?>&_wpnonce=<?= $dev_action_nonce ?>"><i
                                                            class="fa-solid fa-rotate-left"></i>
                                                        Return</a>
                                                    <a href="?maintenance=<?= esc_attr($row->DeviceID) ?>"><i
                                                            class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                                    <a href="#"
                                                        onclick="confirmRetire('<?= esc_js($row->DeviceID) ?>', 'retired', '<?= $dev_action_nonce ?>'); return false;"><i
                                                            class="fa-solid fa-circle text-dark"></i> Retired</a>
                                                <?php elseif ($status == 'Maintenance'): ?>
                                                    <a
                                                        href="?available=<?= esc_attr($row->DeviceID) ?>&_wpnonce=<?= $dev_action_nonce ?>"><i
                                                            class="fa-solid fa-circle text-success"></i> Available</a>
                                                    <a href="#"
                                                        onclick="confirmRetire('<?= esc_js($row->DeviceID) ?>', 'retired', '<?= $dev_action_nonce ?>'); return false;"><i
                                                            class="fa-solid fa-circle text-dark"></i> Retired</a>
                                                <?php elseif ($status == 'Retired'): ?>
                                                    <a
                                                        href="?available=<?= esc_attr($row->DeviceID) ?>&_wpnonce=<?= $dev_action_nonce ?>"><i
                                                            class="fa-solid fa-circle text-success"></i> Available</a>
                                                <?php endif; ?>
                                                <a href="#"
                                                    onclick="printDeviceLabels([{ id: '<?= esc_js($row->DeviceID) ?>', sn: '<?= esc_js($row->SerialNumber ?? "") ?>' }]); return false;"><i
                                                        class="fa-solid fa-print"></i> Print Label</a>
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
                                                    <span class="text-muted d-block"
                                                        style="font-size: 0.85em;">Position</span>
                                                    <strong><?= formatName($row->Position ?? '-') ?></strong>
                                                </div>
                                                <div class="col-sm-3 mb-2 mb-sm-0">
                                                    <span class="text-muted d-block" style="font-size: 0.85em;">Assign
                                                        Date</span>
                                                    <strong><?= formatName($row->ReceiveDate) ?></strong>
                                                </div>
                                                <div class="col-sm-3 mb-2 mb-sm-0">
                                                    <span class="text-muted d-block" style="font-size: 0.85em;">Return
                                                        Date</span>
                                                    <strong><?= formatName($row->ReturnDate) ?></strong>
                                                </div>
                                                <div class="col-sm-3 mb-2 mb-sm-0">
                                                    <span class="text-muted d-block" style="font-size: 0.85em;">Repair
                                                        Date</span>
                                                    <strong><?= formatName($row->RepairDate) ?></strong>
                                                </div>

                                                <?php if ($row->Status === 'Maintenance'): ?>
                                                    <div class="col-sm-12 mt-2">
                                                        <?php
                                                        // Fetch latest maintenance detail
                                                        $m_details = $wpdb->get_var($wpdb->prepare("SELECT Details FROM Maintenance WHERE DeviceID = %s ORDER BY RepairDate DESC LIMIT 1", $row->DeviceID));
                                                        ?>
                                                        <span class="text-muted d-block" style="font-size: 0.85em;">Maintenance
                                                            Reason</span>
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

            <!-- Mobile Device Cards -->
            <div class="mobile-only-container" style="margin-top: 16px;">
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $status = $row->Status;
                    $statusClass = '';
                    if (strcasecmp($status, 'Available') === 0)
                        $statusClass = 'status-available';
                    elseif (strcasecmp($status, 'In Use') === 0)
                        $statusClass = 'status-inuse';
                    elseif (strcasecmp($status, 'Maintenance') === 0)
                        $statusClass = 'status-maintenance';
                    elseif (strcasecmp($status, 'Retired') === 0)
                        $statusClass = 'status-retired';
                    ?>
                    <div class="mobile-device-card">
                        <div class="mobile-device-header">
                            <div class="mobile-device-title-area">
                                <div class="mobile-device-title"><?= esc_html($row->Brand) ?>
                                    <?= esc_html(!empty($row->Model) ? $row->Model : '') ?>
                                </div>
                                <div class="mobile-device-meta"><?= esc_html($row->Category) ?> | SN:
                                    <?= esc_html(!empty($row->SerialNumber) ? $row->SerialNumber : '-') ?>
                                </div>
                            </div>
                            <div class="status-badge <?= $statusClass ?>">
                                <span class="status-dot"></span>
                                <?= esc_html($status) ?>
                            </div>
                        </div>
                        <div class="mobile-device-body">
                            <div class="mobile-device-owner">
                                <i class="fa-solid fa-user"></i>
                                <?php
                                $owner = trim($row->Owner ?? '');
                                $nickname = trim($row->Nickname ?? '');
                                if ($owner === '' && $nickname === '') {
                                    echo '-';
                                } else {
                                    if ($nickname !== '')
                                        echo htmlspecialchars($nickname) . ' ';
                                    if ($owner !== '') {
                                        preg_match('/\((.*?)\)$/', $owner, $matches);
                                        $nameOnly = trim(preg_replace('/\s*\(.*?\)$/', '', $owner));
                                        $nameParts = explode(' ', $nameOnly);
                                        if (count($nameParts) > 1) {
                                            $lastInitial = strtoupper(mb_substr(end($nameParts), 0, 1)) . '.';
                                            echo htmlspecialchars($lastInitial);
                                        }
                                    }
                                    $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? '');
                                    if (!empty($deptAbbr)) {
                                        echo ' <span class="text-muted small">' . htmlspecialchars($deptAbbr) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="mobile-device-id"><strong><?= esc_html($row->DeviceID) ?></strong></div>
                        </div>
                        <div class="mobile-device-actions">
                            <?php if (strcasecmp($row->Status, 'Maintenance') === 0): ?>
                                <a href="?maintenance=<?= esc_attr($row->DeviceID) ?>"
                                    class="mobile-btn-action mobile-btn-secondary"><i class="fa-solid fa-gear"></i> Edit</a>
                            <?php else: ?>
                                <a href="?edit=<?= esc_attr($row->DeviceID) ?>" class="mobile-btn-action mobile-btn-secondary"><i
                                        class="fa-solid fa-gear"></i> Edit</a>
                            <?php endif; ?>
                            <a href="?view=<?= esc_attr($row->DeviceID) ?>" class="mobile-btn-action mobile-btn-primary"><i
                                    class="fa-solid fa-magnifying-glass"></i> View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>

        <style>
            .collapse-content {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }

            /* Next.js Table UI */
            .next-table-wrapper {
                background: #ffffff;
                margin-top: 20px;
                border-radius: 20px;
                border: 1.5px solid #e5e7eb;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                overflow: visible;
                margin-bottom: 2rem;
            }

            .next-table {
                margin: 0;
                border-collapse: collapse;
                width: 100%;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                border-radius: 20px;
                overflow: hidden;
            }

            .next-table thead {
                background: #f9fafb;
                border-bottom: 1px solid #e5e7eb;
            }

            .next-table thead th:first-child {
                border-top-left-radius: 12px;
            }

            .next-table thead th:last-child {
                border-top-right-radius: 12px;
            }

            .next-table th {
                color: #6b7280;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                padding: 1rem 1.5rem;
                border: none;
            }

            .next-table tbody tr.next-table-row {
                border-bottom: 1px solid #e5e7eb;
                transition: all 0.2s ease;
                opacity: 0;
                animation: rowFadeIn 0.4s ease-out forwards;
                position: relative;
            }

            .next-table tbody tr.next-table-row:last-child {
                border-bottom: none;
            }

            .next-table tbody tr.next-table-row:hover {
                background-color: #f8fafc;
                z-index: 10;
            }

            .next-table tbody tr.next-table-row:focus-within,
            .next-table tbody tr.next-table-row:has(.show),
            .next-table tbody tr.next-table-row:has([aria-expanded="true"]) {
                z-index: 100 !important;
            }

            .next-table td {
                padding: 1rem 1.5rem;
                border: none;
                vertical-align: middle;
            }

            .next-table .font-medium {
                font-weight: 500;
            }

            .next-table .text-gray-900 {
                color: #111827;
            }

            .device-title {
                font-weight: 600;
                color: #111827;
                font-size: 0.95rem;
                margin-bottom: 2px;
            }

            .device-subtitle {
                font-size: 0.8rem;
                color: #6b7280;
            }

            .owner-name {
                font-weight: 500;
                color: #374151;
            }

            .owner-position {
                font-size: 0.8rem;
                color: #9ca3af;
            }

            /* Status Badges */
            .status-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
            }

            .status-available {
                background: #ecfdf5;
                color: #059669;
            }

            .status-available .status-dot {
                background: #10b981;
            }

            .status-inuse {
                background: #fef2f2;
                color: #dc2626;
            }

            .status-inuse .status-dot {
                background: #ef4444;
            }

            .status-maintenance {
                background: #fffbeb;
                color: #d97706;
            }

            .status-maintenance .status-dot {
                background: #f59e0b;
            }

            .status-retired {
                background: #f3f4f6;
                color: #374151;
            }

            .status-retired .status-dot {
                background: #6b7280;
            }

            /* Action buttons */
            .next-table .btn-outline-secondary {
                border-color: #e5e7eb;
                color: #4b5563;
                background: #ffffff;
                transition: all 0.2s;
            }

            .next-table .btn-outline-secondary:hover {
                background: #f3f4f6;
                color: #111827;
            }

            .next-table .action-btn {
                background: none;
                border: none;
                color: #6b7280;
                font-size: 1.25rem;
                cursor: pointer;
                transition: color 0.2s;
            }

            .next-table .action-btn:hover {
                color: #111827;
            }

            @keyframes rowFadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
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

    <!-- Bottom Sheet for Filters -->
    <div class="bottom-sheet-backdrop" id="bottomSheetBackdrop" onclick="closeBottomSheet()"></div>
    <div class="bottom-sheet" id="mobileBottomSheet">
        <div class="bottom-sheet-header">
            <h3>Filters</h3>
            <button class="bottom-sheet-close" onclick="closeBottomSheet()"><i class="fa-solid fa-times"></i></button>
        </div>
        <div id="mobile-filter-container"></div>
    </div>
    <script>
        function closeBottomSheet() {
            var sheet = document.getElementById('mobileBottomSheet');
            var backdrop = document.getElementById('bottomSheetBackdrop');
            if (sheet) sheet.classList.remove('open');
            if (backdrop) backdrop.classList.remove('open');
        }
        function openBottomSheet() {
            var sheet = document.getElementById('mobileBottomSheet');
            var backdrop = document.getElementById('bottomSheetBackdrop');
            if (sheet) {
                if (sheet.parentElement !== document.body) {
                    document.body.appendChild(sheet);
                }
                sheet.classList.add('open');
            }
            if (backdrop) {
                if (backdrop.parentElement !== document.body) {
                    document.body.appendChild(backdrop);
                }
                backdrop.classList.add('open');
            }
            var filterForm = document.getElementById('advanced-filter-form');
            var mobileContainer = document.getElementById('mobile-filter-container');
            if (filterForm && mobileContainer && filterForm.parentElement !== mobileContainer) {
                mobileContainer.appendChild(filterForm);
                filterForm.style.display = 'block';
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            var sheet = document.getElementById('mobileBottomSheet');
            var backdrop = document.getElementById('bottomSheetBackdrop');
            if (sheet && sheet.parentElement !== document.body) {
                document.body.appendChild(sheet);
            }
            if (backdrop && backdrop.parentElement !== document.body) {
                document.body.appendChild(backdrop);
            }
            if (window.innerWidth <= 768) {
                var filterForm = document.getElementById('advanced-filter-form');
                var mobileContainer = document.getElementById('mobile-filter-container');
                if (filterForm && mobileContainer && filterForm.parentElement !== mobileContainer) {
                    mobileContainer.appendChild(filterForm);
                    filterForm.style.display = 'block';
                }
            }
        });
    </script>

    <!-- Bottom Sheet for Add Device (Mobile Only) -->
    <div class="bottom-sheet-backdrop" id="addDeviceBottomSheetBackdrop" onclick="closeAddDeviceBottomSheet()"></div>
    <div class="bottom-sheet" id="addDeviceBottomSheet" style="height: 90vh; overflow-y: auto; padding: 20px;">
        <div class="bottom-sheet-header"
            style="position: sticky; top: 0; background: #ffffff; z-index: 10; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 1.2rem; margin: 0;">Add New Device</h3>
            <button class="bottom-sheet-close" onclick="closeAddDeviceBottomSheet()"><i
                    class="fa-solid fa-times"></i></button>
        </div>
        <div id="add-device-container">
            <style>
                #add-device-container h2 {
                    display: none !important;
                }
            </style>
            <?php echo do_shortcode('[device_form]'); ?>
        </div>
    </div>
    <script>
        function openAddDeviceBottomSheet() {
            document.getElementById('addDeviceBottomSheet').classList.add('open');
            document.getElementById('addDeviceBottomSheetBackdrop').classList.add('open');
        }
        function closeAddDeviceBottomSheet() {
            document.getElementById('addDeviceBottomSheet').classList.remove('open');
            document.getElementById('addDeviceBottomSheetBackdrop').classList.remove('open');
        }
    </script>

    <script src="<?= get_stylesheet_directory_uri() ?>/js/print_labels.js?v=<?= time() ?>"></script>

    <?php

    return ob_get_clean();
}



add_shortcode('device_crud', 'device_crud');
?>