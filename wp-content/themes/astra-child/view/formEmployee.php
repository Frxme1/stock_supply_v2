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
                window.location.href = '/Owner';
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
    $search = isset($_GET['search']) ? $_GET['search'] : '';
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
        <div class="row align-items-center g-2">
            <label class="col-auto col-form-label">Employee</label>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <input type="text" name="search" list="search_suggestions" class="form-control" placeholder="Search Employee..." value="<?= esc_attr($search) ?>" />
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


    <!-- form add-owner -->
    <form action="/add-owner" method="post" style="margin-top: 10px;">
        <div class="section-search" style="margin-bottom: 20px;">
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <button class="rounded-pill" style="width: 6rem; background-color: #6ABF57;" type="submit">Add</button>
            </div>
        </div>
    </form>

    <div class="table-responsive-xl">
        <table class="table table-bordered text-center">
            <tr class="table-secondary">
                <th class="py-3">NickName</th>
                <th class="py-3">FirstName</th>
                <th class="py-3">LastName</th>
                <th class="py-3">Department</th>
                <th class="py-3">Position</th>
                <th class="py-3">Status</th>
                <th class="py-3">Action</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row->Nickname ?></td>
                    <td><?= $row->FirstName ?></td>
                    <td><?= $row->LastName ?></td>
                    <td style="width: 200px;"><?= $row->Department ?></td>
                    <td><?= $row->Position ?></td>
                    <td>
                        <?php
                        $status = $row->Status;
                        $emojis = [
                            'Active'   => '🟢',
                            'Resigned' => '🔴',
                        ];
                        echo ($emojis[$status] ?? '') . ' ' . esc_html($status);
                        ?>
                    </td>

                    <td>
                        <div class="action-menu">
                            <div style="text-align: center;">
                                <button class="action-btn" style="background-color: #8bd8f4;">⋮</button>
                            </div>
                            <div class="action-dropdown" style="text-align: start;">
                                <a href="?edit=<?= $row->OwnerID ?>">⚙️ Edit</a>
                                <?php if ($row->Status == 'Available'): ?>
                                    <a href="?receive=<?= $row->OwnerID ?>">📦 Receive</a>
                                    <a href="?maintenance=<?= $row->OwnerID ?>">🛠 Maintenance</a>
                                    <a href="?retire=<?= $row->OwnerID ?>">⚫ Retired</a>
                                <?php elseif ($row->Status == 'In Use'): ?>
                                    <a href="?return=<?= $row->OwnerID ?>">↩️ Return</a>
                                    <a href="?maintenance=<?= $row->OwnerID ?>">🛠 Maintenance</a>
                                    <a href="?retire=<?= $row->OwnerID ?>">⚫ Retired</a>
                                <?php elseif ($row->Status == 'Maintenance'): ?>
                                    <a href="?available=<?= $row->OwnerID ?>">🟢 Available</a>
                                    <a href="?retire=<?= $row->OwnerID ?>">⚫ Retired</a>
                                <?php elseif ($row->Status == 'Retired'): ?>
                                    <a href="?available=<?= $row->OwnerID ?>">🟢 Available</a>
                                <?php endif; ?>
                                <a href="#" onclick="confirmDelete('<?= $row->OwnerID ?>')">🗑 Delete</a>
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



<?php

    return ob_get_clean();
}
add_shortcode('form_owner', 'form_owner');
