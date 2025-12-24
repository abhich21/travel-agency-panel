<?php
// admin/api/add_cab_schedule.php - Assign user to a cab with WhatsApp notification

session_start();
require_once '../../config/config.php';
require_once '../../config/whatsapp_helper.php';

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

// Get Organization ID and Title
$stmt_org = $conn->prepare("SELECT id, title FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();

if (!$org = $result_org->fetch_assoc()) {
    $response['message'] = 'Organization not found.';
    echo json_encode($response);
    exit;
}

$organization_id = $org['id'];
$org_title = $org['title'];
$stmt_org->close();

// Get form data
$cab_id = intval($_POST['cab_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$schedule_date = trim($_POST['schedule_date'] ?? '');
$pickup_time = trim($_POST['pickup_time'] ?? '');
$trip_type = trim($_POST['trip_type'] ?? 'pickup');
$pickup_location = trim($_POST['pickup_location'] ?? '');
$drop_location = trim($_POST['drop_location'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validation
if ($cab_id <= 0 || $user_id <= 0) {
    $response['message'] = 'Please select both a cab and a user.';
    echo json_encode($response);
    exit;
}

if (empty($schedule_date) || empty($pickup_time)) {
    $response['message'] = 'Schedule date and pickup time are required.';
    echo json_encode($response);
    exit;
}

// Verify cab belongs to this organization and is active - get full details for WhatsApp
$stmt_cab = $conn->prepare("SELECT id, cab_number, cab_name, cab_type, capacity, driver_name, driver_phone FROM cab_details WHERE id = ? AND organization_id = ? AND status = 'active'");
$stmt_cab->bind_param("ii", $cab_id, $organization_id);
$stmt_cab->execute();
$cab_result = $stmt_cab->get_result();

if (!$cab = $cab_result->fetch_assoc()) {
    $response['message'] = 'Cab not found or is not available.';
    echo json_encode($response);
    exit;
}
$capacity = $cab['capacity'];
$stmt_cab->close();

// Verify user belongs to this organization - get phone for WhatsApp
$stmt_user = $conn->prepare("SELECT id, name, phone FROM registered WHERE id = ? AND organization_id = ?");
$stmt_user->bind_param("ii", $user_id, $organization_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

if (!$user = $user_result->fetch_assoc()) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}
$stmt_user->close();

// Check if user is already assigned to this cab for this date/trip type
$stmt_check = $conn->prepare("SELECT id FROM cab_schedule WHERE cab_id = ? AND user_id = ? AND schedule_date = ? AND trip_type = ?");
$stmt_check->bind_param("iiss", $cab_id, $user_id, $schedule_date, $trip_type);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    $response['message'] = 'This user is already assigned to this cab for the selected date and trip type.';
    echo json_encode($response);
    exit;
}
$stmt_check->close();

// Check capacity
$stmt_count = $conn->prepare("SELECT COUNT(*) as booked FROM cab_schedule WHERE cab_id = ? AND schedule_date = ? AND status != 'cancelled'");
$stmt_count->bind_param("is", $cab_id, $schedule_date);
$stmt_count->execute();
$count_result = $stmt_count->get_result()->fetch_assoc();
$booked_seats = $count_result['booked'];
$stmt_count->close();

if ($booked_seats >= $capacity) {
    $response['message'] = "This cab is fully booked for {$schedule_date}. Capacity: {$capacity}, Booked: {$booked_seats}";
    echo json_encode($response);
    exit;
}

// Insert schedule
$sql = "INSERT INTO cab_schedule (organization_id, cab_id, user_id, schedule_date, pickup_time, trip_type, pickup_location, drop_location, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissssss", $organization_id, $cab_id, $user_id, $schedule_date, $pickup_time, $trip_type, $pickup_location, $drop_location, $notes);

if ($stmt->execute()) {
    $schedule_id = $stmt->insert_id;
    $response['success'] = true;
    $response['message'] = 'User assigned to cab successfully!';
    $response['schedule_id'] = $schedule_id;
    
    // Send WhatsApp notification
    if (!empty($user['phone'])) {
        $scheduleDetails = [
            'schedule_date' => $schedule_date,
            'pickup_time' => $pickup_time,
            'trip_type' => $trip_type,
            'pickup_location' => $pickup_location,
            'drop_location' => $drop_location
        ];
        
        $whatsappResult = sendCabAssignmentWhatsApp($user['phone'], $cab, $scheduleDetails, $org_title);
        $response['whatsapp'] = $whatsappResult;
        
        // Update whatsapp_sent flag if successful
        if ($whatsappResult['success']) {
            $stmt_update = $conn->prepare("UPDATE cab_schedule SET whatsapp_sent = 1 WHERE id = ?");
            $stmt_update->bind_param("i", $schedule_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    } else {
        $response['whatsapp'] = ['success' => false, 'message' => 'No phone number for user'];
    }
} else {
    $response['message'] = 'Failed to create schedule: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
