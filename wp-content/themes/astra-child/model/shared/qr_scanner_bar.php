<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared QR Code Scanner Component
 * Include this file in any page to add a compact QR scan button bar.
 * 
 * Requirements:
 * - Font Awesome 6+ must be loaded
 * - SweetAlert2 (Swal) must be available
 * - WordPress admin-ajax.php must be accessible
 * 
 * Usage: 
 *   $qr_category_filter = 'Laptop'; // optional filter
 *   include(get_stylesheet_directory() . '/model/shared/qr_scanner_bar.php');
 */

$category_filter = isset($qr_category_filter) ? esc_attr($qr_category_filter) : '';
$status_filter = isset($qr_status_filter) ? esc_attr($qr_status_filter) : '';
$details_only = isset($qr_details_only) && $qr_details_only ? 'true' : 'false';

$hint_text = ($details_only === 'true') ? "Scan a device QR to view details" : "Scan a device QR to view details & perform quick actions";
if ($category_filter) {
    $hint_text = "Scan " . $category_filter . " QR code only";
} elseif ($status_filter) {
    $hint_text = "Scan " . $status_filter . " status devices QR code only";
}
?>

<!-- ===== QR Scanner Compact Bar ===== -->
<div class="dash-qr-bar mt-4 slide-up" data-category-filter="<?= $category_filter ?>"
    data-status-filter="<?= $status_filter ?>" data-details-only="<?= $details_only ?>" style="animation-delay: 0.15s;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <button type="button" id="dash-btn-start-qr" class="dash-qr-scan-btn">
            <i class="fa-solid fa-qrcode"></i> Scan QR Code
        </button>
        <button type="button" id="dash-btn-stop-qr" class="dash-qr-stop-btn" style="display: none;">
            <i class="fa-solid fa-xmark"></i> Stop Camera
        </button>
        <span class="dash-qr-hint"><i class="fa-solid fa-circle-info"></i> <?= esc_html($hint_text) ?></span>
    </div>
    <!-- Camera View Container -->
    <div id="dash-qr-reader"
        style="width: 100%; max-width: 500px; margin: 16px auto 0; display: none; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
    </div>
</div>

<style>
    /* ===== QR Scanner Compact Bar ===== */
    .dash-qr-bar {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 20px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
    }

    .dash-qr-scan-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }

    .dash-qr-scan-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
    }

    .dash-qr-stop-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .dash-qr-stop-btn:hover {
        background: #dc2626;
    }

    .dash-qr-hint {
        color: #94a3b8;
        font-size: 0.82rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .dash-scan-popup {
        border-radius: 16px !important;
        position: relative !important;
    }
    .dash-scan-popup .swal2-close {
        top: 14px !important;
        right: 14px !important;
        width: 32px !important;
        height: 32px !important;
        line-height: 32px !important;
        font-size: 1.2rem !important;
        color: #64748b !important;
        background: #f1f5f9 !important;
        border-radius: 8px !important;
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease !important;
        z-index: 10 !important;
    }
    .dash-scan-popup .swal2-close:hover {
        color: #0f172a !important;
        background: #e2e8f0 !important;
    }
</style>

<script>
    (function () {
        if (window.__qrScannerGlobalBound) return;
        window.__qrScannerGlobalBound = true;

        let dashQrScanner = null;
        let isStarting = false;
        const ajaxUrl = '<?= admin_url("admin-ajax.php") ?>';
        const ajaxNonce = '<?= wp_create_nonce("stock_supply_ajax_nonce") ?>';

        // Global document event listener for Start button (Event Delegation guarantees it works even if element loads later)
        document.addEventListener('click', function (e) {
            const startBtn = e.target.closest('#dash-btn-start-qr');
            if (startBtn) {
                e.preventDefault();
                if (!isStarting) {
                    isStarting = true;
                    handleStartScan(startBtn);
                }
                return;
            }

            const stopBtn = e.target.closest('#dash-btn-stop-qr');
            if (stopBtn) {
                e.preventDefault();
                handleStopScan(stopBtn);
                return;
            }
        });

        function handleStartScan(btn) {
            const dashReaderDiv = document.getElementById('dash-qr-reader');
            const dashStopBtn = document.getElementById('dash-btn-stop-qr');
            if (!dashReaderDiv) {
                console.error("QR reader container #dash-qr-reader not found");
                return;
            }

            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Starting Camera...';
            btn.disabled = true;

            dashReaderDiv.style.display = 'block';

            function launchScanner() {
                initDashScanner(function onStarted() {
                    isStarting = false;
                    btn.style.display = 'none';
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    if (dashStopBtn) dashStopBtn.style.display = 'inline-flex';
                }, function onError(err) {
                    isStarting = false;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    dashReaderDiv.style.display = 'none';
                    console.error("Camera Launch Error:", err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Camera Access Error',
                            text: 'ไม่สามารถเปิดใช้งานกล้องได้ โปรดอนุญาตสิทธิ์การใช้กล้องในเบราว์เซอร์',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            }

            if (typeof Html5Qrcode === 'undefined') {
                const script = document.createElement('script');
                script.src = "https://unpkg.com/html5-qrcode";
                script.onload = launchScanner;
                script.onerror = function () {
                    isStarting = false;
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    alert("Failed to load QR scanner library. Please check your internet connection.");
                };
                document.head.appendChild(script);
            } else {
                launchScanner();
            }
        }

        function handleStopScan(stopBtn) {
            const dashReaderDiv = document.getElementById('dash-qr-reader');
            const dashStartBtn = document.getElementById('dash-btn-start-qr');

            isStarting = false;

            if (dashQrScanner) {
                dashQrScanner.stop().then(() => {
                    dashQrScanner = null;
                    if (dashReaderDiv) {
                        dashReaderDiv.style.display = 'none';
                        dashReaderDiv.innerHTML = '';
                    }
                    stopBtn.style.display = 'none';
                    if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
                }).catch(err => {
                    console.error("Error stopping scanner", err);
                    dashQrScanner = null;
                    if (dashReaderDiv) {
                        dashReaderDiv.style.display = 'none';
                        dashReaderDiv.innerHTML = '';
                    }
                    stopBtn.style.display = 'none';
                    if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
                });
            } else {
                if (dashReaderDiv) dashReaderDiv.style.display = 'none';
                stopBtn.style.display = 'none';
                if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
            }
        }

        function initDashScanner(onSuccessStart, onErrorStart) {
            if (dashQrScanner) {
                try {
                    dashQrScanner.stop().catch(() => { }).finally(() => {
                        dashQrScanner = null;
                        document.getElementById('dash-qr-reader').innerHTML = '';
                        startNewScanner(onSuccessStart, onErrorStart);
                    });
                } catch (e) {
                    dashQrScanner = null;
                    document.getElementById('dash-qr-reader').innerHTML = '';
                    startNewScanner(onSuccessStart, onErrorStart);
                }
            } else {
                startNewScanner(onSuccessStart, onErrorStart);
            }
        }

        function startNewScanner(onSuccessStart, onErrorStart) {
            dashQrScanner = new Html5Qrcode("dash-qr-reader");

            const config = {
                fps: 25, // Increased FPS from 10 to 25 for ultra-fast scanning
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    return {
                        width: Math.floor(minEdge * 0.85),
                        height: Math.floor(minEdge * 0.85)
                    };
                },
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            };

            if (typeof Html5QrcodeSupportedFormats !== 'undefined' && Html5QrcodeSupportedFormats.QR_CODE !== undefined) {
                config.formatsToSupport = [Html5QrcodeSupportedFormats.QR_CODE];
            }

            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length > 0) {
                    let cameraId = cameras[0].id;
                    for (let i = 0; i < cameras.length; i++) {
                        const label = cameras[i].label.toLowerCase();
                        if (label.includes('back') || label.includes('environment') || label.includes('rear')) {
                            cameraId = cameras[i].id;
                            break;
                        }
                    }

                    dashQrScanner.start(cameraId, config, onDashScanSuccess, () => { })
                        .then(() => { onSuccessStart(); })
                        .catch(err => { onErrorStart(err); });
                } else {
                    dashQrScanner.start({ facingMode: "environment" }, config, onDashScanSuccess, () => { })
                        .then(() => { onSuccessStart(); })
                        .catch(err => { onErrorStart(err); });
                }
            }).catch(err => {
                dashQrScanner.start({ facingMode: "environment" }, config, onDashScanSuccess, () => { })
                    .then(() => { onSuccessStart(); })
                    .catch(err2 => { onErrorStart(err2); });
            });
        }

        function onDashScanSuccess(decodedText) {
            const dashReaderDiv = document.getElementById('dash-qr-reader');
            const dashStopBtn = document.getElementById('dash-btn-stop-qr');
            const dashStartBtn = document.getElementById('dash-btn-start-qr');

            if (dashQrScanner) {
                try {
                    dashQrScanner.stop().then(() => {
                        dashQrScanner = null;
                        if (dashReaderDiv) {
                            dashReaderDiv.style.display = 'none';
                            dashReaderDiv.innerHTML = '';
                        }
                        if (dashStopBtn) dashStopBtn.style.display = 'none';
                        if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
                    }).catch(e => {
                        console.error(e);
                        dashQrScanner = null;
                        if (dashReaderDiv) {
                            dashReaderDiv.style.display = 'none';
                            dashReaderDiv.innerHTML = '';
                        }
                        if (dashStopBtn) dashStopBtn.style.display = 'none';
                        if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
                    });
                } catch (e) {
                    dashQrScanner = null;
                    if (dashReaderDiv) {
                        dashReaderDiv.style.display = 'none';
                        dashReaderDiv.innerHTML = '';
                    }
                    if (dashStopBtn) dashStopBtn.style.display = 'none';
                    if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
                }
            } else {
                if (dashReaderDiv) {
                    dashReaderDiv.style.display = 'none';
                    dashReaderDiv.innerHTML = '';
                }
                if (dashStopBtn) dashStopBtn.style.display = 'none';
                if (dashStartBtn) dashStartBtn.style.display = 'inline-flex';
            }

            let cleanCode = decodedText.trim();
            const match = cleanCode.match(/[?&]view=([^&]+)/i);
            if (match && match[1]) {
                cleanCode = decodeURIComponent(match[1]);
            }

            fetchAndShowDeviceModal(cleanCode);
        }

        function fetchAndShowDeviceModal(code) {
            const qrBar = document.querySelector('.dash-qr-bar');
            const categoryFilter = qrBar ? (qrBar.getAttribute('data-category-filter') || '').trim() : '';
            const statusFilter = qrBar ? (qrBar.getAttribute('data-status-filter') || '').trim() : '';

            Swal.fire({
                title: '<i class="fa-solid fa-magnifying-glass fa-beat-fade" style="color:#6366f1"></i> Searching Device...',
                html: '<span style="color:#64748b;font-size:0.9rem;">QR / ID: <strong>' + code + '</strong></span>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('action', 'get_scanned_device_details');
            formData.append('code', code);
            formData.append('nonce', ajaxNonce);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const dev = data.data;

                        // Filter check by Category
                        if (categoryFilter && dev.CategoryName.toLowerCase() !== categoryFilter.toLowerCase()) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Wrong Device Category',
                                html: `หน้านี้สำหรับสแกนอุปกรณ์ประเภท <strong>${categoryFilter}</strong> เท่านั้น<br><br><small style="color:#64748b;">(อุปกรณ์ที่สแกนเป็นประเภท <strong>${dev.CategoryName}</strong> - รหัส: ${dev.DeviceID})</small>`,
                                confirmButtonColor: '#f59e0b'
                            });
                            return;
                        }

                        // Filter check by Status
                        if (statusFilter && dev.StatusName.toLowerCase() !== statusFilter.toLowerCase()) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Wrong Device Status',
                                html: `หน้านี้สำหรับสแกนอุปกรณ์ที่มีสถานะ <strong>${statusFilter}</strong> เท่านั้น<br><br><small style="color:#64748b;">(อุปกรณ์ที่สแกนมีสถานะ <strong>${dev.StatusName}</strong> - รหัส: ${dev.DeviceID})</small>`,
                                confirmButtonColor: '#f59e0b'
                            });
                            return;
                        }

                        renderScannedDeviceModal(dev);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Device Not Found',
                            text: data.data ? data.data.message : 'No device matching this code was found in the system.',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Unable to connect to the server. Please try again.',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        function renderScannedDeviceModal(dev) {
            const qrBar = document.querySelector('.dash-qr-bar');
            const detailsOnly = qrBar ? (qrBar.getAttribute('data-details-only') === 'true') : false;
            window.__lastScannedOwners = dev.all_owners || [];
            const statusMap = {
                'In Use': { icon: 'fa-circle-xmark', color: '#dc2626', bg: '#fee2e2', label: 'In Use' },
                'Available': { icon: 'fa-circle-check', color: '#16a34a', bg: '#dcfce7', label: 'Available' },
                'Maintenance': { icon: 'fa-wrench', color: '#d97706', bg: '#fef3c7', label: 'Maintenance' },
                'Retired': { icon: 'fa-box-archive', color: '#64748b', bg: '#f1f5f9', label: 'Retired' }
            };
            const st = statusMap[dev.StatusName] || { icon: 'fa-circle-question', color: '#6366f1', bg: '#e0e7ff', label: dev.StatusName };

            let htmlContent = `
        <div style="text-align:left; font-size:0.92rem;">
            <!-- Header -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid #f1f5f9; padding-right:40px;">
                <div>
                    <h3 style="margin:0; color:#0f172a; font-size:1.35rem; font-weight:700;"><i class="fa-solid fa-box-archive" style="color:#6366f1; margin-right:8px;"></i>${dev.DeviceID}</h3>
                    <div style="color:#64748b; font-size:0.82rem; margin-top:2px;">${dev.CategoryName} &bull; ${dev.BrandName} ${dev.Model}</div>
                </div>
                <span style="background:${st.bg}; color:${st.color}; padding:6px 14px; border-radius:20px; font-weight:700; font-size:0.82rem; display:inline-flex; align-items:center; gap:6px; flex-shrink:0;">
                    <i class="fa-solid ${st.icon}"></i> ${st.label}
                </span>
            </div>

            <!-- Device Info Grid -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; margin-bottom:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden;">
                <div style="padding:10px 14px; border-bottom:1px solid #e2e8f0; border-right:1px solid #e2e8f0;"><span style="color:#94a3b8; font-size:0.78rem; display:block;"><i class="fa-solid fa-barcode" style="margin-right:4px;"></i>Serial Number</span><strong style="color:#0f172a;">${dev.SerialNumber}</strong></div>
                <div style="padding:10px 14px; border-bottom:1px solid #e2e8f0;"><span style="color:#94a3b8; font-size:0.78rem; display:block;"><i class="fa-solid fa-user" style="margin-right:4px;"></i>Assigned To</span><strong style="color:#0f172a;">${dev.OwnerName}</strong></div>
                <div style="padding:10px 14px; border-bottom:1px solid #e2e8f0; border-right:1px solid #e2e8f0;"><span style="color:#94a3b8; font-size:0.78rem; display:block;"><i class="fa-solid fa-building" style="margin-right:4px;"></i>Department</span><strong style="color:#0f172a;">${dev.DepartmentName}</strong></div>
                <div style="padding:10px 14px; border-bottom:1px solid #e2e8f0;"><span style="color:#94a3b8; font-size:0.78rem; display:block;"><i class="fa-solid fa-calendar-check" style="margin-right:4px;"></i>Received Date</span><strong style="color:#0f172a;">${dev.ReceiveDate}</strong></div>
                <div style="padding:10px 14px;"><span style="color:#94a3b8; font-size:0.78rem; display:block;"><i class="fa-solid fa-screwdriver-wrench" style="margin-right:4px;"></i>Last Repair</span><strong style="color:#0f172a;">${dev.RepairDate}</strong></div>
            </div>
`;

            if (!detailsOnly) {
                htmlContent += `
                <!-- Quick Actions -->
                <div style="font-weight:700; color:#334155; margin-bottom:10px; font-size:0.85rem;"><i class="fa-solid fa-bolt" style="color:#6366f1; margin-right:4px;"></i>Quick Actions (เปลี่ยนสถานะทันที)</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
            `;

                if (dev.StatusName === 'In Use') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'return')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-right-to-bracket" style="margin-right:6px;"></i>รับคืน (Return)</button>
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'maintenance')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#f59e0b,#d97706); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-wrench" style="margin-right:6px;"></i>ส่งซ่อม (Repair)</button>
                `;
                } else if (dev.StatusName === 'Available') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptAssign('${dev.DeviceID}')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#6366f1,#4f46e5); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-hand-holding-hand" style="margin-right:6px;"></i>เบิกจ่าย (Assign)</button>
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'maintenance')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-wrench" style="margin-right:6px;"></i>ส่งซ่อม (Repair)</button>
                `;
                } else if (dev.StatusName === 'Maintenance') {
                    if (dev.OwnerID && dev.OwnerName && dev.OwnerName !== '-') {
                        htmlContent += `
                        <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'return_to_owner')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#3b82f6,#2563eb); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-user-check" style="margin-right:6px;"></i>ส่งคืน ${dev.OwnerName}</button>
                    `;
                    }
                    htmlContent += `
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'available')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>คืนเข้าคลัง (Available)</button>
                `;
                } else {
                    htmlContent += `
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'available')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>ทำให้ใช้งานได้ (Available)</button>
                `;
                }

                if (dev.StatusName !== 'Retired') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrExecAction('${dev.DeviceID}', 'retired')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#475569,#334155); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-box-archive" style="margin-right:6px;"></i>ปลดระวาง (Retire)</button>
                `;
                }

                htmlContent += `
                </div>
                <div style="margin-top:10px;">
                    <a href="/stock_supply/laptop/?view=${encodeURIComponent(dev.DeviceID)}" style="background:#f1f5f9; color:#475569; width:100%; margin:0; padding:10px 16px; font-weight:600; text-decoration:none; text-align:center; display:block; border-radius:10px; font-size:0.88rem; transition:all 0.2s;"><i class="fa-solid fa-arrow-up-right-from-square" style="margin-right:6px;"></i>ดูรายละเอียดและประวัติทั้งหมด</a>
                </div>
            `;
            } else {
                htmlContent += `
                <div style="margin-top:12px;">
                    <a href="/stock_supply/laptop/?view=${encodeURIComponent(dev.DeviceID)}" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; width:100%; margin:0; padding:11px 16px; font-weight:600; text-decoration:none; text-align:center; display:block; border-radius:10px; font-size:0.88rem; transition:all 0.2s;"><i class="fa-solid fa-arrow-up-right-from-square" style="margin-right:6px;"></i>ดูรายละเอียดและประวัติทั้งหมด</a>
                </div>
            `;
            }

            htmlContent += `</div>`;

            Swal.fire({
                html: htmlContent,
                showConfirmButton: false,
                showCloseButton: true,
                width: '560px',
                padding: '24px',
                customClass: { popup: 'dash-scan-popup' }
            });
        }

        // Expose action functions globally for SweetAlert button onclick
        window.__qrExecAction = function (deviceId, actionType, extraData = {}) {
            Swal.fire({
                title: '<i class="fa-solid fa-spinner fa-spin" style="color:#6366f1"></i> Processing...',
                html: '<span style="color:#64748b;">กำลังดำเนินการอัปเดตสถานะแบบ Real-time</span>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const formData = new FormData();
            formData.append('action', 'quick_device_action');
            formData.append('device_id', deviceId);
            formData.append('action_type', actionType);
            formData.append('nonce', ajaxNonce);
            if (extraData) {
                if (extraData.new_due_date) formData.append('new_due_date', extraData.new_due_date);
                if (extraData.owner_id) formData.append('owner_id', extraData.owner_id);
            }

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'อัปเดตสถานะสำเร็จ!',
                            text: data.data.message,
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            if (typeof window.loadAjaxContent === 'function') {
                                window.loadAjaxContent(window.location.href);
                            } else {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Action Failed',
                            text: data.data ? data.data.message : 'Unable to complete the requested action.',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Unable to connect to the server. Please try again.',
                        confirmButtonColor: '#ef4444'
                    });
                });
        };

        window.__qrPromptAssign = function (deviceId) {
            const owners = window.__lastScannedOwners || [];
            let ownerOptionsHtml = '<option value="">-- Select Employee / Borrower --</option>';
            owners.forEach(o => {
                let nickname = (o.Nickname || '').trim();
                let fullName = [o.FirstName, o.LastName].filter(Boolean).map(s => s.trim()).filter(Boolean).join(' ');
                let namePart = nickname || fullName || `Owner #${o.OwnerID}`;
                let dept = (o.DepartmentName || '').trim();
                
                let displayName = dept ? `${namePart} (${dept})` : namePart;
                ownerOptionsHtml += `<option value="${o.OwnerID}">${displayName}</option>`;
            });

            const today = new Date().toISOString().split('T')[0];

            Swal.fire({
                title: '<i class="fa-solid fa-hand-holding-hand" style="color:#6366f1; margin-right:6px;"></i> Check-out / Assign Device',
                html: `
                <div style="text-align:left; font-size:0.9rem; color:#475569;">
                    <p style="margin-bottom:14px; color:#64748b;">Assign device <strong>${deviceId}</strong> directly to an employee:</p>
                    
                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:6px; color:#334155;">Employee / Borrower *</label>
                    <select id="swal_qr_owner_id" style="width:100%; box-sizing:border-box; margin:0 0 16px 0; border-radius:10px; height:44px; padding:0 12px; font-size:0.9rem; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a; outline:none;">
                        ${ownerOptionsHtml}
                    </select>
                </div>
            `,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> Confirm Check-out',
                cancelButtonText: '<i class="fa-solid fa-xmark"></i> Cancel',
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#94a3b8',
                customClass: { popup: 'dash-scan-popup' },
                preConfirm: () => {
                    const ownerId = document.getElementById('swal_qr_owner_id').value;
                    if (!ownerId) {
                        Swal.showValidationMessage('Please select an employee');
                        return false;
                    }
                    return { owner_id: ownerId };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.__qrExecAction(deviceId, 'assign', result.value);
                }
            });
        };
    })();
</script>