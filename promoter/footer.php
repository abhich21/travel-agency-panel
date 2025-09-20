<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">© <?php echo date("Y"); ?> Promoter Panel. All rights reserved.</span>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showSweetAlert(icon, title, message) {
        Swal.fire({
            icon: icon,
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }
</script>
