<?php
// login_verify.php - Processes Attendee Login (Email OR Phone) - AJAX VERSION

// --- 1. START SESSION AND LOAD DATABASE ---
session_start();
require_once '../config/config.php';

// --- SET JSON HEADER ---
header('Content-Type: application/json');

$organization_id = null;
$org_title_safe = '';

// --- 2. GET ORGANIZATION ID FROM THE ?VIEW PARAMETER ---
if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    $organization_title = $_GET['view'];
    $org_title_safe = htmlspecialchars($organization_title);

    $stmt_org = $conn->prepare("SELECT id FROM organizations WHERE title = ? LIMIT 1");
    $stmt_org->bind_param("s", $organization_title);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();
    
    if ($org_details = $result_org->fetch_assoc()) {
        $organization_id = $org_details['id'];
    }
    $stmt_org->close();
}

// Check for required fields
if ($organization_id === null || !isset($_POST['login_identifier']) || !isset($_POST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request. Please try again.']);
    exit;
}

// --- 3. GET SUBMITTED DATA ---
$login_identifier = $_POST['login_identifier']; 
$plain_password = $_POST['password'];

// --- 4. THE (INSECURE) LOGIN CHECK ---
$sql = "SELECT id, name, email FROM registered 
        WHERE (email = ? OR phone = ?) 
        AND password = ? 
        AND organization_id = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database query error.']);
    exit;
}

$stmt->bind_param("sssi", $login_identifier, $login_identifier, $plain_password, $organization_id);
$stmt->execute();
$result = $stmt->get_result();

// --- 5. HANDLE LOGIN SUCCESS OR FAILURE ---
if ($result->num_rows === 1) {
    // --- LOGIN SUCCESS ---
    $user = $result->fetch_assoc();
    
    session_regenerate_id(true); 
    $_SESSION['attendee_logged_in'] = true;
    $_SESSION['attendee_email'] = $user['email']; 
    $_SESSION['attendee_name'] = $user['name'];
    session_write_close(); // Save session

    // Send success JSON with the URL to redirect to
    echo json_encode([
        'status' => 'success', 
        'message' => 'Login successful! Redirecting...',
        'redirectUrl' => 'my_qr_code.php?view=' . urlencode($org_title_safe)
    ]);
    exit;

} else {
    // --- LOGIN FAILURE ---
    echo json_encode(['status' => 'error', 'message' => 'Invalid Email/Phone or Password.']);
    exit;
}
?>