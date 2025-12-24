<?php
// admin/api/edit_cab_schedule.php - Update a cab schedule with WhatsApp notification

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
$schedule_id = intval($_POST['schedule_id'] ?? 0);
$pickup_time = trim($_POST['pickup_time'] ?? '');
$trip_type = trim($_POST['trip_type'] ?? 'pickup');
$pickup_location = trim($_POST['pickup_location'] ?? '');
$drop_location = trim($_POST['drop_location'] ?? '');
$status = trim($_POST['status'] ?? 'scheduled');
$notes = trim($_POST['notes'] ?? '');

// Validation
if ($schedule_id <= 0) {
    $response['message'] = 'Invalid schedule ID.';
    echo json_encode($response);
    exit;
}

// Verify schedule belongs to this organization and get current data with user phone
$stmt_verify = $conn->prepare("
    SELECT cs.*, r.phone as user_phone, r.name as user_name,
           cd.cab_number, cd.cab_name, cd.cab_type, cd.driver_name, cd.driver_phone
    FROM cab_schedule cs 
    JOIN registered r ON cs.user_id = r.id 
    JOIN cab_details cd ON cs.cab_id = cd.id
    WHERE cs.id = ? AND cs.organization_id = ?
");
$stmt_verify->bind_param("ii", $schedule_id, $organization_id);
$stmt_verify->execute();
$schedule_result = $stmt_verify->get_result();

if (!$current_schedule = $schedule_result->fetch_assoc()) {
    $response['message'] = 'Schedule not found or access denied.';
    echo json_encode($response);
    exit;
}
$stmt_verify->close();

// Track changes for notification
$changes = [];
if ($current_schedule['pickup_time'] !== $pickup_time) {
    $changes[] = "Pickup time changed to " . date('h:i A', strtotime($pickup_time));
}
if ($current_schedule['pickup_location'] !== $pickup_location && !empty($pickup_location)) {
    $changes[] = "Pickup location updated";
}
if ($current_schedule['drop_location'] !== $drop_location && !empty($drop_location)) {
    $changes[] = "Drop location updated";
}
if ($current_schedule['trip_type'] !== $trip_type) {
    $changes[] = "Trip type changed to " . ucfirst(str_replace('_', ' ', $trip_type));
}

// Update schedule
$sql = "UPDATE cab_schedule 
        SET pickup_time = ?, trip_type = ?, pickup_location = ?, drop_location = ?, status = ?, notes = ?
        WHERE id = ? AND organization_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssii", $pickup_time, $trip_type, $pickup_location, $drop_location, $status, $notes, $schedule_id, $organization_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Schedule updated successfully!';
    
    // If status changed to 'completed', update the user's arrival flag
    if ($status === 'completed' && $current_schedule['status'] !== 'completed') {
        $stmt_arrival = $conn->prepare("UPDATE registered SET is_arrived_on_bus = 1 WHERE id = ?");
        $stmt_arrival->bind_param("i", $current_schedule['user_id']);
        $stmt_arrival->execute();
        $stmt_arrival->close();
        $response['arrival_updated'] = true;
    }
    
    // Send WhatsApp update notification if there are significant changes (not just status change to completed/cancelled)
    if (!empty($changes) && !empty($current_schedule['user_phone']) && $status !== 'cancelled' && $status !== 'completed') {
        $cabDetails = [
            'cab_number' => $current_schedule['cab_number'],
            'cab_name' => $current_schedule['cab_name'],
            'cab_type' => $current_schedule['cab_type'],
            'driver_name' => $current_schedule['driver_name'],
            'driver_phone' => $current_schedule['driver_phone']
        ];
        
        $scheduleDetails = [
            'schedule_date' => $current_schedule['schedule_date'],
            'pickup_time' => $pickup_time,
            'trip_type' => $trip_type,
            'pickup_location' => $pickup_location,
            'drop_location' => $drop_location
        ];
        
        $whatsappResult = sendCabUpdateWhatsApp($current_schedule['user_phone'], $cabDetails, $scheduleDetails, $changes, $org_title);
        $response['whatsapp'] = $whatsappResult;
    }
} else {
    $response['message'] = 'Failed to update schedule: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
