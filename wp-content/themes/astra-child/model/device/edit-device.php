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
		/* Next.js Inspired Form UI */
		form {
			max-width: 650px;
			margin: 40px auto;
			margin-top: 10px;
			background: #ffffff;
			padding: 2.5rem;
			border-radius: 16px;
			border: 1px solid #e5e7eb;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			animation: formFadeIn 0.5s ease-out forwards;
		}

		@keyframes formFadeIn {
			from { opacity: 0; transform: translateY(10px); }
			to { opacity: 1; transform: translateY(0); }
		}

		form h2 {
			font-weight: 700;
			color: #111827;
			letter-spacing: -0.025em;
			margin-bottom: 1.5rem;
		}

		.form-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1.5rem;
			margin-top: 1rem;
		}

		.form-group {
			display: flex;
			flex-direction: column;
			margin-bottom: 0;
			position: relative;
		}

		.form-group label {
			font-size: 0.875rem;
			font-weight: 600;
			color: #374151;
			margin-bottom: 6px;
			transition: color 0.2s ease;
		}

		.form-group:focus-within label {
			color: #3b82f6;
		}

		/* Unified Input and Select Styling */
		.form-group input,
		.form-group select {
			width: 100%;
			box-sizing: border-box;
			height: 44px; /* Ensure uniform height */
			padding: 0.5rem 1rem;
			font-size: 0.95rem;
			color: #111827;
			background-color: #ffffff;
			border: 1px solid #d1d5db;
			border-radius: 10px;
			transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
			appearance: none; /* For custom select arrow */
		}

		/* Select specific - Custom Arrow */
		.form-group select {
			background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
			background-position: right 0.75rem center;
			background-repeat: no-repeat;
			background-size: 1.25em 1.25em;
			cursor: pointer;
		}

		/* Hover and Focus States */
		.form-group input:hover:not([readonly]):not([disabled]),
		.form-group select:hover:not([readonly]):not([disabled]) {
			border-color: #9ca3af;
		}

		.form-group input:focus:not([readonly]):not([disabled]),
		.form-group select:focus:not([readonly]):not([disabled]) {
			outline: none;
			border-color: #3b82f6;
			box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
			transform: translateY(-1px);
		}

		/* Click Animation for Select (Active state) */
		.form-group select:active:not([disabled]) {
			transform: scale(0.98);
		}

		/* Readonly/Disabled Input Styling */
		.form-group input[readonly],
		.form-group input[disabled],
		.form-group select[disabled] {
			background-color: #f9fafb !important;
			color: #6b7280 !important;
			cursor: not-allowed !important;
			border-color: #e5e7eb !important;
			box-shadow: none !important;
		}

		.form-actions {
			display: flex;
			justify-content: center;
			gap: 1rem;
			margin-top: 2.5rem;
			padding-top: 1.5rem;
			border-top: 1px solid #f3f4f6;
		}

		.form-actions button {
			padding: 0.6rem 2rem;
			font-weight: 600;
			font-size: 0.95rem;
			letter-spacing: 0.025em;
			transition: all 0.2s ease;
			box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
		}

		.form-actions button:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
		}

		.form-actions button:active {
			transform: translateY(0);
		}

		@media (max-width: 640px) {
			.form-grid {
				grid-template-columns: 1fr;
			}
			form {
				margin: 20px;
				padding: 1.5rem;
			}
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
