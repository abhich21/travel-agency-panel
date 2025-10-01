<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/helper_function.php';

header('Content-Type: application/json');

$response = ['data' => []];

if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    echo json_encode(['data' => [], 'message' => "Unauthorized access."]);
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
    echo json_encode(['data' => [], 'message' => "Organization details not found."]);
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
}

$select_fields = [];
foreach ($fields_to_display as $field) {
    $select_fields[] = "`" . $field['field'] . "`";
}
$select_fields[] = "`govt_id_link`";
$select_fields[] = "`qr_code`";
$select_fields[] = "`id`";

$select_clause = implode(", ", $select_fields);

// Query to get registered users for the current organization
$stmt_users = $conn->prepare("SELECT $select_clause FROM registered WHERE organization_id = ?");
$stmt_users->bind_param("i", $organization_id);
$stmt_users->execute();
$result_users = $stmt_users->get_result();

$table_headers = array_column($fields_to_display, 'name');
$table_headers[] = 'QR Code';
$table_headers[] = 'Govt ID';
$table_headers[] = 'Actions';

$data = [];
while ($row = $result_users->fetch_assoc()) {
    $rowData = [];
    $fieldData = [];
    
    foreach ($fields_to_display as $field) {
        $fieldName = $field['field'];
        $fieldData[] = ['name' => $fieldName, 'value' => $row[$fieldName] ?? ''];
        $rowData[] = htmlspecialchars($row[$fieldName] ?? '');
    }
    
    $rowData[] = '<a href="' . htmlspecialchars($row['qr_code']) . '" target="_blank"><i class="fa fa-qrcode"></i></a>';
    
    if (!empty($row['govt_id_link'])) {
        $rowData[] = '<a href="' . htmlspecialchars($row['govt_id_link']) . '" target="_blank"><i class="fa fa-id-card"></i></a>';
    } else {
        $rowData[] = 'N/A';
    }
    
    $rowData[] = '
        <button class="btn btn-sm btn-primary edit-btn" data-id="' . $row['id'] . '"><i class="fa fa-edit"></i> Edit</button>
        <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i> Delete</button>
    ';

    $data[] = [
        'id' => $row['id'],
        'fields' => $fieldData,
        'qr_code' => $row['qr_code'],
        'govt_id_link' => $row['govt_id_link'],
        'DT_RowId' => 'row_' . $row['id'],
        'DT_RowData' => $rowData,
    ];
}

echo json_encode([
    "headers" => $table_headers,
    "data" => $data,
]);

$conn->close();
?>
