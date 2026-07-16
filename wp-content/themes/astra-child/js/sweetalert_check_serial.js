document.addEventListener('DOMContentLoaded', function () {
	const params = new URLSearchParams(window.location.search);

	const serialInput = document.querySelector('input[name="SerialNumber"]');

	if (params.get('error') === 'serial_exists') {
		Swal.fire({
			icon: 'error',
			title: 'Unable to Add Device',
			text: 'This Serial Number already exists in the system.',
			confirmButtonColor: '#d33',
			confirmButtonText: 'OK'
		}).then(() => {
			// Remove the query string to prevent the alert from showing on refresh
			const url = new URL(window.location.href);
			url.searchParams.delete('error');
			window.history.replaceState({}, document.title, url.toString());
		});

		// Highlight the serial number input field in red
		if (serialInput) {
			serialInput.style.border = '2px solid red';
		}
	}

	// Remove red border on input or focus
	if (serialInput) {
		serialInput.addEventListener('focus', function () {
			serialInput.style.border = '';
		});
		serialInput.addEventListener('input', function () {
			serialInput.style.border = '';
		});
	}
});
