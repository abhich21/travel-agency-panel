<?php
// admin/manage_cabs.php - Cab Inventory Management

session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];
$user_name = $_SESSION['admin_user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Manage Cabs</title>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-taxi me-2"></i>Manage Cabs</h4>
                    <div>
                        <button id="addNewCabBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Cab
                        </button>
                        <a href="manage_cab_schedule.php" class="btn btn-sm btn-outline-info ms-2">
                            <i class="fas fa-calendar-alt me-2"></i>Manage Schedules
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="cabsTable">
                            <thead>
                                <tr>
                                    <th>Cab Number</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Driver</th>
                                    <th>Driver Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <!-- Add/Edit Cab Modal -->
    <div class="modal fade" id="cabModal" tabindex="-1" aria-labelledby="cabModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="cabForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cabModalLabel">Add New Cab</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="cab_id" id="cabId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cabNumber" class="form-label">Cab Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cabNumber" name="cab_number" placeholder="e.g., MH-01-AB-1234" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cabName" class="form-label">Cab Name</label>
                                <input type="text" class="form-control" id="cabName" name="cab_name" placeholder="e.g., Shuttle-1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cabType" class="form-label">Type</label>
                                <select class="form-select" id="cabType" name="cab_type">
                                    <option value="sedan">Sedan</option>
                                    <option value="suv">SUV</option>
                                    <option value="van">Van</option>
                                    <option value="minibus">Minibus</option>
                                    <option value="bus">Bus</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cabCapacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cabCapacity" name="capacity" min="1" max="100" value="4" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="driverName" class="form-label">Driver Name</label>
                                <input type="text" class="form-control" id="driverName" name="driver_name" placeholder="Driver's full name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="driverPhone" class="form-label">Driver Phone</label>
                                <input type="tel" class="form-control" id="driverPhone" name="driver_phone" placeholder="e.g., 9876543210">
                            </div>
                        </div>
                        <div class="row" id="statusRow" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="cabStatus" class="form-label">Status</label>
                                <select class="form-select" id="cabStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cabNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="cabNotes" name="notes" rows="2" placeholder="Any additional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Save Cab
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const cabModal = new bootstrap.Modal(document.getElementById('cabModal'));
        let dataTable;

        // Load cabs on page load
        loadCabs();

        function loadCabs() {
            $.ajax({
                url: 'api/get_cabs.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderCabsTable(response.data);
                    } else {
                        showSweetAlert('error', response.message || 'Failed to load cabs');
                    }
                },
                error: function() {
                    showSweetAlert('error', 'Error loading cabs');
                }
            });
        }

        function renderCabsTable(cabs) {
            // Destroy existing DataTable if exists
            if (dataTable) {
                dataTable.destroy();
            }

            const tbody = $('#cabsTable tbody');
            tbody.empty();

            cabs.forEach(cab => {
                const statusBadge = getStatusBadge(cab.status);
                const typeIcon = getCabTypeIcon(cab.cab_type);
                
                tbody.append(`
                    <tr data-id="${cab.id}">
                        <td><strong>${escapeHtml(cab.cab_number)}</strong></td>
                        <td>${escapeHtml(cab.cab_name || '-')}</td>
                        <td>${typeIcon} ${escapeHtml(cab.cab_type)}</td>
                        <td><span class="badge bg-secondary">${cab.capacity} seats</span></td>
                        <td>${escapeHtml(cab.driver_name || '-')}</td>
                        <td>${cab.driver_phone ? `<a href="tel:${cab.driver_phone}">${escapeHtml(cab.driver_phone)}</a>` : '-'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-info edit-btn" data-cab='${JSON.stringify(cab)}'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${cab.id}" data-name="${escapeHtml(cab.cab_number)}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });

            // Initialize DataTable
            dataTable = $('#cabsTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10,
                responsive: true
            });
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success">Active</span>',
                'inactive': '<span class="badge bg-danger">Inactive</span>',
                'maintenance': '<span class="badge bg-warning text-dark">Maintenance</span>'
            };
            return badges[status] || status;
        }

        function getCabTypeIcon(type) {
            const icons = {
                'sedan': '<i class="fas fa-car"></i>',
                'suv': '<i class="fas fa-truck-pickup"></i>',
                'van': '<i class="fas fa-shuttle-van"></i>',
                'minibus': '<i class="fas fa-bus-alt"></i>',
                'bus': '<i class="fas fa-bus"></i>'
            };
            return icons[type] || '<i class="fas fa-car"></i>';
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add New Cab button
        $('#addNewCabBtn').on('click', function() {
            $('#cabForm')[0].reset();
            $('#cabId').val('');
            $('#cabModalLabel').text('Add New Cab');
            $('#statusRow').hide();
            cabModal.show();
        });

        // Edit button click
        $(document).on('click', '.edit-btn', function() {
            const cab = $(this).data('cab');
            $('#cabId').val(cab.id);
            $('#cabNumber').val(cab.cab_number);
            $('#cabName').val(cab.cab_name);
            $('#cabType').val(cab.cab_type);
            $('#cabCapacity').val(cab.capacity);
            $('#driverName').val(cab.driver_name);
            $('#driverPhone').val(cab.driver_phone);
            $('#cabStatus').val(cab.status);
            $('#cabNotes').val(cab.notes);
            $('#cabModalLabel').text('Edit Cab');
            $('#statusRow').show();
            cabModal.show();
        });

        // Delete button click
        $(document).on('click', '.delete-btn', function() {
            const cabId = $(this).data('id');
            const cabName = $(this).data('name');
            
            Swal.fire({
                title: 'Delete Cab?',
                text: `Are you sure you want to delete "${cabName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/delete_cab.php',
                        type: 'POST',
                        data: { cab_id: cabId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showSweetAlert('success', response.message, true);
                            } else {
                                showSweetAlert('error', response.message);
                            }
                        },
                        error: function() {
                            showSweetAlert('error', 'Error deleting cab');
                        }
                    });
                }
            });
        });

        // Form submission
        $('#cabForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.spinner-border');
            submitBtn.prop('disabled', true);
            spinner.removeClass('d-none');

            const cabId = $('#cabId').val();
            const apiUrl = cabId ? 'api/edit_cab.php' : 'api/add_cab.php';
            
            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    cabModal.hide();
                    if (response.success) {
                        showSweetAlert('success', response.message, true);
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'An error occurred');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    spinner.addClass('d-none');
                }
            });
        });
    });
    </script>
</body>
</html>
