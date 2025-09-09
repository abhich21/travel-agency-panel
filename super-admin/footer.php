<footer class="bg-black text-white py-3 mt-4 fixed-bottom">
    <div class="container text-center">
        <p>&copy; <?php echo date('Y'); ?> Admin Panel. All rights reserved.</p>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /**
     * Displays a SweetAlert popup with a status and message.
     * @param {string} status 'success' or 'error'
     * @param {string} message The message to display
     */
    function showSweetAlert(status, message) {
        Swal.fire({
            icon: status,
            title: message,
            showConfirmButton: false,
            timer: 2000, // 2 seconds
            timerProgressBar: true
        }).then((result) => {
            // Reload the page only after the user clicks OK on a success message
            if (type === 'success' && result.isConfirmed) {
                location.reload();
            }
        });
    }

    // Example usage:
    // Call showSweetAlert('success', 'User created successfully!');
    // Call showSweetAlert('error', 'Something went wrong!');

    // You can call this function from your PHP code like this:
    // echo '<script>showSweetAlert("success", "Login successful!");</script>';
</script>
