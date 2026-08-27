<style>
/* =========================================
   Mobile-First Web App Styles (PWA - High Touch Target & Contrast)
   ========================================= */

body, #content.site-content {
    -webkit-tap-highlight-color: transparent; 
    padding-bottom: 120px !important; /* Prevent floating bottom nav overlap */
}

/* Fix Beaver Builder offset if it exists */
.fl-col-content:has(.mobile-only-container) {
    margin-left: 0 !important;
    width: 100% !important;
}

/* --- Mobile Card Container --- */
.mobile-only-container {
    position: relative;
    z-index: 10;
}

/* --- Mobile Card View for Devices --- */
.mobile-device-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 22px;
    margin-bottom: 18px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
    border: 1.5px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 18px;
    position: relative;
    z-index: 10;
}

.mobile-device-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: flex-start; 
    gap: 12px;
}
.mobile-device-title-area { flex-grow: 1; }
.mobile-device-title { 
    font-size: 1.25rem; 
    font-weight: 800; 
    color: #0f172a; 
    margin-bottom: 4px;
    line-height: 1.3;
}
.mobile-device-meta { 
    font-size: 0.95rem; 
    color: #475569; 
    font-weight: 500;
}

.mobile-device-body { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    padding-top: 14px; 
    border-top: 1.5px dashed #e2e8f0; 
}
.mobile-device-owner { 
    font-size: 1.05rem; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    font-weight: 600; 
    color: #334155;
}
.mobile-device-owner i { 
    color: #64748b; 
    font-size: 1.25rem; 
}
.mobile-device-id {
    font-size: 1.05rem;
    color: #0f172a;
}

.mobile-device-actions { 
    display: flex; 
    gap: 12px; 
    margin-top: 6px; 
}

/* Big Action Buttons (Touch Target 52px+) */
.mobile-btn-action {
    flex: 1; 
    min-height: 52px;
    padding: 14px 18px; 
    border-radius: 16px; 
    font-weight: 700; 
    font-size: 1.05rem;
    display: flex; 
    justify-content: center; 
    align-items: center; 
    gap: 10px; 
    border: none; 
    text-decoration: none !important; 
    text-align: center;
    cursor: pointer !important;
    pointer-events: auto !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.15s ease, background-color 0.15s ease;
}
.mobile-btn-action:active {
    transform: scale(0.96);
}

.mobile-btn-primary { 
    background: #1e40af !important; /* Premium deep blue */
    color: #ffffff !important; 
    border: 1px solid #1e3a8a !important;
}
.mobile-btn-primary:hover, .mobile-btn-primary:active { 
    background: #172554 !important; 
    color: #ffffff !important; 
}

.mobile-btn-secondary { 
    background: #f8fafc !important; 
    color: #334155 !important; 
    border: 1.5px solid #cbd5e1 !important; 
}
.mobile-btn-secondary:hover, .mobile-btn-secondary:active { 
    background: #e2e8f0 !important; 
    color: #0f172a !important; 
}

.mobile-btn-warning { background: #d97706 !important; color: #ffffff !important; }
.mobile-btn-success { background: #059669 !important; color: #ffffff !important; }

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.85rem;
    font-weight: 700;
    white-space: nowrap;
}

/* Quick Action Grid (Extra Large Cards) */
.quick-action-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 16px; 
    margin-bottom: 24px; 
}
.quick-action-card {
    background: #ffffff; 
    border-radius: 28px; 
    padding: 28px 16px; 
    display: flex; 
    flex-direction: column;
    align-items: center; 
    text-align: center; 
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    border: 1.5px solid #e2e8f0; 
    text-decoration: none !important; 
    color: #1e293b; 
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer !important;
    pointer-events: auto !important;
}
.quick-action-card:active { 
    transform: scale(0.95); 
}
.quick-action-icon {
    width: 72px; 
    height: 72px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    font-size: 2rem; 
    margin-bottom: 16px;
}
.quick-action-card.receive .quick-action-icon { background: #d1fae5; color: #059669; }
.quick-action-card.return .quick-action-icon { background: #e0e7ff; color: #4338ca; }
.quick-action-card.swap .quick-action-icon { background: #fef3c7; color: #d97706; }
.quick-action-card.scan .quick-action-icon { background: #f1f5f9; color: #0f172a; }
.quick-action-title { 
    font-size: 1.15rem; 
    font-weight: 800; 
    color: #0f172a; 
}

/* Mobile Filter Button (Super Size Touch Target) */
.mobile-filter-btn {
    width: 100%; 
    background: #ffffff; 
    border: 1.5px solid #cbd5e1; 
    border-radius: 24px; 
    padding: 20px 24px;
    min-height: 64px;
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    color: #0f172a;
    font-weight: 700; 
    font-size: 1.2rem; 
    margin-bottom: 24px; 
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
    cursor: pointer !important;
    pointer-events: auto !important;
    transition: background-color 0.15s ease;
}
.mobile-filter-btn:active {
    background: #f8fafc;
}

/* Bottom Sheet Modal for Filters */
.bottom-sheet {
    position: fixed; 
    bottom: 0; 
    left: 0; 
    right: 0; 
    transform: translateY(100%);
    visibility: hidden;
    background: #ffffff;
    border-top-left-radius: 28px; 
    border-top-right-radius: 28px; 
    z-index: 100000 !important;
    padding: 28px 24px; 
    padding-bottom: calc(28px + env(safe-area-inset-bottom));
    box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.2); 
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.28s ease;
    max-height: 85vh;
    overflow-y: auto;
    pointer-events: none;
}
.bottom-sheet.open { 
    transform: translateY(0) !important;
    visibility: visible !important;
    pointer-events: auto !important;
}
.bottom-sheet-backdrop {
    position: fixed; 
    top: 0; 
    left: 0; 
    right: 0; 
    bottom: 0; 
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 99999 !important; 
    display: none; 
    opacity: 0; 
    transition: opacity 0.3s ease;
}
.bottom-sheet-backdrop.open { 
    display: block !important; 
    opacity: 1 !important; 
}
.bottom-sheet-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 24px; 
}
.bottom-sheet-header h3 { 
    margin: 0; 
    font-size: 1.35rem; 
    font-weight: 800; 
    color: #0f172a;
}
.bottom-sheet-close {
    background: #f1f5f9; 
    border: none; 
    width: 40px; 
    height: 40px; 
    border-radius: 50%;
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #475569;
    font-size: 1.2rem;
    cursor: pointer !important;
}

/* Responsive overrides */
@media (max-width: 768px) {
    .next-dashboard { display: none !important; }
    .table-wrapper { display: none !important; }
    #advanced-filter-form { display: none !important; }
    #mobile-filter-container #advanced-filter-form { display: block !important; }
    .mobile-only-container { display: block !important; }
}
@media (min-width: 769px) {
    .mobile-only-container, .quick-action-grid, .mobile-filter-btn, .bottom-sheet, .bottom-sheet-backdrop { 
        display: none !important; 
    }
}
</style>

<script>
function closeBottomSheet() {
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    if (sheet) sheet.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
}

function openBottomSheet() {
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    if (sheet) {
        if (sheet.parentElement !== document.body) {
            document.body.appendChild(sheet);
        }
        sheet.classList.add('open');
    }
    if (backdrop) {
        if (backdrop.parentElement !== document.body) {
            document.body.appendChild(backdrop);
        }
        backdrop.classList.add('open');
    }
    var filterForm = document.getElementById('advanced-filter-form') || document.querySelector('.ajax-filter-form') || document.querySelector('form[method="GET"]');
    var mobileContainer = document.getElementById('mobile-filter-container');
    if (filterForm && mobileContainer && filterForm.parentElement !== mobileContainer) {
        mobileContainer.appendChild(filterForm);
    }
    if (filterForm) {
        filterForm.style.setProperty('display', 'block', 'important');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Move mobileBottomSheet and backdrop directly under <body> to prevent form nesting issues
    var sheet = document.getElementById('mobileBottomSheet');
    var backdrop = document.getElementById('bottomSheetBackdrop');
    if (sheet && sheet.parentElement !== document.body) {
        document.body.appendChild(sheet);
    }
    if (backdrop && backdrop.parentElement !== document.body) {
        document.body.appendChild(backdrop);
    }

    if (window.innerWidth <= 768) {
        var filterForm = document.getElementById('advanced-filter-form') || document.querySelector('.ajax-filter-form');
        var mobileContainer = document.getElementById('mobile-filter-container');
        if (filterForm && mobileContainer && filterForm.parentElement !== mobileContainer) {
            mobileContainer.appendChild(filterForm);
            filterForm.style.setProperty('display', 'block', 'important');
        }
    }
});
</script>
