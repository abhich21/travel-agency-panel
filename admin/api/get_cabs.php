<?php
// admin/api/get_cabs.php - Fetch all cabs for the admin's organization

session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => ''];

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = 'Unauthorized access.';
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

// Fetch all cabs for this organization
$sql = "SELECT 
            id, 
            cab_number, 
            cab_name, 
            cab_type, 
            capacity, 
            driver_name, 
            driver_phone, 
            status, 
            notes,
            created_at
        FROM cab_details 
        WHERE organization_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organization_id);
$stmt->execute();
$result = $stmt->get_result();

$cabs = [];
while ($row = $result->fetch_assoc()) {
    $cabs[] = $row;
}

$response['success'] = true;
$response['data'] = $cabs;

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
