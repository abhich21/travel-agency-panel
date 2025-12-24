<?php
// admin/api/edit_cab.php - Update an existing cab

session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];

// Get Organization ID
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();

if (!$org = $result_org->fetch_assoc()) {
    $response['message'] = 'Organization not found.';
    echo json_encode($response);
    exit;
}

$organization_id = $org['id'];
$stmt_org->close();

// Get form data
$cab_id = intval($_POST['cab_id'] ?? 0);
$cab_number = trim($_POST['cab_number'] ?? '');
$cab_name = trim($_POST['cab_name'] ?? '');
$cab_type = trim($_POST['cab_type'] ?? 'sedan');
$capacity = intval($_POST['capacity'] ?? 4);
$driver_name = trim($_POST['driver_name'] ?? '');
$driver_phone = trim($_POST['driver_phone'] ?? '');
$status = trim($_POST['status'] ?? 'active');
$notes = trim($_POST['notes'] ?? '');

// Validation
if ($cab_id <= 0) {
    $response['message'] = 'Invalid cab ID.';
    echo json_encode($response);
    exit;
}

if (empty($cab_number)) {
    $response['message'] = 'Cab number is required.';
    echo json_encode($response);
    exit;
}

if ($capacity < 1 || $capacity > 100) {
    $response['message'] = 'Capacity must be between 1 and 100.';
    echo json_encode($response);
    exit;
}

// Verify cab belongs to this organization
$stmt_verify = $conn->prepare("SELECT id FROM cab_details WHERE id = ? AND organization_id = ?");
$stmt_verify->bind_param("ii", $cab_id, $organization_id);
$stmt_verify->execute();
if ($stmt_verify->get_result()->num_rows === 0) {
    $response['message'] = 'Cab not found or access denied.';
    echo json_encode($response);
    exit;
}
$stmt_verify->close();

// Check for duplicate cab number (excluding current cab)
$stmt_check = $conn->prepare("SELECT id FROM cab_details WHERE cab_number = ? AND organization_id = ? AND id != ?");
$stmt_check->bind_param("sii", $cab_number, $organization_id, $cab_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    $response['message'] = 'Another cab with this number already exists.';
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Update cab
$sql = "UPDATE cab_details 
        SET cab_number = ?, cab_name = ?, cab_type = ?, capacity = ?, 
            driver_name = ?, driver_phone = ?, status = ?, notes = ?
        WHERE id = ? AND organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssissssii", $cab_number, $cab_name, $cab_type, $capacity, 
                   $driver_name, $driver_phone, $status, $notes, $cab_id, $organization_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Cab updated successfully!';
} else {
    $response['message'] = 'Failed to update cab: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
