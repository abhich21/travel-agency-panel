<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $userName = $_POST['user_name'];
    $password = $_POST['password'];
    $title = $_POST['title'];
    $bgColor = $_POST['bg_color'];
    $textColor = $_POST['text_color'];
    $role = 'admin'; // Always create an admin user from this page

    // Validate inputs
    if (empty($userName) || empty($password) || empty($title)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: create_user.php');
        exit;
    }

    // Check if a file was uploaded
    $logo_url = null;
    if (isset($_FILES['logo_url']) && $_FILES['logo_url']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadToS3($s3Client, $bucketName, $_FILES['logo_url'], $title, 'logos');
        
        if ($upload_result['success']) {
            $logo_url = $upload_result['url'];
        } else {
            $_SESSION['error'] = 'Failed to upload logo to S3: ' . $upload_result['error'];
            header('Location: create_user.php');
            exit;
        }
    }

    $conn->begin_transaction();

    try {
        // Store password as plain text (not recommended)
        $unhashed_password = $password;

        // Insert new user
        $sqlUser = "INSERT INTO users (user_name, password, role) VALUES (?, ?, ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("sss", $userName, $unhashed_password, $role);
        if (!$stmtUser->execute()) {
            throw new Exception("Error inserting user: " . $stmtUser->error);
        }

        $userId = $stmtUser->insert_id;
        $stmtUser->close();

        // Insert new organization
        $sqlOrg = "INSERT INTO organizations (user_id, title, logo_url, bg_color, text_color) VALUES (?, ?, ?, ?, ?)";
        $stmtOrg = $conn->prepare($sqlOrg);
        $stmtOrg->bind_param("issss", $userId, $title, $logo_url, $bgColor, $textColor);
        if (!$stmtOrg->execute()) {
            throw new Exception("Error inserting organization: " . $stmtOrg->error);
        }
        $stmtOrg->close();

        $conn->commit();
        $_SESSION['success'] = 'New admin user and organization created successfully.';
        header('Location: create_user.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // If logo was uploaded, delete it due to a database error
        if ($logo_url) {
            deleteFromS3($s3Client, $bucketName, $logo_url);
        }
        $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
        header('Location: create_user.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'head_includes.php'; ?>
    <title>Create User</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .color-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-input-container input[type="text"] {
            flex-grow: 1;
        }
        .color-swatch {
            width: 38px;
            height: 38px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
        }
        .password-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
        }
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="loader-overlay" id="loader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="card p-4 mx-auto mb-5" style="max-width: 600px;">
            <h2 class="text-center mb-4">Create New Admin User</h2>
            <form action="create_user.php" method="POST" enctype="multipart/form-data" id="createUserForm">
                <div class="mb-3">
                    <label for="user_name" class="form-label">User Name</label>
                    <input type="text" class="form-control" id="user_name" name="user_name" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="password-toggle-btn">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <hr>
                <h4 class="text-center my-4">Organization Details</h4>
                <div class="mb-3">
                    <label for="title" class="form-label">Organization Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="logo_url" class="form-label">Logo</label>
                    <input type="file" class="form-control" id="logo_url" name="logo_url">
                </div>
                
                <!-- Background Color Input -->
                <div class="mb-3">
                    <label for="bg_color" class="form-label">Background Color</label>
                    <div class="input-group color-input-container">
                        <input type="text" class="form-control" id="bg_color_hex" name="bg_color" value="#000000">
                        <input type="color" class="form-control form-control-color" id="bg_color_picker" value="#000000">
                    </div>
                </div>

                <!-- Text Color Input -->
                <div class="mb-3">
                    <label for="text_color" class="form-label">Text Color</label>
                    <div class="input-group color-input-container">
                        <input type="text" class="form-control" id="text_color_hex" name="text_color" value="#ffffff">
                        <input type="color" class="form-control form-control-color" id="text_color_picker" value="#ffffff">
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary mt-3">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script>

        // Synchronize the background color picker and text input
        const bgColorPicker = document.getElementById('bg_color_picker');
        const bgColorHex = document.getElementById('bg_color_hex');

        bgColorPicker.addEventListener('input', (e) => {
            bgColorHex.value = e.target.value;
        });

        bgColorHex.addEventListener('input', (e) => {
            // Optional: Add basic hex code validation
            const hex = e.target.value;
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                bgColorPicker.value = hex;
            }
        });

        // Synchronize the text color picker and text input
        const textColorPicker = document.getElementById('text_color_picker');
        const textColorHex = document.getElementById('text_color_hex');

        textColorPicker.addEventListener('input', (e) => {
            textColorHex.value = e.target.value;
        });

        textColorHex.addEventListener('input', (e) => {
            // Optional: Add basic hex code validation
            const hex = e.target.value;
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                textColorPicker.value = hex;
            }
        });

        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const passwordToggleBtn = document.getElementById('password-toggle-btn');
        const passwordToggleIcon = passwordToggleBtn.querySelector('i');

        passwordToggleBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle the eye icon
            if (type === 'password') {
                passwordToggleIcon.classList.remove('fa-eye-slash');
                passwordToggleIcon.classList.add('fa-eye');
            } else {
                passwordToggleIcon.classList.remove('fa-eye');
                passwordToggleIcon.classList.add('fa-eye-slash');
            }
        });
        
        // Loader functionality
        const form = document.getElementById('createUserForm');
        const loader = document.getElementById('loader');

        form.addEventListener('submit', function() {
            loader.style.display = 'flex';
        });

        // Display success or error toast
        <?php
        if (!empty($success)) {
            echo "showSweetAlert('success', '" . addslashes($success) . "');";
        }
        if (!empty($error)) {
            echo "showSweetAlert('error', '" . addslashes($error) . "');";
        }
        ?>
    </script>
</body>
</html>
