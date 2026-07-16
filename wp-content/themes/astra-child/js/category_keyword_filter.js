document.addEventListener("DOMContentLoaded", function () {
	const categorySelect = document.getElementById("category_select");
	const keywordSelect = document.getElementById("keyword_select");

	if (!categorySelect || !keywordSelect || !window.keywordMap || !window.accessoryKeywordIDs) {
		return;
	}

	const keywordGroup = keywordSelect.closest('.form-group');

	function updateKeywordVisibility(resetValue = false) {
		const selectedCategory = categorySelect.options[categorySelect.selectedIndex].text.trim().toLowerCase();

		// Show all keyword options
		Array.from(keywordSelect.options).forEach(option => {
			option.style.display = '';
		});

		if (selectedCategory === 'accessories') {
			if (keywordGroup) {
				keywordGroup.style.display = 'flex'; // Restore display
			}
			Array.from(keywordSelect.options).forEach(option => {
				const val = parseInt(option.value);
				if (!window.accessoryKeywordIDs.includes(val)) {
					option.style.display = 'none';
				}
			});
			if (resetValue) {
				keywordSelect.value = ''; // reset
			}
		} else {
			if (keywordGroup) {
				keywordGroup.style.display = 'none';
			}
			if (window.keywordMap[selectedCategory]) {
				keywordSelect.value = window.keywordMap[selectedCategory];
			}
		}
	}

	categorySelect.addEventListener("change", function () {
		updateKeywordVisibility(true);
	});

	// Run on initial load
	updateKeywordVisibility(false);
});
