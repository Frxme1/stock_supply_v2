<?php
function form_owner()
{
    global $wpdb;
    $table_owner = 'Owners';
    $table_owner_wn = 'ViewOwnersWithNames';
    $table_devices = 'Devices';


    ob_start();
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';



    if (isset($_GET['delete'])) {
        $owner_id = intval($_GET['delete']);

        // get Owner data
        $owner_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_owner_wn WHERE OwnerID = %d", $owner_id));

        if ($owner_data) {
            $owner_fullname = $owner_data->FirstName . ' ' . $owner_data->LastName;

            // insert to History_new (Action Delete)
            $wpdb->insert('History_new', [
                'DeviceID'    => $owner_data->OwnerID,
                'Action'      => 'Delete Employee',
                'Date'        => current_time('mysql'),
                'Description' => 'Deleted employee: ' . $owner_fullname,
                'user_email'  => wp_get_current_user()->user_email,
                'CategoryID'  => 'Employee',
                'Owner'       => $owner_fullname,
            ]);

            // ดึง StatusID ของ 'Available'
            $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");

            // update Devices  Owner 
            $wpdb->update(
                $table_devices,
                [
                    'StatusID'     => $available_status_id,
                    'OwnerID'      => null,
                    'DepartmentID' => null,
                    'ReceiveDate'  => null,
                    'ReturnDate'   => null,
                    'RepairDate'   => null
                ],
                ['OwnerID' => $owner_id],
                ['%d', null, null, null, null, null],
                ['%d']
            );

            // delete Owner
            $wpdb->delete($table_owner, ['OwnerID' => $owner_id]);
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Deleted Employee',
                text: 'The employee has been removed.',
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



    // section search
    $search = isset($_GET['device_search']) ? $_GET['device_search'] : '';
    $search_sql = '';
    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_sql = $wpdb->prepare(
            "WHERE Nickname LIKE %s OR FirstName LIKE %s OR LastName LIKE %s OR Department LIKE %s OR Position LIKE %s OR Status LIKE %s",
            $like,
            $like,
            $like,
            $like,
            $like,
            $like
        );
    }

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_owner_wn $search_sql");
    $total_pages = ceil($total_items / $page);
    $rows = $wpdb->get_results("SELECT * FROM $table_owner_wn $search_sql ORDER BY OwnerID DESC LIMIT $page OFFSET $offset");

    $suggestions = $wpdb->get_col("SELECT DISTINCT Nickname FROM $table_owner_wn ORDER BY OwnerType ASC");

    ob_start();




?>

    <!-- form search -->
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
            <label class="col-auto col-form-label">Employee</label>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <input type="text" name="device_search" list="search_suggestions" class="form-control" placeholder="Search Employee..." value="<?= esc_attr($search) ?>" />
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


    <!-- form add-owner -->
    <form action="<?= esc_url(home_url('/add-owner/')) ?>" method="post" style="margin-top: 10px;">
        <div class="section-search" style="margin-bottom: 20px;">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <button class="rounded-pill" style="width: 6rem; background-color: #6ABF57;" type="submit">Add</button>
            </div>
        </div>
    </form>

    <div class="table-responsive-xl rounded">
        <table class="table table-bordered table-sm">
            <thead class="table-secondary">
                <tr>
                    <th class="text-nowrap py-3 text-start" style="width: 15%;">NickName</th>
                    <th class="text-nowrap py-3 text-start" style="width: 15%;">FirstName</th>
                    <th class="text-nowrap py-3 text-start" style="width: 15%;">LastName</th>
                    <th class="text-nowrap py-3 text-start" style="width: 25%;">Department</th>
                    <th class="text-nowrap py-3 text-start" style="width: 10%;">Position</th>
                    <th class="text-nowrap py-3 text-start" style="width: 10%;">Status</th>
                    <th class="text-nowrap py-3 text-center" style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td class="text-start align-middle"><?= $row->Nickname ?></td>
                    <td class="text-start align-middle"><?= $row->FirstName ?></td>
                    <td class="text-start align-middle"><?= $row->LastName ?></td>
                    <td class="text-start align-middle" style="width: 200px;"><?= $row->Department ?></td>
                    <td class="text-start align-middle"><?= $row->Position ?></td>
                    <td class="text-start align-middle">
                        <?php
                        $status = $row->Status;
                        $emojis = [
                            'Active'   => '<i class="fa-solid fa-circle text-success" style="font-size:12px;"></i>',
                            'Resigned' => '<i class="fa-solid fa-circle text-danger" style="font-size:12px;"></i>',
                        ];
                        echo ($emojis[$status] ?? '') . ' ' . esc_html($status);
                        ?>
                    </td>

                    <td class="text-center align-middle">
                        <div class="dropdown action-menu text-center">
                            <button type="button" class="action-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                ...
                            </button>
                            <div class="dropdown-menu action-dropdown text-start">
                                <div class="action-dropdown-header">Actions</div>
                                <div class="action-dropdown-separator"></div>
                                <a href="?edit=<?= $row->OwnerID ?>"><i class="fa-solid fa-gear"></i> Edit</a>
                                <?php if ($row->Status == 'Available'): ?>
                                    <a href="?receive=<?= $row->OwnerID ?>"><i class="fa-solid fa-box"></i> Receive</a>
                                    <a href="?maintenance=<?= $row->OwnerID ?>"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                    <a href="#" onclick="confirmRetire('<?= $row->OwnerID ?>', 'retire'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                <?php elseif ($row->Status == 'In Use'): ?>
                                    <a href="?return=<?= $row->OwnerID ?>"><i class="fa-solid fa-rotate-left"></i> Return</a>
                                    <a href="?maintenance=<?= $row->OwnerID ?>"><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance</a>
                                    <a href="#" onclick="confirmRetire('<?= $row->OwnerID ?>', 'retire'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                <?php elseif ($row->Status == 'Maintenance'): ?>
                                    <a href="?available=<?= $row->OwnerID ?>"><i class="fa-solid fa-circle text-success"></i> Available</a>
                                    <a href="#" onclick="confirmRetire('<?= $row->OwnerID ?>', 'retire'); return false;"><i class="fa-solid fa-circle text-dark"></i> Retired</a>
                                <?php elseif ($row->Status == 'Retired'): ?>
                                    <a href="?available=<?= $row->OwnerID ?>"><i class="fa-solid fa-circle text-success"></i> Available</a>
                                <?php endif; ?>
                                <a href="#" onclick="confirmDelete('<?= $row->OwnerID ?>')"><i class="fa-solid fa-trash-can"></i> Delete</a>
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



<?php

    return ob_get_clean();
}
add_shortcode('form_owner', 'form_owner');
