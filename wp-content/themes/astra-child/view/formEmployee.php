<?php
if (!defined('ABSPATH')) {
    exit;
}

function form_owner()
{
    global $wpdb;
    $table_owner = 'Owners';
    $table_owner_wn = 'ViewOwnersWithNames';
    $table_devices = 'Devices';


    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';



    // ===== RESIGN (Soft Delete) =====
    if (isset($_GET['resign'])) {
        if (!is_user_logged_in() || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'resign_owner_nonce')) {
            return;
        }
        $owner_id = intval($_GET['resign']);
        $owner_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner_wn WHERE OwnerID = %d", $owner_id));

        if ($owner_data) {
            $owner_fullname = trim($owner_data->FirstName . ' ' . $owner_data->LastName);
            $owner_nickname = $owner_data->Nickname ?? '-';

            // Set StatusID = 2 (Resigned) — Soft Delete, ข้อมูลยังอยู่
            $resigned_status_id = $wpdb->get_var("SELECT StatusID FROM Status_Employee WHERE Status_name = 'Resigned'");
            $wpdb->update($table_owner, ['StatusID' => $resigned_status_id], ['OwnerID' => $owner_id]);

            // คืนอุปกรณ์ทั้งหมดที่ถือครองกลับเป็น Available
            $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
            $wpdb->update(
                $table_devices,
                ['StatusID' => $available_status_id, 'OwnerID' => null, 'DepartmentID' => null, 'ReceiveDate' => null, 'ReturnDate' => null, 'RepairDate' => null],
                ['OwnerID' => $owner_id]
            );

            // บันทึก History
            $wpdb->insert('History_new', [
                'DeviceID' => 0,
                'Action' => 'Employee Resigned',
                'Date' => current_time('mysql'),
                'Description' => 'Employee resigned: ' . $owner_nickname . ($owner_fullname ? ' (' . $owner_fullname . ')' : ''),
                'user_email' => wp_get_current_user()->user_email,
                'CategoryID' => 1,
                'Owner' => $owner_nickname,
            ]);

            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Resigned',
                text: '{$owner_nickname} has been marked as Resigned.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = '" . esc_url(home_url('/Owner/')) . "';
            });
            </script>";
            exit;
        }
    }

    // ===== DELETE (Hard Delete) =====
    if (isset($_GET['delete'])) {
        if (!is_user_logged_in() || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_owner_nonce')) {
            return;
        }
        $owner_id = intval($_GET['delete']);
        $owner_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner_wn WHERE OwnerID = %d", $owner_id));

        if ($owner_data) {
            $owner_fullname = $owner_data->FirstName . ' ' . $owner_data->LastName;
            $wpdb->insert('History_new', [
                'DeviceID' => 0,
                'Action' => 'Delete Employee',
                'Date' => current_time('mysql'),
                'Description' => 'Permanently deleted employee: ' . $owner_fullname,
                'user_email' => wp_get_current_user()->user_email,
                'CategoryID' => 1,
                'Owner' => $owner_data->Nickname ?? $owner_fullname,
            ]);

            $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
            $wpdb->update(
                $table_devices,
                ['StatusID' => $available_status_id, 'OwnerID' => null, 'DepartmentID' => null, 'ReceiveDate' => null, 'ReturnDate' => null, 'RepairDate' => null],
                ['OwnerID' => $owner_id]
            );
            $wpdb->delete($table_owner, ['OwnerID' => $owner_id]);

            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Deleted Employee',
                text: 'The employee has been permanently removed.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = '" . esc_url(home_url('/Owner/')) . "';
            });
            </script>";
            exit;
        }
    }




    // form edit
    if (isset($_GET['edit'])) {
        $edit_id = sanitize_text_field($_GET['edit']);
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner WHERE OwnerID = %d", $edit_id));
        echo form_edit_owner($editing);
        return;
    }

    echo employee_dashboard();


    $page = 25;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $page;



    // section search & filter
    $search = isset($_GET['device_search']) ? sanitize_text_field($_GET['device_search']) : '';
    $filter_dept = isset($_GET['filter_department']) ? sanitize_text_field($_GET['filter_department']) : '';
    $filter_pos = isset($_GET['filter_position']) ? sanitize_text_field($_GET['filter_position']) : '';
    $filter_st = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

    $where_clauses = ["1=1"];

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_clauses[] = $wpdb->prepare(
            "(Nickname LIKE %s OR FirstName LIKE %s OR LastName LIKE %s OR Department LIKE %s OR Position LIKE %s OR Status LIKE %s)",
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }
    if (!empty($filter_dept)) {
        $where_clauses[] = $wpdb->prepare("Department = %s", $filter_dept);
    }
    if (!empty($filter_pos)) {
        $where_clauses[] = $wpdb->prepare("Position = %s", $filter_pos);
    }
    if (!empty($filter_st)) {
        $where_clauses[] = $wpdb->prepare("Status = %s", $filter_st);
    }

    $search_sql = "WHERE " . implode(" AND ", $where_clauses);

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_owner_wn $search_sql");
    $total_pages = ceil($total_items / $page);
    $rows = $wpdb->get_results("SELECT * FROM $table_owner_wn $search_sql ORDER BY OwnerID DESC LIMIT $page OFFSET $offset");

    $suggestions = $wpdb->get_col("SELECT DISTINCT Nickname FROM $table_owner_wn ORDER BY OwnerType ASC");

    ?>

    <!-- Advanced Filter & Action Bar -->
    <form method="GET" action="" id="advanced-filter-form" style="margin-bottom: 24px;">
        <?php
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['device_search', 'filter_status', 'filter_department', 'filter_position', 'paged'])) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                    }
                } else {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $filter_dept = isset($_GET['filter_department']) ? sanitize_text_field($_GET['filter_department']) : '';
        $filter_pos = isset($_GET['filter_position']) ? sanitize_text_field($_GET['filter_position']) : '';

        $departments = $wpdb->get_col("SELECT DISTINCT Department FROM $table_owner_wn WHERE Department IS NOT NULL AND Department != '' AND Department != '-' ORDER BY Department ASC");
        $positions = $wpdb->get_col("SELECT DISTINCT Position FROM $table_owner_wn WHERE Position IS NOT NULL AND Position != '' ORDER BY Position ASC");
        $statuses = $wpdb->get_col("SELECT DISTINCT Status FROM $table_owner_wn WHERE Status IS NOT NULL AND Status != '' ORDER BY Status ASC");
        ?>

        <style>
            .filter-select-custom {
                height: 38px !important;
                border-radius: 8px !important;
                border: 1px solid #d1d5db !important;
                font-size: 0.875rem !important;
                color: #374151 !important;
                background-color: #ffffff !important;
                width: 100% !important;
            }

            .btn-filter-cyan {
                height: 38px;
                background: #00a8ff !important;
                color: #ffffff !important;
                font-weight: 700;
                font-size: 0.875rem;
                border: none;
                border-radius: 8px;
                padding: 0 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                box-shadow: 0 2px 6px rgba(0, 168, 255, 0.25);
                transition: all 0.2s ease;
                cursor: pointer;
                text-decoration: none !important;
            }

            .btn-filter-cyan:hover {
                background: #0097e6 !important;
                color: #ffffff !important;
                transform: translateY(-1px);
            }

            .btn-reset-underline {
                height: 38px;
                background: #ffffff;
                color: #475569 !important;
                font-weight: 700;
                font-size: 0.875rem;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 0 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-decoration: underline !important;
                text-underline-offset: 2px;
                transition: all 0.2s ease;
            }

            .btn-reset-underline:hover {
                background: #f8fafc;
                color: #1e293b !important;
            }

            .btn-add-black {
                width: 38px;
                height: 38px;
                background: #0f172a;
                color: #ffffff !important;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.9rem;
                border: none;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.3);
                transition: all 0.2s ease;
                text-decoration: none !important;
                flex-shrink: 0;
            }

            .btn-add-black:hover {
                background: #1e293b;
                transform: scale(1.05);
                color: #ffffff !important;
            }

            .filter-actions-item {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            @media (max-width: 768px) {
                #advanced-filter-form .filter-bar-container {
                    flex-direction: column !important;
                    align-items: stretch !important;
                    gap: 10px !important;
                    margin-top: -20px !important;
                }

                #advanced-filter-form .filter-field-item {
                    width: 100% !important;
                    min-width: 100% !important;
                    max-width: 100% !important;
                }

                #advanced-filter-form .filter-actions-item {
                    display: flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    width: 100% !important;

                }

                #advanced-filter-form .btn-filter-cyan {
                    flex: 1 !important;
                    height: 42px !important;
                }

                #advanced-filter-form .btn-reset-underline {
                    flex: 1 !important;
                    height: 42px !important;
                }

                #advanced-filter-form .btn-add-black {
                    width: 42px !important;
                    height: 42px !important;
                }
            }
        </style>

        <div class="d-flex align-items-end flex-wrap gap-2 w-100 filter-bar-container">
            <!-- Search Employee -->
            <div class="flex-grow-1 filter-field-item" style="min-width: 220px; max-width: 320px;">
                <label class="form-label text-secondary small mb-1 fw-bold">Search Employee</label>
                <?php
                $search_placeholder = 'Search by name, nickname, dept...';
                $search_list = 'search_suggestions';
                include get_stylesheet_directory() . '/view/animated-search.php';
                ?>
                <datalist id="search_suggestions">
                    <?php foreach ($suggestions as $suggest): ?>
                        <option value="<?= esc_attr($suggest) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <!-- Department Filter -->
            <div class="filter-field-item" style="min-width: 140px; flex: 1;">
                <label class="form-label text-secondary small mb-1 fw-bold">Department</label>
                <select name="filter_department" class="form-select form-select-sm filter-select-custom staggered-dropdown">
                    <option value="">All Depts</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= esc_attr($dept) ?>" <?= $filter_dept === $dept ? 'selected' : '' ?>>
                            <?= esc_html($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Position Filter -->
            <div class="filter-field-item" style="min-width: 140px; flex: 1;">
                <label class="form-label text-secondary small mb-1 fw-bold">Position</label>
                <select name="filter_position" class="form-select form-select-sm filter-select-custom staggered-dropdown">
                    <option value="">All Positions</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= esc_attr($pos) ?>" <?= $filter_pos === $pos ? 'selected' : '' ?>><?= esc_html($pos) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="filter-field-item" style="min-width: 140px; flex: 1;">
                <label class="form-label text-secondary small mb-1 fw-bold">Status</label>
                <select name="filter_status" class="form-select form-select-sm filter-select-custom staggered-dropdown">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= esc_attr($st) ?>" <?= $filter_status === $st ? 'selected' : '' ?>><?= esc_html($st) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Buttons Container -->
            <div class="filter-actions-item">
                <button type="submit" class="btn-filter-cyan">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <?php $reset_url = remove_query_arg(['device_search', 'filter_status', 'filter_department', 'filter_position', 'paged']); ?>
                <a href="<?= esc_url($reset_url) ?>" class="btn-reset-underline">Reset</a>
                <a href="<?= esc_url(home_url('/add-owner/')) ?>" class="btn-add-black" title="Add Employee">
                    <i class="fa-solid fa-user-plus"></i>
                </a>
            </div>
        </div>
    </form>

    <!-- Mobile Employee List View -->
    <div class="mobile-only-container mb-4">
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row):
                $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? '');
                $fullname = trim($row->FirstName . ' ' . $row->LastName);
                $statusClass = (strcasecmp($row->Status, 'Active') === 0) ? 'status-available' : 'status-inuse';
                ?>
                <div class="mobile-device-card slide-up" style="background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 14px;
    width: 110%;
    margin-left: -20px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.03);">
                    <!-- Header: Nickname & Status -->
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <h4 style="margin: 0; font-weight: 800; font-size: 1.15rem; color: #0f172a;">
                                <?= esc_html($row->Nickname) ?>
                                <?php if ($deptAbbr): ?>
                                    <span class="badge bg-light text-dark border ms-1"
                                        style="font-size: 0.75rem; font-weight: 600;"><?= esc_html($deptAbbr) ?></span>
                                <?php endif; ?>
                            </h4>
                            <div style="font-size: 0.85rem; color: #64748b; font-weight: 500; margin-top: 2px;">
                                <i class="fa-solid fa-id-card me-1" style="color: #6366f1;"></i> <?= $fullname ?: '-' ?>
                            </div>
                        </div>
                        <span class="status-badge <?= $statusClass ?>">
                            <span class="status-dot"></span>
                            <?= esc_html($row->Status) ?>
                        </span>
                    </div>

                    <!-- Info Body: Email, Dept, Position -->
                    <div
                        style="padding-top: 10px; border-top: 1px dashed #e2e8f0; font-size: 0.85rem; color: #475569; display: flex; flex-direction: column; gap: 6px;">
                        <?php if (!empty($row->Email)): ?>
                            <div><i class="fa-regular fa-envelope me-1" style="color: #94a3b8; width: 16px;"></i>
                                <?= esc_html($row->Email) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($row->Department)): ?>
                            <div><i class="fa-solid fa-building me-1" style="color: #94a3b8; width: 16px;"></i>
                                <?= esc_html($row->Department) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($row->Position)): ?>
                            <div><i class="fa-solid fa-briefcase me-1" style="color: #94a3b8; width: 16px;"></i>
                                <?= esc_html($row->Position) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end mt-3 pt-2" style="border-top: 1px dashed #f1f5f9;">
                        <a href="?edit=<?= $row->OwnerID ?>" class="btn btn-sm btn-outline-secondary"
                            style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; padding: 6px 14px; text-decoration: none;">
                            <i class="fa-solid fa-gear me-1"></i> Edit
                        </a>
                        <button type="button"
                            onclick="confirmDelete('<?= $row->OwnerID ?>', '<?= wp_create_nonce('delete_owner_nonce') ?>')"
                            class="btn btn-sm btn-outline-danger"
                            style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; padding: 6px 12px;">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted bg-white rounded-4 border p-4">
                <i class="fa-solid fa-users-slash display-6 mb-2 text-muted"></i>
                <p class="mb-0 font-medium">No employee records found.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-wrapper table-wrapper-employee">
        <table class="table-custom" style="width: 100%;">
            <thead>
                <tr>
                    <th class="text-nowrap py-3 text-start" style="width: 12%;">Nickname</th>
                    <th class="text-nowrap py-3 text-start" style="width: 18%;">Full Name</th>
                    <th class="text-nowrap py-3 text-start" style="width: 22%;">Email</th>
                    <th class="text-nowrap py-3 text-start" style="width: 15%;">Department</th>
                    <th class="text-nowrap py-3 text-start" style="width: 13%;">Position</th>
                    <th class="text-nowrap py-3 text-start" style="width: 10%;">Status</th>
                    <th class="text-nowrap py-3 text-center" style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="next-table-row" style="animation-delay: <?= min($index * 0.05, 1) ?>s;">
                        <td class="text-start align-middle" data-label="ID">
                            <?php $deptAbbr = stock_supply_get_dept_abbr($row->Department ?? ''); ?>
                            <strong><?= esc_html($row->Nickname) ?></strong>
                            <?= $deptAbbr ? '<span class="text-muted small fw-normal">' . esc_html($deptAbbr) . '</span>' : '' ?>
                            <small class="text-muted d-block font-monospace">#<?= $row->OwnerID ?></small>
                        </td>
                        <td class="text-start align-middle" data-label="Employee">
                            <?= !empty(trim($row->FirstName . ' ' . $row->LastName)) ? esc_html(trim($row->FirstName . ' ' . $row->LastName)) . ($deptAbbr ? ' <span class="text-muted small">' . esc_html($deptAbbr) . '</span>' : '') : '-' ?>
                        </td>
                        <td class="text-start align-middle text-muted" data-label="Email">
                            <?= !empty($row->Email) ? esc_html($row->Email) : '-' ?>
                        </td>
                        <td class="text-start align-middle" data-label="Department">
                            <?= !empty($row->Department) ? esc_html($row->Department) : '-' ?>
                        </td>
                        <td class="text-start align-middle" data-label="Position">
                            <span
                                class="badge bg-light text-dark border"><?= !empty($row->Position) ? esc_html($row->Position) : '-' ?></span>
                        </td>
                        <td class="text-start align-middle" data-label="Status">
                            <?php
                            $status = $row->Status;
                            $statusClass = strcasecmp($status, 'Active') === 0 ? 'status-available' : 'status-inuse';
                            ?>
                            <span class="status-badge <?= $statusClass ?>">
                                <span class="status-dot"></span>
                                <?= esc_html($status) ?>
                            </span>
                        </td>

                        <td class="text-center align-middle" data-label="Action">
                            <div class="dropdown action-menu text-center">
                                <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                    ...
                                </button>
                                <div class="dropdown-menu action-dropdown text-start">
                                    <div class="action-dropdown-header">Actions</div>
                                    <div class="action-dropdown-separator"></div>
                                    <a href="?edit=<?= $row->OwnerID ?>"><i class="fa-solid fa-gear"></i> Edit</a>
                                    <a href="#"
                                        onclick="confirmDelete('<?= $row->OwnerID ?>', '<?= wp_create_nonce('delete_owner_nonce') ?>')"><i
                                            class="fa-solid fa-trash-can text-danger"></i> Delete</a>
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

    <style>
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

        .next-table td {
            padding: 1rem 1.5rem;
            border: none;
            vertical-align: middle;
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

    <!-- Employee Action Scripts -->
    <script>
        // Resign confirmation dialog
        function confirmResign(ownerID, nickname, nonce) {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ Confirm Resignation?',
                html: `<div style="text-align:center;">
                    <p style="margin-bottom:8px;">Employee <strong style="color:#0f172a;">${nickname}</strong> status will be changed to <span style="color:#d97706;font-weight:700;">Resigned</span></p>
                    <p style="color:#64748b; font-size:0.9rem;">All assigned devices will be automatically returned to Stock.<br>Employee record will remain in system (soft delete).</p>
                </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-user-minus me-1"></i> Confirm Resign',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?resign=' + ownerID + '&_wpnonce=' + nonce;
                }
            });
        }

        // Delete confirmation dialog
        function confirmDelete(ownerID, nonce) {
            Swal.fire({
                icon: 'error',
                title: '🗑️ Permanently Delete Employee?',
                html: `<p style="color:#64748b;">This action <strong style="color:#dc2626;">cannot be undone</strong>.<br>All employee data will be permanently removed.</p>`,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Delete Permanently',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?delete=' + ownerID + '&_wpnonce=' + nonce;
                }
            });
        }
    </script>

    <!-- Offboard Employee JavaScript -->
    <script>
        function offboardEmployee(ownerID, nickname) {
            const ajaxUrl = '<?= admin_url("admin-ajax.php") ?>';
            const ajaxNonce = '<?= wp_create_nonce("stock_supply_ajax_nonce") ?>';

            // Step 1: Show loading
            Swal.fire({
                title: 'Loading devices...',
                text: 'Fetching equipment assigned to ' + nickname,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // Step 2: AJAX — get devices
            const formData = new FormData();
            formData.append('action', 'get_employee_devices');
            formData.append('nonce', ajaxNonce);
            formData.append('owner_id', ownerID);

            fetch(ajaxUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(result => {
                    if (!result.success) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Devices Found',
                            html: '<p style="color:#64748b;">"<strong>' + nickname + '</strong>" does not have any assigned devices.</p>',
                            confirmButtonColor: '#1976D2'
                        });
                        return;
                    }

                    const devices = result.data.devices;
                    const owner = result.data.owner;
                    const count = result.data.count;

                    if (count === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Devices Found',
                            html: '<p style="color:#64748b;">"<strong>' + nickname + '</strong>" does not have any assigned devices.</p>',
                            confirmButtonColor: '#1976D2'
                        });
                        return;
                    }

                    // Step 3: Build device table HTML
                    let tableRows = '';
                    devices.forEach((d, i) => {
                        tableRows += `
                        <tr class="offboard-row">
                            <td class="offboard-td-num">${i + 1}</td>
                            <td class="offboard-td-id">${d.DeviceID}</td>
                            <td class="offboard-td-model">${d.Model || '-'}</td>
                            <td class="offboard-td-cat">
                                <span class="offboard-cat-badge">${d.CategoryName || '-'}</span>
                            </td>
                            <td class="offboard-td-status">
                                <span class="offboard-status-badge">${d.StatusName || '-'}</span>
                            </td>
                        </tr>`;
                    });

                    const modalHtml = `
                    <div class="offboard-modal-container">
                        <div class="offboard-employee-info">
                            <div class="offboard-avatar">
                                <i class="fa-solid fa-user-large"></i>
                            </div>
                            <div class="offboard-emp-details">
                                <div class="offboard-emp-name">${owner ? owner.full_name : nickname}</div>
                                <div class="offboard-emp-nick">${owner ? owner.nickname : ''}</div>
                            </div>
                            <div class="offboard-device-count">
                                <span class="offboard-count-num">${count}</span>
                                <span class="offboard-count-label">device${count > 1 ? 's' : ''}</span>
                            </div>
                        </div>
                        <div class="offboard-table-wrap">
                            <table class="offboard-device-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Device ID</th>
                                        <th>Model</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>${tableRows}</tbody>
                            </table>
                        </div>
                    </div>`;

                    // Step 4: Show confirmation modal
                    Swal.fire({
                        title: 'Offboard Employee',
                        html: modalHtml,
                        width: 700,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-rotate-left"></i> Unassign All & Return to Stock',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#D32F2F',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            popup: 'offboard-swal-popup',
                            confirmButton: 'offboard-confirm-btn',
                        },
                        reverseButtons: true
                    }).then((confirmResult) => {
                        if (!confirmResult.isConfirmed) return;

                        // Step 5: Execute offboard
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Returning ' + count + ' device(s) to stock',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        const offboardData = new FormData();
                        offboardData.append('action', 'offboard_employee');
                        offboardData.append('nonce', ajaxNonce);
                        offboardData.append('owner_id', ownerID);

                        fetch(ajaxUrl, { method: 'POST', body: offboardData })
                            .then(res => res.json())
                            .then(offResult => {
                                if (offResult.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Offboard Complete!',
                                        html: '<p>' + offResult.data.message + '</p>',
                                        showConfirmButton: false,
                                        timer: 2000
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Offboard Failed',
                                        text: offResult.data ? offResult.data.message : 'An error occurred.',
                                        confirmButtonColor: '#1976D2'
                                    });
                                }
                            })
                            .catch(err => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Network Error',
                                    text: 'Could not connect to the server.',
                                    confirmButtonColor: '#1976D2'
                                });
                            });
                    });
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not connect to the server.',
                        confirmButtonColor: '#1976D2'
                    });
                });
        }
    </script>

    <?php

    return ob_get_clean();
}
add_shortcode('form_owner', 'form_owner');
