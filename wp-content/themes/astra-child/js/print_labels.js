function printDeviceLabels(devices) {
    if (!devices || devices.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('No devices selected', 'Please select at least one device to print.', 'warning');
        } else {
            alert('Please select at least one device to print.');
        }
        return;
    }

    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('Popup Blocked', 'Please allow popups for this site to print labels.', 'error');
        } else {
            alert('Please allow popups for this site to print labels.');
        }
        return;
    }

    const baseUrl = window.location.origin + window.location.pathname;

    let html = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title></title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <style>
            @page {
                size: A4 portrait;
                margin: 8mm 6mm;
            }
            @media print {
                html, body {
                    display: block !important;
                    background-color: white !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    width: 100% !important;
                    height: auto !important;
                    min-height: 0 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .no-print {
                    display: none !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .labels-container {
                    display: grid !important;
                    grid-template-columns: repeat(4, 1fr) !important;
                    gap: 6px 8px !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    margin: 0 auto !important;
                    padding: 0 !important;
                }
                .label-card {
                    border: none !important;
                    box-shadow: none !important;
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                    padding: 4px 2px !important;
                    min-height: unset !important;
                    height: auto !important;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 24px;
                background-color: #f1f5f9;
                color: #0f172a;
            }

            .labels-container {
                width: 100%;
                max-width: 760px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px 8px;
                justify-items: center;
                align-items: start;
            }

            .label-card {
                width: 100%;
                max-width: 155px;
                background: white;
                border: none;
                border-radius: 0;
                padding: 6px 4px 4px 4px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .qr-col {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                margin-bottom: 4px;
            }

            .qr-code {
                width: 76px;
                height: 76px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .qr-code img,
            .qr-code canvas {
                display: block;
                margin: 0 auto;
                width: 76px !important;
                height: 76px !important;
            }

            .info-col {
                width: 100%;
                padding: 0 2px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
            }

            .info-col h3 {
                margin: 0 0 2px 0;
                font-size: 14px;
                font-weight: 800;
                color: #0f172a;
                white-space: nowrap;
                letter-spacing: 0.02em;
                line-height: 1.2;
            }

            .info-col p {
                margin: 0;
                font-size: 8px;
                font-weight: 600;
                color: #334155;
                line-height: 1.25;
                word-break: break-word;
                overflow-wrap: anywhere;
                max-width: 100%;
                text-align: center;
            }

            .print-btn-container {
                width: 100%;
                max-width: 760px;
                display: flex;
                justify-content: center;
                gap: 12px;
                margin: 0 auto 24px auto;
            }

            .print-btn-container button {
                padding: 10px 24px;
                font-size: 15px;
                font-weight: 700;
                background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
                color: white;
                border: none;
                border-radius: 9999px;
                cursor: pointer;
                box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }

            .print-btn-container button:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="print-btn-container no-print">
            <button onclick="window.print()">🖨️ Print Now</button>
        </div>
        <div class="labels-container">
    `;

    devices.forEach((dev, index) => {
        html += `
        <div class="label-card">
            <div class="qr-col">
                <div id="qr-${index}" class="qr-code"></div>
            </div>
            <div class="info-col">
                <h3>${dev.id}</h3>
                <p>SN: ${dev.sn || '-'}</p>
            </div>
        </div>
        `;
    });

    html += `
        </div>
        <script>
            window.onload = function() {
                const devices = ${JSON.stringify(devices)};
                const baseUrl = "${baseUrl}";
                
                devices.forEach((dev, index) => {
                    // Generate QR Code
                    const qrUrl = baseUrl + "?view=" + encodeURIComponent(dev.id);
                    new QRCode(document.getElementById('qr-' + index), {
                        text: qrUrl,
                        width: 76,
                        height: 76,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.L
                    });
                });

                // Automatically trigger print dialog after rendering
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    `;

    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
}

// Bulk action handler logic
function handleBulkAction(type) {
    try {
        let formId = document.getElementById('bulk-action-form-device') ? 'bulk-action-form-device' : 'bulk-action-form';
        let checkboxClass = '.device-checkbox';

        if (type && type !== 'device') {
            formId = 'bulk-action-form-' + type;
            checkboxClass = '.device-checkbox-' + type;
        }

        const form = document.getElementById(formId);
        if (!form) return;

        const selectEl = form.querySelector('[name="bulk_action"]');
        const action = selectEl ? selectEl.value : 'print_labels';
        const checked = form.querySelectorAll(checkboxClass + ':checked');

        if (!action) {
            alert('Please select a bulk action.');
            return;
        }
        if (checked.length === 0) {
            alert('Please select at least one device.');
            return;
        }

        if (action === 'print_labels') {
            const devices = [];
            for (let i = 0; i < checked.length; i++) {
                devices.push({
                    id: checked[i].value,
                    sn: checked[i].getAttribute('data-sn') || '-'
                });
            }
            if (typeof printDeviceLabels === 'function') {
                printDeviceLabels(devices);
            } else {
                alert('Print function not loaded. Please try hard refreshing (Ctrl+F5).');
            }
        } else {
            if (confirm('Are you sure you want to apply this action to the selected devices?')) {
                form.submit();
            }
        }
    } catch (err) {
        console.error("Error in bulk action:", err);
        alert("An error occurred: " + err.message);
    }
}

// Global select all listener
document.addEventListener('change', function (e) {
    if (e.target && e.target.id && e.target.id.startsWith('selectAll')) {
        let type = e.target.id.replace('selectAll-', '');
        if (e.target.id === 'selectAll') type = 'device';

        let checkboxClass = type === 'device' ? '.device-checkbox' : '.device-checkbox-' + type;
        const checkboxes = document.querySelectorAll(checkboxClass);
        for (let i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = e.target.checked;
        }
    }
});
