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

// --- Fetch Cab Assignment Data ---
$cab_assignment = null;
if (!empty($user_data['id']) && isset($organization_id)) {
    $stmt_cab = $conn->prepare("
        SELECT 
            cs.schedule_date,
            cs.pickup_time,
            cs.trip_type,
            cs.pickup_location,
            cs.drop_location,
            cs.status,
            cs.notes,
            cd.cab_number,
            cd.cab_name,
            cd.cab_type,
            cd.driver_name,
            cd.driver_phone
        FROM cab_schedule cs
        JOIN cab_details cd ON cs.cab_id = cd.id
        WHERE cs.user_id = ? AND cs.organization_id = ? AND cs.status != 'cancelled'
        ORDER BY cs.schedule_date DESC, cs.pickup_time DESC
        LIMIT 1
    ");
    $stmt_cab->bind_param("ii", $user_data['id'], $organization_id);
    $stmt_cab->execute();
    $result_cab = $stmt_cab->get_result();
    if ($cab_data = $result_cab->fetch_assoc()) {
        $cab_assignment = $cab_data;
    }
    $stmt_cab->close();
}

// --- 3. Start Output Buffering ---
ob_start();
?>

<style>
    .account-header {
        padding: 2rem 1.5rem;
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
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cab-tab" data-bs-toggle="tab" data-bs-target="#cab" type="button" role="tab" aria-controls="cab" aria-selected="false">
                    <i class="fas fa-taxi me-2"></i>My Cab
                </button>
            </li>
        </ul>

        <div class="tab-content" id="accountTabsContent">
            
           <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">My Registration Details</h3>
        <button id="edit-details-btn" class="btn btn-outline-primary"><i class="fas fa-pencil-alt me-2"></i>Edit Details</button>
    </div>

    <div id="display-view">
        <?php if (!empty($user_data) && !empty($form_fields)): ?>
            <dl class="row">
                <?php foreach ($form_fields as $field_key => $field_label): ?>
                    <?php if (isset($user_data[$field_key]) && $field_key != 'password' && $field_key != 'govt_id_link'): ?>
                        <dt class="col-sm-3" data-field="<?php echo $field_key; ?>-label"><?php echo htmlspecialchars($field_label); ?></dt>
                        <dd class="col-sm-9" data-field="<?php echo $field_key; ?>-value"><?php echo htmlspecialchars($user_data[$field_key]); ?></dd>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        <?php else: ?>
            <p>Could not load user details.</p>
        <?php endif; ?>
    </div>

    <div id="edit-view" class="d-none">
        <div id="edit-alert-placeholder"></div>
        <form id="edit-account-form">
            <div class="mb-3">
                <label for="edit-name" class="form-label">Name</label>
                <input type="text" class="form-control" id="edit-name" name="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="edit-email" class="form-label">Email</label>
                <input type="email" class="form-control" id="edit-email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" id="cancel-edit-btn" class="btn btn-secondary me-2">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
    <div id="success-card-view" class="text-center p-5 d-none">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-4x text-success"></i>
                </div>
                <h2 class="card-title">Update Successful</h2>
                <p class="lead">Your account details have been saved.</p>
                <hr class="my-4">
                <button id="close-success-card-btn" class="btn btn-primary">Done</button>
            </div>
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
                <div class="text-center p-4">
                    <h3 class="mb-4"><i class="fas fa-ticket-alt me-2"></i>Your Event Ticket</h3>
                    
                    <!-- PDF Viewer Placeholder -->
                    <div class="pdf-viewer-placeholder mx-auto mb-4" style="max-width: 500px; background-color: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 0.5rem; padding: 3rem 2rem;">
                        <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                        <h5>Event Ticket</h5>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($user_data['name'] ?? 'Guest'); ?></p>
                        <p class="text-muted small"><?php echo $org_title; ?></p>
                        <hr>
                        <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Click below to download your personalized ticket</p>
                    </div>
                    
                    <!-- Download Button -->
                    <a href="api/download_ticket.php?view=<?php echo urlencode($org_title); ?>" class="btn btn-lg text-white" style="background-color: <?php echo $nav_bg_color; ?>;">
                        <i class="fas fa-download me-2"></i>Download Ticket
                    </a>
                    <p class="text-muted small mt-3"><i class="fas fa-file-pdf me-1"></i>PDF format</p>
                </div>
            </div>

            <!-- My Cab Tab -->
            <div class="tab-pane fade" id="cab" role="tabpanel" aria-labelledby="cab-tab">
                <div class="text-center">
                    <h3 class="mb-4"><i class="fas fa-taxi me-2"></i>Your Cab Assignment</h3>
                    <?php if ($cab_assignment): ?>
                        <div class="card mx-auto" style="max-width: 450px; border: 2px solid <?php echo $nav_bg_color; ?>;">
                            <div class="card-header text-white" style="background-color: <?php echo $nav_bg_color; ?>;">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($cab_assignment['cab_name'] ?: $cab_assignment['cab_number']); ?>
                                </h5>
                                <small><?php echo ucfirst($cab_assignment['cab_type']); ?></small>
                            </div>
                            <div class="card-body text-start">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($cab_assignment['schedule_date'])); ?>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <strong>Pickup Time:</strong> <?php echo date('h:i A', strtotime($cab_assignment['pickup_time'])); ?>
                                </div>
                                <div class="mb-3">
                                    <i class="fas fa-route text-primary me-2"></i>
                                    <strong>Trip Type:</strong> 
                                    <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $cab_assignment['trip_type'])); ?></span>
                                </div>
                                <?php if ($cab_assignment['pickup_location']): ?>
                                <div class="mb-3">
                                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                                    <strong>Pickup:</strong> <?php echo htmlspecialchars($cab_assignment['pickup_location']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($cab_assignment['drop_location']): ?>
                                <div class="mb-3">
                                    <i class="fas fa-map-pin text-danger me-2"></i>
                                    <strong>Drop:</strong> <?php echo htmlspecialchars($cab_assignment['drop_location']); ?>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="mb-2">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <strong>Driver:</strong> <?php echo htmlspecialchars($cab_assignment['driver_name'] ?: 'TBA'); ?>
                                </div>
                                <?php if ($cab_assignment['driver_phone']): ?>
                                <div class="mb-3">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <strong>Phone:</strong> 
                                    <a href="tel:<?php echo $cab_assignment['driver_phone']; ?>">
                                        <?php echo htmlspecialchars($cab_assignment['driver_phone']); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <?php 
                                    $status_classes = [
                                        'scheduled' => 'bg-info',
                                        'in_progress' => 'bg-warning text-dark',
                                        'completed' => 'bg-success'
                                    ];
                                    $status_class = $status_classes[$cab_assignment['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> fs-6">
                                        <?php echo ucfirst(str_replace('_', ' ', $cab_assignment['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No cab has been assigned to you yet. Please check back later.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Get all the elements we need
    const editBtn = document.getElementById('edit-details-btn');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const displayView = document.getElementById('display-view');
    const editView = document.getElementById('edit-view');
    const editForm = document.getElementById('edit-account-form');
    const alertPlaceholder = document.getElementById('edit-alert-placeholder');
    
    // NEW: Get the success card elements
    const successCardView = document.getElementById('success-card-view');
    const closeSuccessCardBtn = document.getElementById('close-success-card-btn');

    // Function to show an alert message (still used for errors)
    const showAlert = (message, type) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = `<div class="alert alert-${type} alert-dismissible" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        alertPlaceholder.append(wrapper);
    };

    // Show the edit form when "Edit" is clicked
    editBtn.addEventListener('click', () => {
        displayView.classList.add('d-none');
        editBtn.classList.add('d-none'); // Hide the edit button itself
        editView.classList.remove('d-none');
    });

    // Go back to the display view when "Cancel" is clicked
    cancelBtn.addEventListener('click', () => {
        editView.classList.add('d-none');
        displayView.classList.remove('d-none');
        editBtn.classList.remove('d-none'); // Show the edit button again
        alertPlaceholder.innerHTML = ''; 
    });
    
    // NEW: Go back to display view when "Done" on success card is clicked
    closeSuccessCardBtn.addEventListener('click', () => {
        successCardView.classList.add('d-none');
        displayView.classList.remove('d-none');
        editBtn.classList.remove('d-none'); // Show the edit button again
    });

    // Handle the form submission
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitButton = editForm.querySelector('button[type="submit"]');
        const spinner = submitButton.querySelector('.spinner-border');

        submitButton.disabled = true;
        spinner.classList.remove('d-none');
        alertPlaceholder.innerHTML = '';

        const formData = new FormData(editForm);
        const currentUrl = new URL(window.location.href);
        const viewParam = currentUrl.searchParams.get('view');

        try {
            const response = await fetch(`api/update_account.php?view=${viewParam}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Update the display text in the background
                document.querySelector('[data-field="name-value"]').textContent = result.data.name;
                document.querySelector('[data-field="email-value"]').textContent = result.data.email;
                document.querySelector('.account-header h1 + p').textContent = `Welcome, ${result.data.name}!`;
                document.querySelector('.ticket-body h5').textContent = result.data.name;
                document.querySelector('.ticket-body p').textContent = result.data.email;

                // Hide the edit form and show the success card
                editView.classList.add('d-none');
                successCardView.classList.remove('d-none');

            } else {
                showAlert(result.message || 'An error occurred.', 'danger');
            }
        } catch (error) {
            showAlert('A technical error occurred. Please try again.', 'danger');
        } finally {
            submitButton.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});
</script>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'My Account';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>