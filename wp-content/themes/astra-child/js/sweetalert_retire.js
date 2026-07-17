function confirmRetire(id, paramName = 'retired') {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to retire this item? Please provide a reason.',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'Enter reason here...',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a reason!';
            }
        },
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, retire it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = new URL(window.location.href);
            url.searchParams.set(paramName, id);
            url.searchParams.set('reason', result.value);
            window.location.href = url.toString();
        }
    });
}
