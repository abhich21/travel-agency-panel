<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/helper_function.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if promoter is logged in
if (!isset($_SESSION['promoterLoggedIn']) || !$_SESSION['promoterLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access. Please log in.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['unique_id'])) {
    $response['message'] = "Invalid request or missing unique ID.";
    echo json_encode($response);
    exit;
}

$unique_id = $_POST['unique_id'];

// Get the organization_id for the logged-in promoter from the promoters table
$promoter_user_id = $_SESSION['promoter_user_id'];
$stmt_org = $conn->prepare("SELECT organization_id FROM promoters WHERE user_id = ?");
$stmt_org->bind_param("i", $promoter_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if (!$org_data) {
    $response['message'] = "Promoter's organization details not found.";
    echo json_encode($response);
    exit;
}
$organization_id = $org_data['organization_id'];

// Find the user by their unique ID (email or phone) and organization
$stmt_find = $conn->prepare("SELECT id, is_arrived_on_airport FROM registered WHERE (email = ? OR phone = ?) AND organization_id = ?");
$stmt_find->bind_param("ssi", $unique_id, $unique_id, $organization_id);
$stmt_find->execute();
$result_find = $stmt_find->get_result();
$user = $result_find->fetch_assoc();

if (!$user) {
    $response['message'] = "User not found with this ID or for this organization.";
    echo json_encode($response);
    exit;
}

$current_status = $user['is_arrived_on_airport'];
$new_status = $current_status == 1 ? 0 : 1;
$message = $new_status == 1 ? "User successfully checked in!" : "User successfully checked out!";

// Update the user's arrival status
$stmt_update = $conn->prepare("UPDATE registered SET is_arrived_on_airport = ? WHERE id = ?");
$stmt_update->bind_param("ii", $new_status, $user['id']);

if ($stmt_update->execute()) {
    $response['success'] = true;
    $response['message'] = $message;
} else {
    $response['message'] = "Failed to update user status: " . $stmt_update->error;
}

echo json_encode($response);

$stmt_find->close();
$stmt_update->close();
$conn->close();
?>
