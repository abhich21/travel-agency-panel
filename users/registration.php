<?php
// registration.php - Dynamic Event Registration Page

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal'; // Default title
$organization_id = null;
$form_fields = []; // This will hold the fields for the dynamic form

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

        // --- 2. FETCH DYNAMIC REGISTRATION FIELDS ---
        $stmt_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
        $stmt_fields->bind_param("i", $organization_id);
        $stmt_fields->execute();
        $result_fields = $stmt_fields->get_result();
        if ($fields_data = $result_fields->fetch_assoc()) {
            $form_fields = json_decode($fields_data['fields'], true);
        }
        $stmt_fields->close();
    }
    $stmt->close();
}

// --- 3. HANDLE FORM SUBMISSION (SERVER-SIDE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($organization_id)) {
    
    // Place this inside the "if ($_SERVER['REQUEST_METHOD'] === 'POST')" block in registration.php

$govt_id_photo_url = null;
if (isset($_FILES['govt_id_link']) && $_FILES['govt_id_link']['error'] === UPLOAD_ERR_OK) {
    
    // We need a unique identifier for the user. Email is a good choice.
    $user_identifier = isset($_POST['email']) ? $_POST['email'] : 'user_' . time();

    // Call the same helper function you use in create_user.php
    // The helper function needs to be included, likely in config.php or helper_function.php
    $upload_result = uploadToS3($s3Client, $bucketName, $_FILES['govt_id_link'], $user_identifier, 'govt-ids');
    
    if ($upload_result['success']) {
        $govt_id_photo_url = $upload_result['url'];
    } else {
        // If the upload fails, stop and send an error message
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload ID photo: ' . $upload_result['error']]);
        exit;
    }
}
    // Dynamically build the list of columns and placeholders for the SQL query
    $columns = ['organization_id'];
    $placeholders = ['?'];
    $bind_types = 'i';
    $bind_values = [$organization_id];

    // ... and replace it with this one.
foreach ($form_fields as $field) {
    $field_name = $field['field'];
    
    // Check if it's our special photo field
    if ($field_name === 'govt_id_link' && $govt_id_photo_url !== null) {
        $columns[] = '`govt_id_link`';
        $placeholders[] = '?';
        $bind_types .= 's';
        $bind_values[] = $govt_id_photo_url;
    } 
    // Handle all other standard text fields
    elseif (isset($_POST[$field_name]) && $field_name !== 'govt_id_link') {
        $columns[] = "`" . $field_name . "`";
        $placeholders[] = '?';
        $bind_types .= 's';
        $bind_values[] = htmlspecialchars(strip_tags($_POST[$field_name]));
    }
}

    if (count($columns) > 1) { // Only proceed if there are fields to insert
        $sql = "INSERT INTO registered (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt_insert = $conn->prepare($sql);
        // Use the splat operator (...) to pass array values as individual arguments
        $stmt_insert->bind_param($bind_types, ...$bind_values);

        if ($stmt_insert->execute()) {
            // Send a success response back to the JavaScript
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
        }
        $stmt_insert->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No fields to register.']);
    }
    exit(); // Stop script execution after handling the POST request
}


// --- 4. Start Output Buffering ---
ob_start();
?>

<!-- Page-specific styles and HTML for the Registration Page -->
<style>
    .registration-header {
        padding: 4rem 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .registration-form-container {
        max-width: 700px;
        margin: 3rem auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .form-control:focus {
        border-color: <?php echo $nav_bg_color; ?>;
        box-shadow: 0 0 0 0.25rem <?php echo $nav_bg_color . '40'; ?>; /* 40 adds transparency */
    }
    .btn-submit {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
    }
    .btn-submit:hover {
        opacity: 0.9;
    }
</style>

<main>
    <div class="registration-header">
        <h1>Event Registration</h1>
        <p class="lead">Complete the form below to secure your spot for <?php echo $org_title; ?>.</p>
    </div>

    <div class="registration-form-container">
        <?php if (!empty($form_fields)): ?>
      
        <form id="registrationForm" novalidate enctype="multipart/form-data">
                <div class="row g-3">
                    <?php foreach ($form_fields as $field): 
                        $field_name = htmlspecialchars($field['field']);
                        $label = htmlspecialchars($field['label']);
                        // in registration.php
            $input_type = ($field_name === 'email') ? 'email' : (($field_name === 'phone') ? 'tel' : (($field_name === 'govt_id_link') ? 'file' : 'text'));
                    ?>
                        <div class="col-12">
                            <label for="<?php echo $field_name; ?>" class="form-label"><?php echo $label; ?></label>
                            <input type="<?php echo $input_type; ?>" class="form-control" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" required>
                            <div class="invalid-feedback">
                                Please provide a valid <?php echo strtolower($label); ?>.
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr class="my-4">
                <button class="w-100 btn btn-submit" type="submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Register Now
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                Registration is not currently open for this event. Please check back later.
            </div>
        <?php endif; ?>
    </div>
       <div id="successCard" class="registration-form-container text-center d-none">
        <div class="card-body">
            <div class="mb-4">
                <i class="fas fa-check-circle fa-4x text-success"></i>
            </div>
            <h2 class="card-title">Thank You!</h2>
            <p class="lead">Your registration was successful.</p>
            <p>You can now access your event details, including your personal QR code and ticket information, from the navigation menu.</p>
            <hr class="my-4">
            <a href="my_qr_code.php?view=<?php echo urlencode($org_title); ?>" class="btn btn-submit">
                View My QR Code
            </a>
        </div>
    </div>
</main>

<script>
// --- JAVASCRIPT FOR FORM VALIDATION AND AJAX SUBMISSION ---
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        form.classList.add('was-validated');

        // Disable button and show spinner
        submitButton.disabled = true;
        spinner.classList.remove('d-none');

        const formData = new FormData(form);
        const currentUrl = window.location.href;

        try {
            const response = await fetch(currentUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

          if (result.status === 'success') {
    // Set the local storage flag to update the navbar immediately
    const orgTitle = '<?php echo urlencode($org_title); ?>';
    localStorage.setItem('event_user_registered_' + orgTitle, 'true');

    // Dispatch a custom event to notify the navbar to update
    window.dispatchEvent(new CustomEvent('registrationStatusChanged'));

    // Get the form and the new success card elements
    const formContainer = document.querySelector('.registration-form-container');
    const successCard = document.getElementById('successCard');

    // Hide the form and show the success card
    if(formContainer) formContainer.classList.add('d-none');
    if(successCard) successCard.classList.remove('d-none');

} else {
                // Show error message
                alert('Error: ' + result.message);
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('A technical error occurred. Please try again.');
            submitButton.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});
</script>

<?php
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Registration';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>
