<?php
// admin/api/add_faq_item.php

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
$question = trim($_POST['question'] ?? '');
$answer = $_POST['answer'] ?? '';

// Basic validation
if (empty($question) || empty($answer)) {
    echo json_encode(['success' => false, 'message' => 'Question and Answer are required.']);
    exit;
}

// Insert into database
$sql = "INSERT INTO faqs (organization_id, question, answer) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $organization_id, $question, $answer);

$response = [];
if ($stmt->execute()) {
    $response = ['success' => true, 'message' => 'FAQ added successfully!'];
} else {
    $response = ['success' => false, 'message' => 'Failed to add FAQ.'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;