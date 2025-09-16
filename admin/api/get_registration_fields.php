<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'data' => []];

// Check if admin is logged in
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;

// Get the organization_id for the current admin
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if ($org_data) {
    $organization_id = $org_data['id'];
} else {
    $response['message'] = "Organization details not found for this user.";
    echo json_encode($response);
    exit;
}

try {
    // Get the fields JSON for the organization
    $query = "SELECT fields FROM registration_fields WHERE organization_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $fields = [];
    if ($row && $row['fields']) {
        $decoded_fields = json_decode($row['fields'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_fields)) {
            $fields = $decoded_fields;
        } else {
            // Handle JSON decoding error
            $response['message'] = "Error decoding registration fields from database.";
            echo json_encode($response);
            exit;
        }
    }
    
    // Add the govt_id_link field, which is not in the registration_fields table
    $fields[] = [
        'field_name' => 'govt_id_link',
        'is_unique' => 0
    ];

    $response['success'] = true;
    $response['data'] = $fields;
    $response['message'] = "Registration fields fetched successfully.";

} catch (Exception $e) {
    $response['message'] = "Database error: " . $e->getMessage();
}

echo json_encode($response);

$stmt->close();
$stmt_org->close();
$conn->close();
?>
