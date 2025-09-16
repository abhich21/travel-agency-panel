<?php
// logout.php - Destroys the attendee session

session_start();

// Capture the current view parameter before we destroy the session
$view_param = '';
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $view_param = '?view=' . urlencode($_GET['view']);
}

// Unset all session variables
session_unset();
// Destroy the session completely
session_destroy();

// Redirect back to the public home page (or login page) with the theme parameter
header("Location: home.php" . $view_param); // Assumes you have a home.php, otherwise use login.php
exit;
?>