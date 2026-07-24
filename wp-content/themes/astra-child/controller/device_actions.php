<?php
if (!defined('ABSPATH')) {
    exit;
}

function handle_device_actions()
{
    if (!is_user_logged_in()) {
        return;
    }

    global $wpdb;
    $table_devices = 'Devices';
    $table_maintenance = 'Maintenance';

    // Handle Bulk Actions
    if (isset($_POST['bulk_action']) && !empty($_POST['bulk_device_ids'])) {
        if (!isset($_POST['bulk_action_nonce']) || !wp_verify_nonce($_POST['bulk_action_nonce'], 'bulk_device_action_nonce')) {
            return;
        }
        $action = sanitize_text_field($_POST['bulk_action']);
        $device_ids = array_map('sanitize_text_field', $_POST['bulk_device_ids']);
        
        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email ?? 'unknown@domain.com';
        
        $success_count = 0;
        
        foreach ($device_ids as $device_id) {
            $device_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id));
            if (!$device_info) continue;
            
            $owner_nickname = null;
            if (!empty($device_info->OwnerID)) {
                $owner_nickname = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device_info->OwnerID));
            }
            $safe_owner = $owner_nickname ?? '-';
            $safe_category_id = !empty($device_info->CategoryID) ? $device_info->CategoryID : null;
            
            if ($action === 'delete') {
                $wpdb->insert('History_new', [
                    'DeviceID'    => $device_info->DeviceID,
                    'Action'      => 'Delete Device',
                    'Date'        => current_time('mysql'),
                    'Description' => 'Bulk Deleted Device ID : ' . $device_info->DeviceID . ' - ' . $device_info->Model . ' (SN: ' . $device_info->SerialNumber . ')',
                    'user_email'  => $user_email,
                    'CategoryID'  => $safe_category_id,
                    'Owner'       => $safe_owner,
                ]);
                $wpdb->delete($table_maintenance, ['DeviceID' => $device_id], ['%s']);
                $wpdb->delete($table_devices, ['DeviceID' => $device_id], ['%s']);
                $success_count++;
                
            } elseif ($action === 'available') {
                $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
                if ($available_status_id) {
                    $wpdb->delete($table_maintenance, ['DeviceID' => $device_id], ['%s']);
                    $updated = $wpdb->update(
                        $table_devices,
                        [
                            'StatusID'     => $available_status_id,
                            'OwnerID'      => null,
                            'DepartmentID' => null,
                            'ReceiveDate'  => null,
                            'ReturnDate'   => null,
                            'RepairDate'   => null,
                            'PositionID'   => null
                        ],
                        ['DeviceID' => $device_id],
                        ['%d', null, null, null, null, null, null],
                        ['%s']
                    );
                    if ($updated !== false) {
                        $wpdb->insert('History_new', [
                            'DeviceID'    => $device_id,
                            'Action'      => 'Available',
                            'Date'        => current_time('mysql'),
                            'Description' => "Bulk Action: Device ID {$device_id} set to Available",
                            'user_email'  => $user_email,
                            'CategoryID'  => $safe_category_id,
                            'Owner'       => $safe_owner
                        ]);
                        $success_count++;
                    }
                }
                
            } elseif ($action === 'retired') {
                $retired_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Retired'");
                if ($retired_status_id) {
                    $updated = $wpdb->update(
                        $table_devices,
                        [
                            'StatusID'     => $retired_status_id,
                            'OwnerID'      => null,
                            'DepartmentID' => null,
                            'ReceiveDate'  => null,
                            'RepairDate'   => null,
                            'ReturnDate'   => null,
                            'PositionID'   => null
                        ],
                        ['DeviceID' => $device_id]
                    );
                    if ($updated !== false) {
                        $wpdb->insert('History_new', [
                            'DeviceID'    => $device_id,
                            'Action'      => 'Retired',
                            'Date'        => current_time('mysql'),
                            'Description' => "Bulk Action: Device ID {$device_id} set to Retired",
                            'user_email'  => $user_email,
                            'CategoryID'  => $safe_category_id,
                            'Owner'       => $safe_owner
                        ]);
                        $success_count++;
                    }
                }
            }
        }
        
        if ($success_count > 0) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Bulk Action Success!',
                text: 'Processed {$success_count} devices successfully.',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = window.location.href; // Redirects to clear POST data while keeping GET params
            });
            </script>";
            exit;
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Bulk Action Failed!',
                text: 'No devices were updated.',
                showConfirmButton: true
            });
            </script>";
        }
    }


    if (isset($_GET['delete'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'device_action_nonce')) {
            return;
        }
        $device_id = sanitize_text_field($_GET['delete']);

        // Get Device
        $device_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id)
        );

        if ($device_data) {
            // Get Nickname for Owner from OwnerID
            $owner_nickname = '-';
            if (!empty($device_data->OwnerID)) {
                $owner = $wpdb->get_row(
                    $wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device_data->OwnerID)
                );
                if ($owner && !empty($owner->Nickname)) {
                    $owner_nickname = $owner->Nickname;
                }
            }

            // Insert into History_new
            $wpdb->insert('History_new', [
                'DeviceID'    => $device_data->DeviceID,
                'Action'      => 'Delete Device',
                'Date'        => current_time('mysql'),
                'Description' => 'Deleted Device ID : ' . $device_data->DeviceID . ' - ' . $device_data->Model . ' (SN: ' . $device_data->SerialNumber . ')',
                'user_email'  => wp_get_current_user()->user_email,
                'CategoryID'  => $device_data->CategoryID,
                'Owner'       => $owner_nickname,
            ]);

            // Delete related Maintenance records
            $wpdb->delete('Maintenance', ['DeviceID' => $device_id], ['%s']);

            // Delete Device
            $wpdb->delete($table_devices, ['DeviceID' => $device_id], ['%s']);
        }
    }






    if (isset($_GET['edit'])) {
        $device_id = $_GET['edit'];
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id));
        return edit_device_form($editing);
    }



    if (isset($_GET['receive'])) {
        $device_id = $_GET['receive'];
        $device = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $device_id));
        return receive_device($device);
    }


    if (isset($_GET['maintenance'])) {
        $device_id = sanitize_text_field($_GET['maintenance']);
        $editing = $wpdb->get_row($wpdb->prepare("
        SELECT d.*, m.RepairDate, m.Details
        FROM {$table_devices} d
        LEFT JOIN Maintenance m ON d.DeviceID = m.DeviceID
        WHERE d.DeviceID = %s
        ORDER BY m.MaintenanceID DESC
        LIMIT 1
    ", $device_id));
        return form_maintenance($editing);
    }


    if (isset($_GET['view'])) {
        $device_id = $_GET['view'];
        return device_view_details($device_id);
    }


    if (isset($_GET['return'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'device_action_nonce')) {
            return;
        }
        $device_id = $_GET['return'];
        $return_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
        $return_date = current_time('Y-m-d');

        if ($return_status_id) {
            // Pull Data
            $device_info = $wpdb->get_row($wpdb->prepare(
                "SELECT OwnerID, CategoryID FROM $table_devices WHERE DeviceID = %s",
                $device_id
            ));

            if ($device_info) {
                $update_data = [
                    'StatusID'           => $return_status_id,
                    'ReturnDate'         => $return_date,
                    'DepartmentID'       => null,
                    'ReceiveDate'        => null,
                    'RepairDate'         => null,
                    'ExpectedReturnDate' => null,
                    'LastNotifiedDate'   => null,
                ];

                // Keep the original owner for the ReturnDate trigger, then clear the assignment.
                $wpdb->query('START TRANSACTION');
                $return_updated = $wpdb->update($table_devices, $update_data, ['DeviceID' => $device_id]);
                $owner_cleared = $return_updated !== false
                    ? $wpdb->update(
                        $table_devices,
                        ['OwnerID' => null, 'PositionID' => null],
                        ['DeviceID' => $device_id],
                        [null, null],
                        ['%s']
                    )
                    : false;

                if ($return_updated !== false && $owner_cleared !== false) {
                    $wpdb->query('COMMIT');
                    $updated = true;
                } else {
                    $wpdb->query('ROLLBACK');
                    $updated = false;
                }

                // if update pass -> History
                if ($updated) {
                    $owner_nickname = null;
                    if ($device_info->OwnerID) {
                        $owner_nickname = $wpdb->get_var($wpdb->prepare(
                            "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                            $device_info->OwnerID
                        ));
                    }

                    $current_user = wp_get_current_user();
                    $user_email = $current_user->user_email ?? '';

                    $wpdb->insert('History_new', [
                        'DeviceID'    => $device_id,
                        'Action'      => 'Return',
                        'Date'        => current_time('mysql'),
                        'Description' => "Device ID {$device_id} returned and status set to Available",
                        'user_email'  => $user_email,
                        'CategoryID'  => $device_info->CategoryID,
                        'Owner'       => $owner_nickname ?? '-'
                    ]);

                    // ส่งอีเมลแจ้งเตือน
                    if (function_exists('stock_supply_send_email') && $device_info->OwnerID) {
                        stock_supply_send_email('Return', $device_id, $device_info->OwnerID);
                    }

                    $category_slug = $wpdb->get_var($wpdb->prepare(
                        "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                        $device_info->CategoryID
                    ));


                    $redirect_url = $category_slug ? home_url('/' . sanitize_title($category_slug) . '/') : home_url('/');

                    // Redirect 
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Return Success!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '{$redirect_url}';
                    });
                </script>";
                    exit;
                }
            }
        }

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Return Failed!',
            showConfirmButton: true
        });
    </script>";
        return false;
    }


    if (isset($_GET['available'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'device_action_nonce')) {
            return;
        }
        $device_id = sanitize_text_field($_GET['available']);
        $available_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");

        if ($available_status_id) {
            $device_info = $wpdb->get_row($wpdb->prepare(
                "SELECT OwnerID, CategoryID FROM $table_devices WHERE DeviceID = %s",
                $device_id
            ));

            $wpdb->delete($table_maintenance, ['DeviceID' => $device_id], ['%s']);

            $updated = $wpdb->update(
                $table_devices,
                [
                    'StatusID'     => $available_status_id,
                    'OwnerID'      => null,
                    'DepartmentID' => null,
                    'ReceiveDate'  => null,
                    'ReturnDate'   => null,
                    'RepairDate'   => null,
                    'PositionID'   => null
                ],
                ['DeviceID' => $device_id],
                ['%d', null, null, null, null, null, null],
                ['%s']
            );

            // เพิ่มข้อมูลลง History_new ถ้าอัปเดตผ่าน
            if ($updated !== false) {
                $owner_nickname = null;
                if (!empty($device_info->OwnerID)) {
                    $owner_nickname = $wpdb->get_var($wpdb->prepare(
                        "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                        $device_info->OwnerID
                    ));
                }

                // fallback หากไม่มีค่า (เนื่องจาก NOT NULL)
                    $safe_category_id = !empty($device_info->CategoryID) ? $device_info->CategoryID : null;
                $safe_owner       = $owner_nickname ?? '-';

                $current_user = wp_get_current_user();
                $user_email = $current_user->user_email ?? 'unknown@domain.com';

                $wpdb->insert('History_new', [
                    'DeviceID'    => $device_id,
                    'Action'      => 'Available',
                    'Date'        => current_time('mysql'),
                    'Description' => "Device ID {$device_id} set to Available",
                    'user_email'  => $user_email,
                    'CategoryID'  => $safe_category_id,
                    'Owner'       => $safe_owner
                ]);
            }
        }
        // Redirect
        $category_slug = $wpdb->get_var($wpdb->prepare(
            "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
            $device_info->CategoryID
        ));
        $redirect_url = $category_slug ? home_url('/' . sanitize_title($category_slug) . '/') : home_url('/');

        if ($updated !== false && $updated >= 0) {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Device Available!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = '{$redirect_url}';
            });
        </script>";
            exit;
        } else {
            echo "<p>Can't Change to Available due to database error.</p>";
        }
    }
    if (isset($_GET['return_to_owner'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'device_action_nonce')) {
            return;
        }
        $device_id = sanitize_text_field($_GET['return_to_owner']);
        $inuse_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");

        if ($inuse_status_id) {
            $device_info = $wpdb->get_row($wpdb->prepare(
                "SELECT OwnerID, CategoryID FROM $table_devices WHERE DeviceID = %s",
                $device_id
            ));

            if ($device_info && !empty($device_info->OwnerID)) {
                $wpdb->delete($table_maintenance, ['DeviceID' => $device_id], ['%s']);
                
                $updated = $wpdb->update(
                    $table_devices,
                    [
                        'StatusID'     => $inuse_status_id,
                        'RepairDate'   => null
                    ],
                    ['DeviceID' => $device_id],
                    ['%d', null],
                    ['%s']
                );

                if ($updated !== false) {
                    $owner_nickname = $wpdb->get_var($wpdb->prepare(
                        "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                        $device_info->OwnerID
                    ));

                    $safe_category_id = !empty($device_info->CategoryID) ? $device_info->CategoryID : null;
                    $safe_owner       = $owner_nickname ?? '-';
                    $current_user = wp_get_current_user();
                    $user_email = $current_user->user_email ?? 'unknown@domain.com';

                    $wpdb->insert('History_new', [
                        'DeviceID'    => $device_id,
                        'Action'      => 'Return to Owner',
                        'Date'        => current_time('mysql'),
                        'Description' => "Device ID {$device_id} repaired and returned to owner",
                        'user_email'  => $user_email,
                        'CategoryID'  => $safe_category_id,
                        'Owner'       => $safe_owner
                    ]);

                    $wpdb->update(
                        'Repair_Requests',
                        ['Status' => 'Completed', 'ActionDate' => current_time('mysql')],
                        ['DeviceID' => $device_id]
                    );

                    // ส่งอีเมลแจ้งเตือน
                    if (function_exists('stock_supply_send_email')) {
                        stock_supply_send_email('Return_to_Owner', $device_id, $device_info->OwnerID);
                    }
                }
                
                $category_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                    $device_info->CategoryID
                ));
                $redirect_url = $category_slug ? home_url('/' . sanitize_title($category_slug) . '/') : home_url('/');

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Returned to Owner!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{$redirect_url}';
                });
                </script>";
                exit;
            } else {
                 echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                 echo "<script>
                 Swal.fire({
                     icon: 'error',
                     title: 'Failed',
                     text: 'This device does not have an owner.',
                     showConfirmButton: true
                 });
                 </script>";
                 exit;
            }
        }
    }



    if (isset($_GET['retired'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'device_action_nonce')) {
            return;
        }
        $device_id = sanitize_text_field($_GET['retired']);
        $retired_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Retired'");

        if ($retired_status_id) {
            // ดึงข้อมูล Device
            $device_info = $wpdb->get_row($wpdb->prepare(
                "SELECT OwnerID, CategoryID FROM $table_devices WHERE DeviceID = %s",
                $device_id
            ));

            if ($device_info) {
                // อัปเดตสถานะเป็น Retired
                $update_data = [
                    'StatusID'     => $retired_status_id,
                    'OwnerID'      => null,
                    'DepartmentID' => null,
                    'ReceiveDate'  => null,
                    'RepairDate'   => null,
                    'ReturnDate'   => null,
                    'PositionID'   => null
                ];

                $updated = $wpdb->update($table_devices, $update_data, ['DeviceID' => $device_id]);

                if ($updated !== false) {
                    // เตรียมข้อมูล History
                    $owner_nickname = null;
                    if (!empty($device_info->OwnerID)) {
                        $owner_nickname = $wpdb->get_var($wpdb->prepare(
                            "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                            $device_info->OwnerID
                        ));
                    }

                    $current_user = wp_get_current_user();
                    $user_email = $current_user->user_email ?? 'unknown@domain.com';

                    // fallback ค่าที่จำเป็น (เพราะ NOT NULL)
                        $safe_category_id = !empty($device_info->CategoryID) ? $device_info->CategoryID : null;
                    $safe_owner       = $owner_nickname ?? '-';

                    $reason = isset($_GET['reason']) ? sanitize_text_field($_GET['reason']) : '';
                    $description_text = "Device ID {$device_id} set to Retired";
                    if (!empty($reason)) {
                        $description_text .= " | Reason: " . $reason;
                    }

                    $insert_result = $wpdb->insert('History_new', [
                        'DeviceID'    => $device_id,
                        'Action'      => 'Retired',
                        'Date'        => current_time('mysql'),
                        'Description' => $description_text,
                        'user_email'  => $user_email,
                        'CategoryID'  => $safe_category_id,
                        'Owner'       => $safe_owner
                    ]);

                    if ($insert_result === false) {
                        error_log('[ERROR] Insert History_new (Retired) failed: ' . $wpdb->last_error);
                        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                        echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'History Insert Failed!',
                            text: 'Error: " . esc_js($wpdb->last_error) . "',
                            showConfirmButton: true
                        });
                        </script>";
                        exit;
                    }
                }

                // Redirect
                $category_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                    $device_info->CategoryID
                ));

                $redirect_url = $category_slug ? home_url('/' . sanitize_title($category_slug) . '/') : home_url('/');

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Retired Success!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{$redirect_url}';
                });
            </script>";
                exit;
            }
        }

        // กรณีล้มเหลว
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Retired Failed!',
            showConfirmButton: true
        });
    </script>";
        return false;
    }

    if (isset($_GET['lost'])) {
        $device_id = sanitize_text_field($_GET['lost']);
        $lost_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Lost'");

        if ($lost_status_id) {
            // ดึงข้อมูล Device
            $device_info = $wpdb->get_row($wpdb->prepare(
                "SELECT OwnerID, CategoryID FROM $table_devices WHERE DeviceID = %s",
                $device_id
            ));

            if ($device_info) {
                // อัปเดตสถานะเป็น Lost
                $update_data = [
                    'StatusID'     => $lost_status_id,
                    'OwnerID'      => null,
                    'DepartmentID' => null,
                    'ReceiveDate'  => null,
                    'RepairDate'   => null,
                    'ReturnDate'   => null,
                    'PositionID'   => null
                ];

                $updated = $wpdb->update($table_devices, $update_data, ['DeviceID' => $device_id]);

                if ($updated !== false) {
                    // เตรียมข้อมูล History
                    $owner_nickname = null;
                    if (!empty($device_info->OwnerID)) {
                        $owner_nickname = $wpdb->get_var($wpdb->prepare(
                            "SELECT Nickname FROM Owners WHERE OwnerID = %d",
                            $device_info->OwnerID
                        ));
                    }

                    $current_user = wp_get_current_user();
                    $user_email = $current_user->user_email ?? 'unknown@domain.com';

                    $safe_category_id = !empty($device_info->CategoryID) ? $device_info->CategoryID : null;
                    $safe_owner       = $owner_nickname ?? '-';

                    $reason = isset($_GET['reason']) ? sanitize_text_field($_GET['reason']) : '';
                    $description_text = "Device ID {$device_id} set to Lost";
                    if (!empty($reason)) {
                        $description_text .= " | Reason: " . $reason;
                    }

                    $wpdb->insert('History_new', [
                        'DeviceID'    => $device_id,
                        'Action'      => 'Lost',
                        'Date'        => current_time('mysql'),
                        'Description' => $description_text,
                        'user_email'  => $user_email,
                        'CategoryID'  => $safe_category_id,
                        'Owner'       => $safe_owner
                    ]);
                }

                // Redirect
                $category_slug = $wpdb->get_var($wpdb->prepare(
                    "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                    $device_info->CategoryID
                ));

                $redirect_url = $category_slug ? home_url('/' . sanitize_title($category_slug) . '/') : home_url('/');

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated to Lost!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{$redirect_url}';
                });
            </script>";
                exit;
            }
        } else {
             echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
             echo "<script>
             Swal.fire({
                 icon: 'warning',
                 title: 'Status Not Found!',
                 text: 'Please add \"Lost\" to your Statuses table.',
                 showConfirmButton: true
             });
         </script>";
         exit;
        }
    }
}
