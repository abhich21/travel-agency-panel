$(document).ready(function() {

    // Loader functions
    function showLoader() {
        $('#loader').show();
    }

    function hideLoader() {
        $('#loader').hide();
    }

    // Handle Delete button click
    $(document).on('click', '.delete-btn', function() {
        const userId = $(this).data('id');
        const logoUrl = $(this).data('logo-url');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Perform AJAX deletion
                $.ajax({
                    url: 'api/delete_user.php',
                    method: 'POST',
                    data: {
                        userId: userId,
                        logoUrl: logoUrl
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        showLoader();
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000, // 2 seconds
                                timerProgressBar: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred during deletion.',
                            showConfirmButton: false,
                            timer: 2000, // 2 seconds
                            timerProgressBar: true
                    });
                        console.error("AJAX Error: ", status, error);
                    },
                    complete: function() {
                        hideLoader();
                    }
                });
            }
        });
    });

    // Handle Edit button click
    $(document).on('click', '.edit-btn', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('user-name');
        const orgTitle = $(this).data('org-title');
        const logoUrl = $(this).data('logo-url');
        const bgColor = $(this).data('bg-color');
        const textColor = $(this).data('text-color');

        $('#userId').val(userId);
        $('#userName').val(userName);
        $('#orgTitle').val(orgTitle);
        $('#oldLogoUrl').val(logoUrl);

        // Populate the color pickers with the correct values
        $('#bgColor_hex').val(bgColor);
        $('#bgColor_picker').val(bgColor);
        $('#textColor_hex').val(textColor);
        $('#textColor_picker').val(textColor);

        // Show logo preview if available
        const logoPreviewContainer = document.getElementById('logo-preview-container');
        logoPreviewContainer.innerHTML = '';
        if (logoUrl) {
            const img = document.createElement('img');
            img.src = logoUrl;
            img.alt = 'Logo Preview';
            img.classList.add('preview-logo-thumb');
            logoPreviewContainer.appendChild(img);
        } else {
            logoPreviewContainer.innerHTML = '<small class="text-muted">No logo saved</small>';
        }

        $('#editModal').modal('show');
    });

    // Handle form submission for editing
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get the hex values from the input fields
        const bgColor = $('#bgColor_hex').val();
        const textColor = $('#textColor_hex').val();

        // Create a new FormData object and append the updated values
        const formData = new FormData(this);
        formData.set('bgColor', bgColor);
        formData.set('textColor', textColor);

        $.ajax({
            url: 'api/edit_user.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function() {
                showLoader();
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 2000, // 2 seconds
                        timerProgressBar: true
                    }).then(() => {
                        $('#editModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 2000, // 2 seconds
                        timerProgressBar: true
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred during edit.',
                });
                console.error("AJAX Error: ", status, error);
            },
            complete: function() {
                hideLoader();
            }
        });
    });

    // Synchronize the color picker and text input for background color
    $('#bgColor_picker').on('input', function(e) {
        $('#bgColor_hex').val(e.target.value);
    });
    $('#bgColor_hex').on('input', function(e) {
        if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
            $('#bgColor_picker').val(e.target.value);
        }
    });

    // Synchronize the color picker and text input for text color
    $('#textColor_picker').on('input', function(e) {
        $('#textColor_hex').val(e.target.value);
    });
    $('#textColor_hex').on('input', function(e) {
        if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
            $('#textColor_picker').val(e.target.value);
        }
    });
});
