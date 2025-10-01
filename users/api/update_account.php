<?php
// users/api/update_account.php

session_start();
require_once '../../config/config.php';
header('Content-Type: application/json');

// --- 1. Security Check: Ensure user is logged in ---
if (!isset($_SESSION['attendee_logged_in']) || $_SESSION['attendee_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

// --- 2. Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$organization_title = $_GET['view'] ?? '';

if (empty($name) || empty($email) || empty($organization_title)) {
    echo json_encode(['status' => 'error', 'message' => 'Name and email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
    exit;
}

// --- 3. Database Update ---
$organization_id = null;
$original_email = $_SESSION['attendee_email']; // Use original email from session to find the record

// Get organization ID from view title
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE title = ?");
$stmt_org->bind_param("s", $organization_title);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
if ($org = $result_org->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt_org->close();

if (!$organization_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event context.']);
    exit;
}

// Prepare and execute the update
$stmt_update = $conn->prepare("UPDATE registered SET name = ?, email = ? WHERE email = ? AND organization_id = ?");
$stmt_update->bind_param("sssi", $name, $email, $original_email, $organization_id);

if ($stmt_update->execute()) {
    // --- 4. Update Session Variables ---
    $_SESSION['attendee_name'] = $name;
    $_SESSION['attendee_email'] = $email;

    echo json_encode([
        'status' => 'success',
        'message' => 'Details updated successfully!',
        'data' => ['name' => $name, 'email' => $email]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed. Please try again.']);
}

$stmt_update->close();
$conn->close();
?>