<?php
// login.php - Attendee Login Page

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
session_start();
require_once '../config/config.php'; 

$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal'; // Default title
$organization_id = null;

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    $organization_title = $_GET['view'];
    $stmt = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt->bind_param("s", $organization_title);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($org_details = $result->fetch_assoc()) {
        $organization_id = $org_details['id'];
        $org_title = htmlspecialchars($org_details['title']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
    }
    $stmt->close();
}

// --- 2. Start Output Buffering ---
ob_start();
?>

<style>
    /* We can reuse the style from registration.php for consistency */
    .login-form-container {
        max-width: 500px; /* Login forms are usually narrower */
        margin: 3rem auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .form-control:focus {
        border-color: <?php echo $nav_bg_color; ?>;
        box-shadow: 0 0 0 0.25rem <?php echo $nav_bg_color . '40'; ?>; 
    }
    .btn-submit {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }
</style>

<main>
    <div class="login-form-container">
          <div id="loginSuccessCard" class="alert alert-success d-none">
            <i class="fas fa-check-circle"></i> <strong>Success!</strong>
            <span id="loginSuccessMessage"></span>
        </div>
        <div id="loginErrorCard" class="alert alert-danger d-none">
            <i class="fas fa-exclamation-triangle"></i> <strong>Error!</strong>
            <span id="loginErrorMessage"></span>
        </div>
        <h2 class="text-center mb-4">Attendee Login</h2>
        <p class="text-center text-muted mb-4">Please log in to access your event details for <?php echo $org_title; ?>.</p>
        
        <form id="loginForm" method="POST" action="login_verify.php?view=<?php echo urlencode($org_title); ?>"  novalidate>
            <div class="mb-3">
                <label for="login_identifier" class="form-label">Email or Phone Number</label>
                <input type="text" class="form-control" id="login_identifier" name="login_identifier" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <hr class="my-4">
            
            <button class="w-100 btn btn-submit" type="submit">
    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    Login
</button>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');

    const successCard = document.getElementById('loginSuccessCard');
    const successMessage = document.getElementById('loginSuccessMessage');
    const errorCard = document.getElementById('loginErrorCard');
    const errorMessage = document.getElementById('loginErrorMessage');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        event.stopPropagation();

        // Hide previous alerts
        successCard.classList.add('d-none');
        errorCard.classList.add('d-none');

        // Simple client-side validation
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        form.classList.add('was-validated');

        // Disable button and show spinner
        submitButton.disabled = true;
        spinner.classList.remove('d-none');

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, { // form.action gets the URL from the HTML
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.status === 'success') {
                // --- SUCCESS ---
                successMessage.textContent = result.message || 'Login successful! Redirecting...';
                successCard.classList.remove('d-none');

                // Redirect the user to their QR page after 2 seconds
                setTimeout(() => {
                    window.location.href = result.redirectUrl;
                }, 2000);

            } else {
                // --- FAILURE ---
                errorMessage.textContent = result.message || 'An unknown error occurred.';
                errorCard.classList.remove('d-none');
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            }
        } catch (error) {
            console.error('Submission error:', error);
            errorMessage.textContent = 'A technical error occurred. Please try again.';
            errorCard.classList.remove('d-none');
            submitButton.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});
</script>

<?php
// --- 3. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 4. Define Page Variables ---
$page_title = 'Login';

// --- 5. Include the Master Layout ---
include 'layout.php'; // This layout file contains the navbar
?>