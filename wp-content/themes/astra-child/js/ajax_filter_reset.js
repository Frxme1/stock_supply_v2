/**
 * Universal AJAX Filter, Reset & Pagination System (No Page Reload)
 * Includes iOS Spinner Loading Overlay & Mobile Top Progress Bar
 * Stock Supply Theme
 */

// 1. Inject iOS Spinner, Overlay & Mobile Top Bar Styles
if (!document.getElementById('ajax-filter-reset-styles')) {
    const style = document.createElement('style');
    style.id = 'ajax-filter-reset-styles';
    style.textContent = `
        /* Mobile & Desktop Universal Top Progress Bar (Apple / YouTube Style) */
        #ajax-top-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3.5px;
            width: 0%;
            background: linear-gradient(90deg, #2563eb, #38bdf8, #0ea5e9, #60a5fa);
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.8), 0 0 6px rgba(37, 99, 235, 0.6);
            z-index: 99999999;
            pointer-events: none;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
            border-radius: 0 3px 3px 0;
        }

        #ajax-top-progress-bar.running {
            width: 78%;
            transition: width 0.85s cubic-bezier(0.1, 0.5, 0.1, 1);
        }

        #ajax-top-progress-bar.finished {
            width: 100%;
            transition: width 0.15s ease-out;
        }

        #ajax-top-progress-bar.fade-out {
            opacity: 0;
            transition: opacity 0.25s ease-out;
        }

        /* Table / Mobile List Container Overlay Base */
        .table-loading-container {
            position: relative !important;
        }

        .mobile-only-container.table-loading-container {
            min-height: 220px !important;
        }

        .table-loading-overlay-blur {
            opacity: 0.4 !important;
            filter: blur(1px) !important;
            pointer-events: none !important;
            transition: opacity 0.22s ease, filter 0.22s ease !important;
        }

        /* iOS Spinner Overlay Card */
        .ios-spinner-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            border-radius: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.22s ease-in-out;
        }

        .ios-spinner-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .ios-spinner-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 14px 24px;
            border-radius: 16px;
            box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.18), 0 4px 12px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            transform: scale(0.95);
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ios-spinner-overlay.show .ios-spinner-card {
            transform: scale(1);
        }

        .ios-spinner-text {
            font-size: 0.88rem;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: -0.2px;
        }

        /* iOS Spinner 12-Blade Icon Structure */
        .ios-spinner {
            position: relative;
            display: inline-block;
            width: 24px;
            height: 24px;
        }

        .ios-spinner.sm { width: 14px; height: 14px; }
        .ios-spinner.md { width: 18px; height: 18px; }
        .ios-spinner.lg { width: 24px; height: 24px; }

        .spinner-blade {
            position: absolute;
            height: 20%;
            width: 8%;
            background-color: #0f172a;
            border-radius: 1px;
            animation: spinner-blade 1s linear infinite;
            will-change: opacity;
            top: 37%;
            left: 44%;
        }

        .spinner-blade:nth-child(1) { transform: rotate(0deg) translateY(-130%); animation-delay: -1.667s; }
        .spinner-blade:nth-child(2) { transform: rotate(30deg) translateY(-130%); animation-delay: -1.583s; }
        .spinner-blade:nth-child(3) { transform: rotate(60deg) translateY(-130%); animation-delay: -1.5s; }
        .spinner-blade:nth-child(4) { transform: rotate(90deg) translateY(-130%); animation-delay: -1.417s; }
        .spinner-blade:nth-child(5) { transform: rotate(120deg) translateY(-130%); animation-delay: -1.333s; }
        .spinner-blade:nth-child(6) { transform: rotate(150deg) translateY(-130%); animation-delay: -1.25s; }
        .spinner-blade:nth-child(7) { transform: rotate(180deg) translateY(-130%); animation-delay: -1.167s; }
        .spinner-blade:nth-child(8) { transform: rotate(210deg) translateY(-130%); animation-delay: -1.083s; }
        .spinner-blade:nth-child(9) { transform: rotate(240deg) translateY(-130%); animation-delay: -1s; }
        .spinner-blade:nth-child(10) { transform: rotate(270deg) translateY(-130%); animation-delay: -0.917s; }
        .spinner-blade:nth-child(11) { transform: rotate(300deg) translateY(-130%); animation-delay: -0.833s; }
        .spinner-blade:nth-child(12) { transform: rotate(330deg) translateY(-130%); animation-delay: -0.75s; }

        @keyframes spinner-blade {
            0% { opacity: 0.9; }
            50% { opacity: 0.25; }
            100% { opacity: 0.25; }
        }

        /* Button Loading state */
        .btn-filter-loading {
            pointer-events: none !important;
            opacity: 0.8 !important;
            position: relative !important;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Top Progress Bar Controller
 */
function startTopProgressBar() {
    let bar = document.getElementById('ajax-top-progress-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'ajax-top-progress-bar';
        document.body.appendChild(bar);
    }
    bar.className = '';
    bar.style.width = '18%';
    bar.style.opacity = '1';
    void bar.offsetWidth; // Force reflow
    bar.classList.add('running');
}

function finishTopProgressBar() {
    const bar = document.getElementById('ajax-top-progress-bar');
    if (!bar) return;
    bar.classList.remove('running');
    bar.classList.add('finished');
    setTimeout(() => {
        bar.classList.add('fade-out');
        setTimeout(() => {
            bar.style.width = '0%';
            bar.className = '';
        }, 300);
    }, 200);
}

/**
 * Button Loading state switcher
 */
function setFilterButtonsLoading(isLoading) {
    const btns = document.querySelectorAll('.btn-filter-modern, .btn-filter-cyan, #mobileBottomSheet button[type="submit"], #advanced-filter-form button[type="submit"], .ajax-filter-form button[type="submit"]');
    btns.forEach(btn => {
        if (isLoading) {
            if (!btn.getAttribute('data-original-html')) {
                btn.setAttribute('data-original-html', btn.innerHTML);
            }
            btn.classList.add('btn-filter-loading');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Filtering...';
        } else {
            btn.classList.remove('btn-filter-loading');
            const orig = btn.getAttribute('data-original-html');
            if (orig) {
                btn.innerHTML = orig;
                btn.removeAttribute('data-original-html');
            }
        }
    });
}

/**
 * Get target list/table containers across ALL pages (Handles both Desktop & Mobile Views)
 */
function getTargetContainers(docObj) {
    const root = docObj || document;
    const isMobile = window.innerWidth <= 768;
    const containers = [];

    // 1. Mobile specific card containers
    if (isMobile) {
        const mobileCons = root.querySelectorAll('.mobile-only-container');
        if (mobileCons.length > 0) {
            mobileCons.forEach(c => containers.push(c));
        }
    }

    // 2. Desktop & standard table wrappers
    if (containers.length === 0) {
        const desktopWrapper = root.querySelector('.table-wrapper') ||
                               root.querySelector('.table-custom') ||
                               root.querySelector('.table-wrapper-employee') ||
                               root.querySelector('.active-maintenance-panel') ||
                               root.querySelector('.dtl-history-timeline-wrapper') ||
                               root.querySelector('#bulk-action-form') ||
                               root.querySelector('#bulk-action-form-monitor') ||
                               root.querySelector('.card-body');
        if (desktopWrapper) {
            containers.push(desktopWrapper);
        }
    }

    // Fallback if still empty
    if (containers.length === 0) {
        const anyMobile = root.querySelector('.mobile-only-container');
        if (anyMobile) containers.push(anyMobile);
    }

    return containers;
}

/**
 * Show iOS Spinner Overlay inside target containers
 */
function showIosSpinner(containers) {
    const list = Array.isArray(containers) ? containers : [containers];
    list.filter(Boolean).forEach(container => {
        container.classList.add('table-loading-container', 'table-loading-overlay-blur');
        
        let overlay = container.querySelector('.ios-spinner-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'ios-spinner-overlay';
            overlay.innerHTML = `
                <div class="ios-spinner-card">
                    <div class="ios-spinner lg">
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                        <div class="spinner-blade"></div>
                    </div>
                    <span class="ios-spinner-text">Loading data...</span>
                </div>
            `;
            container.appendChild(overlay);
        }
        
        // Force reflow
        void overlay.offsetWidth;
        overlay.classList.add('show');
    });
}

/**
 * Hide iOS Spinner Overlay
 */
function hideIosSpinner(containers) {
    const list = Array.isArray(containers) ? containers : [containers];
    list.filter(Boolean).forEach(container => {
        container.classList.remove('table-loading-overlay-blur');
        const overlay = container.querySelector('.ios-spinner-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                if (overlay && overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 250);
        }
    });
}

/**
 * Clean URL helper: Removes hashes (#) and handles relative/hash-only URLs
 */
function getCleanUrl(rawUrl) {
    if (!rawUrl || rawUrl === '#' || rawUrl.startsWith('#')) {
        return window.location.pathname;
    }
    try {
        const base = window.location.origin + window.location.pathname;
        const urlObj = new URL(rawUrl, base);
        urlObj.hash = '';

        let search = urlObj.search.replace(/^\?&/, '?').replace(/&&+/g, '&');
        return urlObj.pathname + search;
    } catch (e) {
        return rawUrl.replace(/#.*$/, '');
    }
}

/**
 * Save Filter State in sessionStorage
 */
function saveFilterState(targetUrl) {
    const pagePath = window.location.pathname;
    try {
        const urlObj = new URL(targetUrl, window.location.origin);
        if (urlObj.search && urlObj.search !== '?') {
            sessionStorage.setItem('filterState_' + pagePath, urlObj.search);
        } else {
            sessionStorage.removeItem('filterState_' + pagePath);
        }
    } catch (e) {
        // ignore
    }
}

/**
 * Clear all filter inputs inside a form & remove stored filter state
 */
function clearFormInputs(form) {
    const pagePath = window.location.pathname;
    sessionStorage.removeItem('filterState_' + pagePath);

    const targetForm = form || document.querySelector('#advanced-filter-form') || document.querySelector('.ajax-filter-form') || document.querySelector('form[method="GET"]') || document.querySelector('form');
    if (!targetForm) return;

    // 1. Reset all <select> dropdowns to default empty option & trigger change event
    const selectElements = targetForm.querySelectorAll('select');
    selectElements.forEach(select => {
        select.value = '';
        select.selectedIndex = 0;
        Array.from(select.options).forEach((opt, idx) => {
            opt.selected = (idx === 0 || opt.value === '');
        });
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // 2. Clear text and search inputs
    const textInputs = targetForm.querySelectorAll('input[type="text"], input[type="search"], input[name="device_search"]');
    textInputs.forEach(input => {
        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    // Close any autocomplete dropdowns
    document.querySelectorAll('.custom-autocomplete-card').forEach(c => {
        c.style.display = 'none';
        c.innerHTML = '';
    });

    // 3. Handle custom department wrapper toggle if function exists
    if (typeof window.toggleDepartment === 'function') {
        window.toggleDepartment();
    }
}

/**
 * Safely identify Reset Buttons across ALL pages (Handles text nodes, SVGs, buttons, etc.)
 */
function isResetButton(element) {
    if (!element) return false;

    // Handle Text Nodes safely
    var el = (element.nodeType === 3) ? element.parentElement : element;
    if (!el || typeof el.closest !== 'function') return false;

    var btn = el.closest('a, button, input[type="button"], input[type="reset"]');
    if (!btn) return false;

    // 1. Check classes on element
    var classListStr = Array.from(btn.classList || []).join(' ').toLowerCase();
    if (classListStr.includes('reset') || classListStr.includes('btn-reset-modern') || classListStr.includes('btn-reset-underline')) {
        return true;
    }

    // 2. Check text content (removing special chars and spaces)
    var rawText = (btn.textContent || '').replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    if (rawText === 'reset' || rawText.includes('reset')) {
        return true;
    }

    // 3. Check href parameter or id
    var href = btn.getAttribute('href') || '';
    var btnId = (btn.id || '').toLowerCase();
    if (btnId.includes('reset') || href.includes('remove_query_arg') || href.includes('filter_') || href.includes('device_search')) {
        return true;
    }

    return false;
}

/**
 * Core AJAX Content Loader with iOS Spinner, Mobile Progress Bar & Button States
 */
async function loadAjaxContent(targetUrl, formToClear = null) {
    const cleanUrl = getCleanUrl(targetUrl);
    const containers = getTargetContainers(document);

    // 1. Start Top Progress Bar
    startTopProgressBar();

    // 2. Show iOS Spinner overlay on visible desktop/mobile containers
    if (containers.length > 0) {
        showIosSpinner(containers);
    }

    // 3. Set Filter Buttons to loading state
    setFilterButtonsLoading(true);

    if (formToClear) {
        clearFormInputs(formToClear);
    } else {
        // Sync form controls with cleanUrl parameters
        try {
            const urlObj = new URL(cleanUrl, window.location.origin);
            const filterForm = document.querySelector('#advanced-filter-form') || document.querySelector('.ajax-filter-form') || document.querySelector('form[method="GET"]');
            if (filterForm) {
                const params = urlObj.searchParams;
                filterForm.querySelectorAll('select').forEach(sel => {
                    const val = params.get(sel.name) || '';
                    sel.value = val;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                });
                filterForm.querySelectorAll('input[type="text"], input[name="device_search"]').forEach(inp => {
                    const val = params.get(inp.name) || '';
                    inp.value = val;
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                });
                if (typeof window.toggleDepartment === 'function') {
                    window.toggleDepartment();
                }
            }
        } catch (e) {
            // ignore url parse error
        }
    }

    try {
        const response = await fetch(cleanUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            // 0. Replace View Details Container if present
            const newViewDetails = doc.querySelector('.view-details-container');
            const currentViewDetails = document.querySelector('.view-details-container');
            if (newViewDetails && currentViewDetails) {
                currentViewDetails.innerHTML = newViewDetails.innerHTML;
            } else if (newViewDetails && !currentViewDetails) {
                const targetArea = document.querySelector('#content.site-content') || document.querySelector('.entry-content') || document.querySelector('#content') || document.body;
                if (targetArea) {
                    targetArea.innerHTML = newViewDetails.outerHTML;
                }
            }

            // 0.5 Replace Dashboard Summary Cards (.next-dashboard)
            const newDash = doc.querySelector('.next-dashboard');
            const curDash = document.querySelector('.next-dashboard');
            if (newDash && curDash) {
                curDash.innerHTML = newDash.innerHTML;
                if (typeof window.initNextDashboardShared === 'function') {
                    window.initNextDashboardShared();
                }
                if (typeof window.initEmpDashboard === 'function') {
                    window.initEmpDashboard();
                }
                if (typeof window.initNextDashboard === 'function') {
                    window.initNextDashboard();
                }
            }

            // 1. Replace Desktop Table Container Content
            const newDesktop = doc.querySelector('.table-wrapper') || doc.querySelector('.table-custom') || doc.querySelector('.table-wrapper-employee');
            const currentDesktop = document.querySelector('.table-wrapper') || document.querySelector('.table-custom') || document.querySelector('.table-wrapper-employee');
            if (newDesktop && currentDesktop) {
                currentDesktop.innerHTML = newDesktop.innerHTML;
            }

            // 1.5 Replace All Mobile Cards Content (.mobile-only-container)
            const newMobiles = doc.querySelectorAll('.mobile-only-container');
            const currentMobiles = document.querySelectorAll('.mobile-only-container');
            if (newMobiles.length > 0 && currentMobiles.length > 0) {
                currentMobiles.forEach((cur, idx) => {
                    if (newMobiles[idx]) {
                        cur.innerHTML = newMobiles[idx].innerHTML;
                    }
                });
            } else if (newMobiles.length > 0 && currentMobiles.length === 0) {
                const targetArea = document.querySelector('#content.site-content') || document.querySelector('.entry-content') || document.querySelector('#content') || document.body;
                if (targetArea) {
                    newMobiles.forEach(nm => {
                        const clone = nm.cloneNode(true);
                        targetArea.appendChild(clone);
                    });
                }
            }

            // 1.6 Replace other specialized panels (Maintenance, History, etc.)
            const newMaint = doc.querySelector('.active-maintenance-panel');
            const curMaint = document.querySelector('.active-maintenance-panel');
            if (newMaint && curMaint) curMaint.innerHTML = newMaint.innerHTML;

            const newTimeline = doc.querySelector('.dtl-history-timeline-wrapper');
            const curTimeline = document.querySelector('.dtl-history-timeline-wrapper');
            if (newTimeline && curTimeline) curTimeline.innerHTML = newTimeline.innerHTML;

            if (typeof window.initMobileLoadMore === 'function') {
                window.initMobileLoadMore();
            }

            // Make sure timeline nodes are revealed if present
            document.querySelectorAll('.dtl-node').forEach(n => n.classList.add('dtl-visible'));

            // 2. Replace Pagination Container
            const newPagination = doc.querySelector('.pagination');
            const currentPagination = document.querySelector('.pagination');
            if (currentPagination) {
                if (newPagination) {
                    currentPagination.innerHTML = newPagination.innerHTML;
                    currentPagination.style.display = '';
                } else {
                    currentPagination.innerHTML = '';
                    currentPagination.style.display = 'none';
                }
            }

            // 3. Update Browser History State silently (no reload)
            window.history.pushState({}, '', cleanUrl);

            // 4. Save filter state to sessionStorage
            saveFilterState(cleanUrl);

            // 5. Close Mobile Bottom Sheet if open
            if (typeof closeBottomSheet === 'function') {
                closeBottomSheet();
            } else {
                const sheet = document.getElementById('mobileBottomSheet');
                const backdrop = document.getElementById('bottomSheetBackdrop');
                if (sheet) sheet.classList.remove('open');
                if (backdrop) backdrop.classList.remove('open');
            }

            // 6. Close any open search autocomplete dropdowns
            document.querySelectorAll('.custom-autocomplete-card').forEach(c => {
                c.style.display = 'none';
            });
        }

    } catch (err) {
        console.error('AJAX Load Warning:', err);
    } finally {
        finishTopProgressBar();
        if (containers.length > 0) {
            hideIosSpinner(containers);
        }
        setFilterButtonsLoading(false);
    }
}

// Expose loadAjaxContent & clearFormInputs globally
window.loadAjaxContent = loadAjaxContent;
window.clearFormInputs = clearFormInputs;

/**
 * Restore Filter State from sessionStorage if returning to page
 */
function restoreFilterState() {
    const pagePath = window.location.pathname;
    const currentSearch = window.location.search;

    if (currentSearch.includes('view=') || currentSearch.includes('edit=') || currentSearch.includes('delete=')) {
        return;
    }

    const isInternalBack = document.referrer && document.referrer.includes(window.location.host);

    if ((!currentSearch || currentSearch === '?') && isInternalBack) {
        const savedState = sessionStorage.getItem('filterState_' + pagePath);
        if (savedState) {
            const restoredUrl = pagePath + savedState;
            const params = new URLSearchParams(savedState);

            const form = document.querySelector('#advanced-filter-form') || document.querySelector('.ajax-filter-form') || document.querySelector('form[method="GET"]');
            if (form) {
                params.forEach((val, key) => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = val;
                        if (field.tagName === 'SELECT') {
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });
            }

            loadAjaxContent(restoredUrl);
        }
    } else if (currentSearch && currentSearch !== '?') {
        saveFilterState(window.location.href);
    }
}

document.addEventListener('DOMContentLoaded', function () {

    /**
     * Global Event Delegation: Click Handlers for Reset & Pagination
     */
    document.addEventListener('click', function (e) {
        // 1. Reset Button Click (Universal Detection across all pages)
        if (isResetButton(e.target)) {
            e.preventDefault();
            var el = (e.target.nodeType === 3) ? e.target.parentElement : e.target;
            var resetBtn = el.closest('a, button, input') || el;
            var rawHref = resetBtn.getAttribute('href') || window.location.pathname;
            var parentForm = resetBtn.closest('form') || document.querySelector('#advanced-filter-form') || document.querySelector('.ajax-filter-form') || document.querySelector('form[method="GET"]');
            
            clearFormInputs(parentForm);
            loadAjaxContent(rawHref, parentForm);
            return;
        }

        // 2. Pagination Link Click
        const pageLink = e.target.closest('.pagination .page-link');
        if (pageLink) {
            e.preventDefault();

            if (pageLink.closest('.disabled') || pageLink.closest('.active')) {
                return;
            }

            const pageHref = pageLink.getAttribute('href');
            if (pageHref && pageHref !== '#') {
                loadAjaxContent(pageHref);
            }
        }
    });

    /**
     * Global Event Delegation: Submit Filter Forms via AJAX
     */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Intercept any GET method filter form across all pages
        if (form.method && form.method.toUpperCase() === 'GET' && (form.querySelector('.btn-filter-modern') || form.querySelector('.btn-filter-cyan') || form.querySelector('.search-input-modern') || form.id === 'advanced-filter-form' || form.classList.contains('ajax-filter-form') || form.querySelector('select') || form.querySelector('input[name="device_search"]'))) {
            e.preventDefault();

            const formData = new FormData(form);
            const params = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value !== '' && key !== 'paged') {
                    params.append(key, value);
                }
            }

            const rawAction = form.getAttribute('action') || window.location.pathname;
            const cleanAction = getCleanUrl(rawAction);
            const targetUrl = params.toString() ? `${cleanAction}?${params.toString()}` : cleanAction;

            loadAjaxContent(targetUrl);
        }
    });

    /**
     * Support Browser Back / Forward Navigation
     */
    window.addEventListener('popstate', function () {
        loadAjaxContent(window.location.href);
    });

    /**
     * Auto-restore filter state on page load when returning
     */
    restoreFilterState();
});
