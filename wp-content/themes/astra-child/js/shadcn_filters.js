class ShadcnFilterManager {
    constructor(containerSelector, formSelector, config, initialState) {
        this.container = document.querySelector(containerSelector);
        this.form = document.querySelector(formSelector);
        this.config = config; // Array of filter definitions
        this.state = initialState || []; // Array of active filters: { id, type, operator, values: [] }

        this.initUI();
        this.render();
    }

    initUI() {
        // Clear container
        this.container.innerHTML = '';
        this.container.classList.add('shadcn-filters-container');

        // Create container for active tags
        this.tagsContainer = document.createElement('div');
        this.tagsContainer.style.display = 'flex';
        this.tagsContainer.style.gap = '0.5rem';
        this.tagsContainer.style.flexWrap = 'wrap';
        this.container.appendChild(this.tagsContainer);

        // Create Add Filter button
        this.addBtnContainer = document.createElement('div');
        this.addBtnContainer.className = 'dropdown';
        
        this.addBtn = document.createElement('button');
        this.addBtn.type = 'button';
        this.addBtn.className = 'add-filter-btn';
        this.addBtn.setAttribute('data-bs-toggle', 'dropdown');
        this.addBtn.setAttribute('data-bs-auto-close', 'outside');
        this.addBtn.innerHTML = '<i class="fa-solid fa-list-check"></i> Filter';
        this.addBtnContainer.appendChild(this.addBtn);

        // Add Filter Dropdown Menu
        this.addMenu = document.createElement('div');
        this.addMenu.className = 'dropdown-menu shadcn-popover shadow-sm';
        this.addMenu.innerHTML = `
            <div class="command-input-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="command-input" placeholder="Filter...">
            </div>
            <div class="command-list">
                <div class="command-empty">No results found.</div>
                <!-- Filter Types will be appended here -->
            </div>
        `;
        this.addBtnContainer.appendChild(this.addMenu);
        this.container.appendChild(this.addBtnContainer);

        // Bind Search in Add Menu
        const searchInput = this.addMenu.querySelector('.command-input');
        searchInput.addEventListener('input', (e) => this.handleTypeSearch(e.target.value));

        // Create Clear button
        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'clear-filters-btn d-none';
        this.clearBtn.innerText = 'Clear';
        this.clearBtn.onclick = () => {
            this.state = [];
            this.updateHiddenInputs();
            // Clear the search input as well
            const searchInput = this.form.querySelector('input[name="device_search"]');
            if (searchInput) searchInput.value = '';
            this.form.submit();
        };
        this.container.appendChild(this.clearBtn);

        // Add hidden inputs to form for form submission
        this.hiddenInputsContainer = document.createElement('div');
        this.form.appendChild(this.hiddenInputsContainer);
    }

    handleTypeSearch(query) {
        query = query.toLowerCase();
        let found = false;
        const items = this.addMenu.querySelectorAll('.command-item');
        items.forEach(item => {
            if (item.innerText.toLowerCase().includes(query)) {
                item.style.display = 'flex';
                found = true;
            } else {
                item.style.display = 'none';
            }
        });
        this.addMenu.querySelector('.command-empty').style.display = found ? 'none' : 'block';
    }

    getFilterConfig(typeId) {
        return this.config.find(c => c.id === typeId);
    }

    addFilter(typeId) {
        // Prevent duplicate filter types unless allowed
        if (this.state.some(f => f.type === typeId)) return;
        
        const newFilter = {
            id: 'f_' + Date.now(),
            type: typeId,
            operator: 'is any of',
            values: []
        };
        this.state.push(newFilter);
        
        // Close dropdown
        const dropdown = bootstrap.Dropdown.getInstance(this.addBtn);
        if(dropdown) dropdown.hide();

        this.render();
    }

    removeFilter(filterId) {
        this.state = this.state.filter(f => f.id !== filterId);
        this.updateHiddenInputs();
        this.render();
    }

    toggleFilterValue(filterId, value) {
        const filter = this.state.find(f => f.id === filterId);
        if (!filter) return;

        const idx = filter.values.indexOf(value);
        if (idx > -1) {
            filter.values.splice(idx, 1);
        } else {
            filter.values.push(value);
        }
        this.updateHiddenInputs();
        this.render(); // Re-render to show updated tags and checkboxes
    }

    render() {
        this.tagsContainer.innerHTML = '';

        // Render Add Menu items based on what's NOT already added
        const commandList = this.addMenu.querySelector('.command-list');
        commandList.querySelectorAll('.command-item').forEach(e => e.remove());
        
        this.config.forEach(c => {
            if (!this.state.some(f => f.type === c.id)) {
                const item = document.createElement('div');
                item.className = 'command-item';
                item.innerHTML = `${c.icon} <span>${c.name}</span>`;
                item.onclick = () => this.addFilter(c.id);
                commandList.appendChild(item);
            }
        });

        // Hide "Filter" button text if there are active filters
        if (this.state.length > 0) {
            this.addBtn.innerHTML = '<i class="fa-solid fa-list-check"></i>';
            this.clearBtn.classList.remove('d-none');
        } else {
            this.addBtn.innerHTML = '<i class="fa-solid fa-list-check"></i> Filter';
            this.clearBtn.classList.add('d-none');
        }

        // Render tags
        this.state.forEach(filter => {
            const conf = this.getFilterConfig(filter.type);
            if (!conf) return;

            const tag = document.createElement('div');
            tag.className = 'filter-tag';

            // Tag Type
            const tagType = document.createElement('div');
            tagType.className = 'tag-type';
            tagType.innerHTML = `${conf.icon} ${conf.name}`;
            tag.appendChild(tagType);

            // Tag Operator (Static for now: 'is any of')
            const tagOp = document.createElement('div');
            tagOp.className = 'tag-operator dropdown';
            tagOp.innerHTML = `
                <span data-bs-toggle="dropdown">${filter.operator}</span>
                <div class="dropdown-menu shadcn-popover p-1 shadow-sm">
                    <div class="command-item selected">is any of</div>
                </div>
            `;
            tag.appendChild(tagOp);

            // Tag Values
            const tagVal = document.createElement('div');
            tagVal.className = 'tag-value dropdown';
            
            let valText = filter.values.length === 0 ? 'Select options' : 
                          filter.values.length === 1 ? filter.values[0] : 
                          `${filter.values.length} selected`;

            tagVal.innerHTML = `
                <span data-bs-toggle="dropdown" data-bs-auto-close="outside">${valText}</span>
            `;

            // Dropdown Menu for values
            const valMenu = document.createElement('div');
            valMenu.className = 'dropdown-menu shadcn-popover shadow-sm';
            
            let valMenuHTML = `
                <div class="command-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" class="command-input" placeholder="Search ${conf.name}...">
                </div>
                <div class="command-list">
            `;
            
            conf.options.forEach(opt => {
                const isChecked = filter.values.includes(opt.value);
                valMenuHTML += `
                    <div class="command-item value-item" data-value="${opt.value}">
                        <div class="command-item-checkbox ${isChecked ? 'checked' : ''}">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        ${opt.icon || ''}
                        <span>${opt.label}</span>
                    </div>
                `;
            });
            valMenuHTML += `</div>`;
            valMenu.innerHTML = valMenuHTML;

            // Bind click for values
            valMenu.querySelectorAll('.value-item').forEach(item => {
                item.onclick = (e) => {
                    e.stopPropagation();
                    this.toggleFilterValue(filter.id, item.getAttribute('data-value'));
                };
            });

            // Bind search for values
            const valSearch = valMenu.querySelector('.command-input');
            valSearch.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                valMenu.querySelectorAll('.value-item').forEach(item => {
                    if (item.innerText.toLowerCase().includes(query)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            tagVal.appendChild(valMenu);
            tag.appendChild(tagVal);

            // Tag Close
            const tagClose = document.createElement('button');
            tagClose.type = 'button';
            tagClose.className = 'tag-close';
            tagClose.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            tagClose.onclick = () => this.removeFilter(filter.id);
            tag.appendChild(tagClose);

            this.tagsContainer.appendChild(tag);
        });
    }

    updateHiddenInputs() {
        this.hiddenInputsContainer.innerHTML = '';
        this.state.forEach(filter => {
            filter.values.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = filter.type + '[]'; // e.g. filter_status[]
                input.value = val;
                this.hiddenInputsContainer.appendChild(input);
            });
        });
    }
}
