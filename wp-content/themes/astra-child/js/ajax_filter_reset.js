/**
 * AJAX Filter, Reset & Pagination System (No-Reload) + State Persistence
 * Stock Supply Theme
 */

document.addEventListener('DOMContentLoaded', function () {

    // Inject smooth transition CSS
    if (!document.getElementById('ajax-filter-reset-styles')) {
        const style = document.createElement('style');
        style.id = 'ajax-filter-reset-styles';
        style.textContent = `
            .table-loading-overlay {
                opacity: 0.35 !important;
                pointer-events: none !important;
                transition: opacity 0.2s ease !important;
            }
        `;
        document.head.appendChild(style);
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

        // 1. Reset all <select> dropdowns to default empty option & trigger change
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
     * Core AJAX Content Loader
     */
    async function loadAjaxContent(targetUrl, formToClear = null) {
        const cleanUrl = getCleanUrl(targetUrl);
        const tableContainer = document.querySelector('.table-wrapper') || document.querySelector('.table-custom');

        if (tableContainer) {
            tableContainer.classList.add('table-loading-overlay');
        }

        if (formToClear) {
            clearFormInputs(formToClear);
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

                // 1. Replace Table Content (.table-wrapper)
                const newTable = doc.querySelector('.table-wrapper') || doc.querySelector('.table-custom');
                const currentTable = document.querySelector('.table-wrapper') || document.querySelector('.table-custom');

                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }

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
            }

        } catch (err) {
            console.error('AJAX Load Warning:', err);
        } finally {
            if (tableContainer) {
                tableContainer.classList.remove('table-loading-overlay');
            }
        }
    }

    /**
     * Restore Filter State from sessionStorage if returning to page
     */
    function restoreFilterState() {
        const pagePath = window.location.pathname;
        const currentSearch = window.location.search;

        // Skip auto-restore if action parameters like view, edit, delete exist
        if (currentSearch.includes('view=') || currentSearch.includes('edit=') || currentSearch.includes('delete=')) {
            return;
        }

        // Only restore state if referrer indicates navigation from within the same domain (e.g. Back button from Details view)
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

    /**
     * Global Event Delegation: Click Handlers
     */
    document.addEventListener('click', function (e) {
        // 1. Reset Button Click
        const resetBtn = e.target.closest('.btn-reset-modern');
        if (resetBtn) {
            e.preventDefault();
            const rawHref = resetBtn.getAttribute('href');
            const parentForm = resetBtn.closest('form') || document.querySelector('#advanced-filter-form') || document.querySelector('form[method="GET"]');
            clearFormInputs(parentForm);
            loadAjaxContent(rawHref, parentForm);
            return;
        }

        // 2. Pagination Link Click
        const pageLink = e.target.closest('.pagination .page-link');
        if (pageLink) {
            // ALWAYS prevent default browser navigation to avoid empty dashboard redirects
            e.preventDefault();

            // Ignore disabled or active page links
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
     * Global Event Delegation: Submit Filter Forms
     */
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.method && form.method.toUpperCase() === 'GET' && (form.querySelector('.btn-filter-modern') || form.querySelector('.search-input-modern') || form.id === 'advanced-filter-form')) {
            e.preventDefault();

            const formData = new FormData(form);
            const params = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value !== '' && key !== 'paged') {
                    params.append(key, value);
                }
            }

            const rawAction = form.getAttribute('action');
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
     * Mutual Exclusion for Search & Dropdown Filters (Prevent 0-result conflicts)
     */
    document.addEventListener('change', function (e) {
        if (e.target && e.target.tagName === 'SELECT' && e.target.value !== '') {
            const form = e.target.closest('form');
            if (form) {
                const searchInput = form.querySelector('.search-input-modern, input[name="device_search"]');
                if (searchInput) {
                    searchInput.value = '';
                }
            }
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target && (e.target.classList.contains('search-input-modern') || e.target.name === 'device_search')) {
            const form = e.target.closest('form');
            if (form && e.target.value.trim() !== '') {
                const selects = form.querySelectorAll('select');
                selects.forEach(select => {
                    select.selectedIndex = 0;
                });
                if (typeof window.toggleDepartment === 'function') {
                    window.toggleDepartment();
                }
            }
        }
    });

    // Auto-restore filter state on page load when returning
    restoreFilterState();
});
