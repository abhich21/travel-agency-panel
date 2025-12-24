<?php
// admin/api/delete_cab_schedule.php - Remove a cab schedule

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

// Get schedule ID
$schedule_id = intval($_POST['schedule_id'] ?? 0);

if ($schedule_id <= 0) {
    $response['message'] = 'Invalid schedule ID.';
    echo json_encode($response);
    exit;
}

// Verify schedule belongs to this organization
$stmt_verify = $conn->prepare("SELECT id FROM cab_schedule WHERE id = ? AND organization_id = ?");
$stmt_verify->bind_param("ii", $schedule_id, $organization_id);
$stmt_verify->execute();
if ($stmt_verify->get_result()->num_rows === 0) {
    $response['message'] = 'Schedule not found or access denied.';
    echo json_encode($response);
    exit;
}
$stmt_verify->close();

// Delete the schedule
$stmt = $conn->prepare("DELETE FROM cab_schedule WHERE id = ? AND organization_id = ?");
$stmt->bind_param("ii", $schedule_id, $organization_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Schedule removed successfully!';
} else {
    $response['message'] = 'Failed to remove schedule: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
