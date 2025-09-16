<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/helper_function.php';

use Aws\S3\S3Client;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    $response['message'] = "Invalid request or missing user ID.";
    echo json_encode($response);
    exit;
}

$user_id = $_POST['user_id'];
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if ($org_data) {
    $organization_id = $org_data['id'];
} else {
    $response['message'] = "Organization details not found.";
    echo json_encode($response);
    exit;
}

try {
    // Get user data to delete QR code from S3
    $stmt_user = $conn->prepare("SELECT qr_code FROM registered WHERE id = ? AND organization_id = ?");
    $stmt_user->bind_param("ii", $user_id, $organization_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();

    if (!$user_data) {
        $response['message'] = "User not found or you don't have permission to delete.";
        echo json_encode($response);
        exit;
    }

    if (!empty($user_data['qr_code'])) {
        deleteFromS3($s3Client, $bucketName, $user_data['qr_code']);
    }

    // Delete the user from the database
    $stmt_delete = $conn->prepare("DELETE FROM registered WHERE id = ? AND organization_id = ?");
    $stmt_delete->bind_param("ii", $user_id, $organization_id);
    
    if ($stmt_delete->execute()) {
        $response['success'] = true;
        $response['message'] = "User deleted successfully.";
    } else {
        $response['message'] = "Failed to delete user: " . $stmt_delete->error;
    }

} catch (Exception $e) {
    $response['message'] = "An error occurred: " . $e->getMessage();
}

echo json_encode($response);
exit;
?>
