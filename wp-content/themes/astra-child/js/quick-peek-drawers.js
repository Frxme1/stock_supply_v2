/**
 * Universal Quick Peek & Slide-Over Drawers
 * Provides instant in-place inspection for Devices and Employees without page navigation.
 */
(function ($) {
    'use strict';

    const ajaxUrl = (typeof stockSupplyAjax !== 'undefined' && stockSupplyAjax.ajax_url) ? stockSupplyAjax.ajax_url : '/wp-admin/admin-ajax.php';

    function ensureDrawerDOM() {
        if ($('#globalQuickPeekDrawer').length) return;

        const drawerHtml = `
            <div id="globalQuickPeekBackdrop" class="gqp-backdrop" onclick="window.closeQuickPeekDrawer()"></div>
            <div id="globalQuickPeekDrawer" class="gqp-drawer" role="dialog" aria-modal="true">
                <div class="gqp-header">
                    <div class="gqp-header-title" id="gqpHeaderTitle">
                        <span class="gqp-header-tag"><i class="fa-solid fa-bolt"></i> Quick Peek</span>
                        <h4 class="gqp-title-text" id="gqpTitleText">Loading...</h4>
                    </div>
                    <button type="button" class="gqp-close-btn" onclick="window.closeQuickPeekDrawer()" title="Close (Esc)">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="gqp-body" id="gqpBody">
                    <!-- Dynamic Content Injected Here -->
                </div>
            </div>
        `;
        $('body').append(drawerHtml);

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $('#globalQuickPeekDrawer').hasClass('is-open')) {
                window.closeQuickPeekDrawer();
            }
        });
    }

    window.closeQuickPeekDrawer = function () {
        $('#globalQuickPeekDrawer').removeClass('is-open');
        $('#globalQuickPeekBackdrop').removeClass('is-open');
        $('body').removeClass('gqp-open');
    };

    function showLoadingDrawer(title) {
        ensureDrawerDOM();
        $('#gqpTitleText').text(title || 'Loading...');
        $('#gqpBody').html(`
            <div class="gqp-loading-state">
                <div class="gqp-spinner"></div>
                <p>Fetching real-time data...</p>
            </div>
        `);
        $('#globalQuickPeekDrawer').addClass('is-open');
        $('#globalQuickPeekBackdrop').addClass('is-open');
        $('body').addClass('gqp-open');
    }

    /**
     * Open Universal Device Quick Peek
     */
    window.openDeviceQuickPeek = function (deviceId) {
        if (!deviceId) return;
        showLoadingDrawer(`Device #${deviceId}`);

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
                action: 'get_quick_device_peek',
                device_id: deviceId
            },
            dataType: 'json',
            success: function (res) {
                if (!res.success || !res.data) {
                    $('#gqpBody').html(`
                        <div class="gqp-error-state">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <p>${res.data?.message || 'Device information not found.'}</p>
                        </div>
                    `);
                    return;
                }

                const d = res.data;
                $('#gqpTitleText').html(`${d.brand} ${d.model} <span class="gqp-id-pill">#${d.device_id}</span>`);

                let catIcon = 'fa-laptop';
                let catClass = 'cat-laptop';
                const cLower = d.category.toLowerCase();
                if (cLower.includes('monitor')) {
                    catIcon = 'fa-desktop';
                    catClass = 'cat-monitor';
                } else if (cLower.includes('access')) {
                    catIcon = 'fa-plug';
                    catClass = 'cat-accessories';
                }

                let statusClass = 'status-available';
                const sLower = d.status.toLowerCase();
                if (sLower.includes('use') || sLower.includes('borrow')) {
                    statusClass = 'status-inuse';
                } else if (sLower.includes('maint') || sLower.includes('repair')) {
                    statusClass = 'status-maintenance';
                }

                // Owner Section
                let ownerHtml = '';
                if (d.owner_id && d.owner_name && d.owner_name !== '-') {
                    ownerHtml = `
                        <div class="gqp-card gqp-owner-card">
                            <div class="gqp-card-title"><i class="fa-solid fa-user-check text-primary"></i> Current Assignee</div>
                            <div class="gqp-owner-info-row">
                                <div class="gqp-owner-avatar">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div class="gqp-owner-meta">
                                    <div class="gqp-owner-name">${d.owner_name}</div>
                                    <div class="gqp-owner-dept-pos">
                                        <span class="gqp-badge-sub">${d.department}</span>
                                        <span class="gqp-badge-sub pos-badge">${d.position}</span>
                                    </div>
                                    ${d.receive_date && d.receive_date !== '-' ? `<div class="gqp-owner-date"><i class="fa-regular fa-calendar-check me-1"></i> Assigned: ${d.receive_date}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    ownerHtml = `
                        <div class="gqp-card gqp-owner-card-empty">
                            <div class="gqp-card-title"><i class="fa-solid fa-box-archive text-success"></i> Stock Status</div>
                            <div class="gqp-empty-owner-text">
                                <i class="fa-solid fa-check-circle text-success me-1"></i> This item is available in storage and ready to be assigned.
                            </div>
                        </div>
                    `;
                }

                // Maintenance banner if applicable
                let maintHtml = '';
                if (d.maintenance) {
                    maintHtml = `
                        <div class="gqp-card gqp-maint-card">
                            <div class="gqp-card-title text-warning"><i class="fa-solid fa-wrench"></i> Active Repair Case</div>
                            <p class="gqp-maint-desc">${d.maintenance.details}</p>
                            <span class="gqp-maint-date"><i class="fa-regular fa-clock me-1"></i> Sent: ${d.maintenance.repair_date}</span>
                        </div>
                    `;
                }

                // Recent History Timeline
                let historyHtml = '';
                if (d.history && d.history.length > 0) {
                    historyHtml = `
                        <div class="gqp-card gqp-history-card">
                            <div class="gqp-card-title"><i class="fa-solid fa-clock-rotate-left text-muted"></i> Recent Activity</div>
                            <div class="gqp-mini-timeline">
                                ${d.history.map(h => `
                                    <div class="gqp-timeline-item">
                                        <div class="gqp-tl-dot"></div>
                                        <div class="gqp-tl-content">
                                            <div class="gqp-tl-top">
                                                <span class="gqp-tl-action">${h.action}</span>
                                                <span class="gqp-tl-time">${h.date}</span>
                                            </div>
                                            <div class="gqp-tl-desc">${h.desc}</div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                // Action Buttons
                let actionsHtml = `<div class="gqp-actions-footer">`;
                if (sLower.includes('use') || sLower.includes('borrow')) {
                    actionsHtml += `
                        <button type="button" class="gqp-btn gqp-btn-return"
                            data-device='${JSON.stringify({
                                id: d.device_id,
                                brand: d.brand,
                                model: d.model,
                                category: d.category,
                                serialNumber: d.serial_number,
                                owner: d.owner_name,
                                department: d.department,
                                nonce: d.nonces?.device_action_nonce || ''
                            })}'
                            onclick="if(typeof confirmReturnDevice === 'function') { confirmReturnDevice(JSON.parse(this.getAttribute('data-device')), '${d.nonces?.device_action_nonce || ''}'); }">
                            <i class="fa-solid fa-arrow-rotate-left"></i> Return to Stock
                        </button>
                        <a href="${d.urls.maintenance}" class="gqp-btn gqp-btn-repair"><i class="fa-solid fa-screwdriver-wrench"></i> Repair</a>
                    `;
                } else if (sLower.includes('maint')) {
                    actionsHtml += `
                        <a href="${d.urls.view}" class="gqp-btn gqp-btn-success"><i class="fa-solid fa-circle-check"></i> Manage Repair</a>
                    `;
                } else {
                    actionsHtml += `
                        <a href="${d.urls.receive}" class="gqp-btn gqp-btn-primary"><i class="fa-solid fa-user-plus"></i> Assign Device</a>
                        <a href="${d.urls.maintenance}" class="gqp-btn gqp-btn-repair"><i class="fa-solid fa-screwdriver-wrench"></i> Repair</a>
                    `;
                }
                actionsHtml += `
                    <a href="${d.urls.edit}" class="gqp-btn gqp-btn-outline"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                    <a href="${d.urls.view}" class="gqp-btn gqp-btn-dark"><i class="fa-solid fa-arrow-up-right-from-square"></i> Full Details</a>
                </div>`;

                const bodyHtml = `
                    <div class="gqp-device-hero ${catClass}">
                        <div class="gqp-hero-top">
                            <div class="gqp-hero-icon"><i class="fa-solid ${catIcon}"></i></div>
                            <div class="gqp-hero-status">
                                <span class="status-badge ${statusClass}">
                                    <span class="status-dot"></span> ${d.status}
                                </span>
                            </div>
                        </div>
                        <h3 class="gqp-hero-title">${d.brand} ${d.model}</h3>
                        <div class="gqp-hero-meta-grid">
                            <div class="gqp-meta-pill"><span>Category:</span> <strong>${d.category}</strong></div>
                            <div class="gqp-meta-pill"><span>SN:</span> <strong class="font-mono">${d.serial_number}</strong></div>
                            ${d.specs && d.specs !== '-' ? `<div class="gqp-meta-pill full-w"><span>Specs:</span> <strong>${d.specs}</strong></div>` : ''}
                        </div>
                    </div>

                    ${ownerHtml}
                    ${maintHtml}
                    ${historyHtml}
                    ${actionsHtml}
                `;

                $('#gqpBody').html(bodyHtml);
            },
            error: function () {
                $('#gqpBody').html(`
                    <div class="gqp-error-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <p>Unable to connect to server. Please try again.</p>
                    </div>
                `);
            }
        });
    };

    /**
     * Open Instant Employee Equipment Drawer
     */
    window.openEmployeeQuickDrawer = function (ownerId) {
        if (!ownerId) return;
        showLoadingDrawer('Employee Hardware Profile');

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
                action: 'get_quick_employee_peek',
                owner_id: ownerId
            },
            dataType: 'json',
            success: function (res) {
                if (!res.success || !res.data) {
                    $('#gqpBody').html(`
                        <div class="gqp-error-state">
                            <i class="fa-solid fa-user-slash"></i>
                            <p>${res.data?.message || 'Employee record not found.'}</p>
                        </div>
                    `);
                    return;
                }

                const emp = res.data;
                $('#gqpTitleText').html(`${emp.display_name} <span class="gqp-id-pill">#EMP-${emp.owner_id}</span>`);

                const isIntern = emp.position && emp.position.toLowerCase().includes('intern');
                const posClass = isIntern ? 'position-intern' : 'position-fulltime';

                // Devices Breakdown
                let devicesHtml = '';
                if (emp.devices && emp.devices.length > 0) {
                    devicesHtml = `
                        <div class="gqp-card gqp-emp-devices-card">
                            <div class="gqp-card-title d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-boxes-stacked text-primary"></i> Assigned Hardware (${emp.device_count})</span>
                                <span class="badge bg-primary rounded-pill px-2 py-1">${emp.device_count} Units</span>
                            </div>
                            <div class="gqp-emp-dev-list">
                                ${emp.devices.map(d => {
                                    const cLower = (d.category || '').toLowerCase();
                                    let catIcon = 'fa-laptop';
                                    let catClass = 'cat-laptop';
                                    if (cLower.includes('monitor')) {
                                        catIcon = 'fa-desktop';
                                        catClass = 'cat-monitor';
                                    } else if (cLower.includes('access')) {
                                        catIcon = 'fa-plug';
                                        catClass = 'cat-accessories';
                                    }

                                    return `
                                        <div class="gqp-emp-dev-item">
                                            <div class="gqp-emp-dev-left">
                                                <div class="gqp-emp-dev-icon ${catClass}">
                                                    <i class="fa-solid ${catIcon}"></i>
                                                </div>
                                                <div class="gqp-emp-dev-text">
                                                    <div class="gqp-emp-dev-id-model">
                                                        <span class="gqp-emp-dev-id font-mono">${d.id}</span>
                                                        <span class="gqp-emp-dev-model">${d.brand} ${d.model}</span>
                                                    </div>
                                                    <div class="gqp-emp-dev-sub">
                                                        <span class="gqp-emp-dev-sn">SN: ${d.serial_number}</span>
                                                        ${d.receive_date && d.receive_date !== '-' ? `<span class="gqp-emp-dev-date">&bull; Assigned: ${d.receive_date}</span>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="gqp-emp-dev-actions">
                                                <button type="button" class="gqp-btn-mini gqp-btn-mini-return"
                                                    data-device='${JSON.stringify(d.return_data)}'
                                                    onclick="if(typeof confirmReturnDevice === 'function') { confirmReturnDevice(JSON.parse(this.getAttribute('data-device')), '${d.return_data.nonce}'); }"
                                                    title="Return device to stock">
                                                    <i class="fa-solid fa-arrow-rotate-left"></i> Return
                                                </button>
                                                <button type="button" class="gqp-btn-mini gqp-btn-mini-peek"
                                                    onclick="window.openDeviceQuickPeek('${d.id}')"
                                                    title="Quick peek device details">
                                                    <i class="fa-solid fa-magnifying-glass"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    `;
                } else {
                    devicesHtml = `
                        <div class="gqp-card gqp-owner-card-empty">
                            <div class="gqp-card-title"><i class="fa-solid fa-box-archive text-muted"></i> Assigned Hardware (0)</div>
                            <div class="gqp-empty-owner-text">
                                <i class="fa-solid fa-circle-info text-muted me-1"></i> No equipment is currently assigned to this employee.
                            </div>
                        </div>
                    `;
                }

                const actionsHtml = `
                    <div class="gqp-actions-footer">
                        <a href="${emp.edit_url}" class="gqp-btn gqp-btn-outline"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
                        <a href="${emp.view_url}" class="gqp-btn gqp-btn-dark"><i class="fa-solid fa-id-card"></i> Full Profile Page</a>
                    </div>
                `;

                const bodyHtml = `
                    <div class="gqp-emp-hero">
                        <div class="gqp-emp-avatar-wrap">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="gqp-emp-hero-info">
                            <h3 class="gqp-emp-hero-name">${emp.display_name}</h3>
                            <div class="gqp-emp-hero-email">${emp.email}</div>
                            <div class="gqp-emp-hero-badges">
                                <span class="gqp-badge-sub">${emp.department}</span>
                                <span class="position-badge ${posClass}">${emp.position}</span>
                            </div>
                        </div>
                    </div>

                    ${devicesHtml}
                    ${actionsHtml}
                `;

                $('#gqpBody').html(bodyHtml);
            },
            error: function () {
                $('#gqpBody').html(`
                    <div class="gqp-error-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <p>Unable to connect to server. Please try again.</p>
                    </div>
                `);
            }
        });
    };

    $(document).ready(function () {
        ensureDrawerDOM();
    });

})(jQuery);
