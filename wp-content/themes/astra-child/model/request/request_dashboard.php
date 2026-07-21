<?php
function device_request_dashboard()
{
    global $wpdb;

    $current_user = wp_get_current_user();
    $admin_email = $current_user->user_email ?? 'admin';

    // Handle Fulfill Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fulfill_request'])) {
        $request_id = intval($_POST['request_id']);
        $device_id = sanitize_text_field($_POST['assign_device_id']);

        $req = $wpdb->get_row($wpdb->prepare("
            SELECT r.OwnerID, r.CategoryID, c.CategoryName 
            FROM Device_Requests r 
            LEFT JOIN Categories c ON r.CategoryID = c.CategoryID 
            WHERE r.RequestID = %d
        ", $request_id));

        if ($req && $device_id) {
            $inuse_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'In Use'");
            $owner_info = $wpdb->get_row($wpdb->prepare("SELECT DepartmentID, PositionID FROM Owners WHERE OwnerID = %d", $req->OwnerID));

            // Assign Device
            $wpdb->update(
                'Devices',
                [
                    'OwnerID' => $req->OwnerID,
                    'DepartmentID' => $owner_info->DepartmentID ?? null,
                    'PositionID' => $owner_info->PositionID ?? null,
                    'ReceiveDate' => current_time('mysql'),
                    'StatusID' => $inuse_status,
                    'ReturnDate' => null
                ],
                ['DeviceID' => $device_id]
            );

            // Update Request Status
            $wpdb->update(
                'Device_Requests',
                [
                    'Status' => 'Fulfilled',
                    'ActionDate' => current_time('mysql'),
                    'AssignedDeviceID' => $device_id,
                    'IT_Admin_Email' => $admin_email
                ],
                ['RequestID' => $request_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            // History
            $owner_nickname = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $req->OwnerID));
            $wpdb->insert('History_new', [
                'DeviceID' => $device_id,
                'Action' => 'Receive',
                'Date' => current_time('mysql'),
                'Description' => "Device assigned from Request #{$request_id}",
                'user_email' => $admin_email,
                'CategoryID' => $req->CategoryID,
                'Owner' => $owner_nickname
            ]);

            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('Assign', $device_id, $req->OwnerID);
            }

            // Determine Redirect URL
            $redirect_url = '/stock_supply/home/';
            if (!empty($req->CategoryName)) {
                $slug = strtolower(str_replace(' ', '-', $req->CategoryName));
                $redirect_url = '/stock_supply/' . $slug . '/';
            }

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Fulfilled',
                        text: 'Device " . esc_js($device_id) . " has been assigned.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '" . esc_js($redirect_url) . "';
                    });
                });
            </script>";
        }
    }

    // Handle Reject Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_request'])) {
        $request_id = intval($_POST['request_id']);
        $reject_reason = sanitize_textarea_field($_POST['reject_reason']);

        $req = $wpdb->get_row($wpdb->prepare("SELECT OwnerID FROM Device_Requests WHERE RequestID = %d", $request_id));

        if ($req) {
            $wpdb->update(
                'Device_Requests',
                [
                    'Status' => 'Rejected',
                    'ActionDate' => current_time('mysql'),
                    'IT_Admin_Email' => $admin_email,
                    'Reason' => 'Rejected: ' . $reject_reason
                ],
                ['RequestID' => $request_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('RequestRejected', $request_id, $req->OwnerID, $reject_reason);
            }

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Rejected',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = window.location.href;
                    });
                });
            </script>";
        }
    }

    // Handle Approve Repair
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_repair'])) {
        $repair_id = intval($_POST['repair_id']);

        $req = $wpdb->get_row($wpdb->prepare("SELECT OwnerID, DeviceID, Reason FROM Repair_Requests WHERE RequestID = %d", $repair_id));
        if ($req) {
            $maintenance_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Maintenance'");

            // Update Device Status
            $wpdb->update(
                'Devices',
                ['StatusID' => $maintenance_status],
                ['DeviceID' => $req->DeviceID]
            );

            // Update Request Status
            $wpdb->update(
                'Repair_Requests',
                [
                    'Status' => 'Approved',
                    'ActionDate' => current_time('mysql')
                ],
                ['RequestID' => $repair_id],
                ['%s', '%s'],
                ['%d']
            );

            // Insert into Maintenance table
            $wpdb->insert('Maintenance', [
                'DeviceID' => $req->DeviceID,
                'RepairDate' => current_time('mysql'),
                'Details' => $req->Reason,
                'user_email' => $admin_email,
                'CreatedAt' => current_time('mysql'),
                'UpdatedAt' => current_time('mysql'),
            ]);

            // History
            $owner_nickname = $wpdb->get_var($wpdb->prepare("SELECT Nickname FROM Owners WHERE OwnerID = %d", $req->OwnerID));
            $wpdb->insert('History_new', [
                'DeviceID' => $req->DeviceID,
                'Action' => 'Maintenance',
                'Date' => current_time('mysql'),
                'Description' => "Repair Request Approved. Device sent to Maintenance.",
                'user_email' => $admin_email,
                'Owner' => $owner_nickname
            ]);

            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('RepairApproved', $req->DeviceID, $req->OwnerID, $req->Reason);
            }

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Repair Approved',
                        text: 'Device " . esc_js($req->DeviceID) . " has been sent to Maintenance.',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = '/stock_supply/maintenance/';
                    });
                });
            </script>";
        }
    }

    // Handle Reject Repair
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_repair'])) {
        $repair_id = intval($_POST['repair_id']);
        $reject_reason = sanitize_textarea_field($_POST['reject_reason']);

        $req = $wpdb->get_row($wpdb->prepare("SELECT OwnerID, DeviceID FROM Repair_Requests WHERE RequestID = %d", $repair_id));
        if ($req) {
            $wpdb->update(
                'Repair_Requests',
                [
                    'Status' => 'Rejected',
                    'ActionDate' => current_time('mysql'),
                    'Reason' => 'Rejected: ' . $reject_reason
                ],
                ['RequestID' => $repair_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            if (function_exists('stock_supply_send_email')) {
                stock_supply_send_email('RepairRejected', $req->DeviceID, $req->OwnerID, $reject_reason);
            }

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Repair Rejected',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = window.location.href;
                    });
                });
            </script>";
        }
    }

    // Fetch requests
    $requests = $wpdb->get_results("
        SELECT r.*, o.Nickname, o.FirstName, o.LastName, c.CategoryName 
        FROM Device_Requests r
        LEFT JOIN Owners o ON r.OwnerID = o.OwnerID
        LEFT JOIN Categories c ON r.CategoryID = c.CategoryID
        ORDER BY CASE WHEN r.Status = 'Pending' THEN 1 ELSE 2 END, r.RequestDate DESC
    ");

    // Fetch repair requests
    $repair_requests = $wpdb->get_results("
        SELECT r.*, o.Nickname, o.FirstName, o.LastName 
        FROM Repair_Requests r
        LEFT JOIN Owners o ON r.OwnerID = o.OwnerID
        ORDER BY CASE WHEN r.Status = 'Pending' THEN 1 ELSE 2 END, r.RequestDate DESC
    ");

    // Fetch available devices
    $available_status = $wpdb->get_var("SELECT StatusID FROM Statuses WHERE StatusName = 'Available'");
    $available_devices = $wpdb->get_results($wpdb->prepare("
        SELECT d.DeviceID, d.CategoryID, b.BrandName, d.Model, d.SerialNumber
        FROM Devices d
        LEFT JOIN Brands b ON d.BrandID = b.BrandID
        WHERE d.StatusID = %d
    ", $available_status));

    $devices_by_cat = [];
    foreach ($available_devices as $d) {
        $devices_by_cat[$d->CategoryID][] = [
            'id' => $d->DeviceID,
            'label' => $d->DeviceID . ' - ' . ($d->BrandName ?? '') . ' ' . ($d->Model ?? '') . ' (' . ($d->SerialNumber ?? '') . ')'
        ];
    }
    $devices_json = json_encode($devices_by_cat);

    ob_start();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Override Theme Borders */
        #primary,
        #secondary,
        #content,
        .ast-container,
        .site-content,
        .ast-separate-container .ast-article-post {
            border: none !important;
            box-shadow: none !important;
        }

        .ast-right-sidebar #primary,
        .ast-left-sidebar #primary,
        .ast-left-sidebar #secondary {
            border: none !important;
        }

        .dashboard-container {
            padding: 30px;
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: #1e293b;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(to right, #2563eb, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #6366f1;
        }

        .table-wrapper {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            margin-bottom: 40px;
            overflow-x: auto;
        }

        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-custom th {
            background: transparent;
            padding: 16px;
            text-align: left;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .table-custom tr {
            transition: all 0.3s ease;
        }

        .table-custom tbody tr:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.005);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border-radius: 12px;
        }

        .table-custom tbody tr:hover td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table-custom tbody tr:hover td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .badge.pending {
            background: #fef3c7;
            color: #b45309;
        }

        .badge.pending::before {
            background: #f59e0b;
            box-shadow: 0 0 8px #f59e0b;
            animation: pulse 2s infinite;
        }

        .badge.fulfilled {
            background: #dcfce7;
            color: #166534;
        }

        .badge.fulfilled::before {
            background: #22c55e;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.rejected::before {
            background: #ef4444;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.2);
                opacity: 1;
            }

            100% {
                transform: scale(0.95);
                opacity: 0.8;
            }
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            border: none;
            margin-right: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .btn-fulfill {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            color: white;
        }

        .btn-fulfill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #f43f5e 100%);
            color: white;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            padding: 32px;
            border-radius: 24px;
            width: 700px;
            max-width: 95%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.6);
            transform: translateY(20px);
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            font-family: 'Outfit', sans-serif;
        }

        .modal-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .modal-content label {
            font-weight: 500;
            color: #475569;
            font-size: 0.95rem;
        }

        .modal-content textarea {
            width: 100%;
            padding: 12px;
            margin: 12px 0 24px 0;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .modal-content select {
            width: 100%;
            height: 52px;
            padding: 10px 12px;
            margin: 12px 0 24px 0;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .modal-content select:focus,
        .modal-content textarea:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>

    <div class="dashboard-container">
        <h2 class="dashboard-title">IT Device Requests Dashboard</h2>

        <h3 class="section-title">📦 Borrow Requests</h3>
        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Date</th>
                        <th>Requester</th>
                        <th>Category</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No borrow requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>#<?= esc_html($r->RequestID) ?></td>
                                <td><?= esc_html(date('Y-m-d H:i', strtotime($r->RequestDate))) ?></td>
                                <td><?= esc_html($r->Nickname ?: $r->FirstName) ?></td>
                                <td><?= esc_html($r->CategoryName) ?></td>
                                <td><small><?= esc_html($r->Reason) ?></small></td>
                                <td>
                                    <?php
                                    $s = strtolower($r->Status);
                                    echo "<span class='badge {$s}'>" . esc_html($r->Status) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php if ($r->Status === 'Pending'): ?>
                                        <button type="button" class="btn-action btn-fulfill"
                                            onclick="openFulfillModal(<?= esc_attr($r->RequestID) ?>, <?= esc_attr($r->CategoryID) ?>, '<?= esc_attr($r->CategoryName) ?>', '<?= esc_attr($r->AssignedDeviceID) ?>')">Fulfill</button>
                                        <button type="button" class="btn-action btn-reject"
                                            onclick="openRejectModal(<?= esc_attr($r->RequestID) ?>)">Reject</button>
                                    <?php elseif ($r->Status === 'Fulfilled'): ?>
                                        <small class="text-muted">Device: <?= esc_html($r->AssignedDeviceID) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 class="section-title">🛠️ Repair Requests</h3>
        <div class="table-wrapper">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Date</th>
                        <th>Requester</th>
                        <th>Device ID</th>
                        <th>Issue Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($repair_requests)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No repair requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($repair_requests as $r): ?>
                            <tr>
                                <td>#<?= esc_html($r->RequestID) ?></td>
                                <td><?= esc_html(date('Y-m-d H:i', strtotime($r->RequestDate))) ?></td>
                                <td><?= esc_html($r->Nickname ?: $r->FirstName) ?></td>
                                <td><strong><?= esc_html($r->DeviceID) ?></strong></td>
                                <td><small><?= esc_html($r->Reason) ?></small></td>
                                <td>
                                    <?php
                                    $s = strtolower($r->Status);
                                    $s = $s === 'approved' ? 'fulfilled' : $s; // Use same color class as fulfilled
                                    echo "<span class='badge {$s}'>" . esc_html($r->Status) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php if ($r->Status === 'Pending'): ?>
                                        <form method="POST" style="display:inline;"
                                            onsubmit="return confirm('Approve repair and send device to Maintenance?');">
                                            <input type="hidden" name="repair_id" value="<?= esc_attr($r->RequestID) ?>">
                                            <button type="submit" name="approve_repair" class="btn-action btn-fulfill">Approve</button>
                                        </form>
                                        <button type="button" class="btn-action btn-reject"
                                            onclick="openRejectRepairModal(<?= esc_attr($r->RequestID) ?>)">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fulfill Modal -->
    <div id="fulfillModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Fulfill Request</h3>
            <p>Category: <strong id="modal_category_name"></strong></p>
            <form method="POST">
                <input type="hidden" name="request_id" id="fulfill_request_id">
                <input type="hidden" name="assign_device_id" id="hidden_assign_device_id">
                <label>Requested Device<span style="color:red">*</span></label>
                <select id="assign_device_id" disabled required
                    style="background-color: #f1f5f9; color:#475569; cursor: not-allowed; border: 2px dashed #cbd5e1;">
                    <option value="">-- Select Device --</option>
                </select>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="button" class="btn-action btn-cancel" onclick="closeModal('fulfillModal')">Cancel</button>
                    <button type="submit" name="fulfill_request" class="btn-action btn-fulfill">Confirm Fulfill</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Reject Request</h3>
            <form method="POST">
                <input type="hidden" name="request_id" id="reject_request_id">
                <label>Reason for Rejection <span style="color:red">*</span></label>
                <textarea name="reject_reason" rows="3" required
                    placeholder="Explain why this request is rejected..."></textarea>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="button" class="btn-action btn-cancel" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" name="reject_request" class="btn-action btn-reject">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Repair Modal -->
    <div id="rejectRepairModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Reject Repair Request</h3>
            <form method="POST">
                <input type="hidden" name="repair_id" id="reject_repair_id">
                <label>Reason for Rejection <span style="color:red">*</span></label>
                <textarea name="reject_reason" rows="3" required
                    placeholder="Explain why this repair request is rejected..."></textarea>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="button" class="btn-action btn-cancel"
                        onclick="closeModal('rejectRepairModal')">Cancel</button>
                    <button type="submit" name="reject_repair" class="btn-action btn-reject">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const devicesByCat = <?= $devices_json ?>;

        function openFulfillModal(reqId, catId, catName, requestedDeviceId) {
            document.getElementById('fulfill_request_id').value = reqId;
            document.getElementById('hidden_assign_device_id').value = requestedDeviceId;
            document.getElementById('modal_category_name').textContent = catName + (requestedDeviceId ? ' (Requested: ' + requestedDeviceId + ')' : '');

            const select = document.getElementById('assign_device_id');
            select.innerHTML = '';

            if (requestedDeviceId) {
                let deviceLabel = requestedDeviceId;
                if (devicesByCat[catId]) {
                    const found = devicesByCat[catId].find(d => d.id === requestedDeviceId);
                    if (found) deviceLabel = found.label;
                }
                const opt = document.createElement('option');
                opt.value = requestedDeviceId;
                opt.textContent = "⭐ " + deviceLabel + " (Requested)";
                opt.selected = true;
                select.appendChild(opt);
            } else {
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "No device requested";
                select.appendChild(opt);
            }

            document.getElementById('fulfillModal').style.display = 'flex';
        }

        function openRejectModal(reqId) {
            document.getElementById('reject_request_id').value = reqId;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function openRejectRepairModal(reqId) {
            document.getElementById('reject_repair_id').value = reqId;
            document.getElementById('rejectRepairModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('device_request_dashboard', 'device_request_dashboard');
