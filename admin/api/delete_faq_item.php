<?php
// admin/api/delete_faq_item.php

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
$faq_id = filter_input(INPUT_POST, 'faq_id', FILTER_VALIDATE_INT);

if (empty($faq_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid FAQ ID.']);
    exit;
}

// Delete from database, ensuring the item belongs to the admin's organization
$sql = "DELETE FROM faqs WHERE id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $faq_id, $organization_id);

$response = [];
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'FAQ deleted successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'FAQ not found or you do not have permission to delete it.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Failed to delete FAQ.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;