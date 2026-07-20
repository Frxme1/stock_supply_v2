function confirmDelete(DeviceID) {
    Swal.fire({
        title: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed && DeviceID) {
            window.location.href = '?delete=' + encodeURIComponent(DeviceID);
        }
    });
}
