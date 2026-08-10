<?php
if (!defined('ABSPATH')) {
    exit;
}

function receive_device($device_data = null)
{
    global $wpdb;

    $devices_table = 'Devices';

    // ดึงข้อมูล Departments
    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments");
    $positions = $wpdb->get_results("SELECT PositionID, PositionName FROM Positions");

    // ดึงข้อมูล Owners ที่มี Status Active (StatusID = 1)
    $owners_data = $wpdb->get_results("
    SELECT o.OwnerID, o.Nickname, o.DepartmentID, o.PositionID, d.DepartmentName
    FROM Owners o
    LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
    WHERE o.StatusID = 1
");


    ob_start();

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    // เมื่อฟอร์มถูกส่ง
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_device'])) {
        if (!is_user_logged_in() || !isset($_POST['_rcv_nonce']) || !wp_verify_nonce($_POST['_rcv_nonce'], 'receive_device_nonce')) {
            echo '<p style="color:red;">Security check failed.</p>';
            return ob_get_clean();
        }

        $device_id = sanitize_text_field($_POST['DeviceID'] ?? '');
        $owner_id = intval($_POST['OwnerID'] ?? 0);
        $receive_date = sanitize_text_field($_POST['ReceiveDate'] ?? '');
        $owner_info = $wpdb->get_row($wpdb->prepare("SELECT DepartmentID, PositionID FROM Owners WHERE OwnerID = %d", $owner_id));
        $department_id = $owner_info->DepartmentID ?? null;
        $position_id = $owner_info->PositionID ?? null;

        // Process photo upload if provided
        $photo_url = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploaded = wp_handle_upload($_FILES['photo'], array('test_form' => false));
            if ($uploaded && !isset($uploaded['error'])) {
                $photo_url = $uploaded['url'];
            }
        }

        // ดึงสถานะ "In Use"
        $status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");

        // อัปเดตข้อมูลอุปกรณ์
        $updated = $wpdb->update(
            $devices_table,
            [
                'OwnerID'      => $owner_id,
                'DepartmentID' => $department_id,
                'PositionID'   => $position_id,
                'ReceiveDate'  => $receive_date,
                'StatusID'     => $status_id,
                'ReturnDate'   => null,
            ],
            ['DeviceID' => $device_id]
        );


        // ดึงชื่อหมวดหมู่
        $category_name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT c.CategoryName 
        FROM $devices_table d 
        JOIN Categories c ON d.CategoryID = c.CategoryID 
        WHERE d.DeviceID = %s",
                $device_id
            )
        );

        if ($updated !== false && $category_name) {
            $owner_nickname = $wpdb->get_var(
                $wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $owner_id)
            );

            $category_id = $wpdb->get_var(
                $wpdb->prepare("SELECT CategoryID FROM $devices_table WHERE DeviceID = %s", $device_id)
            );

            $current_user = wp_get_current_user();
            $user_email = $current_user->user_email ?? '';

            // บันทึกประวัติ
            $wpdb->insert('History_new', [
                'DeviceID' => $device_id,
                'Action' => 'Receive',
                'Date' => current_time('mysql'),
                'Description' => "Device ID {$device_id} received and assigned to owner",
                'user_email' => $user_email,
                'CategoryID' => $category_id,
                'Owner' => $owner_nickname,
                'Photo' => $photo_url
            ]);

            // ส่งอีเมลแจ้งเตือน
            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('Assign', $device_id, $owner_id);
            }

            // แสดงแจ้งเตือนสำเร็จ
            $redirect_url = home_url('/' . sanitize_title($category_name) . '/?view=' . urlencode($device_id));
            echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Receive Device!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.location.replace('{$redirect_url}');
        });
    </script>";
            return ob_get_clean();
        } else {
            echo "<script>
        Swal.fire({
            icon: 'error',
            showConfirmButton: true
        });
    </script>";
        }
    }


    $date_value = !empty($device_data->ReceiveDate) ? date('Y-m-d', strtotime($device_data->ReceiveDate)) : '';

    // คำนวณอุปกรณ์คงเหลือและข้อมูลรุ่นอุปกรณ์
    $device_model_name = '';
    $category_name_info = '';
    $brand_name_info = '';
    $available_count = 0;

    if (!empty($device_data->DeviceID)) {
        $info = $wpdb->get_row($wpdb->prepare("
            SELECT d.DeviceID, d.Model, d.CategoryID, d.BrandID, c.CategoryName, b.BrandName
            FROM Devices d
            LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
            LEFT JOIN Brands b ON d.BrandID = b.BrandID
            WHERE d.DeviceID = %s
        ", $device_data->DeviceID));

        if ($info) {
            $device_model_name = $info->Model ?? '';
            $category_name_info = $info->CategoryName ?? '';
            $brand_name_info = $info->BrandName ?? '';

            $avail_status_id = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");

            if (!empty($info->BrandID) && !empty($info->CategoryID)) {
                $available_count = (int) $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM Devices 
                    WHERE CategoryID = %d AND BrandID = %d AND (StatusID = %d OR StatusID IS NULL)
                ", $info->CategoryID, $info->BrandID, $avail_status_id));
            } elseif (!empty($info->BrandID)) {
                $available_count = (int) $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM Devices 
                    WHERE BrandID = %d AND (StatusID = %d OR StatusID IS NULL)
                ", $info->BrandID, $avail_status_id));
            }
        }
    }
    ?>

    <?php if ($available_count < 3): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: '⚠️ แจ้งเตือนแบรนด์ <?= esc_js($brand_name_info ?: 'อุปกรณ์') ?> เหลือน้อย!',
                html: 'อุปกรณ์แบรนด์ <strong><?= esc_js($brand_name_info) ?></strong> คงเหลือในสต็อกเพียง <strong><?= $available_count ?></strong> ชิ้น',
                confirmButtonText: 'รับทราบ (ดำเนินการต่อ)',
                confirmButtonColor: '#f59e0b',
                timer: 5000,
                timerProgressBar: true
            });
        });
    </script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('receive_device_nonce', '_rcv_nonce'); ?>
        <h2>Assign Device</h2>

        <!-- Brand Stock & Projection Info Card -->
        <?php $after_borrow_count = max(0, $available_count - 1); ?>
        <div style="background: <?= ($available_count < 3) ? '#fffbe6' : '#ffffff' ?>; border: 1.5px solid <?= ($available_count < 3) ? '#fde047' : '#e2e8f0' ?>; border-radius: 14px; padding: 16px 20px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: <?= ($available_count < 3) ? '#fef3c7' : '#e0f2fe' ?>; color: <?= ($available_count < 3) ? '#d97706' : '#0284c7' ?>; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        <i class="fa-solid <?= ($available_count < 3) ? 'fa-triangle-exclamation' : 'fa-laptop' ?>"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #0f172a; font-size: 0.98rem;">
                            แบรนด์: <?= esc_html($brand_name_info ?: 'ไม่ระบุ') ?> <?= esc_html($device_model_name ? '- ' . $device_model_name : '') ?>
                            <span style="font-size: 0.8rem; font-weight: 500; color: #64748b; margin-left: 4px;">(<?= esc_html($device_data->DeviceID ?? '') ?>)</span>
                        </div>
                        <div style="font-size: 0.82rem; color: #64748b;">หมวดหมู่: <strong style="color: #334155;"><?= esc_html($category_name_info ?: '-') ?></strong></div>
                    </div>
                </div>
                <?php if ($available_count < 3): ?>
                    <span style="font-size: 0.78rem; font-weight: 700; background: #fef3c7; color: #b45309; border: 1px solid #fde047; padding: 3px 10px; border-radius: 20px;">⚠️ สต็อกเหลือน้อย</span>
                <?php endif; ?>
            </div>

            <!-- Mini Stock Stats Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #f1f5f9; text-align: center;">
                <div style="border-right: 1px solid #e2e8f0; padding-right: 8px;">
                    <span style="font-size: 0.78rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px;">
                        <i class="fa-solid fa-boxes-stacked me-1" style="color: #3b82f6;"></i> คงเหลือในสต็อกปัจจุบัน
                    </span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: <?= ($available_count < 3) ? '#d97706' : '#1e293b' ?>;">
                        <?= $available_count ?> <span style="font-size: 0.82rem; font-weight: 600;">เครื่อง</span>
                    </span>
                </div>

                <div style="padding-left: 8px;">
                    <span style="font-size: 0.78rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 2px;">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1" style="color: #8b5cf6;"></i> ถ้ายืม/มอบหมายเครื่องนี้จะเหลือ
                    </span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: <?= ($after_borrow_count < 3) ? '#dc2626' : '#16a34a' ?>;">
                        <?= $after_borrow_count ?> <span style="font-size: 0.82rem; font-weight: 600;">เครื่อง</span>
                    </span>
                </div>
            </div>
        </div>

        <input type="hidden" name="DeviceID" value="<?= esc_attr($device_data->DeviceID ?? '') ?>">

        <div class="form-grid">
            <div class="form-group">
                <label>DeviceID</label>
                <input type="text" value="<?= esc_attr($device_data->DeviceID ?? '') ?>" readonly>
            </div>

            <div class="form-group">
                <label style="font-weight: 600;">Owner (ค้นหา/เลือกชื่อพนักงาน)</label>
                <div id="website_owner_search_wrap" style="position: relative; width: 100%;">
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; pointer-events: none;"></i>
                        <input type="text" id="owner_search_input" placeholder="🔍 พิมพ์หรือเลือกชื่อพนักงาน..." autocomplete="off" style="width: 100%; padding: 10px 36px 10px 38px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-size: 0.9rem; background-color: #ffffff; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02); box-sizing: border-box; transition: all 0.2s;" onfocus="this.select(); openOwnerSearchPopup()" oninput="onOwnerInputChanged(this.value)">
                        <i class="fa-solid fa-chevron-down" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem; pointer-events: none;"></i>
                    </div>

                    <!-- Live Floating Results Popup -->
                    <div id="owner_search_popup" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12); z-index: 99999; padding: 4px;">
                    </div>
                </div>

                <!-- Hidden Input for 100% Form & CSS compatibility -->
                <input type="hidden" name="OwnerID" id="OwnerID" value="" required>
            </div>
            <script>
                const ownerDataList = [
                    <?php foreach ($owners_data as $o): ?>
                    {
                        id: <?= intval($o->OwnerID) ?>,
                        name: <?= json_encode(trim($o->Nickname)) ?>,
                        deptId: <?= json_encode($o->DepartmentID) ?>,
                        posId: <?= json_encode($o->PositionID) ?>,
                        deptName: <?= json_encode($o->DepartmentName ?? '') ?>
                    },
                    <?php endforeach; ?>
                ];

                function openOwnerSearchPopup() {
                    const input = document.getElementById('owner_search_input');
                    if (input) filterOwnerSearchPopup(input.value);
                }

                function onOwnerInputChanged(val) {
                    filterOwnerSearchPopup(val);
                    if (!val.trim()) {
                        const hiddenInput = document.getElementById('OwnerID');
                        if (hiddenInput) {
                            hiddenInput.value = '';
                            handleOwnerChange();
                        }
                    }
                }

                function filterOwnerSearchPopup(query) {
                    const popup = document.getElementById('owner_search_popup');
                    if (!popup) return;
                    const term = query.toLowerCase().trim();

                    const filtered = ownerDataList.filter(o => {
                        return !term || o.name.toLowerCase().includes(term) || o.deptName.toLowerCase().includes(term);
                    });

                    if (filtered.length === 0) {
                        popup.innerHTML = `<div style="padding: 10px 14px; color: #94a3b8; font-size: 0.85rem; text-align: center;">❌ ไม่พบพนักงานที่ค้นหา</div>`;
                    } else {
                        let html = '';
                        filtered.forEach(o => {
                            const deptBadge = o.deptName ? `<span style="font-size: 0.75rem; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-weight: 600;">${o.deptName}</span>` : '';
                            const displayName = o.name + (o.deptName ? ` (${o.deptName})` : '');
                            html += `
                                <div class="owner-item-row" onclick="selectOwnerItem(${o.id}, '${displayName.replace(/'/g, "\\'")}')" style="padding: 8px 12px; border-radius: 6px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; transition: background 0.15s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='transparent';">
                                    <span style="font-weight: 600; color: #0f172a;"><i class="fa-solid fa-user me-2" style="color: #64748b; font-size: 0.8rem;"></i>${o.name}</span>
                                    ${deptBadge}
                                </div>
                            `;
                        });
                        popup.innerHTML = html;
                    }
                    popup.style.display = 'block';
                }

                function selectOwnerItem(id, displayName) {
                    const input = document.getElementById('owner_search_input');
                    const hiddenInput = document.getElementById('OwnerID');
                    const popup = document.getElementById('owner_search_popup');

                    if (input) input.value = displayName;
                    if (hiddenInput) {
                        hiddenInput.value = id;
                        handleOwnerChange();
                    }
                    if (popup) popup.style.display = 'none';
                }

                function handleOwnerChange() {
                    const ownerId = document.getElementById('OwnerID').value;
                    const deptSelect = document.getElementById('DepartmentID');
                    const posSelect = document.getElementById('PositionID');

                    const found = ownerDataList.find(o => String(o.id) === String(ownerId));
                    if (found) {
                        if (found.deptId && deptSelect) deptSelect.value = found.deptId;
                        if (found.posId && posSelect) posSelect.value = found.posId;
                    } else {
                        if (deptSelect) deptSelect.value = '';
                        if (posSelect) posSelect.value = '';
                    }
                }

                document.addEventListener('click', function(e) {
                    const wrap = document.getElementById('website_owner_search_wrap');
                    const popup = document.getElementById('owner_search_popup');
                    if (wrap && popup && !wrap.contains(e.target)) {
                        popup.style.display = 'none';
                    }
                });
            </script>

            <div class="form-group">
                <label>Department</label>
                <select name="DepartmentID" id="DepartmentID" disabled tabindex="-1">
                    <?php foreach ($departments as $dep): ?>
                        <option value="<?= $dep->DepartmentID ?>" <?= selected($device_data->DepartmentID ?? '', $dep->DepartmentID, false) ?>>
                            <?= esc_html($dep->DepartmentName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Position</label>
                <select name="PositionID" id="PositionID" disabled tabindex="-1">
                    <option value="">-- Select --</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= $pos->PositionID ?>" <?= selected($device_data->PositionID ?? '', $pos->PositionID, false) ?>>
                            <?= esc_html($pos->PositionName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Assign Date</label>
                <input type="date" name="ReceiveDate" value="<?= esc_attr($date_value) ?>" min="<?= date('Y-m-d') ?>"
                    required>
            </div>

            <div class="form-group" style="grid-column: span 2; margin-top: 10px;">
                <label style="font-weight: 600; color: #374151; display: block; margin-bottom: 6px;">
                    <i class="fa-solid fa-camera" style="color: #6366f1; margin-right: 4px;"></i> Condition Photo (Camera / Upload)
                </label>
                <input type="file" name="photo" accept="image/*" capture="environment" onchange="if(this.files && this.files[0]){ const r=new FileReader(); r.onload=e=>{ document.getElementById('rcv_photo_img').src=e.target.result; document.getElementById('rcv_photo_wrap').style.display='block'; }; r.readAsDataURL(this.files[0]); }" style="width:100%; padding:10px; border:1px dashed #cbd5e1; border-radius:10px; background:#ffffff;">
                <div id="rcv_photo_wrap" style="display:none; margin-top:10px;">
                    <img id="rcv_photo_img" src="" style="max-height:140px; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                </div>
            </div>

        </div>

        <div class="form-actions">
            <button type="button" onclick="
                const ref = document.referrer;
                if (ref && ref.includes(window.location.host) && !ref.includes('receive=') && !ref.includes('edit=')) {
                    window.location.href = ref;
                } else {
                    window.location.href = '<?= esc_url(home_url('/home/')) ?>';
                }
            " class="btn btn-danger border rounded-pill">Cancel</button>
            <button type="submit" name="update_device" class="btn btn-success border rounded-pill"
                style="background-color: #6ABF57">Assign</button>
        </div>

    </form>

    <script>
        function handleOwnerChange() {
            const ownerSelect = document.getElementById('OwnerID');
            const deptSelect = document.getElementById('DepartmentID');
            const posSelect = document.getElementById('PositionID');

            const selectedOption = ownerSelect.options[ownerSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const dept = selectedOption.getAttribute('data-dept');
                const pos = selectedOption.getAttribute('data-pos');

                if (dept) deptSelect.value = dept;
                if (pos) posSelect.value = pos;
            } else {
                deptSelect.value = '';
                posSelect.value = '';
            }
        }
    </script>

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
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        form h2 {
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.025em;
            margin-bottom: 1.5rem;
            text-align: center;
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
            height: 44px;
            /* Ensure uniform height */
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            color: #111827;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            appearance: none;
            /* For custom select arrow */
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

    <?php
    return ob_get_clean();
}
add_shortcode('receive_device', 'receive_device');
