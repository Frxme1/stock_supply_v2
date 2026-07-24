function confirmDeleteHis(HistoryID, nonce) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && HistoryID) {
            // แปลง URL ปัจจุบันโดยไม่ล้าง query อื่น ๆ
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('delete', HistoryID);
            if (nonce) {
                currentUrl.searchParams.set('_wpnonce', nonce);
            }
            window.location.href = currentUrl.toString();
        }
    });
}
