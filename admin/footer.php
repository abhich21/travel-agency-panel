<?php
// Note: This script assumes a session is already started and a database connection exists
// The parent PHP file (e.g., index.php, login.php) must provide these dependencies
$footer_bg_color = "#343a40"; // Default dark color
$footer_text_color = "#ffffff"; // Default white color

// Only fetch colors if an admin is logged in
if (isset($_SESSION['admin_user_id']) && isset($conn)) {
    $stmt = $conn->prepare("SELECT o.bg_color, o.text_color FROM organizations o WHERE o.user_id = ?");
    $stmt->bind_param("i", $_SESSION['admin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $colors = $result->fetch_assoc();
    if ($colors) {
        $footer_bg_color = htmlspecialchars($colors['bg_color']);
        $footer_text_color = htmlspecialchars($colors['text_color']);
    }
}
?>
<footer class="footer mt-auto py-3 text-center" style="background-color: <?php echo $footer_bg_color; ?>; color: <?php echo $footer_text_color; ?>;">
    <div class="container">
        <span>© 2025 Admin Panel</span>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.0.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

<!-- showSweetAlert function moved here for reusability -->
<script>
    /**
     * Displays a SweetAlert with customizable options and an optional page reload.
     * @param {string} type - The icon type ('success', 'error', 'warning', 'info', 'question').
     * @param {string} message - The text message to display.
     * @param {boolean} reload - Whether to reload the page after the alert closes.
     */
    function showSweetAlert(type, message, reload = false) {
        Swal.fire({
            icon: type,
            title: type === 'success' ? 'Success!' : 'Error!',
            text: message,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didClose: () => {
                if (reload) {
                    window.location.reload();
                }
            }
        });
    }
</script>

</body>
</html>
