<?php
// admin/api/edit_faq_item.php

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
$faq_id = filter_input(INPUT_POST, 'faq_id', FILTER_VALIDATE_INT);
$question = trim($_POST['question'] ?? '');
$answer = $_POST['answer'] ?? '';

// Basic validation
if (empty($faq_id) || empty($question) || empty($answer)) {
    echo json_encode(['success' => false, 'message' => 'ID, Question, and Answer are required.']);
    exit;
}

// Update database
$sql = "UPDATE faqs SET question = ?, answer = ? WHERE id = ? AND organization_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $question, $answer, $faq_id, $organization_id);

$response = [];
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response = ['success' => true, 'message' => 'FAQ updated successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'No changes were made or item not found.'];
    }
} else {
    $response = ['success' => false, 'message' => 'Failed to update FAQ.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;