<?php
if (!defined('ABSPATH')) {
    exit;
}

function device_request_form()
{
    global $wpdb;

    // Fetch active owners
    $owners = $wpdb->get_results("
        SELECT o.OwnerID, o.Nickname, d.DepartmentName 
        FROM Owners o
        LEFT JOIN Departments d ON o.DepartmentID = d.DepartmentID
        WHERE o.StatusID = 1
        ORDER BY o.Nickname ASC
    ");

    // Fetch categories
    $categories = $wpdb->get_results("SELECT CategoryID, CategoryName FROM Categories ORDER BY CategoryName ASC");

    // Fetch available devices
    $available_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
    $available_devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, d.CategoryID, b.BrandName, d.Model, d.SerialNumber
        FROM Devices d
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        WHERE d.StatusID = %d
    ", $available_status));

    // Group devices by category for JS
    $devices_by_cat = [];
    if ($available_devices) {
        foreach ($available_devices as $d) {
            $devices_by_cat[$d->CategoryID][] = [
                'id' => $d->DeviceID,
                'label' => $d->DeviceID . ' - ' . ($d->BrandName ?? '') . ' ' . ($d->Model ?? '') . ' (SN: ' . ($d->SerialNumber ?? 'N/A') . ')'
            ];
        }
    }
    $devices_json = json_encode($devices_by_cat);

    // Fetch currently assigned devices for Repair Request tab
    $assigned_devices = $wpdb->get_results("
        SELECT d.DeviceID, d.OwnerID, b.BrandName, d.Model, d.SerialNumber, c.CategoryName
        FROM Devices d
        LEFT JOIN Categories c ON d.CategoryID = c.CategoryID
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        WHERE d.OwnerID IS NOT NULL AND d.OwnerID != 0
    ");

    $repair_devices_by_owner = [];
    if ($assigned_devices) {
        foreach ($assigned_devices as $ad) {
            $repair_devices_by_owner[$ad->OwnerID][] = [
                'id' => $ad->DeviceID,
                'label' => $ad->DeviceID . ' - ' . ($ad->CategoryName ?? '') . ' ' . ($ad->BrandName ?? '') . ' ' . ($ad->Model ?? '') . ' (SN: ' . ($ad->SerialNumber ?? 'N/A') . ')'
            ];
        }
    }
    $repair_devices_json = json_encode($repair_devices_by_owner);

    ob_start();

    // Handle Device Request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
        $nonce_valid = isset($_POST['_req_nonce']) && wp_verify_nonce($_POST['_req_nonce'], 'submit_device_request_nonce');
        if (!$nonce_valid && !is_user_logged_in()) {
            echo "<script>setTimeout(function() { Swal.fire({icon: 'error', title: 'Security Check Failed', text: 'Invalid request security token.'}); }, 100);</script>";
            return ob_get_clean();
        }

        $owner_id = intval($_POST['OwnerID']);
        $category_id = intval($_POST['CategoryID']);
        $requested_device_id = sanitize_text_field($_POST['RequestedDeviceID']);
        $reason = sanitize_textarea_field($_POST['Reason']);

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

        if ($owner_id && $category_id && !empty($requested_device_id) && !empty($reason)) {
            $inserted = $wpdb->insert(
                'Device_Requests',
                [
                    'OwnerID' => $owner_id,
                    'CategoryID' => $category_id,
                    'Reason' => $reason,
                    'Status' => 'Pending',
                    'AssignedDeviceID' => $requested_device_id,
                    'RequestDate' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $request_id = $wpdb->insert_id;
                $email_msg = "Device: $requested_device_id - $reason";
                if (function_exists('stock_supply_send_email')) {
                    stock_supply_send_email('RequestSubmitted', $request_id, $owner_id, $email_msg);
                }

                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Submitted!',
                            text: 'Your request for device " . esc_js($requested_device_id) . " has been submitted successfully.',
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.href = window.location.pathname;
                        });
                    }, 100);
                </script>";
            } else {
                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Database Error',
                            text: 'An error occurred while processing your request.',
                            confirmButtonColor: '#ef4444'
                        });
                    }, 100);
                </script>";
            }
        }
    }

    // Handle Repair Request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_repair_request'])) {
        $nonce_valid = isset($_POST['_req_nonce']) && wp_verify_nonce($_POST['_req_nonce'], 'submit_device_request_nonce');
        if (!$nonce_valid && !is_user_logged_in()) {
            echo "<script>setTimeout(function() { Swal.fire({icon: 'error', title: 'Security Check Failed', text: 'Invalid request security token.'}); }, 100);</script>";
            return ob_get_clean();
        }

        $repair_owner_id = intval($_POST['RepairOwnerID']);
        $repair_device_id = sanitize_text_field($_POST['RepairDeviceID']);
        $repair_reason = sanitize_textarea_field($_POST['RepairReason']);

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

        if ($repair_owner_id && !empty($repair_device_id) && !empty($repair_reason)) {
            $inserted = $wpdb->insert(
                'Repair_Requests',
                [
                    'OwnerID'     => $repair_owner_id,
                    'DeviceID'    => $repair_device_id,
                    'Reason'      => $repair_reason,
                    'Status'      => 'Pending',
                    'RequestDate' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $req_id = $wpdb->insert_id;
                if (function_exists('stock_supply_send_email')) {
                    stock_supply_send_email('RequestSubmitted', $req_id, $repair_owner_id, "Repair Request for $repair_device_id: $repair_reason");
                }

                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Repair Request Submitted!',
                            text: 'Your repair request for device " . esc_js($repair_device_id) . " has been submitted to IT.',
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.href = window.location.pathname;
                        });
                    }, 100);
                </script>";
            } else {
                echo "<script>
                    setTimeout(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Database Error',
                            text: 'An error occurred while processing your repair request.',
                            confirmButtonColor: '#ef4444'
                        });
                    }, 100);
                </script>";
            }
        }
    }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .request-form-container {
            max-width: 650px;
            margin: 40px auto;
            background: #ffffff;
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid #f3f4f6;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            font-family: 'Inter', sans-serif;
        }

        .tab-switcher {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
            background: #f3f4f6;
            padding: 6px;
            border-radius: 12px;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 14px;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.95rem;
            color: #6b7280;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background: #ffffff;
            color: #4f46e5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .form-header p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            color: #1f2937;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            transition: all 0.2s ease;
            box-sizing: border-box;
            appearance: none;
        }

        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
    </style>

    <div class="request-form-container">
        <div class="tab-switcher">
            <button type="button" class="tab-btn active" onclick="switchFormTab('device')">📦 ขอเบิกอุปกรณ์</button>
            <button type="button" class="tab-btn" onclick="switchFormTab('repair')">🛠️ แจ้งส่งซ่อมอุปกรณ์</button>
        </div>

        <!-- Form 1: Device Request -->
        <div id="form-device-container">
            <div class="form-header">
                <h2>Request an IT Device</h2>
                <p>Select an available device from the inventory below.</p>
            </div>
            <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <?php wp_nonce_field('submit_device_request_nonce', '_req_nonce'); ?>
                <input type="hidden" name="submit_request" value="1">
                <div class="form-group">
                    <label for="OwnerID">Requester Name</label>
                    <select name="OwnerID" id="OwnerID" class="form-control" required>
                        <option value="">-- Select Your Name --</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?= esc_attr($owner->OwnerID) ?>">
                                <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="CategoryID">Device Category</label>
                    <select name="CategoryID" id="CategoryID" class="form-control" required onchange="filterDevices()">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc_attr($cat->CategoryID) ?>">
                                <?= esc_html($cat->CategoryName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RequestedDeviceID">Select Available Device</label>
                    <select name="RequestedDeviceID" id="RequestedDeviceID" class="form-control" required disabled>
                        <option value="">-- Please select a category first --</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label for="Reason">Reason / Justification</label>
                    <textarea name="Reason" id="Reason" rows="3" class="form-control"
                        placeholder="E.g. Old laptop is broken, needed for new project..." required></textarea>
                </div>

                <button type="submit" name="submit_request" class="btn-submit">Submit Request</button>
            </form>
        </div>

        <!-- Form 2: Repair Request -->
        <div id="form-repair-container" style="display: none;">
            <div class="form-header">
                <h2>Submit a Repair Request</h2>
                <p>Report an issue for an assigned IT device.</p>
            </div>
            <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                <?php wp_nonce_field('submit_device_request_nonce', '_req_nonce'); ?>
                <input type="hidden" name="submit_repair_request" value="1">
                <div class="form-group">
                    <label for="RepairOwnerID">Requester Name</label>
                    <select name="RepairOwnerID" id="RepairOwnerID" class="form-control" required onchange="filterRepairDevices()">
                        <option value="">-- Select Your Name --</option>
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?= esc_attr($owner->OwnerID) ?>">
                                <?= esc_html($owner->Nickname) ?> (<?= esc_html($owner->DepartmentName ?: 'No Dept') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="RepairDeviceID">Select Your Assigned Device</label>
                    <select name="RepairDeviceID" id="RepairDeviceID" class="form-control" required disabled>
                        <option value="">-- Please select your name first --</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label for="RepairReason">Issue Description / Reason for Repair</label>
                    <textarea name="RepairReason" id="RepairReason" rows="3" class="form-control"
                        placeholder="E.g. Screen flickering, battery swelling, device cannot turn on..." required></textarea>
                </div>

                <button type="submit" name="submit_repair_request" class="btn-submit" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">Submit Repair Request</button>
            </form>
        </div>
    </div>

    <script>
        const devicesByCat = <?= $devices_json ?>;
        const repairDevicesByOwner = <?= $repair_devices_json ?>;

        function switchFormTab(tab) {
            const devContainer = document.getElementById('form-device-container');
            const repairContainer = document.getElementById('form-repair-container');
            const btns = document.querySelectorAll('.tab-btn');

            btns.forEach(b => b.classList.remove('active'));

            if (tab === 'repair') {
                devContainer.style.display = 'none';
                repairContainer.style.display = 'block';
                btns[1].classList.add('active');
            } else {
                repairContainer.style.display = 'none';
                devContainer.style.display = 'block';
                btns[0].classList.add('active');
            }
        }

        function filterDevices() {
            const catId = document.getElementById('CategoryID').value;
            const deviceSelect = document.getElementById('RequestedDeviceID');

            deviceSelect.innerHTML = '<option value="">-- Select Device --</option>';

            if (!catId) {
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select a category first --</option>';
                return;
            }

            if (devicesByCat[catId] && devicesByCat[catId].length > 0) {
                deviceSelect.disabled = false;
                devicesByCat[catId].forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label;
                    deviceSelect.appendChild(opt);
                });
            } else {
                deviceSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "❌ No available devices in this category";
                deviceSelect.appendChild(opt);
            }
        }

        function filterRepairDevices() {
            const ownerId = document.getElementById('RepairOwnerID').value;
            const deviceSelect = document.getElementById('RepairDeviceID');

            deviceSelect.innerHTML = '<option value="">-- Select Your Assigned Device --</option>';

            if (!ownerId) {
                deviceSelect.disabled = true;
                deviceSelect.innerHTML = '<option value="">-- Please select your name first --</option>';
                return;
            }

            if (repairDevicesByOwner[ownerId] && repairDevicesByOwner[ownerId].length > 0) {
                deviceSelect.disabled = false;
                repairDevicesByOwner[ownerId].forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label;
                    deviceSelect.appendChild(opt);
                });
            } else {
                deviceSelect.disabled = true;
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "❌ No assigned devices found for this employee";
                deviceSelect.appendChild(opt);
            }
        }
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('device_request_form', 'device_request_form');
