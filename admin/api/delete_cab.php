<?php
// admin/api/delete_cab.php - Delete (soft-delete) a cab

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

// Get cab ID
$cab_id = intval($_POST['cab_id'] ?? 0);

if ($cab_id <= 0) {
    $response['message'] = 'Invalid cab ID.';
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

// Check if cab has upcoming schedules
$stmt_schedules = $conn->prepare("SELECT COUNT(*) as count FROM cab_schedule WHERE cab_id = ? AND status IN ('scheduled', 'in_progress')");
$stmt_schedules->bind_param("i", $cab_id);
$stmt_schedules->execute();
$schedule_result = $stmt_schedules->get_result()->fetch_assoc();

if ($schedule_result['count'] > 0) {
    $response['message'] = 'Cannot delete cab with active schedules. Please cancel or complete them first.';
    echo json_encode($response);
    exit;
}
$stmt_schedules->close();

// Soft delete - set status to 'inactive'
$stmt = $conn->prepare("UPDATE cab_details SET status = 'inactive' WHERE id = ? AND organization_id = ?");
$stmt->bind_param("ii", $cab_id, $organization_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Cab deleted successfully!';
} else {
    $response['message'] = 'Failed to delete cab: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
