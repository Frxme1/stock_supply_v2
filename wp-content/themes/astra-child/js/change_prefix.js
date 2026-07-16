document.addEventListener('DOMContentLoaded', () => {
	const categorySelect = document.getElementById('category_select');
	const deviceIdInput = document.getElementById('device_id_input');

	categorySelect.addEventListener('change', () => {
		const catId = categorySelect.value;
		if (!catId || !window.categoryData[catId]) {
			deviceIdInput.value = '';
			return;
		}
		const prefix = window.categoryData[catId].prefix;
		const nextNumber = window.categoryData[catId].last_number + 1;
		deviceIdInput.value = prefix + String(nextNumber).padStart(3, '0');
	});
});
