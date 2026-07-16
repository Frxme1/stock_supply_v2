function confirmDeleteHis(HistoryID) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && HistoryID) {
            // แปลง URL ปัจจุบันโดยไม่ล้าง query อื่น ๆ
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('delete', HistoryID);
            window.location.href = currentUrl.toString();
        }
    });
}
