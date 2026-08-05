<?php
if (!defined('ABSPATH')) {
    exit;
}

function audit_mode_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to access this page.</p>';
    }

    global $wpdb;
    $departments = $wpdb->get_results("SELECT DepartmentID, DepartmentName FROM Departments ORDER BY DepartmentName ASC");
    
    ob_start();
    ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include html5-qrcode library -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        .audit-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .audit-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .scanned-list {
            margin-top: 20px;
        }
        .scanned-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-left: 5px solid #10b981; /* Green by default */
        }
        .scanned-item.warning {
            border-left-color: #f59e0b; /* Yellow/Orange */
            background: #fffbeb;
        }
        .scanned-item .details h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .scanned-item .details p {
            margin: 0;
            font-size: 14px;
            color: #64748b;
        }
        .summary-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        .summary-btn:hover {
            background: #2563eb;
        }
        
        /* Modal Styles */
        .summary-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .summary-content {
            background: #fff;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .close-modal {
            font-size: 24px;
            cursor: pointer;
            color: #ef4444;
        }
        .badge-danger {
            background: #ef4444; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;
        }
        .badge-success {
            background: #10b981; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;
        }
    </style>

    <div class="audit-container">
        <div class="audit-header">
            <h2>Stock Audit Mode <i class="fas fa-barcode"></i></h2>
            <p>Select a department and start scanning devices to mark them as Verified.</p>
        </div>

        <div class="form-group">
            <label>Current Auditing Department</label>
            <select id="audit_department" class="form-control">
                <option value="">-- Select Department --</option>
                <?php foreach($departments as $dept): ?>
                    <option value="<?= esc_attr($dept->DepartmentID) ?>"><?= esc_html($dept->DepartmentName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="reader-container" style="display: none;">
            <div id="reader"></div>
            
            <div style="text-align: center; margin-top: 15px;">
                <p>Or Enter ID Manually:</p>
                <input type="text" id="manual_device_id" class="form-control" placeholder="DEV-001" style="width: 70%; display: inline-block;">
                <button id="btn_manual_submit" class="summary-btn" style="width: 25%; display: inline-block; padding: 10px; margin-top: 0;">Submit</button>
            </div>
            
            <button class="summary-btn" id="btn_view_summary">View Summary Report</button>
            
            <div class="scanned-list" id="scanned_list">
                <h3>Recently Scanned (<span id="scan_count">0</span>)</h3>
                <!-- Scanned items will appear here -->
            </div>
        </div>
    </div>

    <!-- Summary Modal -->
    <div class="summary-modal" id="summary_modal">
        <div class="summary-content">
            <div class="summary-header">
                <h3>Audit Summary</h3>
                <span class="close-modal" id="close_summary">&times;</span>
            </div>
            
            <h4>Missing Devices <span class="badge-danger" id="missing_count">0</span></h4>
            <p style="font-size: 12px; color: #666;">Devices belonging to this department that haven't been scanned today.</p>
            <ul id="missing_list" style="list-style: none; padding: 0;">
            </ul>
            
            <hr style="margin: 20px 0;">
            
            <h4>Verified Devices <span class="badge-success" id="verified_count">0</span></h4>
            <ul id="verified_list" style="list-style: none; padding: 0;">
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let html5QrcodeScanner = null;
            let currentDepartmentId = null;
            const scanDelay = 2000; // ms
            let lastScannedId = null;
            let lastScannedTime = 0;
            let scanCount = 0;
            const scannedDeviceIds = new Set();

            function parseDeviceId(text) {
                if (!text) return '';
                text = text.trim();
                const match = text.match(/[?&]view=([^&]+)/i);
                if (match) {
                    return decodeURIComponent(match[1]).trim();
                }
                return text;
            }

            const deptSelect = document.getElementById('audit_department');
            const readerContainer = document.getElementById('reader-container');
            
            deptSelect.addEventListener('change', function() {
                if (this.value) {
                    currentDepartmentId = this.value;
                    readerContainer.style.display = 'block';
                    if (!html5QrcodeScanner) {
                        startScanner();
                    }
                } else {
                    currentDepartmentId = null;
                    readerContainer.style.display = 'none';
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.clear();
                        html5QrcodeScanner = null;
                    }
                }
            });

            function startScanner() {
                // Determine qrbox size dynamically based on screen width
                const screenWidth = window.innerWidth;
                const qrboxSize = screenWidth < 600 ? 250 : 350;

                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader",
                    { 
                        fps: 15, // Reduced FPS slightly to prevent camera flashing/stuttering
                        qrbox: { width: qrboxSize, height: qrboxSize },
                        aspectRatio: 1.0,
                        disableFlip: false // allow scanning mirrored/inverted QR
                    },
                    /* verbose= */ false
                );
                html5QrcodeScanner.render(onScanSuccess, onScanFailure);
            }

            function onScanSuccess(decodedText, decodedResult) {
                // Prevent duplicate rapid scans
                const now = Date.now();
                if (decodedText === lastScannedId && (now - lastScannedTime) < scanDelay) {
                    return;
                }
                lastScannedId = decodedText;
                lastScannedTime = now;
                
                processScan(decodedText);
            }

            function onScanFailure(error) {
                // handle scan failure, usually better to ignore and keep scanning
            }
            
            document.getElementById('btn_manual_submit').addEventListener('click', function() {
                const id = document.getElementById('manual_device_id').value.trim();
                if (id) {
                    processScan(id);
                    document.getElementById('manual_device_id').value = '';
                }
            });

            function processScan(rawText) {
                if (!currentDepartmentId) {
                    Swal.fire('Error', 'Please select a department first', 'error');
                    return;
                }
                
                const deviceId = parseDeviceId(rawText);
                
                if (scannedDeviceIds.has(deviceId)) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Scanned',
                        text: 'This device (' + deviceId + ') has already been scanned in this session.',
                        position: 'center',
                        timer: 2000,
                        showConfirmButton: false,
                        backdrop: false
                    });
                    return;
                }
                
                // Add immediately to prevent race condition (double scanning while waiting for AJAX)
                scannedDeviceIds.add(deviceId);
                
                // Removed processing alert so it doesn't interrupt rapid scanning
                const formData = new FormData();
                formData.append('action', 'process_audit_scan');
                formData.append('nonce', '<?= wp_create_nonce("audit_mode_nonce") ?>');
                formData.append('device_id', rawText); // Server will parse it again safely
                formData.append('department_id', currentDepartmentId);

                fetch("<?= admin_url('admin-ajax.php') ?>", {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        // Success handling
                        addScannedItem(res.data);
                        if (res.data.is_wrong_department) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Department Mismatch!',
                                text: res.data.warning,
                                position: 'center',
                                timer: 4000,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#f59e0b',
                                backdrop: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Verified',
                                text: deviceId,
                                position: 'center',
                                timer: 1000,
                                showConfirmButton: false,
                                backdrop: false
                            });
                        }
                    } else {
                        scannedDeviceIds.delete(deviceId); // Release lock if failed
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.data.message || 'Scan failed',
                            position: 'center',
                            timer: 3000,
                            showConfirmButton: true,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    scannedDeviceIds.delete(deviceId); // Release lock if network error
                    Swal.fire('Error', 'Server connection error', 'error');
                });
            }

            function addScannedItem(data) {
                scanCount++;
                document.getElementById('scan_count').innerText = scanCount;
                
                const list = document.getElementById('scanned_list');
                const item = document.createElement('div');
                item.className = 'scanned-item ' + (data.is_wrong_department ? 'warning' : '');
                
                const warningHTML = data.is_wrong_department ? `<p style="color: #ea580c; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> ${data.warning}</p>` : '';
                
                item.innerHTML = `
                    <div class="details">
                        <h4>${data.device_id} - ${data.model}</h4>
                        <p>${data.category} | Scanned: ${data.timestamp}</p>
                        ${warningHTML}
                    </div>
                    <div>
                        ${data.is_wrong_department ? '<span class="badge-danger">Misplaced</span>' : '<span class="badge-success">Verified</span>'}
                    </div>
                `;
                
                // insert at top
                if (list.children.length > 1) {
                    list.insertBefore(item, list.children[1]);
                } else {
                    list.appendChild(item);
                }
            }
            
            // Summary Modal Logic
            const modal = document.getElementById('summary_modal');
            document.getElementById('btn_view_summary').addEventListener('click', function() {
                if (!currentDepartmentId) return;
                
                const formData = new FormData();
                formData.append('action', 'get_audit_summary');
                formData.append('nonce', '<?= wp_create_nonce("audit_mode_nonce") ?>');
                formData.append('department_id', currentDepartmentId);
                
                fetch("<?= admin_url('admin-ajax.php') ?>", {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const mList = document.getElementById('missing_list');
                        const vList = document.getElementById('verified_list');
                        mList.innerHTML = '';
                        vList.innerHTML = '';
                        
                        document.getElementById('missing_count').innerText = res.data.missing.length;
                        document.getElementById('verified_count').innerText = res.data.verified.length;
                        
                        res.data.missing.forEach(d => {
                            mList.innerHTML += `<li style="padding: 10px; border-bottom: 1px solid #eee;">
                                <strong>${d.DeviceID}</strong> - ${d.Model} (${d.CategoryName})
                            </li>`;
                        });
                        
                        if (res.data.missing.length === 0) {
                            mList.innerHTML = '<li><p style="color: #10b981;">No missing devices!</p></li>';
                        }
                        
                        res.data.verified.forEach(d => {
                            const badge = d.LastAuditStatus === 'Misplaced' ? '<span class="badge-danger">Misplaced</span>' : '<span class="badge-success">Verified</span>';
                            vList.innerHTML += `<li style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;">
                                <span><strong>${d.DeviceID}</strong> - ${d.Model}</span>
                                ${badge}
                            </li>`;
                        });
                        
                        modal.style.display = 'flex';
                    }
                });
            });
            
            document.getElementById('close_summary').addEventListener('click', function() {
                modal.style.display = 'none';
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('audit_mode', 'audit_mode_shortcode');
?>
