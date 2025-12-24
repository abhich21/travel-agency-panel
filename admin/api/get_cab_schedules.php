<?php
// admin/api/get_cab_schedules.php - Fetch cab schedules with filters

session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => ''];

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    $response['message'] = 'Unauthorized access.';
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

// Get filter parameters
$schedule_date = $_GET['date'] ?? null;
$cab_id = isset($_GET['cab_id']) ? intval($_GET['cab_id']) : null;

// Build query with optional filters
$sql = "SELECT 
            cs.id,
            cs.cab_id,
            cs.user_id,
            cs.schedule_date,
            cs.pickup_time,
            cs.trip_type,
            cs.pickup_location,
            cs.drop_location,
            cs.status,
            cs.notes,
            cs.created_at,
            cd.cab_number,
            cd.cab_name,
            cd.cab_type,
            cd.capacity,
            cd.driver_name,
            cd.driver_phone,
            r.name as user_name,
            r.phone as user_phone,
            r.email as user_email
        FROM cab_schedule cs
        JOIN cab_details cd ON cs.cab_id = cd.id
        JOIN registered r ON cs.user_id = r.id
        WHERE cs.organization_id = ?";

$params = [$organization_id];
$types = "i";

if ($schedule_date) {
    $sql .= " AND cs.schedule_date = ?";
    $params[] = $schedule_date;
    $types .= "s";
}

if ($cab_id) {
    $sql .= " AND cs.cab_id = ?";
    $params[] = $cab_id;
    $types .= "i";
}

$sql .= " ORDER BY cs.schedule_date ASC, cs.pickup_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

// Also get cab capacity stats for the date if filtering by date
$capacity_stats = [];
if ($schedule_date) {
    $sql_stats = "SELECT 
                    cd.id as cab_id,
                    cd.cab_number,
                    cd.cab_name,
                    cd.capacity,
                    cd.status,
                    COUNT(cs.id) as booked_seats
                  FROM cab_details cd
                  LEFT JOIN cab_schedule cs ON cd.id = cs.cab_id 
                    AND cs.schedule_date = ? 
                    AND cs.status != 'cancelled'
                  WHERE cd.organization_id = ? AND cd.status = 'active'
                  GROUP BY cd.id
                  ORDER BY cd.cab_name ASC";
    
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bind_param("si", $schedule_date, $organization_id);
    $stmt_stats->execute();
    $result_stats = $stmt_stats->get_result();
    
    while ($row = $result_stats->fetch_assoc()) {
        $row['available_seats'] = $row['capacity'] - $row['booked_seats'];
        $capacity_stats[] = $row;
    }
    $stmt_stats->close();
}

$response['success'] = true;
$response['data'] = $schedules;
$response['capacity_stats'] = $capacity_stats;

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
