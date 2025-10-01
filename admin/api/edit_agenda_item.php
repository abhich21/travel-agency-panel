<?php
// admin/api/edit_agenda_item.php

session_start();
require_once '../../config/config.php';

// Security & Validation
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

// Get Organization ID
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
if ($org = $result_org->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt_org->close();

if (!$organization_id) {
    echo json_encode(['success' => false, 'message' => 'Could not find an organization for this admin.']);
    exit;
}

// Get data from form
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$day_number = filter_input(INPUT_POST, 'day_number', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$item_time = trim($_POST['item_time'] ?? '');
$description = $_POST['description'] ?? ''; // Don't trim HTML content
$icon_class = trim($_POST['icon_class'] ?? 'fas fa-info-circle');

// Basic validation
if (empty($item_id) || empty($day_number) || empty($title)) {
    echo json_encode(['success' => false, 'message' => 'ID, Day Number, and Title are required.']);
    exit;
}
if (empty($icon_class)) {
    $icon_class = 'fas fa-info-circle';
}

// Update database
$sql = "UPDATE agenda_items SET day_number = ?, item_time = ?, title = ?, description = ?, icon_class = ? WHERE id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
// Note the order of parameters and types: i sss si i
$stmt->bind_param("issssii", $day_number, $item_time, $title, $description, $icon_class, $item_id, $organization_id);

$response = [];
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'Agenda item updated successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'No changes were made or item not found.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Failed to update agenda item.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;