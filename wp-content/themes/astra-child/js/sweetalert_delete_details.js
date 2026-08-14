function confirmDeleteHis(HistoryID, nonce) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && HistoryID) {
            // Convert current URL without clearing other query parameters
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('delete', HistoryID);
            if (nonce) {
                currentUrl.searchParams.set('_wpnonce', nonce);
            }
            window.location.href = currentUrl.toString();
        }
    });
}
