<?php
// my_account.php - User's account page with Details, QR, and Ticket

// --- 1. SETUP AND PAGE GUARD ---
session_start();
require_once '../config/config.php';

// --- PAGE GUARD ---
// Redirect to login if not authenticated
if (!isset($_SESSION['attendee_logged_in']) || $_SESSION['attendee_logged_in'] !== true) {
    $view_param = isset($_GET['view']) ? urlencode($_GET['view']) : '';
    header("Location: login.php?view=" . $view_param);
    exit;
}

// --- 2. FETCH THEME AND USER DATA ---
$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal';
$user_data = []; // Will hold all user details
$form_fields = []; // Will hold the field labels

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    // Fetch theme data
    $organization_title = $_GET['view'];
    $stmt_org = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt_org->bind_param("s", $organization_title);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();
    
    if ($org_details = $result_org->fetch_assoc()) {
        $org_title = htmlspecialchars($org_details['title']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
        $organization_id = $org_details['id']; // Get org ID
        
        // --- Fetch User Data (using session email) ---
        if (isset($_SESSION['attendee_email'])) {
            $user_email = $_SESSION['attendee_email'];
            // Fetch all data for this user
            $stmt_user = $conn->prepare("SELECT * FROM registered WHERE email = ? AND organization_id = ? ORDER BY id DESC LIMIT 1");
            $stmt_user->bind_param("si", $user_email, $organization_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($data = $result_user->fetch_assoc()) {
                $user_data = $data;
            }
            $stmt_user->close();
        }

        // --- Fetch Field Labels (to display details nicely) ---
        $stmt_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
        $stmt_fields->bind_param("i", $organization_id);
        $stmt_fields->execute();
        $result_fields = $stmt_fields->get_result();
        if ($fields_data = $result_fields->fetch_assoc()) {
            // Convert the JSON array of fields into a simple 'field' => 'Label' map
            $decoded_fields = json_decode($fields_data['fields'], true);
            foreach ($decoded_fields as $field) {
                $form_fields[$field['field']] = $field['label'];
            }
        }
        $stmt_fields->close();
    }
    $stmt_org->close();
}

// --- 3. Start Output Buffering ---
ob_start();
?>

<style>
    .account-header {
        padding: 4rem 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .account-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 0;
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        overflow: hidden; /* To contain the nav-tabs border */
    }
    .nav-tabs {
        border-bottom: 3px solid <?php echo $nav_bg_color; ?>;
    }
    .nav-tabs .nav-link {
        color: #495057;
        padding: 1rem 1.5rem;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        color: <?php echo $nav_bg_color; ?>;
        background-color: #fff;
        border-bottom: 3px solid <?php echo $nav_bg_color; ?>;
    }
    .tab-content {
        padding: 2rem;
    }
    .qr-code-display {
        text-align: center;
    }
    .qr-code-display img {
        max-width: 100%;
        width: 300px;
        height: auto;
        border: 5px solid #eee;
        border-radius: 0.5rem;
    }
    
    /* Simple Ticket Stub CSS */
    .ticket-stub {
        max-width: 500px;
        margin: 1rem auto;
        border: 2px dashed <?php echo $nav_bg_color; ?>;
        border-radius: 10px;
        background: #fdfdfd;
        overflow: hidden;
    }
    .ticket-header {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        padding: 1.5rem;
        text-align: center;
    }
    .ticket-header h4 {
        margin: 0;
        font-weight: 300;
        letter-spacing: 1px;
    }
    .ticket-header h2 {
        margin: 0;
        font-weight: 700;
    }
    .ticket-body {
        padding: 2rem;
        text-align: center;
    }
    .ticket-body h5 {
        font-weight: 700;
        color: #333;
    }
    .ticket-body p {
        font-size: 1.1rem;
        color: #555;
        margin-bottom: 0;
    }
</style>

<main>
    <div class="account-header">
        <h1>My Account</h1>
        <p class="lead">Welcome, <?php echo htmlspecialchars($user_data['name'] ?? 'Guest'); ?>!</p>
    </div>

    <div class="account-container">
        <ul class="nav nav-tabs nav-fill" id="accountTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">
                    <i class="fas fa-user-circle me-2"></i>My Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr" type="button" role="tab" aria-controls="qr" aria-selected="false">
                    <i class="fas fa-qrcode me-2"></i>My QR Code
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ticket-tab" data-bs-toggle="tab" data-bs-target="#ticket" type="button" role="tab" aria-controls="ticket" aria-selected="false">
                    <i class="fas fa-ticket-alt me-2"></i>My Ticket
                </button>
            </li>
        </ul>

        <div class="tab-content" id="accountTabsContent">
            
            <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                <h3 class="mb-4">My Registration Details</h3>
                <?php if (!empty($user_data) && !empty($form_fields)): ?>
                    <dl class="row">
                        <?php foreach ($form_fields as $field_key => $field_label): ?>
                            <?php if (isset($user_data[$field_key]) && $field_key != 'password' && $field_key != 'govt_id_link'): ?>
                                <dt class="col-sm-3"><?php echo htmlspecialchars($field_label); ?></dt>
                                <dd class="col-sm-9"><?php echo htmlspecialchars($user_data[$field_key]); ?></dd>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                <?php else: ?>
                    <p>Could not load user details.</p>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="qr" role="tabpanel" aria-labelledby="qr-tab">
                <div class="qr-code-display">
                    <?php if (!empty($user_data['qr_code'])): ?>
                        <p class="lead">This is your unique code for event check-in.</p>
                        <img src="<?php echo htmlspecialchars($user_data['qr_code']); ?>" alt="Your Personal QR Code">
                    <?php else: ?>
                        <div class="alert alert-warning">Could not find your QR Code.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="ticket" role="tabpanel" aria-labelledby="ticket-tab">
                <div class="ticket-stub">
                    <div class="ticket-header">
                        <h4>EVENT TICKET FOR</h4>
                        <h2><?php echo $org_title; ?></h2>
                    </div>
                    <div class="ticket-body">
                        <h5><?php echo htmlspecialchars($user_data['name'] ?? 'Guest'); ?></h5>
                        <p><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'My Account';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>