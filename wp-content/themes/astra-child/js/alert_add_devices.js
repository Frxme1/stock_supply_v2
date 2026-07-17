document.addEventListener('DOMContentLoaded', () => {
	const params = new URLSearchParams(window.location.search);

	if (params.has('success')) {
		const categorySlug = params.get('category') || 'device';

		Swal.fire({
			icon: 'success',
			title: `Added ${categorySlug} successfully`,
			showConfirmButton: false,
			timer: 1500,
		}).then(() => {
			// Clear params from URL
			window.history.replaceState({}, document.title, window.location.pathname);

			// Redirect to siteUrl + categorySlug
			const siteUrl = window.siteUrl || '/';
			window.location.href = siteUrl + categorySlug + '/';
		});
	}
});
