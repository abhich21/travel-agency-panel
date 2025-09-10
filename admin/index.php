<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

// Check if admin is logged in, otherwise redirect to login page
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

$user_name = "Admin";
if (isset($_SESSION['admin_user_id'])) {
    $stmt = $conn->prepare("SELECT user_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $user_name = htmlspecialchars($user['user_name']);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Admin Dashboard</title>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="jumbotron">
                <h1 class="display-4">Welcome, <?php echo $user_name; ?>!</h1>
                <p class="lead">This is your admin dashboard. You can manage registered users and other settings from here.</p>
                <hr class="my-4">
                <p>Use the navigation menu to access different features of the panel.</p>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script>
        // Display a one-time success or error toast on page load
        <?php
        if (!empty($success)) {
            echo "showSweetAlert('success', '" . addslashes($success) . "', true);";
        }
        if (!empty($error)) {
            echo "showSweetAlert('error', '" . addslashes($error) . "');";
        }
        ?>
    </script>
</body>
</html>
