<?php
// admin/api/add_agenda_item.php

session_start();
require_once '../../config/config.php';

// Security & Validation
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !$_SESSION['admin_user_id']) {
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
$day_number = filter_input(INPUT_POST, 'day_number', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$item_time = trim($_POST['item_time'] ?? '');
$description = trim($_POST['description'] ?? '');
$icon_class = trim($_POST['icon_class'] ?? 'fas fa-info-circle');

// Basic validation
if (empty($day_number) || empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Day Number and Title are required.']);
    exit;
}
if (empty($icon_class)) {
    $icon_class = 'fas fa-info-circle';
}

// Insert into database
$sql = "INSERT INTO agenda_items (organization_id, day_number, item_time, title, description, icon_class) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissss", $organization_id, $day_number, $item_time, $title, $description, $icon_class);

$response = [];
if ($stmt->execute()) {
    $response = ['success' => true, 'message' => 'Agenda item added successfully!'];
} else {
    $response = ['success' => false, 'message' => 'Failed to add agenda item.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;