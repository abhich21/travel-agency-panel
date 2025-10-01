<?php
// admin/edit_home.php

session_start();
require_once '../config/config.php';

// --- 1. Security Check: Ensure an admin is logged in ---
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$current_title = 'About The Event';
$current_content = '<p>Enter your event description here.</p>';

// --- 2. Get the Organization ID for the logged-in admin ---
$stmt = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $admin_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($org = $result->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt->close();

if (!$organization_id) {
    die("Could not find an organization associated with this admin account.");
}

// --- 3. Fetch current content from the database to pre-fill the editor ---
$stmt_content = $conn->prepare("SELECT home_about_title, home_about_content FROM event_details WHERE organization_id = ? LIMIT 1");
$stmt_content->bind_param("i", $organization_id);
$stmt_content->execute();
$result_content = $stmt_content->get_result();
if ($content = $result_content->fetch_assoc()) {
    if (!empty($content['home_about_title'])) {
        $current_title = htmlspecialchars($content['home_about_title']);
    }
    if (!empty($content['home_about_content'])) {
        // We don't use htmlspecialchars here because we want to render the HTML in the editor
        $current_content = $content['home_about_content'];
    }
}
$stmt_content->close();

$user_name = "Admin";
if (isset($_SESSION['admin_user_id'])) {
    // Code to get admin user_name for navbar (optional, but good for consistency)
    $stmt_user = $conn->prepare("SELECT user_name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $_SESSION['admin_user_id']);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    if ($user) {
        $user_name = htmlspecialchars($user['user_name']);
    }
    $stmt_user->close();
}

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Edit Home Page Content</title>
    <script src="https://cdn.tiny.cloud/1/1lpfjgdxu7dqlbtcuzjkds9eot8zzw6q8hjuk7el0rl2cwft/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Edit Home Page Content</h4>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
                <div class="card-body">
                    <form id="homeContentForm">
                        <div class="mb-3">
                            <label for="home_about_title" class="form-label">Section Title</label>
                            <input type="text" class="form-control" id="home_about_title" name="home_about_title" value="<?php echo $current_title; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="home_about_content" class="form-label">Main Content</label>
                            <textarea id="home_about_content" name="home_about_content"><?php echo $current_content; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <script>
        // Initialize TinyMCE on our textarea
        tinymce.init({
            selector: 'textarea#home_about_content',
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code'
        });

        // Handle the form submission with AJAX
        $(document).ready(function() {
            $('#homeContentForm').on('submit', function(e) {
                e.preventDefault();
                
                // We need to tell TinyMCE to update the underlying textarea before we submit
                tinymce.triggerSave();

                const formData = new FormData(this);

                $.ajax({
                    url: 'api/update_event_details.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSweetAlert('success', response.message);
                        } else {
                            showSweetAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showSweetAlert('error', 'An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>