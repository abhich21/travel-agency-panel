<?php
// my_qr_code.php - Displays the user's unique QR code

// --- 1. SETUP ---
// Start the session to access session variables like the user's email
session_start();

// +++ START NEW PAGE GUARD +++
if (!isset($_SESSION['attendee_logged_in']) || $_SESSION['attendee_logged_in'] !== true) {
    $view_param = isset($_GET['view']) ? urlencode($_GET['view']) : '';
    header("Location: login.php?view=" . $view_param);
    exit;
}
// +++ END NEW PAGE GUARD +++

require_once '../config/config.php';

$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal';
$user_qr_code_url = '';
$user_name = 'Guest';

// --- 2. FETCH THEME AND USER DATA ---
if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    // Fetch theme data first
    $organization_title = $_GET['view'];
    $stmt_org = $conn->prepare("SELECT title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt_org->bind_param("s", $organization_title);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();
    if ($org_details = $result_org->fetch_assoc()) {
        $org_title = htmlspecialchars($org_details['title']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
    }
    $stmt_org->close();

    // Now, fetch the user's QR code using the email from our verified login session
if (isset($_SESSION['attendee_email'])) {
    $user_email = $_SESSION['attendee_email'];

    // Find the most recent registration for this email
    $stmt_user = $conn->prepare("SELECT name, qr_code FROM registered WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt_user->bind_param("s", $user_email);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($user_data = $result_user->fetch_assoc()) {
        $user_name = htmlspecialchars($user_data['name']);
        // The qr_code column now holds the full S3 URL
        $user_qr_code_url = htmlspecialchars($user_data['qr_code']);
    } else {
        // This can happen if the registered user was deleted after they logged in
        $user_name = "Error";
    }
    $stmt_user->close();
}
    
}

// --- 3. Start Output Buffering ---
ob_start();
?>

<!-- Page-specific styles and HTML for the QR Code Page -->
<style>
    .qr-code-header {
        padding: -4rem 1rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .qr-code-container {
        max-width: 500px;
        margin: 1rem auto;
        padding: 1rem;
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        text-align: center;
    }
    .qr-code-container img {
        max-width: 100%;
        width: 300px; /* Give the QR code a consistent size */
        height: auto;
        border: 5px solid #eee;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
</style>

<main>
    <div class="qr-code-header">
        <h1>Your Event QR Code</h1>
        <p class="lead">Welcome, <?php echo $user_name; ?>!</p>
    </div>

    <div class="container">
        <div class="qr-code-container">
            <?php if (!empty($user_qr_code_url)): ?>
                <p class="lead">This is your unique code for event check-in.</p>
                <hr>
                <!-- Display the image using the S3 URL from the database -->
                <img src="<?php echo $user_qr_code_url; ?>" alt="Your Personal QR Code">
                <p class="text-muted">Please present this code at the registration desk upon arrival. You can also access this page any time from the navigation menu.</p>
            <?php else: ?>
                <div class="alert alert-warning">
                    Could not find your registration details. Please try registering again or contact support if you believe this is an error.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'My QR Code';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>