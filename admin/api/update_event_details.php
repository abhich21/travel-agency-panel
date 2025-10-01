<?php
// admin/api/update_event_details.php

session_start();
require_once '../../config/config.php';

// --- 1. Security & Validation (No changes here) ---
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !isset($_SESSION['admin_user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- 2. Get Organization ID (No changes here) ---
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$stmt = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $admin_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($org = $result->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt->close();

if (!$organization_id) {
    echo json_encode(['success' => false, 'message' => 'Could not find an organization associated with this admin account.']);
    exit;
}

// --- 3. DYNAMICALLY BUILD THE UPDATE QUERY ---
$fields = [];
$params = [];
$types = '';

// Check for each possible field from our forms
if (isset($_POST['event_date'])) {
    $fields[] = 'event_date = ?';
    $params[] = $_POST['event_date'];
    $types .= 's';
}
if (isset($_POST['venue_name'])) {
    $fields[] = 'venue_name = ?';
    $params[] = trim($_POST['venue_name']);
    $types .= 's';
}
if (isset($_POST['venue_address'])) {
    $fields[] = 'venue_address = ?';
    $params[] = trim($_POST['venue_address']);
    $types .= 's';
}
if (isset($_POST['venue_url'])) {
    $fields[] = 'venue_url = ?';
    $params[] = trim($_POST['venue_url']);
    $types .= 's';
}
// Add our new homepage fields
if (isset($_POST['home_about_title'])) {
    $fields[] = 'home_about_title = ?';
    $params[] = trim($_POST['home_about_title']);
    $types .= 's';
}
if (isset($_POST['home_about_content'])) {
    $fields[] = 'home_about_content = ?';
    $params[] = $_POST['home_about_content']; // Don't trim, to preserve HTML formatting
    $types .= 's';
}

// If no fields were submitted, there's nothing to do.
if (empty($fields)) {
    echo json_encode(['success' => false, 'message' => 'No data submitted.']);
    exit;
}

// --- 4. EXECUTE THE QUERY ---
// First, ensure a row exists for this organization.
$conn->query("INSERT INTO event_details (organization_id) VALUES ($organization_id) ON DUPLICATE KEY UPDATE organization_id = $organization_id");

// Now, build and execute the dynamic UPDATE statement
$sql = "UPDATE event_details SET " . implode(', ', $fields) . " WHERE organization_id = ?";
$params[] = $organization_id;
$types .= 'i';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

$response = [];
if ($stmt->execute()) {
    $response = ['success' => true, 'message' => 'Event details updated successfully!'];
} else {
    $response = ['success' => false, 'message' => 'Failed to update event details. Please try again.'];
}
$stmt->close();
$conn->close();

// --- 5. Return JSON response ---
header('Content-Type: application/json');
echo json_encode($response);
exit;