function confirmRetire(id, paramName = 'retired', nonce = '') {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to retire this item? Please provide a reason.',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Enter reason here...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a reason!';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Yes, retire it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = new URL(window.location.href);
            url.searchParams.set(paramName, id);
            url.searchParams.set('reason', result.value);
            if (nonce) {
                url.searchParams.set('_wpnonce', nonce);
            }
            window.location.href = url.toString();
        }
    });
}
window.confirmRetire = confirmRetire;

function confirmLost(id, paramName = 'lost') {
    Swal.fire({
        title: 'Report as Lost?',
        text: 'Do you want to mark this device as lost? Please provide a reason or details.',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Enter details here...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter details!';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Yes, mark as lost!'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = new URL(window.location.href);
            url.searchParams.set(paramName, id);
            url.searchParams.set('reason', result.value);
            window.location.href = url.toString();
        }
    });
}
window.confirmLost = confirmLost;

function confirmReceive(idOrData, model = '', brand = '', category = '', serial = '') {
    let data = {};
    if (typeof idOrData === 'object' && idOrData !== null) {
        data = idOrData;
    } else {
        data = {
            id: idOrData,
            model: model || '',
            brand: brand || '',
            category: category || '',
            serialNumber: serial || ''
        };
    }

    const deviceId = data.id || '';
    const brandName = data.brand || '';
    const modelName = data.model || '';
    const categoryName = data.category || 'Hardware';
    const serialNumber = data.serialNumber || '-';

    // Choose icon based on category
    let catIcon = 'fa-laptop';
    const catLower = categoryName.toLowerCase();
    if (catLower.includes('monitor') || catLower.includes('screen') || catLower.includes('display')) {
        catIcon = 'fa-desktop';
    } else if (catLower.includes('access') || catLower.includes('mouse') || catLower.includes('keyboard') || catLower.includes('cable') || catLower.includes('plug')) {
        catIcon = 'fa-plug';
    }

    const fullName = [brandName, modelName].filter(Boolean).join(' - ') || `Device #${deviceId}`;

    const modalHtml = `
    <div style="text-align: left; margin: 6px 0 8px 0;">
        <!-- Device Info Card -->
        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">
                <div style="display: inline-flex; align-items: center; gap: 8px;">
                    <span style="background: #eff6ff; color: #1d4ed8; font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.95rem; padding: 4px 10px; border-radius: 8px; border: 1px solid #bfdbfe;">
                        #${deviceId}
                    </span>
                </div>
                <span style="background: #dcfce7; color: #15803d; font-size: 0.8rem; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 7px; height: 7px; background: #22c55e; border-radius: 50%;"></span>
                    Available
                </span>
            </div>

            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 14px;">
                ${fullName}
            </div>

            <!-- Details Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.88rem;">
                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid ${catIcon} me-1" style="color: #6366f1;"></i> Category
                    </span>
                    <strong style="color: #1e293b;">${categoryName}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-tag me-1" style="color: #0ea5e9;"></i> Brand
                    </span>
                    <strong style="color: #1e293b;">${brandName || '-'}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9; grid-column: span 2;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-barcode me-1" style="color: #f59e0b;"></i> Serial Number
                    </span>
                    <span style="font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 700; color: #334155; font-size: 0.92rem;">
                        ${serialNumber}
                    </span>
                </div>
            </div>
        </div>

        <p style="margin: 0; color: #64748b; font-size: 0.92rem; line-height: 1.5; text-align: center;">
            Do you want to proceed to the assignment page to check out this hardware asset to an employee?
        </p>
    </div>`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '📦 Assign / Receive Device',
            html: modalHtml,
            width: 520,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-arrow-right me-1"></i> Proceed to Assign',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-popup-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.set('receive', deviceId);
                url.searchParams.delete('edit');
                url.searchParams.delete('view');
                url.searchParams.delete('maintenance');
                url.searchParams.delete('return');
                url.searchParams.delete('available');
                url.searchParams.delete('retired');
                window.location.href = url.toString();
            }
        });
    } else {
        if (confirm(`Do you want to assign ${fullName} (#${deviceId}) to an employee?`)) {
            const url = new URL(window.location.href);
            url.searchParams.set('receive', deviceId);
            window.location.href = url.toString();
        }
    }
}
window.confirmReceive = confirmReceive;

function confirmReturnFromMaintenance(idOrData, nonce = '') {
    let data = {};
    if (typeof idOrData === 'object' && idOrData !== null) {
        data = idOrData;
    } else {
        data = {
            id: idOrData,
            model: '',
            brand: '',
            category: 'Hardware',
            serialNumber: '-',
            owner: '',
            department: '',
            details: '',
            repairDate: ''
        };
    }

    const deviceId = data.id || '';
    const brandName = data.brand || '';
    const modelName = data.model || '';
    const categoryName = data.category || 'Hardware';
    const serialNumber = data.serialNumber || '-';
    const ownerName = data.owner && data.owner !== '-' ? data.owner : '';
    const deptName = data.department && data.department !== '-' ? data.department : '';
    const details = data.details || 'Maintenance completed';
    const actionNonce = nonce || data.nonce || '';

    let catIcon = 'fa-laptop';
    const catLower = categoryName.toLowerCase();
    if (catLower.includes('monitor') || catLower.includes('screen') || catLower.includes('display')) {
        catIcon = 'fa-desktop';
    } else if (catLower.includes('access') || catLower.includes('mouse') || catLower.includes('keyboard') || catLower.includes('cable') || catLower.includes('plug')) {
        catIcon = 'fa-plug';
    }

    const fullName = [brandName, modelName].filter(Boolean).join(' - ') || `Device #${deviceId}`;

    const modalHtml = `
    <div style="text-align: left; margin: 6px 0 8px 0;">
        <!-- Card -->
        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">
                <span style="background: #eff6ff; color: #1d4ed8; font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.95rem; padding: 4px 10px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    #${deviceId}
                </span>
                <span style="background: #fef3c7; color: #b45309; font-size: 0.8rem; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fa-solid fa-screwdriver-wrench" style="font-size: 0.75rem;"></i> Repair Done
                </span>
            </div>

            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 14px;">
                ${fullName}
            </div>

            <!-- Details Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.88rem; margin-bottom: 12px;">
                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid ${catIcon} me-1" style="color: #6366f1;"></i> Category
                    </span>
                    <strong style="color: #1e293b;">${categoryName}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-user me-1" style="color: #0ea5e9;"></i> Assigned Owner
                    </span>
                    <strong style="color: #1e293b; font-size: 0.85rem;">${ownerName ? `${ownerName}${deptName ? ` (${deptName})` : ''}` : 'Inventory Stock (No Owner)'}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9; grid-column: span 2;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-barcode me-1" style="color: #f59e0b;"></i> Serial Number
                    </span>
                    <span style="font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 700; color: #334155; font-size: 0.92rem;">
                        ${serialNumber}
                    </span>
                </div>
            </div>

            <!-- Issue Summary Box -->
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: #92400e;">
                <span style="font-weight: 700; display: block; margin-bottom: 2px;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Issue / Repair Reason:
                </span>
                <span style="color: #78350f;">${details}</span>
            </div>
        </div>

        <p style="margin: 0; color: #475569; font-size: 0.92rem; line-height: 1.5; text-align: center;">
            ${ownerName ? `Do you want to return this repaired device to <strong>${ownerName}</strong> or return to available inventory?` : `Do you want to return this repaired device to available inventory stock?`}
        </p>
    </div>`;

    if (typeof Swal !== 'undefined') {
        const swalConfig = {
            title: '🛠️ Return from Maintenance',
            html: modalHtml,
            width: 530,
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            cancelButtonColor: '#64748b',
            customClass: {
                popup: 'swal2-popup-modern'
            }
        };

        if (ownerName) {
            swalConfig.showDenyButton = true;
            swalConfig.confirmButtonText = `<i class="fa-solid fa-user-check me-1"></i> Return to ${ownerName}`;
            swalConfig.confirmButtonColor = '#2563eb';
            swalConfig.denyButtonText = `<i class="fa-solid fa-boxes-stacked me-1"></i> Return to Stock`;
            swalConfig.denyButtonColor = '#16a34a';
        } else {
            swalConfig.showDenyButton = false;
            swalConfig.confirmButtonText = `<i class="fa-solid fa-boxes-stacked me-1"></i> Return to Stock (Available)`;
            swalConfig.confirmButtonColor = '#16a34a';
        }

        Swal.fire(swalConfig).then((result) => {
            if (result.isConfirmed) {
                const targetAction = ownerName ? 'return_to_owner' : 'available';
                const url = new URL(window.location.href);
                url.searchParams.set(targetAction, deviceId);
                if (actionNonce) url.searchParams.set('_wpnonce', actionNonce);
                url.searchParams.delete('view');
                url.searchParams.delete('edit');
                url.searchParams.delete('maintenance');
                window.location.href = url.toString();
            } else if (result.isDenied) {
                const url = new URL(window.location.href);
                url.searchParams.set('available', deviceId);
                if (actionNonce) url.searchParams.set('_wpnonce', actionNonce);
                url.searchParams.delete('view');
                url.searchParams.delete('edit');
                url.searchParams.delete('maintenance');
                window.location.href = url.toString();
            }
        });
    } else {
        if (confirm(`Confirm return device ${fullName} (#${deviceId}) from maintenance?`)) {
            const targetAction = ownerName ? 'return_to_owner' : 'available';
            const url = new URL(window.location.href);
            url.searchParams.set(targetAction, deviceId);
            if (actionNonce) url.searchParams.set('_wpnonce', actionNonce);
            window.location.href = url.toString();
        }
    }
}
window.confirmReturnFromMaintenance = confirmReturnFromMaintenance;

function confirmReturnDevice(idOrData, nonce = '') {
    let data = {};
    if (typeof idOrData === 'object' && idOrData !== null) {
        data = idOrData;
    } else {
        data = {
            id: idOrData,
            model: '',
            brand: '',
            category: 'Hardware',
            serialNumber: '-',
            owner: '',
            department: ''
        };
    }

    const deviceId = data.id || '';
    const brandName = data.brand || '';
    const modelName = data.model || '';
    const categoryName = data.category || 'Hardware';
    const serialNumber = data.serialNumber || '-';
    const ownerName = data.owner && data.owner !== '-' ? data.owner : '';
    const deptName = data.department && data.department !== '-' ? data.department : '';
    const actionNonce = nonce || data.nonce || '';

    let catIcon = 'fa-laptop';
    const catLower = categoryName.toLowerCase();
    if (catLower.includes('monitor') || catLower.includes('screen') || catLower.includes('display')) {
        catIcon = 'fa-desktop';
    } else if (catLower.includes('access') || catLower.includes('mouse') || catLower.includes('keyboard') || catLower.includes('cable') || catLower.includes('plug')) {
        catIcon = 'fa-plug';
    }

    const fullName = [brandName, modelName].filter(Boolean).join(' - ') || `Device #${deviceId}`;
    const ownerDisplay = ownerName ? `${ownerName}${deptName ? ` (${deptName})` : ''}` : 'Employee';

    const modalHtml = `
    <div style="text-align: left; margin: 6px 0 8px 0;">
        <!-- Card -->
        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #edf2f7; padding-bottom: 10px;">
                <span style="background: #eff6ff; color: #1d4ed8; font-weight: 800; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.95rem; padding: 4px 10px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    #${deviceId}
                </span>
                <span style="background: #dbeafe; color: #1e40af; font-size: 0.8rem; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 5px;">
                    <i class="fa-solid fa-rotate-left" style="font-size: 0.75rem;"></i> Return to Stock
                </span>
            </div>

            <div style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin-bottom: 14px;">
                ${fullName}
            </div>

            <!-- Details Grid -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.88rem;">
                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid ${catIcon} me-1" style="color: #6366f1;"></i> Category
                    </span>
                    <strong style="color: #1e293b;">${categoryName}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-user me-1" style="color: #0ea5e9;"></i> Current Assignee
                    </span>
                    <strong style="color: #1e293b; font-size: 0.85rem;">${ownerDisplay}</strong>
                </div>

                <div style="background: #ffffff; padding: 8px 12px; border-radius: 10px; border: 1px solid #f1f5f9; grid-column: span 2;">
                    <span style="color: #64748b; font-size: 0.78rem; display: block; font-weight: 600; text-transform: uppercase; margin-bottom: 2px;">
                        <i class="fa-solid fa-barcode me-1" style="color: #f59e0b;"></i> Serial Number
                    </span>
                    <span style="font-family: ui-monospace, SFMono-Regular, monospace; font-weight: 700; color: #334155; font-size: 0.92rem;">
                        ${serialNumber}
                    </span>
                </div>
            </div>
        </div>

        <p style="margin: 0; color: #475569; font-size: 0.92rem; line-height: 1.5; text-align: center;">
            Are you sure you want to return this device from <strong>${ownerDisplay}</strong> back to available inventory stock?
        </p>
    </div>`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '📦 Return Device to Stock',
            html: modalHtml,
            width: 520,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Confirm Return',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            customClass: {
                popup: 'swal2-popup-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const url = new URL(window.location.href);
                url.searchParams.set('return', deviceId);
                if (actionNonce) url.searchParams.set('_wpnonce', actionNonce);
                url.searchParams.delete('view');
                url.searchParams.delete('edit');
                url.searchParams.delete('receive');
                url.searchParams.delete('maintenance');
                window.location.href = url.toString();
            }
        });
    } else {
        if (confirm(`Confirm return device ${fullName} (#${deviceId}) from ${ownerDisplay} to stock?`)) {
            const url = new URL(window.location.href);
            url.searchParams.set('return', deviceId);
            if (actionNonce) url.searchParams.set('_wpnonce', actionNonce);
            window.location.href = url.toString();
        }
    }
}
window.confirmReturnDevice = confirmReturnDevice;

function handleReturnDeviceClick(el) {
    if (!el) return;
    try {
        const raw = el.getAttribute('data-device');
        const data = JSON.parse(raw);
        if (typeof confirmReturnDevice === 'function') {
            confirmReturnDevice(data, data.nonce || '');
        } else {
            console.error('confirmReturnDevice is not defined');
            if (confirm('Confirm return device ' + (data.id || '') + ' to stock?')) {
                const url = new URL(window.location.href);
                url.searchParams.set('return', data.id);
                if (data.nonce) url.searchParams.set('_wpnonce', data.nonce);
                window.location.href = url.toString();
            }
        }
    } catch (err) {
        console.error('Error parsing device data for return:', err);
    }
}
window.handleReturnDeviceClick = handleReturnDeviceClick;
