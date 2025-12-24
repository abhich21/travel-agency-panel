<?php
// admin/manage_cab_schedule.php - Cab Scheduling Management

session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];

// Get Organization ID and registered users for dropdown
$organization_id = null;
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
if ($org = $result_org->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt_org->close();

// Fetch registered users for the dropdown
$users = [];
if ($organization_id) {
    $stmt_users = $conn->prepare("SELECT id, name, phone, email FROM registered WHERE organization_id = ? ORDER BY name ASC");
    $stmt_users->bind_param("i", $organization_id);
    $stmt_users->execute();
    $result_users = $stmt_users->get_result();
    while ($user = $result_users->fetch_assoc()) {
        $users[] = $user;
    }
    $stmt_users->close();
}

// Get today's date for default filter
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Cab Scheduling</title>
    <style>
        .cab-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        .cab-card:hover {
            border-color: <?php echo $nav_bg_color; ?>;
        }
        .cab-card.selected {
            border-color: <?php echo $nav_bg_color; ?>;
            background-color: rgba(0,0,0,0.03);
        }
        .capacity-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        .capacity-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .capacity-fill.low { background-color: #28a745; }
        .capacity-fill.medium { background-color: #ffc107; }
        .capacity-fill.high { background-color: #dc3545; }
        .schedule-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .schedule-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container-fluid mt-4">
            <div class="row mb-3">
                <div class="col-md-8">
                    <h4><i class="fas fa-calendar-alt me-2"></i>Cab Scheduling</h4>
                </div>
                <div class="col-md-4 text-end">
                    <a href="manage_cabs.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-taxi me-1"></i>Manage Cabs
                    </a>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label for="scheduleDate" class="form-label">Schedule Date</label>
                    <input type="date" class="form-control" id="scheduleDate" value="<?php echo $today; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="loadSchedulesBtn" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Load Schedules
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Cab Selection Panel -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Select Cab</h5>
                        </div>
                        <div class="card-body" id="cabsPanel" style="max-height: 600px; overflow-y: auto;">
                            <p class="text-muted">Select a date and click "Load Schedules" to view cabs</p>
                        </div>
                    </div>
                </div>

                <!-- Schedule Panel -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" id="schedulePanelTitle">Schedules</h5>
                            <button id="addScheduleBtn" class="btn btn-sm btn-success" disabled>
                                <i class="fas fa-user-plus me-1"></i>Add Passenger
                            </button>
                        </div>
                        <div class="card-body" id="schedulePanel">
                            <p class="text-muted">Select a cab to view its schedule</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="scheduleForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Passenger to Cab</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="cab_id" id="modalCabId">
                        <input type="hidden" name="schedule_date" id="modalScheduleDate">
                        
                        <div class="mb-3">
                            <label for="userId" class="form-label">Select User <span class="text-danger">*</span></label>
                            <select class="form-select" id="userId" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> 
                                        (<?php echo htmlspecialchars($user['phone'] ?? $user['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="pickupTime" class="form-label">Pickup Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="pickupTime" name="pickup_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tripType" class="form-label">Trip Type</label>
                                <select class="form-select" id="tripType" name="trip_type">
                                    <option value="pickup">Pickup</option>
                                    <option value="drop">Drop</option>
                                    <option value="round_trip">Round Trip</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="pickupLocation" class="form-label">Pickup Location</label>
                            <input type="text" class="form-control" id="pickupLocation" name="pickup_location" placeholder="e.g., Airport Terminal 3">
                        </div>
                        <div class="mb-3">
                            <label for="dropLocation" class="form-label">Drop Location</label>
                            <input type="text" class="form-control" id="dropLocation" name="drop_location" placeholder="e.g., Hotel Lobby">
                        </div>
                        <div class="mb-3">
                            <label for="scheduleNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="scheduleNotes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Add to Cab
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editScheduleForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="schedule_id" id="editScheduleId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editPickupTime" class="form-label">Pickup Time</label>
                                <input type="time" class="form-control" id="editPickupTime" name="pickup_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editTripType" class="form-label">Trip Type</label>
                                <select class="form-select" id="editTripType" name="trip_type">
                                    <option value="pickup">Pickup</option>
                                    <option value="drop">Drop</option>
                                    <option value="round_trip">Round Trip</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editPickupLocation" class="form-label">Pickup Location</label>
                            <input type="text" class="form-control" id="editPickupLocation" name="pickup_location">
                        </div>
                        <div class="mb-3">
                            <label for="editDropLocation" class="form-label">Drop Location</label>
                            <input type="text" class="form-control" id="editDropLocation" name="drop_location">
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
        const editScheduleModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
        let selectedCabId = null;
        let capacityStats = [];

        // Load schedules on button click
        $('#loadSchedulesBtn').on('click', loadData);

        // Also load on date change
        $('#scheduleDate').on('change', loadData);

        function loadData() {
            const date = $('#scheduleDate').val();
            if (!date) {
                showSweetAlert('error', 'Please select a date');
                return;
            }

            $.ajax({
                url: 'api/get_cab_schedules.php',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        capacityStats = response.capacity_stats;
                        renderCabsPanel(response.capacity_stats);
                        if (selectedCabId) {
                            renderSchedulePanel(response.data.filter(s => s.cab_id == selectedCabId));
                        }
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'Error loading data');
                }
            });
        }

        function renderCabsPanel(stats) {
            const panel = $('#cabsPanel');
            panel.empty();

            if (stats.length === 0) {
                panel.html('<p class="text-muted">No active cabs found. <a href="manage_cabs.php">Add cabs first</a></p>');
                return;
            }

            stats.forEach(cab => {
                const percentage = (cab.booked_seats / cab.capacity) * 100;
                let fillClass = 'low';
                if (percentage >= 75) fillClass = 'high';
                else if (percentage >= 50) fillClass = 'medium';

                panel.append(`
                    <div class="cab-card card mb-2 ${selectedCabId == cab.cab_id ? 'selected' : ''}" data-id="${cab.cab_id}">
                        <div class="card-body p-3">
                            <h6 class="mb-1">${escapeHtml(cab.cab_name || cab.cab_number)}</h6>
                            <small class="text-muted">${escapeHtml(cab.cab_number)}</small>
                            <div class="capacity-bar mt-2">
                                <div class="capacity-fill ${fillClass}" style="width: ${percentage}%"></div>
                            </div>
                            <small class="d-flex justify-content-between mt-1">
                                <span>${cab.booked_seats}/${cab.capacity} booked</span>
                                <span class="text-success">${cab.available_seats} available</span>
                            </small>
                        </div>
                    </div>
                `);
            });
        }

        // Cab card click
        $(document).on('click', '.cab-card', function() {
            const cabId = $(this).data('id');
            selectedCabId = cabId;
            
            $('.cab-card').removeClass('selected');
            $(this).addClass('selected');

            const cabInfo = capacityStats.find(c => c.cab_id == cabId);
            $('#schedulePanelTitle').text(`${cabInfo.cab_name || cabInfo.cab_number} - Schedule`);
            $('#addScheduleBtn').prop('disabled', cabInfo.available_seats <= 0);

            // Load schedules for this cab
            const date = $('#scheduleDate').val();
            $.ajax({
                url: 'api/get_cab_schedules.php',
                type: 'GET',
                data: { date: date, cab_id: cabId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderSchedulePanel(response.data);
                    }
                }
            });
        });

        function renderSchedulePanel(schedules) {
            const panel = $('#schedulePanel');
            panel.empty();

            if (schedules.length === 0) {
                panel.html('<p class="text-muted text-center py-4">No passengers scheduled yet</p>');
                return;
            }

            const table = $(`
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Passenger</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `);

            schedules.forEach(s => {
                const statusClass = {
                    'scheduled': 'bg-info',
                    'in_progress': 'bg-warning text-dark',
                    'completed': 'bg-success',
                    'cancelled': 'bg-danger'
                }[s.status] || 'bg-secondary';

                table.find('tbody').append(`
                    <tr>
                        <td>${s.pickup_time}</td>
                        <td>
                            <strong>${escapeHtml(s.user_name)}</strong><br>
                            <small class="text-muted">${escapeHtml(s.user_phone || s.user_email)}</small>
                        </td>
                        <td><span class="badge bg-secondary">${s.trip_type}</span></td>
                        <td><span class="badge ${statusClass}">${s.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-schedule-btn" data-schedule='${JSON.stringify(s)}'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-schedule-btn" data-id="${s.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            panel.append(table);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add Passenger button
        $('#addScheduleBtn').on('click', function() {
            if (!selectedCabId) return;
            
            $('#scheduleForm')[0].reset();
            $('#modalCabId').val(selectedCabId);
            $('#modalScheduleDate').val($('#scheduleDate').val());
            scheduleModal.show();
        });

        // Add schedule form submit
        $('#scheduleForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');

            $.ajax({
                url: 'api/add_cab_schedule.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    scheduleModal.hide();
                    if (response.success) {
                        showSweetAlert('success', response.message);
                        loadData(); // Refresh
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'Error adding schedule');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });

        // Edit schedule button
        $(document).on('click', '.edit-schedule-btn', function() {
            const schedule = $(this).data('schedule');
            $('#editScheduleId').val(schedule.id);
            $('#editPickupTime').val(schedule.pickup_time);
            $('#editTripType').val(schedule.trip_type);
            $('#editPickupLocation').val(schedule.pickup_location);
            $('#editDropLocation').val(schedule.drop_location);
            $('#editStatus').val(schedule.status);
            $('#editNotes').val(schedule.notes);
            editScheduleModal.show();
        });

        // Edit schedule form submit
        $('#editScheduleForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'api/edit_cab_schedule.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    editScheduleModal.hide();
                    if (response.success) {
                        showSweetAlert('success', response.message);
                        loadData();
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'Error updating schedule');
                }
            });
        });

        // Delete schedule button
        $(document).on('click', '.delete-schedule-btn', function() {
            const scheduleId = $(this).data('id');
            
            Swal.fire({
                title: 'Remove Passenger?',
                text: 'This will remove the passenger from this cab.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/delete_cab_schedule.php',
                        type: 'POST',
                        data: { schedule_id: scheduleId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showSweetAlert('success', response.message);
                                loadData();
                            } else {
                                showSweetAlert('error', response.message);
                            }
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>
