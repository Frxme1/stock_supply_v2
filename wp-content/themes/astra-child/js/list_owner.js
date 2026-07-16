document.addEventListener('DOMContentLoaded', () => {
    const ownerInput = document.getElementById('owner_input');
    const ownerIDInput = document.getElementById('OwnerID');
    const deptSelect = document.getElementById('DepartmentID');
    const positionSelect = document.getElementById('PositionID');

    let dropdown = document.getElementById('owner_dropdown');
    if (!dropdown) {
        dropdown = document.createElement('ul');
        dropdown.id = 'owner_dropdown';
        dropdown.className = 'dropdown-list';
        ownerInput.parentNode.appendChild(dropdown);
        ownerInput.parentNode.style.position = 'relative';
    }

    ownerInput.addEventListener('input', () => {
        const val = ownerInput.value.trim().toLowerCase();
        dropdown.innerHTML = '';

        if (val.length === 0) {
            dropdown.style.display = 'none';
            ownerIDInput.value = '';
            deptSelect.value = '';
            positionSelect.value = '';
            return;
        }

        const matches = window.ownersData.filter(o => o.Nickname.toLowerCase().includes(val));

        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        matches.forEach(o => {
            const li = document.createElement('li');
            li.textContent = o.Nickname;
            li.dataset.id = o.OwnerID;
            li.dataset.dept = o.DepartmentID;
            li.dataset.pos = o.PositionID;
            li.addEventListener('click', () => {
                ownerInput.value = o.Nickname;
                ownerIDInput.value = o.OwnerID;
                deptSelect.value = o.DepartmentID;
                positionSelect.value = o.PositionID;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(li);
        });

        dropdown.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
        if (!ownerInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
