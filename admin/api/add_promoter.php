<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/helper_function.php';


header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if admin is logged in
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = "Invalid request method.";
    echo json_encode($response);
    exit;
}

// Get the promoter data
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = $_SESSION['organization_id'];
$promoter_username = $_POST['promoter_username'];
$promoter_password = $_POST['promoter_password'];

// Prepare for database insertion
$columns = "organization_id, username, password, created_at";
$placeholders = "?, ?, ?, ?";
$types ="isss";

$insert_query = "INSERT INTO promoters ($columns) VALUES ($placeholders)";
$stmt_insert = $conn->prepare($insert_query);

$params[] = $organization_id;
$params[] = $promoter_username;
$params[] = $promoter_password;
$params[] = date('Y-m-d H:i:s');

$stmt_insert->bind_param($types, ...$params);

if ($stmt_insert->execute()) {
    $response['success'] = true;
    $response['message'] = "Promoter created successfully!";
} else {
    $response['message'] = "Error creating promoter: " . $conn->error;
}

echo json_encode($response);
exit;
