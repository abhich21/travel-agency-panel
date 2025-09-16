<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/helper_function.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Aws\S3\S3Client;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if admin is logged in
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access. Please log in.";
    echo json_encode($response);
    exit;
}

// Check for valid request method and required data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'])) {
    $response['message'] = "Invalid request or missing user ID.";
    echo json_encode($response);
    exit;
}

$user_id = $_POST['user_id'];
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$qr_code_link = null;

// Get the organization_id for the current admin
$stmt_org = $conn->prepare("SELECT id, title FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if (!$org_data) {
    $response['message'] = "Organization details not found for this user.";
    echo json_encode($response);
    exit;
}
$organization_id = $org_data['id'];
$org_title = $org_data['title'];

// Fetch existing user data to check for changes
$stmt_user = $conn->prepare("SELECT * FROM registered WHERE id = ? AND organization_id = ?");
$stmt_user->bind_param("ii", $user_id, $organization_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$existing_user_data = $result_user->fetch_assoc();

if (!$existing_user_data) {
    $response['message'] = "User not found or you do not have permission to edit this user.";
    echo json_encode($response);
    exit;
}

// Check for changes in unique fields that require a new QR code
$unique_fields_changed = false;
$unique_fields = ['name', 'email', 'mobile'];
foreach ($unique_fields as $field) {
    if (isset($_POST[$field]) && $_POST[$field] !== $existing_user_data[$field]) {
        $unique_fields_changed = true;
        break;
    }
}

// If unique fields changed, generate and upload a new QR code
if ($unique_fields_changed) {
    try {
        // Build the data string for the QR code from the new form data
        $qr_data_string = "Organization: " . $org_title . "\n";
        foreach ($_POST as $key => $value) {
            // Exclude non-relevant fields from the QR code data
            if ($key !== 'user_id' && $key !== 'uniqueFields' && $key !== 'govt_id_link') {
                $qr_data_string .= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ": " . htmlspecialchars($value) . "\n";
            }
        }
        
        // Use the modern QR code generation method you provided
        $result = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $qr_data_string,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $builtResult = $result->build();
        $temp_file = tempnam(sys_get_temp_dir(), 'qr_');
        $builtResult->saveToFile($temp_file);

        $mock_file = [
            'name' => 'qr_' . md5($qr_data_string) . '.png',
            'type' => 'image/png',
            'tmp_name' => $temp_file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temp_file),
        ];

        // Upload the new QR code to S3
        $s3FolderName = 'users/' . $organization_id;
        $upload_qr_result = uploadToS3($s3Client, $bucketName, $mock_file, $org_title . ' User QR Code', $s3FolderName);

        if (isset($upload_qr_result['success']) && $upload_qr_result['success']) {
            $qr_code_link = $upload_qr_result['url'];
        } else {
            $response['message'] = "Failed to upload new QR Code: " . ($upload_qr_result['error'] ?? 'Unknown error');
            unlink($temp_file);
            echo json_encode($response);
            exit;
        }
        unlink($temp_file);
    } catch (Exception $e) {
        $response['message'] = "QR code generation or upload failed: " . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

// Build the update query dynamically
$fields_to_update = [];
$params = [];
$types = '';

foreach ($_POST as $key => $value) {
    // Exclude user_id, govt_id_link, and the problematic uniqueFields from the update query
    if ($key !== 'user_id' && $key !== 'govt_id_link' && $key !== 'uniqueFields') {
        $fields_to_update[] = "`$key` = ?";
        $params[] = $value;
        $types .= 's';
    }
}

// Add the new QR code link to the update if it was regenerated
if ($unique_fields_changed) {
    $fields_to_update[] = "`qr_code` = ?";
    $params[] = $qr_code_link;
    $types .= 's';
}

// Handle the optional government ID file upload
if (isset($_FILES['govt_id_link']) && $_FILES['govt_id_link']['error'] === UPLOAD_ERR_OK) {
    $govt_id_file = $_FILES['govt_id_link'];
    $s3FolderName = 'users/' . $organization_id;
    $upload_govt_id_result = uploadToS3($s3Client, $bucketName, $govt_id_file, $org_title . ' Government ID', $s3FolderName);
    
    if (isset($upload_govt_id_result['success']) && $upload_govt_id_result['success']) {
        $fields_to_update[] = "`govt_id_link` = ?";
        $params[] = $upload_govt_id_result['url'];
        $types .= 's';
    } else {
        $response['message'] = "Failed to upload government ID: " . ($upload_govt_id_result['error'] ?? 'Unknown error');
        echo json_encode($response);
        exit;
    }
}


if (empty($fields_to_update)) {
    $response['message'] = "No fields to update.";
    echo json_encode($response);
    exit;
}

// Append the user_id to the parameters and its type to the types string
$params[] = $user_id;
$types .= 'i';

$update_query = "UPDATE registered SET " . implode(", ", $fields_to_update) . " WHERE id = ?";
$stmt_update = $conn->prepare($update_query);

if ($stmt_update === false) {
    $response['message'] = "Prepare failed: " . $conn->error;
    echo json_encode($response);
    exit;
}

// The spread operator (...) expands the parameters array into the bind_param method
$stmt_update->bind_param($types, ...$params);

if ($stmt_update->execute()) {
    $response['success'] = true;
    $response['message'] = "User details updated successfully!";
} else {
    $response['message'] = "Failed to update user details: " . $stmt_update->error;
}

$stmt_update->close();
$conn->close();

echo json_encode($response);
?>
