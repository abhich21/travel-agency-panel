<?php
// super-admin/view_cab_logistics.php - Read-only view of all cab logistics

session_start();
require_once '../config/config.php';

// Security Check - Super Admin only
if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== TRUE) {
    header('Location: login.php');
    exit;
}

// Verify role is super_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit;
}

// Fetch all organizations for filter
$organizations = [];
$stmt_orgs = $conn->prepare("SELECT id, title FROM organizations ORDER BY title");
$stmt_orgs->execute();
$result_orgs = $stmt_orgs->get_result();
while ($org = $result_orgs->fetch_assoc()) {
    $organizations[] = $org;
}
$stmt_orgs->close();

// Get filter parameters
$filter_org = isset($_GET['org_id']) ? intval($_GET['org_id']) : null;
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Build query for cab statistics
$stats_sql = "SELECT 
    o.title as org_title,
    COUNT(DISTINCT cd.id) as total_cabs,
    SUM(CASE WHEN cd.status = 'active' THEN 1 ELSE 0 END) as active_cabs,
    (SELECT COUNT(*) FROM cab_schedule cs2 WHERE cs2.organization_id = o.id AND cs2.schedule_date = ?) as scheduled_trips,
    (SELECT COUNT(*) FROM cab_schedule cs3 WHERE cs3.organization_id = o.id AND cs3.schedule_date = ? AND cs3.status = 'completed') as completed_trips
FROM organizations o
LEFT JOIN cab_details cd ON o.id = cd.organization_id";

$stats_params = [$filter_date, $filter_date];
$stats_types = "ss";

if ($filter_org) {
    $stats_sql .= " WHERE o.id = ?";
    $stats_params[] = $filter_org;
    $stats_types .= "i";
}

$stats_sql .= " GROUP BY o.id ORDER BY o.title";

$stmt_stats = $conn->prepare($stats_sql);
$stmt_stats->bind_param($stats_types, ...$stats_params);
$stmt_stats->execute();
$result_stats = $stmt_stats->get_result();
$stats = $result_stats->fetch_all(MYSQLI_ASSOC);
$stmt_stats->close();

// Calculate totals
$total_cabs = array_sum(array_column($stats, 'total_cabs'));
$total_active = array_sum(array_column($stats, 'active_cabs'));
$total_scheduled = array_sum(array_column($stats, 'scheduled_trips'));
$total_completed = array_sum(array_column($stats, 'completed_trips'));

// Fetch all schedules for the data table
$schedules_sql = "SELECT 
    cs.id,
    cs.schedule_date,
    cs.pickup_time,
    cs.trip_type,
    cs.pickup_location,
    cs.drop_location,
    cs.status,
    o.title as org_title,
    cd.cab_number,
    cd.cab_name,
    cd.cab_type,
    cd.driver_name,
    r.name as user_name,
    r.phone as user_phone
FROM cab_schedule cs
JOIN organizations o ON cs.organization_id = o.id
JOIN cab_details cd ON cs.cab_id = cd.id
JOIN registered r ON cs.user_id = r.id
WHERE cs.schedule_date = ?";

$sched_params = [$filter_date];
$sched_types = "s";

if ($filter_org) {
    $schedules_sql .= " AND cs.organization_id = ?";
    $sched_params[] = $filter_org;
    $sched_types .= "i";
}

$schedules_sql .= " ORDER BY o.title, cs.pickup_time";

$stmt_sched = $conn->prepare($schedules_sql);
$stmt_sched->bind_param($sched_types, ...$sched_params);
$stmt_sched->execute();
$result_sched = $stmt_sched->get_result();
$schedules = $result_sched->fetch_all(MYSQLI_ASSOC);
$stmt_sched->close();
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Cab Logistics Overview</title>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-route me-2"></i>Cab Logistics Overview</h4>
                <span class="badge bg-secondary">Read-Only View</span>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="orgFilter" class="form-label">Organization</label>
                            <select class="form-select" id="orgFilter" name="org_id">
                                <option value="">All Organizations</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['id']; ?>" <?php echo $filter_org == $org['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($org['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label">Date</label>
                            <input type="date" class="form-control" id="dateFilter" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $total_cabs; ?></h3>
                            <small>Total Cabs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $total_active; ?></h3>
                            <small>Active Cabs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $total_scheduled; ?></h3>
                            <small>Scheduled Trips</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h3 class="mb-0"><?php echo $total_completed; ?></h3>
                            <small>Completed Trips</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedules Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Schedules for <?php echo date('M j, Y', strtotime($filter_date)); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-info">No schedules found for the selected date and filters.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="schedulesTable">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Cab</th>
                                        <th>Passenger</th>
                                        <th>Time</th>
                                        <th>Trip Type</th>
                                        <th>Pickup</th>
                                        <th>Drop</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $s): ?>
                                        <?php
                                        $status_classes = [
                                            'scheduled' => 'bg-info',
                                            'in_progress' => 'bg-warning text-dark',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $status_class = $status_classes[$s['status']] ?? 'bg-secondary';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['org_title']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($s['cab_name'] ?: $s['cab_number']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($s['driver_name'] ?: 'No driver'); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($s['user_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($s['user_phone']); ?></small>
                                            </td>
                                            <td><?php echo date('h:i A', strtotime($s['pickup_time'])); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($s['trip_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($s['pickup_location'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($s['drop_location'] ?: '-'); ?></td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <script>
    $(document).ready(function() {
        $('#schedulesTable').DataTable({
            order: [[0, 'asc'], [3, 'asc']],
            pageLength: 25,
            responsive: true
        });
    });
    </script>
</body>
</html>
