function confirmDelete(DeviceID, nonce) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && DeviceID) {
            let url = '?delete=' + encodeURIComponent(DeviceID);
            if (nonce) {
                url += '&_wpnonce=' + encodeURIComponent(nonce);
            }
            window.location.href = url;
        }
    });
}
