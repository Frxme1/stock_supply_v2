<?php
function edit_device_form($editing = null)
{
    ob_start();
    global $wpdb;

    $table_devices = 'Devices';

    $brands = $wpdb->get_results("SELECT BrandID, BrandName FROM Brands");
    $statuses = $wpdb->get_results("SELECT StatusID, StatusName FROM Statuses");
    $keywords = $wpdb->get_results("SELECT KeywordID, KeywordName FROM Keywords");
    $categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories");
    $owners = $wpdb->get_results("SELECT OwnerID, Nickname, FirstName, LastName FROM Owners ORDER BY Nickname ASC");


    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if (!$editing && !empty($_GET['device'])) {
        $DeviceID = sanitize_text_field($_GET['device']);
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['DeviceID'])) {
        $DeviceID       = sanitize_text_field($_POST['DeviceID']);
        $Model          = sanitize_text_field($_POST['Model']);
        $SerialNumber   = sanitize_text_field($_POST['SerialNumber']);
        $BrandID        = intval($_POST['BrandID']);
        $StatusID       = intval($_POST['StatusID']);
        $KeywordID      = intval($_POST['KeywordID']);
        $OwnerID        = !empty($_POST['OwnerID']) ? intval($_POST['OwnerID']) : null;
        $AddDeviceDate_edit = sanitize_text_field($_POST['AddDeviceDate']);
        $AddDeviceDate  = date('Y-m-d', strtotime($AddDeviceDate_edit));
        $Reason         = !empty($_POST['Reason']) ? sanitize_text_field($_POST['Reason']) : '';

        // Validate IDs
        $valid_brand   = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM Brands WHERE BrandID = %d", $BrandID));
        $valid_status  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM Statuses WHERE StatusID = %d", $StatusID));
        $valid_keyword = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM Keywords WHERE KeywordID = %d", $KeywordID));

        if (!$valid_brand || !$valid_status || !$valid_keyword) {
            $message = !$valid_brand ? 'Invalid Brand selected!' : (!$valid_status ? 'Invalid Status selected!' : 'Invalid Keyword selected!');
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: '{$message}',
                    showConfirmButton: true
                });
            </script>";
            return ob_get_clean(); // แก้ตรงนี้: return ผลลัพธ์ ไม่ใช่หยุดด้วย return ธรรมดา
        }

        $data = [
            'Model'         => $Model,
            'SerialNumber'  => $SerialNumber,
            'BrandID'       => $BrandID,
            'StatusID'      => $StatusID,
            'KeywordID'     => $KeywordID,
            'OwnerID'       => $OwnerID,
            'AddDeviceDate' => $AddDeviceDate,
            'UpdatedAt'     => current_time('mysql'),
        ];

        $format = ['%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s'];
        $where = ['DeviceID' => $DeviceID];
        $where_format = ['%s'];

        // Get previous info for history
        $device_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));

        $updated = $wpdb->update($table_devices, $data, $where, $format, $where_format);

        // Get category slug
        $category_slug = '';
        if ($device_info && isset($device_info->CategoryID)) {
            $category_name = $wpdb->get_var($wpdb->prepare(
                "SELECT CategoryName FROM Categories WHERE CategoryID = %d",
                $device_info->CategoryID
            ));
            if ($category_name) {
                $category_slug = sanitize_title($category_name);
            }
        }
        $redirect_url = $category_slug ? home_url('/' . $category_slug . '/') : home_url('/');

        $owner_nickname = '-';
        if (!empty($device_info->OwnerID)) {
            $owner_info = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $device_info->OwnerID));
            if ($owner_info) {
                $owner_nickname = $owner_info;
            }
        }

        if ($updated === false) {
            error_log('Update failed: ' . $wpdb->last_error);
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Update failed',
                    text: '" . esc_js($wpdb->last_error) . "',
                    showConfirmButton: true
                });
            </script>";
        } elseif ($updated === 0) {
            echo "<script>
                Swal.fire({
                    icon: 'info',
                    title: 'No changes detected',
                    showConfirmButton: true
                });
            </script>";
        } else {
            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? '';

            $history_description = "Device ID {$DeviceID} information updated";
            if ($Reason !== '') {
                $history_description .= " | Reason: " . $Reason;
            }

            $wpdb->insert('History_new', [
                'DeviceID'    => $DeviceID,
                'Action'      => 'Update Device',
                'Date'        => current_time('mysql'),
                'Description' => $history_description,
                'user_email'  => $user_email,
                'CategoryID'  => $device_info->CategoryID ?? null,
                'Owner'       => $owner_nickname
            ]);

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Device updated!',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = '{$redirect_url}';
                });
            </script>";
        }

        // รีเฟรชหลัง update
        $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_devices WHERE DeviceID = %s", $DeviceID));
    }
?>

    <!-- HTML ฟอร์ม -->
    <form method="POST" action="">
        <h2 style="text-align: center;">Edit Device</h2>
        <input type="hidden" name="DeviceID" value="<?= esc_attr($editing->DeviceID ?? '') ?>">
        <?php
        $hideKeyword = false;
        foreach ($categories as $c) {
            if (isset($editing->CategoryID) && $c->CategoryID == $editing->CategoryID) {
                $cName = strtolower(trim($c->CategoryName));
                if ($cName === 'monitor' || $cName === 'laptop') {
                    $hideKeyword = true;
                }
                break;
            }
        }
        ?>
        <div class="form-grid">



            <div class="form-group">
                <label>Category</label>
                <select disabled style="background-color: #f0f0f0; color: #666; cursor: not-allowed;">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= esc_attr($c->CategoryID) ?>" <?= selected($editing->CategoryID ?? '', $c->CategoryID, false) ?>>
                            <?= esc_html($c->CategoryName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label>DeviceID</label>
                <input type="text" style="background-color: #f0f0f0; color: #666; cursor: not-allowed;" value="<?= esc_attr($editing->DeviceID ?? '') ?>" disabled>
            </div>

            <div class="form-group">
                <label>Brand</label>
                <select name="BrandID" required>
                    <option value="">-- Select Brand --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= esc_attr($b->BrandID) ?>" <?= selected($editing->BrandID ?? '', $b->BrandID, false) ?>>
                            <?= esc_html($b->BrandName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="StatusID" id="StatusID" required>
                    <option value="">-- Select Status --</option>
                    <?php foreach ($statuses as $s): ?>
                        <?php if (in_array($s->StatusName, ['Maintenance', 'Retired']) && (!isset($editing->StatusID) || $editing->StatusID != $s->StatusID)) continue; ?>
                        <option value="<?= esc_attr($s->StatusID) ?>" data-name="<?= esc_attr(strtolower($s->StatusName)) ?>" <?= selected($editing->StatusID ?? '', $s->StatusID, false) ?>>
                            <?=
                            ($s->StatusName === 'Available' ? '<i class="fa-solid fa-circle text-success"></i> Available' : ($s->StatusName === 'In Use' ? '<i class="fa-solid fa-circle text-danger"></i> In Use' : ($s->StatusName === 'Maintenance' ? '<i class="fa-solid fa-circle text-warning"></i> Maintenance' : ($s->StatusName === 'Retired' ? '<i class="fa-solid fa-circle text-dark"></i> Retired' : '❔'))))
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" <?= $hideKeyword ? 'style="display: none;"' : '' ?>>
                <label>Keyword</label>
                <select name="KeywordID" <?= $hideKeyword ? '' : 'required' ?>>
                    <option value="">-- Select Keyword --</option>
                    <?php foreach ($keywords as $k): ?>
                        <option value="<?= esc_attr($k->KeywordID) ?>" <?= selected($editing->KeywordID ?? '', $k->KeywordID, false) ?>>
                            <?= esc_html($k->KeywordName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Model</label>
                <input type="text" name="Model" value="<?= esc_attr($editing->Model ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Serial Number</label>
                <input type="text" name="SerialNumber" value="<?= esc_attr($editing->SerialNumber ?? '') ?>">
            </div>

            <div class="form-group" id="owner-group">
                <label>Owner (Employee)</label>
                <select name="OwnerID" id="OwnerID">
                    <option value="">-- No Owner --</option>
                    <?php foreach ($owners as $o): ?>
                        <option value="<?= esc_attr($o->OwnerID) ?>" <?= selected($editing->OwnerID ?? '', $o->OwnerID, false) ?>>
                            <?= esc_html($o->Nickname . ($o->FirstName ? ' (' . $o->FirstName . ' ' . $o->LastName . ')' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Add Device Date</label>
                <input type="date" name="AddDeviceDate" id="AddDeviceDate" value="<?= esc_attr($editing->AddDeviceDate ?? '') ?>" min="<?= esc_attr($editing->AddDeviceDate ?? date('Y-m-d')) ?>" required>
            </div>

            <div class="form-group" id="reason-group" style="display: none; grid-column: span 2;">
                <label>Reason <span class="text-danger">*</span></label>
                <input type="text" name="Reason" id="Reason" placeholder="Please enter reason (Required for Retired)">
            </div>
        </div>

        <div class="form-actions">
            <button type="button" onclick="history.back()" class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" class="btn btn-success border rounded-pill" style="background-color: #6ABF57">Update</button>
        </div>
    </form>

    <style>
        form {
            max-width: 600px;
            margin: 20px auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 50px;
            font-size: 14px;
        }

        .form-actions {
            text-align: center;
            margin-top: 20px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('StatusID');
            const ownerGroup = document.getElementById('owner-group');
            const ownerSelect = document.getElementById('OwnerID');
            const dateField = document.getElementById('AddDeviceDate');
            
            // ตรวจสอบค่า Status เริ่มต้นเพื่อใช้เทียบถ้ามีการเปลี่ยนสถานะ
            const initialStatusName = statusSelect && statusSelect.options[statusSelect.selectedIndex] 
                                        ? statusSelect.options[statusSelect.selectedIndex].getAttribute('data-name') 
                                        : '';

            function handleStatusChange() {
                const selectedOption = statusSelect.options[statusSelect.selectedIndex];
                const statusName = selectedOption ? selectedOption.getAttribute('data-name') : '';
                const reasonGroup = document.getElementById('reason-group');
                const reasonInput = document.getElementById('Reason');

                if (statusName === 'retired') {
                    if (reasonGroup) reasonGroup.style.display = 'flex';
                    if (reasonInput) reasonInput.required = true;
                } else {
                    if (reasonGroup) reasonGroup.style.display = 'none';
                    if (reasonInput) {
                        reasonInput.required = false;
                        reasonInput.value = '';
                    }
                }

                if (statusName === 'retired' || statusName === 'available') {
                    // ซ่อนและล้างช่อง Owner
                    if (ownerGroup) ownerGroup.style.display = 'none';
                    if (ownerSelect) ownerSelect.value = '';
                } else {
                    // แสดงช่อง Owner ปกติ
                    if (ownerGroup) ownerGroup.style.display = 'flex';
                }

                if (statusName === 'retired') {
                    // ล็อควันที่ และตั้งเป็นวันปัจจุบัน (เฉพาะกรณีที่เพิ่งเปลี่ยนเป็น retired ครั้งแรก หรือกำลังเลือกใหม่)
                    if (dateField) {
                        if (initialStatusName !== 'retired' || !dateField.value) {
                            dateField.value = new Date().toISOString().split('T')[0];
                        }
                        // ทำให้เป็น readonly แทน disabled เพื่อให้ยังส่งค่าผ่าน form ได้
                        dateField.readOnly = true;
                        dateField.style.backgroundColor = '#f0f0f0';
                        dateField.style.color = '#666';
                        dateField.style.cursor = 'not-allowed';
                    }
                } else {
                    // ปลดล็อควันที่
                    if (dateField) {
                        dateField.readOnly = false;
                        dateField.style.backgroundColor = '';
                        dateField.style.color = '';
                        dateField.style.cursor = '';
                    }
                }
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', handleStatusChange);
                handleStatusChange(); // เรียกครั้งแรกตอนโหลดหน้า
            }
        });
    </script>


<?php
    return ob_get_clean();
}

add_shortcode('edit_device', 'edit_device_form');
