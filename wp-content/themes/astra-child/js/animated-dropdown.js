document.addEventListener('DOMContentLoaded', () => {
    // Icons (Lucide)
    const iconChevronDown = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>`;
    const iconChevronRight = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right"><path d="m9 18 6-6-6-6"/></svg>`;
    const iconCheck = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-square"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>`;

    function initStaggeredDropdowns() {
        const selects = document.querySelectorAll('select.staggered-dropdown');

        selects.forEach(select => {
            // Check if already initialized
            if (select.nextElementSibling && select.nextElementSibling.classList.contains('animated-dropdown-wrapper')) {
                return;
            }

            // Hide original
            select.style.display = 'none';

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'animated-dropdown-wrapper';
            select.parentNode.insertBefore(wrapper, select.nextSibling);

            // Create button
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'animated-dropdown-btn';

            const btnText = document.createElement('span');
            btnText.className = 'btn-text';
            btnText.innerHTML = select.options[select.selectedIndex]?.innerHTML || 'Select...';

            const btnIcon = document.createElement('span');
            btnIcon.className = 'chevron';
            btnIcon.innerHTML = iconChevronDown;

            btn.appendChild(btnText);
            btn.appendChild(btnIcon);
            wrapper.appendChild(btn);

            // Create list
            const list = document.createElement('ul');
            list.className = 'animated-dropdown-list';

            // Populate options
            Array.from(select.options).forEach((option, index) => {
                const li = document.createElement('li');
                li.className = 'animated-dropdown-item';
                // Stagger delay (matching 0.1s stagger in framer-motion, using 0.05s to make it slightly faster if there are many items)
                li.style.transitionDelay = `${index * 0.05}s`;

                const iconSpan = document.createElement('span');
                iconSpan.className = 'action-icon';

                // Keep original icons if they exist (e.g. FontAwesome), otherwise default to check/chevron
                if (option.innerHTML.includes('<i')) {
                    iconSpan.style.display = 'none'; // Don't show default icon if it has its own
                } else {
                    iconSpan.innerHTML = option.value ? iconCheck : iconChevronRight;
                }

                const textSpan = document.createElement('span');
                textSpan.innerHTML = option.innerHTML;

                li.appendChild(iconSpan);
                li.appendChild(textSpan);

                li.addEventListener('click', () => {
                    select.value = option.value;
                    btnText.innerHTML = option.innerHTML;

                    // Trigger change event for original select to run existing scripts
                    const event = new Event('change', { bubbles: true });
                    select.dispatchEvent(event);

                    closeDropdown();
                });

                list.appendChild(li);
            });

            wrapper.appendChild(list);

            // Toggle Logic
            let isOpen = false;
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other dropdowns first
                document.querySelectorAll('.animated-dropdown-btn.open').forEach(b => {
                    if (b !== btn) b.click();
                });

                isOpen = !isOpen;
                if (isOpen) {
                    btn.classList.add('open');
                    list.classList.add('open');
                } else {
                    closeDropdown();
                }
            });

            function closeDropdown() {
                isOpen = false;
                btn.classList.remove('open');
                list.classList.remove('open');
            }

            // Click outside to close
            document.addEventListener('click', (e) => {
                if (isOpen && !wrapper.contains(e.target)) {
                    closeDropdown();
                }
            });

            // Update text if original select changes externally
            select.addEventListener('change', () => {
                btnText.innerHTML = select.options[select.selectedIndex]?.innerHTML || 'Select...';
            });
        });
    }

    // Initialize immediately
    initStaggeredDropdowns();

    // Export function if needs to be called dynamically (e.g. after AJAX)
    window.initStaggeredDropdowns = initStaggeredDropdowns;
});
