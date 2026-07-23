<?php
$search_name = isset($search_name) ? $search_name : 'device_search';
$search_placeholder = isset($search_placeholder) ? $search_placeholder : 'Search...';
$search_value = isset($search) ? esc_attr($search) : '';
$search_list = isset($search_list) ? $search_list : '';
?>
<div class="search-wrapper-modern">
    <i class="fa-solid fa-search search-icon-modern"></i>
    <input type="text" 
           name="<?= esc_attr($search_name) ?>" 
           <?php if($search_list) echo 'list="'.esc_attr($search_list).'"'; ?>
           class="search-input-modern form-control form-control-sm"
           placeholder="<?= esc_attr($search_placeholder) ?>" 
           value="<?= $search_value ?>" />
</div>

<style>
/* Modern Search Styles */
.search-wrapper-modern {
    position: relative;
    width: 100%;
}
.search-input-modern {
    width: 100%;
    padding: 0.35rem 1rem 0.35rem 2.25rem !important;
    border-radius: 6px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #f8fafc !important;
    font-size: 0.875rem !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.02) !important;
}
.search-input-modern:focus {
    outline: none !important;
    border-color: #0ea5e9 !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15), inset 0 1px 2px rgba(0,0,0,0.02) !important;
}
.search-icon-modern {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
    transition: color 0.3s;
    pointer-events: none;
}
.search-wrapper-modern:focus-within .search-icon-modern {
    color: #0ea5e9;
}

/* Modern Button Styles */
.btn-filter-modern {
    background-color: #0ea5e9 !important;
    color: white !important;
    border: none !important;
    padding: 0.35rem 1rem !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.375rem !important;
    height: 31px !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
}
.btn-filter-modern:hover {
    background-color: #0284c7 !important;
}
.btn-reset-modern {
    background-color: white !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    padding: 0.35rem 1rem !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-decoration: underline !important;
    text-underline-offset: 2px !important;
    height: 31px !important;
}
.btn-reset-modern:hover {
    background-color: #f8fafc !important;
    color: #0f172a !important;
    border-color: #94a3b8 !important;
}
</style>

<script>
if (!window.hasRegisteredSearchUrlParser) {
    window.hasRegisteredSearchUrlParser = true;
    document.addEventListener('input', function(e) {
        if (e.target && (e.target.name === 'device_search' || e.target.classList.contains('search-input-modern'))) {
            const val = e.target.value;
            const match = val.match(/[?&]view=([^&]+)/i);
            if (match && match[1]) {
                e.target.value = decodeURIComponent(match[1]);
            }
        }
    });
}
</script>
