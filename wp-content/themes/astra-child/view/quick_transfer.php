<?php
if (!defined('ABSPATH')) {
    exit;
}

function quick_transfer_shortcode()
{
    if (!is_user_logged_in()) {
        return '<p>Please log in to access this page.</p>';
    }

    global $wpdb;
    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments ORDER BY DepartmentName ASC");
    $nonce = wp_create_nonce('quick_transfer_nonce');
    $ajax_url = admin_url('admin-ajax.php');

    ob_start();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        .qt-container {
            max-width: 860px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            margin-top: 50px;
        }

        .qt-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .qt-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.3rem;
        }

        .qt-header p {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0;
        }

        /* Step Cards */
        .qt-step {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .qt-step:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
        }

        .qt-step-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 1rem;
        }

        .qt-step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Select */
        .qt-select {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #d1d5db;
            border-radius: 12px;
            font-size: 1rem;
            color: #1e293b;
            background: #fff;
            transition: border-color 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        .qt-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        /* Device Info Card */
        .qt-device-info {
            display: none;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1.25rem;
            margin-top: 1rem;
            animation: qtSlideIn 0.35s ease-out;
        }

        .qt-device-info.visible {
            display: block;
        }

        .qt-device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.85rem;
        }

        .qt-info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .qt-info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 600;
        }

        .qt-info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }

        .qt-arrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .qt-arrow-from {
            background: #fef2f2;
            color: #dc2626;
        }

        .qt-arrow-to {
            background: #ecfdf5;
            color: #059669;
        }

        .qt-arrow-icon {
            color: #6366f1;
            font-size: 1.1rem;
        }

        /* Confirm Button */
        .qt-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .qt-btn-transfer {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .qt-btn-transfer:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
        }

        .qt-btn-transfer:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Hide the green flashing polygon from html5-qrcode */
        #qt_reader__scan_region canvas {
            display: none !important;
        }

        /* Transfer Log */
        .qt-log {
            margin-top: 1.5rem;
        }

        .qt-log-title {
            font-size: 1rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qt-log-count {
            background: #1e293b;
            color: #fff;
            padding: 2px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
        }

        .qt-log-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 8px;
            animation: qtSlideIn 0.3s ease-out;
            flex-wrap: wrap;
            gap: 8px;
        }

        .qt-log-device {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .qt-log-route {
            font-size: 0.82rem;
            color: #64748b;
        }

        .qt-log-badge {
            background: #ecfdf5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .qt-log-time {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Scanner */
        #qt_reader {
            width: 100%;
            max-width: 360px;
            margin: 0 auto 20px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .qt-manual-row {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .qt-manual-input {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .qt-manual-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .qt-manual-btn {
            padding: 10px 20px;
            background: #1e293b;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .qt-manual-btn:hover {
            background: #334155;
        }

        @keyframes qtSlideIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .qt-device-grid {
                grid-template-columns: 1fr 1fr;
            }

            .qt-container {
                padding: 0 12px;
            }
        }
    </style>

    <div class="qt-container">


        <!-- Step 1: Target Department -->
        <div class="qt-step">
            <div class="qt-step-title">
                <span class="qt-step-num">1</span> Select Target Department
            </div>
            <select id="qt_target_dept" class="qt-select">
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dep): ?>
                    <option value="<?= esc_attr($dep->DepartmentID) ?>"><?= esc_html($dep->DepartmentName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Step 2: Scan -->
        <div class="qt-step" id="qt_scan_step" style="display:none;">
            <div class="qt-step-title" style="display:flex; justify-content:space-between; align-items:center;">
                <div><span class="qt-step-num">2</span> Scan Device QR Code</div>
                <button id="qt_stop_camera" class="btn btn-danger btn-sm" style="display:none; border-radius:8px;"><i
                        class="fa-solid fa-xmark"></i> Stop</button>
            </div>
            <div id="qt_reader"></div>
            <div class="qt-manual-row">
                <input type="text" id="qt_manual_id" class="qt-manual-input" placeholder="Or enter Device ID manually...">
                <button type="button" id="qt_manual_btn" class="qt-manual-btn"><i
                        class="fa-solid fa-arrow-right"></i></button>
            </div>

            <!-- Device Info (appears after scan) -->
            <div id="qt_device_info" class="qt-device-info">
                <div class="qt-device-grid">
                    <div class="qt-info-item">
                        <span class="qt-info-label">Device ID</span>
                        <span class="qt-info-value" id="qt_d_id">-</span>
                    </div>
                    <div class="qt-info-item">
                        <span class="qt-info-label">Model</span>
                        <span class="qt-info-value" id="qt_d_model">-</span>
                    </div>
                    <div class="qt-info-item">
                        <span class="qt-info-label">Category</span>
                        <span class="qt-info-value" id="qt_d_cat">-</span>
                    </div>
                    <div class="qt-info-item">
                        <span class="qt-info-label">Serial Number</span>
                        <span class="qt-info-value" id="qt_d_sn">-</span>
                    </div>
                    <div class="qt-info-item">
                        <span class="qt-info-label">Current Owner</span>
                        <span class="qt-info-value" id="qt_d_owner">-</span>
                    </div>
                    <div class="qt-info-item">
                        <span class="qt-info-label">Status</span>
                        <span class="qt-info-value" id="qt_d_status">-</span>
                    </div>
                </div>

                <!-- Transfer Route -->
                <div style="display:flex; align-items:center; gap:10px; margin-top:1rem; flex-wrap:wrap;">
                    <span class="qt-arrow qt-arrow-from" id="qt_from_dept">-</span>
                    <i class="fa-solid fa-arrow-right qt-arrow-icon"></i>
                    <span class="qt-arrow qt-arrow-to" id="qt_to_dept">-</span>
                </div>

                <!-- Optional: New Owner -->
                <div style="margin-top:1rem;">
                    <label class="qt-info-label" style="margin-bottom:6px; display:block;">New Owner (Optional)</label>
                    <select id="qt_new_owner" class="qt-select">
                        <option value="0">-- Keep current / No owner --</option>
                    </select>
                </div>

                <button type="button" id="qt_confirm_btn" class="qt-btn qt-btn-transfer">
                    <i class="fa-solid fa-right-left"></i> Confirm Transfer
                </button>
            </div>
        </div>

        <!-- Transfer Log -->
        <div class="qt-log" id="qt_log" style="display:none;">
            <div class="qt-log-title">
                <i class="fa-solid fa-list-check" style="color:#6366f1;"></i> Transfer Log
                <span class="qt-log-count" id="qt_log_count">0</span>
            </div>
            <div id="qt_log_list"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const AJAX = '<?= $ajax_url ?>';
            const NONCE = '<?= $nonce ?>';
            let scanner = null;
            let currentDevice = null;
            let transferCount = 0;
            let lastScanId = '';
            let lastScanTime = 0;

            const deptSelect = document.getElementById('qt_target_dept');
            const scanStep = document.getElementById('qt_scan_step');
            const deviceInfo = document.getElementById('qt_device_info');
            const confirmBtn = document.getElementById('qt_confirm_btn');
            const logSection = document.getElementById('qt_log');
            const logList = document.getElementById('qt_log_list');
            const logCount = document.getElementById('qt_log_count');
            const stopBtn = document.getElementById('qt_stop_camera');

            function parseDeviceId(text) {
                if (!text) return '';
                text = text.trim();
                const m = text.match(/[?&]view=([^&]+)/i);
                return m ? decodeURIComponent(m[1]).trim() : text;
            }

            function stopCamera() {
                if (scanner) {
                    scanner.stop().then(() => {
                        scanner.clear();
                        scanner = null;
                    }).catch(() => {
                        scanner.clear();
                        scanner = null;
                    });
                }
                stopBtn.style.display = 'none';
                scanStep.style.display = 'none';
                deviceInfo.classList.remove('visible');
                currentDevice = null;
                deptSelect.value = '';
            }

            if (stopBtn) stopBtn.addEventListener('click', stopCamera);

            // Step 1: Show scanner when department selected
            deptSelect.addEventListener('change', function () {
                if (this.value) {
                    scanStep.style.display = 'block';
                    if (!scanner) startScanner();

                    // If a device is already scanned, just update the target department text and reload owners
                    if (currentDevice) {
                        document.getElementById('qt_to_dept').textContent = this.options[this.selectedIndex].text;
                        loadOwners(this.value);
                    } else {
                        deviceInfo.classList.remove('visible');
                    }
                } else {
                    stopCamera();
                }
            });

            function startScanner() {
                const w = window.innerWidth;
                const qr = w < 600 ? 180 : 250;

                scanner = new Html5Qrcode("qt_reader");
                const config = {
                    fps: 25, // Increased FPS for ultra-fast scanning
                    qrbox: { width: qr, height: qr },
                    aspectRatio: 1.0,
                    disableFlip: false,
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    }
                };

                if (typeof Html5QrcodeSupportedFormats !== 'undefined' && Html5QrcodeSupportedFormats.QR_CODE !== undefined) {
                    config.formatsToSupport = [Html5QrcodeSupportedFormats.QR_CODE];
                }

                scanner.start(
                    { facingMode: "environment" },
                    config,
                    onScan,
                    function () { }
                ).then(() => {
                    if (stopBtn) stopBtn.style.display = 'inline-block';
                }).catch(err => {
                    console.error("Camera start error:", err);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Camera Access Denied', timer: 3000, showConfirmButton: false });
                });
            }

            function onScan(text) {
                const now = Date.now();
                if (text === lastScanId && (now - lastScanTime) < 2000) return;
                lastScanId = text;
                lastScanTime = now;
                lookupDevice(text);
            }

            // Manual entry
            document.getElementById('qt_manual_btn').addEventListener('click', function () {
                const val = document.getElementById('qt_manual_id').value.trim();
                if (val) { lookupDevice(val); document.getElementById('qt_manual_id').value = ''; }
            });
            document.getElementById('qt_manual_id').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); document.getElementById('qt_manual_btn').click(); }
            });

            // Lookup device via AJAX
            function lookupDevice(rawId) {
                Swal.fire({
                    title: '<i class="fa-solid fa-magnifying-glass fa-beat-fade" style="color:#6366f1"></i> Checking Device...',
                    html: 'Looking up data for <b>' + parseDeviceId(rawId) + '</b>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const fd = new FormData();
                fd.append('action', 'quick_transfer_lookup');
                fd.append('nonce', NONCE);
                fd.append('device_id', rawId);

                fetch(AJAX, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        Swal.close();
                        if (!res.success) {
                            Swal.fire({ icon: 'error', title: 'Not Found', text: res.data.message, timer: 2500, showConfirmButton: false });
                            return;
                        }
                        currentDevice = res.data;
                        showDeviceInfo(res.data);
                        loadOwners(deptSelect.value);
                    })
                    .catch(() => Swal.fire('Error', 'Connection failed', 'error'));
            }

            function showDeviceInfo(d) {
                document.getElementById('qt_d_id').textContent = d.DeviceID;
                document.getElementById('qt_d_model').textContent = d.Model || '-';
                document.getElementById('qt_d_cat').textContent = d.CategoryName || '-';
                document.getElementById('qt_d_sn').textContent = d.SerialNumber || '-';
                document.getElementById('qt_d_owner').textContent = d.OwnerName || '-';
                document.getElementById('qt_d_status').textContent = d.StatusName || '-';
                document.getElementById('qt_from_dept').textContent = d.DepartmentName || 'Unassigned';
                document.getElementById('qt_to_dept').textContent = deptSelect.options[deptSelect.selectedIndex].text;
                deviceInfo.classList.add('visible');
                confirmBtn.disabled = false;
            }

            function loadOwners(deptId) {
                const sel = document.getElementById('qt_new_owner');
                sel.innerHTML = '<option value="0">-- Keep current / No owner --</option>';
                if (!deptId) return;

                const fd = new FormData();
                fd.append('action', 'quick_transfer_owners');
                fd.append('nonce', NONCE);
                fd.append('department_id', deptId);

                fetch(AJAX, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success && res.data) {
                            res.data.forEach(o => {
                                const opt = document.createElement('option');
                                opt.value = o.OwnerID;
                                opt.textContent = o.Nickname;
                                sel.appendChild(opt);
                            });
                        }

                        // Rebuild custom animated dropdown UI if it exists
                        const wrapper = sel.nextElementSibling;
                        if (wrapper && wrapper.classList.contains('animated-dropdown-wrapper')) {
                            wrapper.remove();
                            if (typeof window.initStaggeredDropdowns === 'function') {
                                window.initStaggeredDropdowns();
                            }
                        }
                    });
            }

            // Confirm Transfer
            confirmBtn.addEventListener('click', function () {
                if (!currentDevice || !deptSelect.value) return;

                const targetDeptName = deptSelect.options[deptSelect.selectedIndex].text;

                // Check if transferring to same department
                if (currentDevice.DepartmentID && parseInt(currentDevice.DepartmentID) === parseInt(deptSelect.value)) {
                    Swal.fire({ icon: 'info', title: 'Same Department', text: 'Device is already in this department.', timer: 2000, showConfirmButton: false });
                    return;
                }

                confirmBtn.disabled = true;

                const fd = new FormData();
                fd.append('action', 'quick_transfer_execute');
                fd.append('nonce', NONCE);
                fd.append('device_id', currentDevice.DeviceID);
                fd.append('new_department_id', deptSelect.value);
                fd.append('new_owner_id', document.getElementById('qt_new_owner').value);

                fetch(AJAX, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Transferred!',
                                html: `<b>${res.data.device_id}</b><br>${res.data.old_dept} → ${res.data.new_dept}`,
                                timer: 2500,
                                showConfirmButton: false
                            });
                            addLogItem(res.data);
                            deviceInfo.classList.remove('visible');
                            currentDevice = null;
                        } else {
                            Swal.fire('Error', res.data.message, 'error');
                            confirmBtn.disabled = false;
                        }
                    })
                    .catch(() => {
                        Swal.fire('Error', 'Connection failed', 'error');
                        confirmBtn.disabled = false;
                    });
            });

            function addLogItem(data) {
                transferCount++;
                logCount.textContent = transferCount;
                logSection.style.display = 'block';

                const item = document.createElement('div');
                item.className = 'qt-log-item';
                item.innerHTML = `
                <div>
                    <div class="qt-log-device">${data.device_id} — ${data.model}</div>
                    <div class="qt-log-route">${data.old_dept} → ${data.new_dept} ${data.new_owner !== '-' ? '| ' + data.new_owner : ''}</div>
                </div>
                <div style="text-align:right;">
                    <span class="qt-log-badge"><i class="fa-solid fa-check"></i> Done</span>
                    <div class="qt-log-time">${data.timestamp}</div>
                </div>
            `;
                logList.insertBefore(item, logList.firstChild);
                if (typeof window.aosGlobalRefresh === 'function') window.aosGlobalRefresh();
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('quick_transfer', 'quick_transfer_shortcode');
?>