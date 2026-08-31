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
    /* ===== QR Scanner Component — Mobile FAB & Active State ===== */
    .dash-qr-bar {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        position: relative;
        overflow: hidden;
    }

    @media (min-width: 769px) {
        .dash-qr-bar {
            display: none !important;
        }
    }

    @media (max-width: 768px) {
        /* Remove display: none to show Scan QR button on mobile */
    }

    .dash-qr-scan-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        padding: 14px 28px;
        background: #0f172a;
        color: #f8fafc;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, background 0.2s ease;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25);
    }

    .dash-qr-scan-btn i {
        font-size: 1.15rem;
        transition: transform 0.3s ease;
    }

    .dash-qr-scan-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.35);
        background: #1e293b;
    }

    .dash-qr-scan-btn:hover i {
        transform: scale(1.1) rotate(-5deg);
    }

    .dash-qr-scan-btn:active {
        transform: translateY(1px) scale(0.98);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
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
            margin-bottom: 20px;
        }

        .dash-qr-hint {
            display: none;
        }
    }

    /* Fix global z-index for SweetAlert2 container and backdrop */
    .swal2-container {
        z-index: 1000000 !important;
    }

    .swal2-backdrop-show {
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
    }

    .dash-scan-popup {
        border-radius: 20px !important;
        position: relative !important;
        background: #ffffff !important;
        padding: 22px 18px !important;
        box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.35) !important;
        border: 1px solid #e2e8f0 !important;
        width: min(94vw, 480px) !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    .dash-scan-popup .swal2-html-container {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .dash-scan-popup .swal2-close {
        top: 14px !important;
        right: 14px !important;
        width: 32px !important;
        height: 32px !important;
        font-size: 1.1rem !important;
        color: #64748b !important;
        background: #f1f5f9 !important;
        border-radius: 10px !important;
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

    /* Completely eliminate unpopulated SweetAlert2 default form inputs */
    .dash-scan-popup select#swal2-select,
    .dash-scan-popup .swal2-select,
    .dash-scan-popup .swal2-input,
    .dash-scan-popup .swal2-file,
    .dash-scan-popup .swal2-textarea,
    .dash-scan-popup .swal2-radio,
    .dash-scan-popup .swal2-checkbox,
    .dash-scan-popup .swal2-range {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
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

        // Global document event listener for Start button
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
                dashQrBar.style.display = 'block';
                dashQrBar.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                    let errText = 'Unable to access camera. Please grant camera permissions in your browser.';
                    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                        errText = 'Mobile browser requires HTTPS to access camera (SSL Required) or please grant camera permissions in browser settings.';
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Camera Access Error',
                            text: errText,
                            confirmButtonColor: '#ef4444'
                        });
                    } else {
                        alert(errText);
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
                fps: 20,
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                    return {
                        width: Math.floor(minEdge * 0.92),
                        height: Math.floor(minEdge * 0.92)
                    };
                },
                disableFlip: false,
                experimentalFeatures: {
                    useBarCodeDetectorIfSupported: true
                },
                videoConstraints: {
                    facingMode: { ideal: "environment" },
                    width: { min: 640, ideal: 1280, max: 1920 },
                    height: { min: 480, ideal: 720, max: 1080 },
                    advanced: [{ focusMode: "continuous" }]
                }
            };

            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                config.formatsToSupport = [
                    Html5QrcodeSupportedFormats.QR_CODE,
                    Html5QrcodeSupportedFormats.DATA_MATRIX,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.UPC_A
                ];
            }

            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length > 0) {
                    let cameraId = cameras[0].id;
                    for (let i = 0; i < cameras.length; i++) {
                        const label = (cameras[i].label || '').toLowerCase();
                        if (label.includes('back') || label.includes('environment') || label.includes('rear') || label.includes('0')) {
                            cameraId = cameras[i].id;
                            break;
                        }
                    }

                    dashQrScanner.start(cameraId, config, onDashScanSuccess, () => { })
                        .then(() => { onSuccessStart(); })
                        .catch(err => {
                            // Fallback to generic facingMode
                            dashQrScanner.start({ facingMode: "environment" }, config, onDashScanSuccess, () => { })
                                .then(() => { onSuccessStart(); })
                                .catch(err2 => { onErrorStart(err2); });
                        });
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
            // Haptic vibration feedback on successful scan
            if (navigator.vibrate) {
                try { navigator.vibrate([60]); } catch (e) { }
            }

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

        window.__qrSwitchScanTab = function (tabName) {
            const tabInfo = document.getElementById('qr_tab_info');
            const tabHist = document.getElementById('qr_tab_history');
            const btnInfo = document.getElementById('qr_tab_btn_info');
            const btnHist = document.getElementById('qr_tab_btn_history');

            if (tabName === 'history') {
                if (tabInfo) tabInfo.style.display = 'none';
                if (tabHist) tabHist.style.display = 'block';
                if (btnInfo) {
                    btnInfo.style.background = 'transparent';
                    btnInfo.style.color = '#64748b';
                    btnInfo.style.boxShadow = 'none';
                }
                if (btnHist) {
                    btnHist.style.background = '#ffffff';
                    btnHist.style.color = '#4338ca';
                    btnHist.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
                }
            } else {
                if (tabInfo) tabInfo.style.display = 'block';
                if (tabHist) tabHist.style.display = 'none';
                if (btnHist) {
                    btnHist.style.background = 'transparent';
                    btnHist.style.color = '#64748b';
                    btnHist.style.boxShadow = 'none';
                }
                if (btnInfo) {
                    btnInfo.style.background = '#ffffff';
                    btnInfo.style.color = '#0f172a';
                    btnInfo.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
                }
            }
        };

        window.__qrFilterCurrentPage = function (code) {
            if (!code) return;
            if (typeof Swal !== 'undefined') Swal.close();

            const searchInput = document.querySelector('input[name="device_search"]') ||
                document.querySelector('input[type="search"]') ||
                document.querySelector('#search-input') ||
                document.querySelector('#animated-search-input');

            if (searchInput) {
                searchInput.value = code;
                const form = searchInput.closest('form');
                if (form) {
                    form.submit();
                    return;
                }
            }

            // Fallback: reload URL with parameter
            const url = new URL(window.location.href);
            url.searchParams.set('device_search', code);
            url.searchParams.delete('paged');
            window.location.href = url.toString();
        };

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

            // Resolve dynamic category URL
            const categorySlugMap = {
                'Laptop': 'laptop',
                'Monitor': 'monitor',
                'Accessories': 'accessories'
            };
            const catSlug = categorySlugMap[dev.CategoryName] || (dev.CategoryName ? dev.CategoryName.toLowerCase() : 'laptop');
            const viewDetailUrl = `<?= home_url('/') ?>${catSlug}/?view=${encodeURIComponent(dev.DeviceID)}`;
            const historyCount = (dev.history && dev.history.length) ? dev.history.length : 0;
            const isMaintForm = window.location.href.includes('maintenance') || !!document.getElementById('maintenance-device-form');

            let htmlContent = `
        <div style="text-align:left; font-size:0.92rem; box-sizing:border-box;">
            <!-- Header -->
            <div style="margin-bottom:12px; padding-bottom:12px; border-bottom:1.5px solid #f1f5f9; padding-right:32px;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap;">
                    <span style="background:#eff6ff; color:#2563eb; padding:3px 10px; border-radius:8px; font-weight:700; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; border:1px solid #dbeafe;">
                        ${dev.CategoryName || 'Device'}
                    </span>
                    <span style="background:${st.bg}; color:${st.color}; padding:3px 10px; border-radius:8px; font-weight:700; font-size:0.72rem; display:inline-flex; align-items:center; gap:4px; border:1px solid rgba(0,0,0,0.05);">
                        <i class="fa-solid ${st.icon}"></i> ${st.label}
                    </span>
                </div>
                <h3 style="margin:0 0 2px 0; color:#0f172a; font-size:1.35rem; font-weight:800; letter-spacing:-0.02em; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-cube" style="color:#6366f1; font-size:1.15rem;"></i>
                    ${dev.DeviceID}
                </h3>
                <div style="color:#64748b; font-size:0.85rem; font-weight:500;">
                    ${dev.BrandName ? `<strong>${dev.BrandName}</strong>` : ''} ${dev.Model || ''}
                </div>
            </div>

            <!-- Segmented Control Tabs (Info & Actions vs Full History) -->
            <div style="display:flex; background:#f1f5f9; border-radius:12px; padding:3px; margin-bottom:14px; border:1px solid #e2e8f0;">
                <button type="button" id="qr_tab_btn_info" onclick="window.__qrSwitchScanTab('info')" style="flex:1; border:none; background:#ffffff; color:#0f172a; font-weight:700; font-size:0.82rem; padding:8px 10px; border-radius:10px; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.08); transition:all 0.15s ease;">
                    <i class="fa-solid fa-bolt me-1 text-primary"></i> Device & Actions
                </button>
                <button type="button" id="qr_tab_btn_history" onclick="window.__qrSwitchScanTab('history')" style="flex:1; border:none; background:transparent; color:#64748b; font-weight:700; font-size:0.82rem; padding:8px 10px; border-radius:10px; cursor:pointer; transition:all 0.15s ease;">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> History (${historyCount})
                </button>
            </div>

            <!-- TAB 1: Device Details & Quick Actions -->
            <div id="qr_tab_info">
                <!-- Device Info Grid (Bento Table) -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; margin-bottom:14px; background:#f8fafc; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden;">
                    <div style="padding:8px 12px; border-bottom:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">
                        <span style="color:#94a3b8; font-size:0.72rem; font-weight:600; display:block; text-transform:uppercase;"><i class="fa-solid fa-barcode" style="margin-right:4px;"></i>Serial Number</span>
                        <strong style="color:#0f172a; font-size:0.86rem; word-break:break-all;" class="font-mono">${dev.SerialNumber || '-'}</strong>
                    </div>
                    <div style="padding:8px 12px; border-bottom:1px solid #e2e8f0;">
                        <span style="color:#94a3b8; font-size:0.72rem; font-weight:600; display:block; text-transform:uppercase;"><i class="fa-solid fa-user" style="margin-right:4px;"></i>Assigned To</span>
                        <strong style="color:#0f172a; font-size:0.86rem;">${dev.OwnerName || '-'}</strong>
                    </div>
                    <div style="padding:8px 12px; border-bottom:1px solid #e2e8f0; border-right:1px solid #e2e8f0;">
                        <span style="color:#94a3b8; font-size:0.72rem; font-weight:600; display:block; text-transform:uppercase;"><i class="fa-solid fa-building" style="margin-right:4px;"></i>Department</span>
                        <strong style="color:#0f172a; font-size:0.86rem;">${dev.DepartmentName || '-'}</strong>
                    </div>
                    <div style="padding:8px 12px; border-bottom:1px solid #e2e8f0;">
                        <span style="color:#94a3b8; font-size:0.72rem; font-weight:600; display:block; text-transform:uppercase;"><i class="fa-solid fa-calendar-check" style="margin-right:4px;"></i>Received Date</span>
                        <strong style="color:#0f172a; font-size:0.86rem;">${dev.ReceiveDate || '-'}</strong>
                    </div>
                    ${dev.maintenance ? `
                        <div style="padding:8px 12px; grid-column:span 2; background:#fffbeb; border-top:1px solid #fef3c7;">
                            <span style="color:#b45309; font-size:0.72rem; font-weight:700; display:block;"><i class="fa-solid fa-wrench" style="margin-right:4px;"></i>Active Maintenance Issue:</span>
                            <span style="color:#92400e; font-size:0.82rem;">${dev.maintenance.details}</span>
                        </div>
                    ` : ''}
                </div>

                ${isMaintForm ? `
                    <div style="margin-bottom:10px;">
                        <a href="?maintenance=${encodeURIComponent(dev.DeviceID)}" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; width:100%; height:44px; font-weight:800; border-radius:12px; font-size:0.88rem; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 14px rgba(245,158,11,0.3);"><i class="fa-solid fa-wrench"></i> Fill Maintenance Form for ${dev.DeviceID}</a>
                    </div>
                ` : ''}

                ${!detailsOnly ? `
                    <!-- Quick Actions Grid -->
                    <div style="font-weight:700; color:#334155; margin-bottom:8px; font-size:0.8rem; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-bolt" style="color:#6366f1;"></i> Quick Actions
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        ${dev.StatusName === 'In Use' ? `
                            <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'return', '<i class=\\'fa-solid fa-right-to-bracket\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Return Device', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#10b981')" style="background:linear-gradient(135deg,#10b981,#059669); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(16,185,129,0.25);"><i class="fa-solid fa-right-to-bracket"></i> Return</button>
                            <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'maintenance', '<i class=\\'fa-solid fa-wrench\\' style=\\'color:#f59e0b; margin-right:6px;\\'></i> Send to Repair', '<i class=\\'fa-solid fa-wrench\\'></i> Confirm Repair', '#f59e0b')" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(245,158,11,0.25);"><i class="fa-solid fa-wrench"></i> Repair</button>
                        ` : ''}

                        ${dev.StatusName === 'Available' ? `
                            <button type="button" onclick="window.__qrPromptAssign('${dev.DeviceID}')" style="background:linear-gradient(135deg,#6366f1,#4f46e5); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(99,102,241,0.25);"><i class="fa-solid fa-hand-holding-hand"></i> Assign</button>
                            <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'maintenance', '<i class=\\'fa-solid fa-wrench\\' style=\\'color:#f59e0b; margin-right:6px;\\'></i> Send to Repair', '<i class=\\'fa-solid fa-wrench\\'></i> Confirm Repair', '#f59e0b')" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(245,158,11,0.25);"><i class="fa-solid fa-wrench"></i> Repair</button>
                        ` : ''}

                        ${dev.StatusName === 'Maintenance' ? `
                            ${(dev.OwnerID && dev.OwnerName && dev.OwnerName !== '-') ? `
                                <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'return_to_owner', '<i class=\\'fa-solid fa-user-check\\' style=\\'color:#3b82f6; margin-right:6px;\\'></i> Return to Owner', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#3b82f6')" style="background:linear-gradient(135deg,#3b82f6,#2563eb); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.82rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(59,130,246,0.25);"><i class="fa-solid fa-user-check"></i> Return to ${dev.OwnerName}</button>
                            ` : ''}
                            <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'available', '<i class=\\'fa-solid fa-circle-check\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Return to Stock', '<i class=\\'fa-solid fa-check\\'></i> Confirm Return', '#10b981')" style="background:linear-gradient(135deg,#10b981,#059669); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(16,185,129,0.25);"><i class="fa-solid fa-circle-check"></i> Return to Stock</button>
                        ` : ''}

                        ${(dev.StatusName !== 'In Use' && dev.StatusName !== 'Available' && dev.StatusName !== 'Maintenance') ? `
                            <button type="button" onclick="window.__qrPromptActionWithPhoto('${dev.DeviceID}', 'available', '<i class=\\'fa-solid fa-circle-check\\' style=\\'color:#10b981; margin-right:6px;\\'></i> Mark Available', '<i class=\\'fa-solid fa-check\\'></i> Confirm', '#10b981')" style="background:linear-gradient(135deg,#10b981,#059669); color:#ffffff; width:100%; height:42px; font-weight:700; border-radius:12px; font-size:0.84rem; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; box-shadow:0 3px 10px rgba(16,185,129,0.25);"><i class="fa-solid fa-circle-check"></i> Mark Available</button>
                        ` : ''}
                    </div>
                ` : ''}

                <!-- Search/Filter in Page & Full View Buttons -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:10px;">
                    <button type="button" onclick="window.__qrFilterCurrentPage('${dev.DeviceID}')" style="background:#eef2ff; color:#4338ca; border:1.5px solid #c7d2fe; height:42px; font-weight:700; border-radius:12px; font-size:0.82rem; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:6px; transition:all 0.15s ease;">
                        <i class="fa-solid fa-magnifying-glass"></i> Filter in Page
                    </button>
                    <a href="${viewDetailUrl}" style="background:#0f172a; color:#ffffff; height:42px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px; border-radius:12px; font-size:0.82rem; transition:all 0.15s ease;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Full View
                    </a>
                </div>
            </div>

            <!-- TAB 2: Complete Chronological History Timeline -->
            <div id="qr_tab_history" style="display:none; max-height:320px; overflow-y:auto; padding-right:2px;">
                ${(dev.history && dev.history.length > 0) ? `
                    <div style="display:flex; flex-direction:column; gap:10px; position:relative; padding-left:14px; margin-top:6px;">
                        <div style="position:absolute; top:6px; bottom:6px; left:4px; width:2px; background:#e2e8f0;"></div>
                        ${dev.history.map(h => `
                            <div style="position:relative;">
                                <div style="position:absolute; left:-14px; top:6px; width:10px; height:10px; border-radius:50%; background:#4f46e5; border:2px solid #ffffff; box-shadow:0 0 0 1px #cbd5e1;"></div>
                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:9px 12px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                                        <span style="font-weight:800; font-size:0.8rem; color:#0f172a;">${h.action}</span>
                                        <span style="font-size:0.7rem; color:#94a3b8;"><i class="fa-regular fa-clock me-1"></i>${h.date}</span>
                                    </div>
                                    <div style="font-size:0.78rem; color:#334155; line-height:1.35;">${h.desc}</div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px; font-size:0.72rem; color:#64748b;">
                                        ${h.owner && h.owner !== '-' ? `<span><i class="fa-solid fa-user me-1 text-muted"></i>${h.owner}</span>` : '<span></span>'}
                                        ${h.user ? `<span style="text-transform:lowercase;"><i class="fa-solid fa-user-gear me-1 text-muted"></i>${h.user}</span>` : ''}
                                    </div>
                                    ${h.photo ? `
                                        <div style="margin-top:6px;">
                                            <img src="${h.photo}" style="max-height:60px; border-radius:8px; border:1px solid #cbd5e1; cursor:pointer;" onclick="if(window.openPhotoModal) window.openPhotoModal('${h.photo}')" title="Click to view full photo">
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : `
                    <div style="text-align:center; padding:36px 12px; color:#94a3b8;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size:2rem; margin-bottom:8px; display:block; color:#cbd5e1;"></i>
                        <p style="margin:0; font-size:0.88rem; font-weight:600;">No history logs recorded yet.</p>
                    </div>
                `}

                <div style="margin-top:12px; padding-top:10px; border-top:1px solid #f1f5f9; text-align:center;">
                    <a href="${viewDetailUrl}" style="color:#4f46e5; font-size:0.82rem; font-weight:700; text-decoration:none;"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Complete History on Details Page</a>
                </div>
            </div>

        </div>`;

            Swal.fire({
                html: htmlContent,
                showConfirmButton: false,
                showCloseButton: true,
                width: 'min(94vw, 480px)',
                padding: '18px',
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
                        <i class="fa-solid fa-camera" style="color:#6366f1; margin-right:4px;"></i> Device Photo (Optional)
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
                width: 'min(94vw, 480px)',
                padding: '20px',
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

        if (!window.stock_supply_get_dept_abbr) {
            window.stock_supply_get_dept_abbr = function (dept) {
                if (!dept || dept === '-') return '';
                const map = {
                    'IT': 'IT',
                    'Content': 'CONTENT',
                    'Content Writer(TH)': 'CW-TH',
                    'Content EN': 'CT-EN',
                    'SEO': 'SEO',
                    'SEM': 'SEM',
                    'SEO & SEM': 'SEO&SEM',
                    'PBN': 'PBN',
                    'Sale': 'SALE',
                    'Account': 'ACC',
                    'Graphic': 'GRAPHIC',
                    'Art Director': 'AD'
                };
                const trimmed = dept.trim();
                const abbr = map[trimmed] || trimmed;
                return `(${abbr})`;
            };
        }

        window.__qrPromptAssign = function (deviceId) {
            const owners = window.__lastScannedOwners || [];
            let ownerOptionsHtml = '<option value="">-- Select Employee / Borrower --</option>';
            owners.forEach(o => {
                let nickname = (o.Nickname || '').trim();
                let firstName = (o.FirstName || '').trim();
                let lastName = (o.LastName || '').trim();
                let lastInitial = '';
                if (lastName && lastName.toLowerCase() !== 'null') {
                    lastInitial = lastName.charAt(0).toUpperCase() + '.';
                } else if (firstName && firstName.toLowerCase() !== 'null') {
                    let parts = firstName.split(/\s+/);
                    if (parts.length > 1 && parts[parts.length - 1].toLowerCase() !== 'null') {
                        lastInitial = parts[parts.length - 1].charAt(0).toUpperCase() + '.';
                    }
                }
                let namePart = nickname ? (lastInitial ? `${nickname} ${lastInitial}` : nickname) : (firstName || `Owner #${o.OwnerID}`);
                let dept = (o.DepartmentName || '').trim();
                let deptAbbr = window.stock_supply_get_dept_abbr(dept);

                let displayName = deptAbbr ? `${namePart} ${deptAbbr}` : namePart;
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
                        <i class="fa-solid fa-camera" style="color:#6366f1; margin-right:4px;"></i> Device Photo (Optional)
                    </label>
                    <input type="file" id="swal_qr_assign_photo" accept="image/*" capture="environment" onchange="window.__qrPreviewAssignImage(this)" style="width:100%; box-sizing:border-box; padding:8px; border:1px dashed #cbd5e1; border-radius:10px; font-size:0.85rem; background:#ffffff;">
                    <div id="swal_qr_assign_preview_wrap" style="display:none; margin-top:10px; text-align:center;">
                        <img id="swal_qr_assign_preview_img" src="" style="max-height:150px; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                </div>
            `,
                showConfirmButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> Confirm Assignment',
                confirmButtonColor: '#6366f1',
                showDenyButton: true,
                denyButtonText: '<i class="fa-solid fa-arrow-left"></i> Back',
                denyButtonColor: '#64748b',
                showCancelButton: false,
                showCloseButton: false,
                width: 'min(94vw, 480px)',
                padding: '20px',
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