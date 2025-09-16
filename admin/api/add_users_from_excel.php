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
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excelFile'])) {
    $response['message'] = "Invalid request or no file uploaded.";
    echo json_encode($response);
    exit;
}

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

try {
    $spreadsheet = IOFactory::load($_FILES['excelFile']['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $excel_data = $worksheet->toArray(null, true, true, true);
    
    if (empty($excel_data) || count($excel_data) < 2) {
        $response['message'] = "The Excel file is empty or has no data rows.";
        echo json_encode($response);
        exit;
    }
    
    $headers = array_map('strtolower', $excel_data[1]);
    $registration_field_names = array_column($fields_to_display, 'field');
    $errors = [];
    $processed_count = 0;

    foreach ($excel_data as $row_index => $row) {
        if ($row_index === 1) {
            continue;
        }

        $valid_data = [];
        $has_required_data = false;

        foreach ($headers as $col_index => $header) {
            $value = $row[$col_index] ?? null;
            if (in_array($header, $registration_field_names)) {
                $valid_data[$header] = $value;
                if (in_array($header, $required_fields) && !empty($value)) {
                    $has_required_data = true;
                }
            }
        }
        
        if (!$has_required_data) {
            $errors[] = "Row " . $row_index . ": Missing required fields (email or phone).";
            continue;
        }

        $govt_id_link = null;
        if (isset($row['govt_id_link']) && !empty($row['govt_id_link'])) {
             $govt_id_link = $row['govt_id_link'];
        }

        $qr_data_string = $valid_data['email'] ?? ($valid_data['phone'] ?? '');
        $qr_code_link = null;

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
        
                $fileNameWithoutExt = pathinfo($mock_file['name'], PATHINFO_FILENAME);
                $upload_qr_result = uploadToS3($s3Client, $bucketName, $mock_file, $fileNameWithoutExt, 'users');
                
                if (isset($upload_qr_result['success']) && $upload_qr_result['success']) {
                    $qr_code_link = $upload_qr_result['url'];
                }
        
                unlink($temp_file);
        
            } catch (Exception $e) {
                $errors[] = "Row " . $row_index . ": QR code generation failed - " . $e->getMessage();
                continue;
            }
        }
        
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
            $processed_count++;
        } else {
            $errors[] = "Row " . $row_index . ": Database insertion failed - " . $stmt_insert->error;
        }
    }

    if ($processed_count > 0) {
        $response['success'] = true;
        $response['message'] = $processed_count . " users registered successfully from the Excel file.";
    } else {
        $response['message'] = "No users were registered. " . count($errors) . " errors occurred.";
    }

} catch (Exception $e) {
    $response['message'] = "An error occurred during file processing: " . $e->getMessage();
}

echo json_encode($response);
exit;
