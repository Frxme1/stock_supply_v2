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
        <title>Print Labels</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
        <style>
            @media print {
                @page { margin: 10mm; }
                body { -webkit-print-color-adjust: exact; }
                .no-print { display: none !important; }
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 15px;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                justify-content: flex-start;
                background-color: #f0f0f0;
            }
            @media print {
                body { background-color: white; padding: 0; }
            }
            .label-card {
                width: 260px; /* Standard A4 address label width equivalent */
                height: 120px;
                background: white;
                border: 1px dashed #aaa;
                border-radius: 8px;
                padding: 10px;
                box-sizing: border-box;
                display: flex;
                flex-direction: row;
                align-items: center;
                page-break-inside: avoid;
            }
            @media print {
                .label-card { border: 1px solid transparent; /* Hide border when printing if using pre-cut labels */ }
            }
            .qr-col {
                width: 80px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .qr-code {
                width: 70px;
                height: 70px;
            }
            .info-col {
                flex: 1;
                padding-left: 10px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                overflow: hidden;
            }
            .info-col h3 {
                margin: 0 0 5px 0;
                font-size: 16px;
                text-align: center;
                white-space: nowrap;
            }
            .info-col p {
                margin: 0 0 5px 0;
                font-size: 12px;
                color: #555;
                text-align: center;
                white-space: nowrap;
            }
            .barcode-svg {
                width: 100%;
                height: 40px;
            }
            .print-btn-container {
                width: 100%;
                text-align: center;
                margin-bottom: 20px;
            }
            .print-btn-container button {
                padding: 10px 20px;
                font-size: 16px;
                background-color: #0d6efd;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
            .print-btn-container button:hover {
                background-color: #0b5ed7;
            }
        </style>
    </head>
    <body>
        <div class="print-btn-container no-print">
            <button onclick="window.print()">🖨️ Print Now</button>
        </div>
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
                <svg id="barcode-${index}" class="barcode-svg"></svg>
            </div>
        </div>
        `;
    });

    html += `
        <script>
            window.onload = function() {
                const devices = ${JSON.stringify(devices)};
                const baseUrl = "${baseUrl}";
                
                devices.forEach((dev, index) => {
                    // Generate QR Code
                    const qrUrl = baseUrl + "?view=" + encodeURIComponent(dev.id);
                    new QRCode(document.getElementById('qr-' + index), {
                        text: qrUrl,
                        width: 70,
                        height: 70,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.L
                    });

                    // Generate Barcode
                    JsBarcode("#barcode-" + index, dev.id, {
                        format: "CODE128",
                        displayValue: false, // Value is already displayed as h3
                        lineColor: "#000000",
                        width: 1.5,
                        height: 35,
                        margin: 0
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
        let formId = 'bulk-action-form';
        let checkboxClass = '.device-checkbox';

        if (type && type !== 'device') {
            formId = 'bulk-action-form-' + type;
            checkboxClass = '.device-checkbox-' + type;
        }

        const form = document.getElementById(formId);
        if (!form) return;

        const selectEl = form.querySelector('select[name="bulk_action"]');
        const action = selectEl ? selectEl.value : '';
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
