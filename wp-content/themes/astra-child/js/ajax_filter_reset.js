/**
 * Universal AJAX Filter, Reset & Pagination System (No Page Reload)
 * Includes iOS Spinner Loading Overlay
 * Stock Supply Theme
 */

// 1. Inject iOS Spinner & Overlay Styles
if (!document.getElementById('ajax-filter-reset-styles')) {
    const style = document.createElement('style');
    style.id = 'ajax-filter-reset-styles';
    style.textContent = `
        /* Table / List Container Overlay Base */
        .table-loading-container {
            position: relative !important;
        }

        .table-loading-overlay-blur {
            opacity: 0.35 !important;
            pointer-events: none !important;
            transition: opacity 0.22s ease !important;
        }

        /* iOS Spinner Overlay Card */
        .ios-spinner-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
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
            box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
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
            color: #334155;
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
            background-color: #334155;
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
            0% { opacity: 0.85; }
            50% { opacity: 0.25; }
            100% { opacity: 0.25; }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Get target list/table container across ALL pages (Never picks entire page wrapper)
 */
function getTargetContainer(docObj) {
    const root = docObj || document;
    return root.querySelector('.table-wrapper') ||
           root.querySelector('.table-custom') ||
           root.querySelector('.table-wrapper-employee') ||
           root.querySelector('.active-maintenance-panel') ||
           root.querySelector('.dtl-history-timeline-wrapper') ||
           root.querySelector('.mobile-only-container') ||
           root.querySelector('#bulk-action-form') ||
           root.querySelector('#bulk-action-form-monitor') ||
           root.querySelector('.card-body');
}

/**
 * Show iOS Spinner Overlay inside target container
 */
function showIosSpinner(container) {
    if (!container) return;
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
                <span class="ios-spinner-text">Loading...</span>
            </div>
        `;
        container.appendChild(overlay);
    }
    
    // Force reflow
    void overlay.offsetWidth;
    overlay.classList.add('show');
}

/**
 * Hide iOS Spinner Overlay
 */
function hideIosSpinner(container) {
    if (!container) return;
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
    if (btn.classList) {
        var classListStr = Array.from(btn.classList).join(' ').toLowerCase();
        if (classListStr.includes('reset')) {
            return true;
        }
    }

    // 2. Check text content (removing special chars and spaces)
    var rawText = (btn.textContent || '').replace(/[^a-zA-Z0-9\u0E00-\u0E7F]/g, '').toLowerCase();
    if (rawText === 'reset' || rawText === 'รีเซ็ต') {
        return true;
    }

    // 3. Check href parameter or onclick
    var href = btn.getAttribute('href') || '';
    if (href.includes('remove_query_arg') || href.includes('filter_') || href.includes('device_search')) {
        return true;
    }

    return false;
}

/**
 * Core AJAX Content Loader with iOS Spinner
 */
async function loadAjaxContent(targetUrl, formToClear = null) {
    const cleanUrl = getCleanUrl(targetUrl);
    const tableContainer = getTargetContainer(document);

    if (tableContainer) {
        showIosSpinner(tableContainer);
    }

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

            // 1. Replace Table / List Container Content
            const newContainer = getTargetContainer(doc);
            const currentContainer = getTargetContainer(document);

            if (newContainer && currentContainer) {
                currentContainer.innerHTML = newContainer.innerHTML;
            }

            // 1.5 Replace Mobile Cards Content (.mobile-only-container) if present separately
            const newMobiles = doc.querySelectorAll('.mobile-only-container');
            const currentMobiles = document.querySelectorAll('.mobile-only-container');
            if (newMobiles.length > 0 && currentMobiles.length === newMobiles.length) {
                for (let i = 0; i < newMobiles.length; i++) {
                    currentMobiles[i].innerHTML = newMobiles[i].innerHTML;
                }
            }

            if (typeof window.initMobileLoadMore === 'function') {
                window.initMobileLoadMore();
            }

            // Make sure timeline nodes are revealed if present
            document.querySelectorAll('.dtl-node').forEach(n => n.classList.add('dtl-visible'));

            // 2. Replace Pagination Container
            const newPagination = doc.querySelector('.pagination')?.closest('div, ul') || doc.querySelector('.pagination');
            const currentPagination = document.querySelector('.pagination')?.closest('div, ul') || document.querySelector('.pagination');

            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
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
        }

    } catch (err) {
        console.error('AJAX Load Warning:', err);
    } finally {
        if (tableContainer) {
            hideIosSpinner(tableContainer);
        }
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
