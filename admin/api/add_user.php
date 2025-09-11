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
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = "Invalid request method.";
    echo json_encode($response);
    exit;
}

// Get the organization_id for the current admin
$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;
$stmt_org = $conn->prepare("SELECT id, title FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if ($org_data) {
    $organization_id = $org_data['id'];
    $organization_title = str_replace(' ', '-',strtolower($org_data['title']));
} else {
    $response['message'] = "Organization details not found for this user.";
    echo json_encode($response);
    exit;
}

// Get the fields to display from the registration_fields table
$fields_to_display = [];
$stmt_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
$stmt_fields->bind_param("i", $organization_id);
$stmt_fields->execute();
$result_fields = $stmt_fields->get_result();
$fields_data = $result_fields->fetch_assoc();

if ($fields_data && !empty($fields_data['fields'])) {
    $fields_to_display = json_decode($fields_data['fields'], true);
} else {
    $response['message'] = "No registration fields configured.";
    echo json_encode($response);
    exit;
}

$required_fields = ['name', 'email', 'phone'];
$valid_data = [];
$errors = [];

// Simple validation and data collection
foreach ($fields_to_display as $field) {
    $column = $field['field'];
    if ($column == 'govt_id_link') {
        continue; // Handle file upload separately
    }
    if (isset($_POST[$column]) && !empty($_POST[$column])) {
        $valid_data[$column] = $_POST[$column];
    } elseif (in_array($column, $required_fields)) {
        $errors[] = "The " . $field['label'] . " field is required.";
    }
}

if (!empty($errors)) {
    $response['message'] = implode("<br>", $errors);
    echo json_encode($response);
    exit;
}

$govt_id_link = null;
$qr_code_link = null;

// Handle government ID link upload
if (isset($_FILES['govt_id_link']) && $_FILES['govt_id_link']['error'] === UPLOAD_ERR_OK) {
    // Get the file name without the extension to prevent duplication
    $fileNameWithoutExt = pathinfo($_FILES['govt_id_link']['name'], PATHINFO_FILENAME);
    $upload_result = uploadToS3($s3Client, $bucketName, $_FILES['govt_id_link'], $organization_title, 'users');

    if (isset($upload_result['success']) && $upload_result['success']) {
        $govt_id_link = $upload_result['url'];
    } else {
        $response['message'] = "Failed to upload Government ID: " . ($upload_result['error'] ?? 'Unknown error');
        echo json_encode($response);
        exit;
    }
}

// Generate and upload QR code
$qr_data_string = '';
if (!empty($valid_data['email'])) {
    $qr_data_string = $valid_data['email'];
} elseif (!empty($valid_data['phone'])) {
    $qr_data_string = $valid_data['phone'];
}

if (!empty($qr_data_string)) {
    try {
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

        // Get the file name without the extension to prevent duplication
        $fileNameWithoutExt = pathinfo($mock_file['name'], PATHINFO_FILENAME);
        $upload_qr_result = uploadToS3($s3Client, $bucketName, $mock_file, $organization_title, 'users');
        
        if (isset($upload_qr_result['success']) && $upload_qr_result['success']) {
            $qr_code_link = $upload_qr_result['url'];
        } else {
            $response['message'] = "Failed to upload QR Code: " . ($upload_qr_result['error'] ?? 'Unknown error');
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

// Prepare for database insertion
$columns = implode(", ", array_keys($valid_data)) . ", govt_id_link, qr_code, organization_id, created_at";
$placeholders = implode(", ", array_fill(0, count($valid_data), '?')) . ", ?, ?, ?, ?";
$types = str_repeat('s', count($valid_data)) . "ssis";

$insert_query = "INSERT INTO registered ($columns) VALUES ($placeholders)";
$stmt_insert = $conn->prepare($insert_query);

$params = array_values($valid_data);
$params[] = $govt_id_link;
$params[] = $qr_code_link;
$params[] = $organization_id;
$params[] = date('Y-m-d H:i:s');

$stmt_insert->bind_param($types, ...$params);

if ($stmt_insert->execute()) {
    $response['success'] = true;
    $response['message'] = "User registered successfully!";
} else {
    $response['message'] = "Error registering user: " . $conn->error;
}

echo json_encode($response);
exit;
