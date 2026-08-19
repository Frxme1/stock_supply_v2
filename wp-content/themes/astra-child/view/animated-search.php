<?php
$search_name = isset($search_name) ? $search_name : 'device_search';
$search_placeholder = isset($search_placeholder) ? $search_placeholder : 'Search...';
$search_value = isset($search) ? esc_attr($search) : '';
$search_list = isset($search_list) ? $search_list : '';
?>
<div class="search-wrapper-modern">
    <i class="fa-solid fa-search search-icon-modern"></i>
    <input type="text" name="<?= esc_attr($search_name) ?>" id="<?= esc_attr($search_name) ?>" autocomplete="off"
        data-list-id="<?= esc_attr($search_list) ?>" class="search-input-modern form-control form-control-sm"
        placeholder="<?= esc_attr($search_placeholder) ?>" value="<?= $search_value ?>" />
    <ul class="custom-autocomplete-card" id="<?= esc_attr($search_name) ?>_auto_list"></ul>
</div>

<style>
    /* Modern Search Styles */
    .search-wrapper-modern {
        position: relative;
        width: 100%;
    }

    .search-input-modern {
        width: 100%;
        padding: 0.4rem 1rem 0.4rem 2.25rem !important;
        border-radius: 10px !important;
        border: 1.5px solid #cbd5e1 !important;
        background-color: #f8fafc !important;
        font-size: 0.875rem !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02) !important;
    }

    .search-input-modern:focus {
        outline: none !important;
        border-color: #0ea5e9 !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3.5px rgba(14, 165, 233, 0.15), inset 0 1px 2px rgba(0, 0, 0, 0.02) !important;
    }

    .search-icon-modern {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
        transition: color 0.25s;
        pointer-events: none;
        z-index: 2;
    }

    .search-wrapper-modern:focus-within .search-icon-modern {
        color: #0ea5e9;
    }

    /* Custom Floating Autocomplete Dropdown Card */
    .custom-autocomplete-card {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        width: 100%;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 12px 32px -4px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(0, 0, 0, 0.05);
        z-index: 9999;
        max-height: 240px;
        overflow-y: auto;
        padding: 6px;
        margin: 0;
        list-style: none;
        display: none;
        transform-origin: top center;
        animation: autoDropdownSlide 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes autoDropdownSlide {
        0% {
            opacity: 0;
            transform: translateY(-6px) scale(0.98);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .custom-autocomplete-card::-webkit-scrollbar {
        width: 5px;
    }

    .custom-autocomplete-card::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 99px;
    }

    .custom-autocomplete-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s ease;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .custom-autocomplete-item:hover,
    .custom-autocomplete-item.active-item {
        background-color: #f0f9ff;
        color: #0284c7;
    }

    .custom-autocomplete-item i {
        font-size: 0.8rem;
        color: #94a3b8;
        transition: color 0.15s ease;
        flex-shrink: 0;
    }

    .custom-autocomplete-item:hover i,
    .custom-autocomplete-item.active-item i {
        color: #0ea5e9;
    }

    /* Modern Button Styles */
    .btn-filter-modern {
        background-color: #0ea5e9 !important;
        color: white !important;
        border: none !important;
        padding: 0.35rem 1rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.375rem !important;
        height: 34px !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
    }

    .btn-filter-modern:hover {
        background-color: #0284c7 !important;
    }

    .btn-reset-modern {
        background-color: white !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        padding: 0.35rem 1rem !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: underline !important;
        text-underline-offset: 2px !important;
        height: 34px !important;
    }

    .btn-reset-modern:hover {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border-color: #94a3b8 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Setup Custom Autocomplete for search-wrapper-modern
        const wrappers = document.querySelectorAll('.search-wrapper-modern');

        wrappers.forEach(wrapper => {
            const input = wrapper.querySelector('.search-input-modern');
            const listCard = wrapper.querySelector('.custom-autocomplete-card');
            if (!input || !listCard) return;

            const listId = input.getAttribute('data-list-id') || input.getAttribute('list');
            if (listId) {
                input.removeAttribute('list'); // Remove native datalist popup
            }

            let suggestions = [];

            // Gather suggestions from datalist if available
            const fetchSuggestions = () => {
                if (listId) {
                    const datalist = document.getElementById(listId);
                    if (datalist) {
                        const options = datalist.querySelectorAll('option');
                        suggestions = Array.from(options).map(opt => opt.value).filter(val => val && val.trim() !== '');
                    }
                }
            };
            fetchSuggestions();

            let activeIndex = -1;

            const renderDropdown = (query) => {
                fetchSuggestions();
                const cleanQuery = (query || '').toLowerCase().trim();

                // Do not open dropdown if query is empty or input is not focused
                if (cleanQuery.length === 0 || document.activeElement !== input) {
                    listCard.style.display = 'none';
                    listCard.innerHTML = '';
                    return;
                }

                const filtered = suggestions.filter(item => item.toLowerCase().includes(cleanQuery));

                if (filtered.length === 0) {
                    listCard.style.display = 'none';
                    listCard.innerHTML = '';
                    return;
                }

                listCard.innerHTML = filtered.map((item, index) => `
                <li class="custom-autocomplete-item" data-value="${item.replace(/"/g, '&quot;')}">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>${item}</span>
                </li>
            `).join('');

                listCard.style.display = 'block';
                activeIndex = -1;

                // Handle Click on Items
                listCard.querySelectorAll('.custom-autocomplete-item').forEach(itemEl => {
                    itemEl.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        const val = this.getAttribute('data-value');
                        input.value = val;
                        listCard.style.display = 'none';

                        // Trigger form submission or input event
                        const form = input.closest('form');
                        if (form) {
                            form.submit();
                        } else {
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });
                });
            };

            input.addEventListener('focus', function () {
                if (this.value.trim().length > 0) {
                    renderDropdown(this.value);
                }
            });

            input.addEventListener('input', function () {
                // URL Parser check
                const val = this.value;
                const match = val.match(/[?&]view=([^&]+)/i);
                if (match && match[1]) {
                    this.value = decodeURIComponent(match[1]);
                }
                renderDropdown(this.value);
            });

            input.addEventListener('keydown', function (e) {
                const items = listCard.querySelectorAll('.custom-autocomplete-item');
                if (listCard.style.display === 'block' && items.length > 0) {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIndex = (activeIndex + 1) % items.length;
                        updateActiveItem(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIndex = (activeIndex - 1 + items.length) % items.length;
                        updateActiveItem(items);
                    } else if (e.key === 'Enter' && activeIndex >= 0) {
                        e.preventDefault();
                        items[activeIndex].dispatchEvent(new MouseEvent('mousedown'));
                    } else if (e.key === 'Escape') {
                        listCard.style.display = 'none';
                    }
                }
            });

            const updateActiveItem = (items) => {
                items.forEach((it, idx) => {
                    if (idx === activeIndex) {
                        it.classList.add('active-item');
                        it.scrollIntoView({ block: 'nearest' });
                    } else {
                        it.classList.remove('active-item');
                    }
                });
            };

            // Close when clicking outside or when parent form submits
            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target)) {
                    listCard.style.display = 'none';
                }
            });

            const parentForm = input.closest('form');
            if (parentForm) {
                parentForm.addEventListener('submit', function () {
                    listCard.style.display = 'none';
                });
            }
        });
    });
</script>