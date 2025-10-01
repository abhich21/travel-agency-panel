<?php
// registration.php - Dynamic Event Registration Page
// +++ ADD THESE TWO LINES FOR TEMPORARY DEBUGGING +++
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Also log to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');


// +++ ADD THIS LINE FOR OUR TEST +++



// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';
require_once '../vendor/autoload.php';

// +++ ADD THIS NEW BLOCK OF USE STATEMENTS +++
// use Endroid\QrCode\Builder\Builder;
// use Endroid\QrCode\Encoding\Encoding;
// use Endroid\QrCode\ErrorCorrectionLevel;
// use Endroid\QrCode\RoundBlockSizeMode;
// use Endroid\QrCode\Writer\PngWriter;

use Endroid\QrCode\QrCode;
// +++ END OF NEW BLOCK +++

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
    
    // --- Data Validation ---
    $valid_data = [];
    $errors = [];
     // +++ START NEW PASSWORD VALIDATION +++
    if (!isset($_POST['password']) || empty(trim($_POST['password']))) {
        $errors[] = "Password is required.";
    } elseif (!isset($_POST['confirm_password']) || trim($_POST['password']) !== trim($_POST['confirm_password'])) {
        $errors[] = "Passwords do not match.";
    }
    // +++ END NEW PASSWORD VALIDATION +++
    foreach ($form_fields as $field) {
        $column = $field['field'];
        if ($column == 'govt_id_link') continue; // Skip file upload for now
        
        if (isset($_POST[$column]) && !empty(trim($_POST[$column]))) {
            $valid_data[$column] = trim($_POST[$column]);
        } else {
            // You can add more specific required fields here if needed
            if (in_array($column, ['name', 'email'])) {
                 $errors[] = "The " . htmlspecialchars($field['label']) . " field is required.";
            }
        }
    }
       if (isset($_POST['password'])) {
        $valid_data['password'] = trim($_POST['password']);
    }
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => implode("\n", $errors)]);
        exit;
    }
      

    // --- Government ID Upload ---
    $govt_id_link = null;
    if (isset($_FILES['govt_id_link']) && $_FILES['govt_id_link']['error'] === UPLOAD_ERR_OK) {
        $org_title_safe = str_replace(' ', '-', strtolower($org_title));
        $upload_result = uploadToS3($s3Client, $bucketName, $_FILES['govt_id_link'], $org_title_safe, 'govt-ids');
        if ($upload_result['success']) {
            $govt_id_link = $upload_result['url'];
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload Government ID: ' . $upload_result['error']]);
            exit;
        }
    }

// In registration.php, replace the old code with this:

    // --- QR Code Generation and Upload ---
    $qr_code_link = null;
    $qr_data_string = $valid_data['email'] ?? $valid_data['phone'] ?? 'user_' . time();
    $org_title_safe = str_replace(' ', '-', strtolower($org_title));

    // Call our new, reliable function from helper_function.php
    $qr_result = generateAndUploadQrCode($s3Client, $bucketName, $qr_data_string, $org_title_safe);

    // Check if the function succeeded or failed
    if ($qr_result['success']) {
        // If it worked, get the URL of the uploaded QR code
        $qr_code_link = $qr_result['url'];
    } else {
        // If it failed, stop everything and report the specific error message
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $qr_result['error']]);
        exit;
    }

    // --- Database Insertion ---

    // --- Database Insertion ---
// --- Database Insertion (Safe Version) ---
$columns_str = implode(", ", array_map(function($col) { return "`$col`"; }, array_keys($valid_data)));
$columns_str .= ", `govt_id_link`, `qr_code`, `organization_id`";

$placeholders_str = implode(", ", array_fill(0, count($valid_data), '?'));
$placeholders_str .= ", ?, ?, ?";

// Types: all strings for form fields, govt_id_link (string), qr_code (string), organization_id (int)
$types_str = str_repeat('s', count($valid_data)) . "ssi";

$params = array_values($valid_data);
$params[] = $govt_id_link;
$params[] = $qr_code_link;
$params[] = $organization_id;

$sql = "INSERT INTO registered ($columns_str) VALUES ($placeholders_str)";

// ---- Safety Checks ----

// 1. Validate count
if (strlen($types_str) !== count($params)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => "Param mismatch: types_str length (" . strlen($types_str) . 
                     ") != params count (" . count($params) . ")",
        'debug' => [
            'sql' => $sql,
            'types_str' => $types_str,
            'params' => $params
        ]
    ]);
    exit;
}

// 2. Prepare
$stmt_insert = $conn->prepare($sql);
if (!$stmt_insert) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

// 3. Bind
if (!$stmt_insert->bind_param($types_str, ...$params)) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Bind failed: ' . $stmt_insert->error
    ]);
    exit;
}



    // 4. Execute
    if ($stmt_insert->execute()) {
        
        // +++ START NEW AUTO-LOGIN LOGIC +++
        // The user was created successfully, so log them in.
        session_regenerate_id(true); // Secure the session
        $_SESSION['attendee_logged_in'] = true;
        $_SESSION['attendee_email'] = $valid_data['email'];
        
        // We can also store their name if it's available
        if (isset($valid_data['name'])) {
            $_SESSION['attendee_name'] = $valid_data['name'];
        }
        session_write_close(); // Save and close the session
        // +++ END NEW AUTO-LOGIN LOGIC +++

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
    } else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Execute failed: ' . $stmt_insert->error
    ]);
}
exit;
}


// --- 4. Start Output Buffering ---
ob_start();
?>

<!-- Page-specific styles and HTML for the Registration Page -->
<style>

    .hero-background-video {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    z-index: 1; /* Sits behind content */
    overflow: hidden; /* Hides any part of the video spilling out */
    background-attachment: fixed !important; /* This keeps the image still (parallax) */
}

.hero-background-video video {
    /* This combo acts like 'background-size: cover' for a video */
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    
    /* This keeps your scroll-blur effect */
    transition: filter 0.2s ease-out;
}

.hero-overlay {
    /* This is the transparent black gradient */
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6));
    z-index: 2; /* Sits on top of the video */
}


  .hero-section {
    /* This is now just a container */
    position: relative;
    overflow: hidden; /* This contains the blurred edges of the image */
}

    /* -- Define animation for fade-in effect -- */
    @keyframes fadeInSlideUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .hero-content {
    position: relative;
    z-index: 3; /* This puts your text on top of the overlay */
    color: white;
    text-align: center;
    padding: 5rem 1.5rem; /* This gives the hero section its size */

    /* You can add your fixed height rules here if you want */
    height: 30vh; 
    display: flex;
    flex-direction: column;
    justify-content: center;
     overflow: hidden;
}

    .registration-form-container {
        max-width: 700px;
        margin: 2rem auto 3rem auto; /* Pulls it up, adds bottom margin */
        padding: 1.5rem 1rem; /* Increased padding */
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);

        /* -- EFFECT 1: Add fade-in animation to the whole form -- */
        animation: fadeInSlideUp 0.6s ease-out;
    }

    .form-label {
        /* -- EFFECT 2: Modernize labels -- */
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
    }

    .form-control, .form-select {
        /* -- EFFECT 3: Flatter, bottom-border inputs -- */
        border: none;
        border-radius: 0; /* Remove radius for a flat look */
        border-bottom: 2px solid #dee2e6;
        padding-left: 2px; /* Align with label text */
        
        /* -- EFFECT 4: Smooth transition for focus -- */
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        /* -- EFFECT 4 (Cont.): New focus style -- */
        border-color: <?php echo $nav_bg_color; ?>; /* Uses your theme color */
        box-shadow: none; /* Removes the default Bootstrap glow */
        background-color: <?php echo $nav_bg_color . '1A'; ?>; /* Faint theme background */
    }

    /* -- EXCEPTION: File inputs look bad with bottom-border-only. -- */
    /* -- This resets the file input to look normal. -- */
    .form-control[type="file"] {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding-left: 0.75rem;
    }
    .form-control[type="file"]:focus {
        border-color: <?php echo $nav_bg_color; ?>;
        box-shadow: 0 0 0 0.25rem <?php echo $nav_bg_color . '40'; ?>;
        background-color: #fff;
    }


    .btn-submit {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        font-weight: 500;
        
        /* -- EFFECT 5: Add transition for hover effect -- */
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        /* -- EFFECT 6: Modern hover effect (lift and shadow) -- */
        opacity: 1; /* Override original 0.9 */
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* -- EFFECT 7: Staggered animation for form fields -- */
    #registrationForm .row > div {
        animation: fadeInSlideUp 0.5s ease-out forwards;
        opacity: 0; /* Start hidden */
    }

    /* -- Apply animation delays to each field -- */
    <?php
    $max_fields = 12; // Animate up to 12 fields
    for ($i = 1; $i <= $max_fields; $i++) {
        $delay = $i * 0.07;
        echo "#registrationForm .row > div:nth-child($i) { animation-delay: {$delay}s; }\n";
    }
    ?>
</style>

<main>
    <div class="hero-section">

       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/register1.mp4">
        </video>
     </div>

     <div class="hero-content">
        <h1>Register yourself for the event.</h1>
        
    </div>

</div>

    <div class="registration-form-container">
        <?php if (!empty($form_fields)): ?>
      
        <form id="registrationForm" novalidate enctype="multipart/form-data">
                <div class="row g-3">
                    <?php foreach ($form_fields as $field): 
                        $field_name = htmlspecialchars($field['field']);
                        $label = htmlspecialchars($field['label']);
                    ?>
                        <div class="col-12">
                            <label for="<?php echo $field_name; ?>" class="form-label"><?php echo $label; ?></label>

                            <?php if ($field_name === 'gender'): // +++ START GENDER DROPDOWN +++ ?>
                                
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="" selected disabled>Please select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Others">Others</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select your gender.
                                </div>
                            
                            <?php else: // +++ ALL OTHER FIELDS (OLD LOGIC) +++
                                // Determine input type for other fields
                                $input_type = ($field_name === 'email') ? 'email' : (($field_name === 'phone') ? 'tel' : (($field_name === 'govt_id_link') ? 'file' : 'text'));
                            ?>
                                <input type="<?php echo $input_type; ?>" class="form-control" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid <?php echo strtolower($label); ?>.
                                </div>
                            <?php endif; // +++ END GENDER DROPDOWN +++ ?>
                            
                        </div>
                    <?php endforeach; ?>
                      <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Password is required.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">
                            Passwords do not match.
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <button class="w-100 btn btn-submit" type="submit">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    Register Now
                </button>
                  <div class="text-center mt-3">
                    <p class="mb-0">
                        Already registered? 
                        <a href="login.php?view=<?php echo urlencode($org_title); ?>" class="login-link">Log In</a>
                    </p>
                </div>
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
const bgVideo = document.querySelector('.hero-background-video video');
    
    if (bgVideo) {
        window.addEventListener('scroll', () => {
            const scrollPos = window.scrollY;

            // --- 1. Calculate Parallax ---
            // This moves the video vertically at 50% of the scroll speed.
            // This creates the "parallax" effect.
            const parallaxOffset = scrollPos * 0.9;

            // --- 2. Calculate Blur ---
            let blurAmount = (scrollPos / 300) * 8; 
            if (blurAmount > 8) {
                blurAmount = 8;
            }
            
            // --- 3. Apply Both Styles ---
            // We combine the original centering transform with our new parallax 'translateY'
            // and apply the blur filter.
            bgVideo.style.transform = `translate(-50%, -50%) translateY(${parallaxOffset}px)`;
            bgVideo.style.filter = `blur(${blurAmount}px)`;
        });
    }


</script>

<?php
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Registration';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>
