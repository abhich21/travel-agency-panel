<?php
// admin/api/delete_agenda_item.php

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

// Get item ID from POST request
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

if (empty($item_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    exit;
}

// Delete from database, ensuring the item belongs to the admin's organization for security
$sql = "DELETE FROM agenda_items WHERE id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $item_id, $organization_id);

$response = [];
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'Agenda item deleted successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'Item not found or you do not have permission to delete it.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Failed to delete agenda item.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;