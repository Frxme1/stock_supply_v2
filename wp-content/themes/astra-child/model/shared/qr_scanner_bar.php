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
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 22px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        position: relative;
    }

    .dash-qr-scan-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 26px;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        position: relative;
        overflow: hidden;
    }

    .dash-qr-scan-btn i {
        font-size: 1.15rem;
    }

    .dash-qr-scan-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    }

    .dash-qr-scan-btn:active {
        transform: translateY(0);
    }

    .dash-qr-stop-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    .dash-qr-stop-btn:hover {
        background: #dc2626;
    }

    .dash-qr-hint {
        color: #64748b;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    @media (max-width: 640px) {
        .dash-qr-bar {
            padding: 12px 14px;
        }

        .dash-qr-scan-btn,
        .dash-qr-stop-btn {
            width: 100%;
            justify-content: center;
            padding: 14px 20px;
            font-size: 1rem;
        }

        .dash-qr-hint {
            display: none;
        }
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

        // Auto trigger scan if URL contains ?scan=1
        if (window.location.search.indexOf('scan=1') !== -1) {
            window.addEventListener('load', function () {
                setTimeout(function () {
                    const startBtn = document.getElementById('dash-btn-start-qr');
                    if (startBtn && !isStarting) {
                        startBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        startBtn.click();
                    }
                }, 400);
            });
        }

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
            const dashQrBar = btn.closest('.dash-qr-bar') || document.querySelector('.dash-qr-bar');
            if (!dashReaderDiv) {
                console.error("QR reader container #dash-qr-reader not found");
                return;
            }

            if (dashQrBar) {
                dashQrBar.classList.add('active-scan');
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
                    if (dashQrBar) dashQrBar.classList.remove('active-scan');
                    console.error("Camera Launch Error:", err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Camera Access Error',
                            text: 'Unable to access camera. Please grant camera permissions in your browser.',
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
                    if (dashQrBar) dashQrBar.classList.remove('active-scan');
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
            const dashQrBar = stopBtn.closest('.dash-qr-bar') || document.querySelector('.dash-qr-bar');

            isStarting = false;

            if (dashQrBar) {
                dashQrBar.classList.remove('active-scan');
            }

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
                                html: `This page is for scanning <strong>${categoryFilter}</strong> category devices only.<br><br><small style="color:#64748b;">(Scanned device category: <strong>${dev.CategoryName}</strong> - ID: ${dev.DeviceID})</small>`,
                                confirmButtonColor: '#f59e0b'
                            });
                            return;
                        }

                        // Filter check by Status
                        if (statusFilter && dev.StatusName.toLowerCase() !== statusFilter.toLowerCase()) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Wrong Device Status',
                                html: `This page is for scanning devices with status <strong>${statusFilter}</strong> only.<br><br><small style="color:#64748b;">(Scanned device status: <strong>${dev.StatusName}</strong> - ID: ${dev.DeviceID})</small>`,
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
            window.__lastScannedDevice = dev;
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
                <div style="font-weight:700; color:#334155; margin-bottom:10px; font-size:0.85rem;"><i class="fa-solid fa-bolt" style="color:#6366f1; margin-right:4px;"></i>Quick Actions (Instant Status Change)</div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
            `;

                if (dev.StatusName === 'In Use') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'return', '<i class=\\'fa-solid fa-right-to-bracket\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Return Device', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#10b981')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-right-to-bracket" style="margin-right:6px;"></i>Return</button>
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'maintenance', '<i class=\\'fa-solid fa-wrench\\' style=\\'color:#f59e0b; margin-right:6px;\\'></i> Send to Repair', '<i class=\\'fa-solid fa-wrench\\'></i> Confirm Repair', '#f59e0b')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#f59e0b,#d97706); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-wrench" style="margin-right:6px;"></i>Repair</button>
                `;
                } else if (dev.StatusName === 'Available') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptAssign('${dev.DeviceID}')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#6366f1,#4f46e5); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-hand-holding-hand" style="margin-right:6px;"></i>Assign</button>
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'maintenance', '<i class=\\'fa-solid fa-wrench\\' style=\\'color:#f59e0b; margin-right:6px;\\'></i> Send to Repair', '<i class=\\'fa-solid fa-wrench\\'></i> Confirm Repair', '#f59e0b')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-wrench" style="margin-right:6px;"></i>Repair</button>
                `;
                } else if (dev.StatusName === 'Maintenance') {
                    if (dev.OwnerID && dev.OwnerName && dev.OwnerName !== '-') {
                        htmlContent += `
                        <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'return_to_owner', '<i class=\\'fa-solid fa-user-check\\' style=\\'color:#3b82f6; margin-right:6px;\\'></i> Return to Owner', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#3b82f6')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#3b82f6,#2563eb); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-user-check" style="margin-right:6px;"></i>Return to ${dev.OwnerName}</button>
                    `;
                    }
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'available', '<i class=\\'fa-solid fa-circle-check\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Return to Stock', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#10b981')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Return to Stock</button>
                `;
                } else {
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'available', '<i class=\\'fa-solid fa-circle-check\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Mark Available', '<i class=\\'fa-solid fa-check\\'></i> Confirm', '#10b981')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#10b981,#059669); width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Mark Available</button>
                `;
                }

                if (dev.StatusName !== 'Retired') {
                    htmlContent += `
                    <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'retired', '<i class=\\'fa-solid fa-box-archive\\' style=\\'color:#475569; margin-right:6px;\\'></i> Retire Device', '<i class=\\'fa-solid fa-box-archive\\'></i> Confirm Retire', '#475569')" class="swal2-confirm swal2-styled" style="background:linear-gradient(135deg,#475569,#334155); color:#ffffff; width:100%; margin:0; padding:10px 12px; font-weight:600; border-radius:10px; font-size:0.85rem;"><i class="fa-solid fa-box-archive" style="margin-right:6px;"></i>Retire</button>
                `;
                }

                htmlContent += `
                </div>
                <div style="margin-top:10px;">
                    <a href="/stock_supply/laptop/?view=${encodeURIComponent(dev.DeviceID)}" style="background:#f1f5f9; color:#475569; width:100%; margin:0; padding:10px 16px; font-weight:600; text-decoration:none; text-align:center; display:block; border-radius:10px; font-size:0.88rem; transition:all 0.2s;"><i class="fa-solid fa-arrow-up-right-from-square" style="margin-right:6px;"></i>View Details & History</a>
                </div>
            `;
            } else {
                htmlContent += `
                <div style="margin-top:12px;">
                    <a href="/stock_supply/laptop/?view=${encodeURIComponent(dev.DeviceID)}" style="background:linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color:#ffffff; width:100%; margin:0; padding:11px 16px; font-weight:600; text-decoration:none; text-align:center; display:block; border-radius:10px; font-size:0.88rem; transition:all 0.2s;"><i class="fa-solid fa-arrow-up-right-from-square" style="margin-right:6px;"></i>View Full Details & History</a>
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

        window.__qrPreviewImage = function (input) {
            const previewWrap = document.getElementById('swal_qr_img_preview_wrap');
            const previewImg = document.getElementById('swal_qr_preview_img');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewImg) previewImg.src = e.target.result;
                    if (previewWrap) previewWrap.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                if (previewWrap) previewWrap.style.display = 'none';
            }
        };

        window.__qrPromptActionWithPhoto = function (deviceId, actionType, titleText, confirmText, confirmColor) {
            Swal.fire({
                title: titleText,
                html: `
                <div style="text-align:left; font-size:0.9rem; color:#475569;">
                    <p style="margin-bottom:14px; color:#64748b;">Device <strong>${deviceId}</strong> - You may attach an optional condition photo:</p>
                    
                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:6px; color:#334155;">
                        <i class="fa-solid fa-camera" style="color:#6366f1; margin-right:4px;"></i> Equipment Condition Photo (Camera / Upload)
                    </label>
                    <input type="file" id="swal_qr_action_photo" accept="image/*" capture="environment" onchange="window.__qrPreviewImage(this)" style="width:100%; box-sizing:border-box; padding:8px; border:1px dashed #cbd5e1; border-radius:10px; font-size:0.85rem; background:#ffffff;">
                    <div id="swal_qr_img_preview_wrap" style="display:none; margin-top:10px; text-align:center;">
                        <img id="swal_qr_preview_img" src="" style="max-height:150px; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                </div>
                `,
                showConfirmButton: true,
                confirmButtonText: confirmText || '<i class="fa-solid fa-check"></i> Confirm Action',
                confirmButtonColor: confirmColor || '#6366f1',
                showDenyButton: true,
                denyButtonText: '<i class="fa-solid fa-arrow-left"></i> Back',
                denyButtonColor: '#64748b',
                showCancelButton: false,
                showCloseButton: false,
                customClass: { popup: 'dash-scan-popup' },
                preConfirm: () => {
                    const photoInput = document.getElementById('swal_qr_action_photo');
                    const photoFile = (photoInput && photoInput.files && photoInput.files[0]) ? photoInput.files[0] : null;
                    return { photo: photoFile };
                }
            }).then((result) => {
                if (result.isDenied) {
                    if (window.__lastScannedDevice) {
                        renderScannedDeviceModal(window.__lastScannedDevice);
                    }
                } else if (result.isConfirmed) {
                    window.__qrExecAction(deviceId, actionType, result.value);
                }
            });
        };

        // Expose action functions globally for SweetAlert button onclick
        window.__qrExecAction = function (deviceId, actionType, extraData = {}) {
            Swal.fire({
                title: '<i class="fa-solid fa-spinner fa-spin" style="color:#6366f1"></i> Processing...',
                html: '<span style="color:#64748b;">Updating status in real-time...</span>',
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
                if (extraData.photo) formData.append('photo', extraData.photo);
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
                            title: 'Status Updated Successfully!',
                            text: data.data.message,
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            if (data.data && data.data.redirect_url) {
                                window.location.href = data.data.redirect_url;
                            } else if (typeof window.loadAjaxContent === 'function') {
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

            Swal.fire({
                title: '<i class="fa-solid fa-hand-holding-hand" style="color:#6366f1; margin-right:6px;"></i> Check-out / Assign Device',
                html: `
                <div style="text-align:left; font-size:0.9rem; color:#475569;">
                    <p style="margin-bottom:14px; color:#64748b;">Assign device <strong>${deviceId}</strong> directly to an employee:</p>
                    
                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:6px; color:#334155;">Employee / Borrower *</label>
                    <select id="swal_qr_owner_id" style="width:100%; box-sizing:border-box; margin:0 0 16px 0; border-radius:10px; height:44px; padding:0 12px; font-size:0.9rem; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a; outline:none;">
                        ${ownerOptionsHtml}
                    </select>

                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:6px; color:#334155;">
                        <i class="fa-solid fa-camera" style="color:#6366f1; margin-right:4px;"></i> Equipment Condition Photo (Camera / Upload)
                    </label>
                    <input type="file" id="swal_qr_assign_photo" accept="image/*" capture="environment" onchange="window.__qrPreviewAssignImage(this)" style="width:100%; box-sizing:border-box; padding:8px; border:1px dashed #cbd5e1; border-radius:10px; font-size:0.85rem; background:#ffffff;">
                    <div id="swal_qr_assign_preview_wrap" style="display:none; margin-top:10px; text-align:center;">
                        <img id="swal_qr_assign_preview_img" src="" style="max-height:150px; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                </div>
            `,
                showConfirmButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> Confirm Check-out',
                confirmButtonColor: '#6366f1',
                showDenyButton: true,
                denyButtonText: '<i class="fa-solid fa-arrow-left"></i> Back',
                denyButtonColor: '#64748b',
                showCancelButton: false,
                showCloseButton: false,
                customClass: { popup: 'dash-scan-popup' },
                didOpen: () => {
                    window.__qrPreviewAssignImage = function (input) {
                        const previewWrap = document.getElementById('swal_qr_assign_preview_wrap');
                        const previewImg = document.getElementById('swal_qr_assign_preview_img');
                        if (input.files && input.files[0]) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                if (previewImg) previewImg.src = e.target.result;
                                if (previewWrap) previewWrap.style.display = 'block';
                            };
                            reader.readAsDataURL(input.files[0]);
                        } else {
                            if (previewWrap) previewWrap.style.display = 'none';
                        }
                    };
                },
                preConfirm: () => {
                    const ownerId = document.getElementById('swal_qr_owner_id').value;
                    if (!ownerId) {
                        Swal.showValidationMessage('Please select an employee');
                        return false;
                    }
                    const photoInput = document.getElementById('swal_qr_assign_photo');
                    const photoFile = (photoInput && photoInput.files && photoInput.files[0]) ? photoInput.files[0] : null;
                    return { owner_id: ownerId, photo: photoFile };
                }
            }).then((result) => {
                if (result.isDenied) {
                    if (window.__lastScannedDevice) {
                        renderScannedDeviceModal(window.__lastScannedDevice);
                    }
                } else if (result.isConfirmed) {
                    window.__qrExecAction(deviceId, 'assign', result.value);
                }
            });
        };
    })();
</script>