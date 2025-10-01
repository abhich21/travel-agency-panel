<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

// Check if admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

$user_name = "Admin";
if (isset($_SESSION['admin_user_id'])) {
    $stmt = $conn->prepare("SELECT user_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $user_name = htmlspecialchars($user['user_name']);
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Admin Dashboard</title>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="jumbotron">
                <h1 class="display-4">Welcome, <?php echo $user_name; ?>!</h1>
                <p class="lead">This is your admin dashboard. You can manage registered users and other settings from here.</p>
                <hr class="my-4">
                <p>Use the navigation menu to access different features of the panel.</p>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4>Event Management</h4>
                </div>
                <div class="card-body">
                    <p>Update the general event details for your organization.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventDateModal">
                        <i class="fas fa-calendar-alt me-2"></i>Set Event Date
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#eventVenueModal">
                        <i class="fas fa-map-marker-alt me-2"></i>Set Venue Location
                    </button>
                    <a href="edit_home.php" class="btn btn-info">
    <i class="fas fa-pencil-alt me-2"></i>Edit Home Page Content
</a>

<a href="manage_agenda.php" class="btn btn-warning">
    <i class="fas fa-calendar-alt me-2"></i>Manage Agenda
</a>

                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4>Registered Users</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <!-- Dynamic headers will be added here by DataTables -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div id="editFieldsContainer">
                            <!-- Dynamic fields will be populated here -->
                        </div>
                        <div class="mb-3">
                            <label for="govtIdFile" class="form-label">Update Government ID</label>
                            <input type="file" class="form-control" id="govtIdFile" name="govt_id_link">
                            <small class="form-text text-muted">Leave blank to keep the current government ID.</small>
                            <div id="currentGovtIdPreview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Full Image View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Full size image" style="max-height: 80vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventDateModal" tabindex="-1" aria-labelledby="eventDateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="eventDateForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventDateModalLabel">Set Event Date</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Select the official start date for the event.</p>
                        <label for="event_date" class="form-label">Event Date</label>
                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Date</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="eventVenueModal" tabindex="-1" aria-labelledby="eventVenueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="eventVenueForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventVenueModalLabel">Set Venue Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Enter the venue details manually.</p>
                    <div class="mb-3">
                        <label for="venue_name" class="form-label">Venue Name</label>
                        <input type="text" class="form-control" id="venue_name" name="venue_name" placeholder="e.g., Hyatt Regency" required>
                    </div>
                    <div class="mb-3">
                        <label for="venue_address" class="form-label">Full Venue Address</label>
                        <textarea class="form-control" id="venue_address" name="venue_address" rows="3" placeholder="Enter the complete address" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="venue_url" class="form-label">Google Maps Link</label>
                        <input type="url" class="form-control" id="venue_url" name="venue_url" placeholder="Paste the share link from Google Maps" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Venue</button>
                </div>
            </form>
        </div>
    </div>
</div>



    <script>
    $(document).ready(function() {
        // --- ORIGINAL USER MANAGEMENT CODE ---
        <?php
        if (!empty($success)) {
            echo "showSweetAlert('success', '" . addslashes($success) . "', false);";
        }
        if (!empty($error)) {
            echo "showSweetAlert('error', '" . addslashes($error) . "', false);";
        }
        ?>

        let usersTable;
        let currentUniqueFields = {};
        let isDataTableInitialized = false;

        // Load data into the DataTable via AJAX
        $.ajax({
            url: 'api/get_users.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    const firstRow = response.data[0];
                    const filteredFields = firstRow.fields.filter(field => field.name !== 'govt_id_link');
                    const headers = filteredFields.map(field => field.name);
                    const columns = filteredFields.map(field => ({
                        data: function(row, type, set, meta) {
                            const foundField = row.fields.find(f => f.name === field.name);
                            return foundField ? foundField.value : '';
                        },
                        title: field.name.replace(/_/g, ' ').toUpperCase()
                    }));
                    if (firstRow.qr_code) {
                        headers.push('QR_Code');
                        columns.push({
                            data: 'qr_code',
                            title: 'QR Code',
                            render: function(data, type, row) {
                                return `<img src="${data}" alt="QR Code" style="width: 50px; height: 50px; cursor: pointer;" class="image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="${data}">`;
                            }
                        });
                    }
                    if (firstRow.govt_id_link) {
                        headers.push('Govt_ID');
                        columns.push({
                            data: 'govt_id_link',
                            title: 'Govt ID',
                            render: function(data, type, row) {
                                return `<img src="${data}" alt="Govt ID" style="width: 50px; height: 50px; cursor: pointer;" class="image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="${data}">`;
                            }
                        });
                    }
                    headers.push('Actions');
                    columns.push({
                        data: null,
                        title: 'Actions',
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><i class="fas fa-trash"></i> Delete</button>
                            `;
                        }
                    });
                    $('#usersTable thead tr').empty();
                    headers.forEach(header => {
                        $('#usersTable thead tr').append(`<th>${header.replace(/_/g, ' ').toUpperCase()}</th>`);
                    });
                    if ($.fn.DataTable.isDataTable('#usersTable')) {
                        usersTable.destroy();
                    }
                    usersTable = $('#usersTable').DataTable({
                        data: response.data,
                        columns: columns,
                        pageLength: 10,
                        lengthMenu: [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "All"] ],
                        responsive: true
                    });
                    isDataTableInitialized = true;
                } else {
                    $('#usersTable').html('<p class="text-center mt-4">No data found.</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown);
                $('#usersTable').html('<p class="text-center mt-4 text-danger">An error occurred while fetching data. Please try again later.</p>');
            }
        });
        $('#usersTable').on('click', '.image-link', function() {
            const imageUrl = $(this).data('image-url');
            const altText = $(this).attr('alt');
            $('#modalImage').attr('src', imageUrl);
            $('#modalImage').attr('alt', altText);
            $('#imageModalLabel').text(altText);
            $('#imageModal').modal('show');
        });
        $('#usersTable').on('click', '.edit-btn', function() {
            const userId = $(this).data('id');
            const rowData = usersTable.row($(this).parents('tr')).data();
            $.ajax({
                url: 'api/get_registration_fields.php',
                type: 'GET',
                dataType: 'json',
                success: function(fieldsResponse) {
                    const fieldsContainer = $('#editFieldsContainer');
                    fieldsContainer.empty();
                    currentUniqueFields = {};
                    $.each(rowData.fields, function(index, field) {
                        if (field.name !== 'id' && field.name !== 'qr_code' && field.name !== 'govt_id_link') {
                            const fieldDetails = fieldsResponse.data.find(f => f.field_name === field.name);
                            if (fieldDetails && fieldDetails.is_unique === 1) {
                                currentUniqueFields[field.name] = field.value;
                            }
                            fieldsContainer.append(`
                                <div class="mb-3">
                                    <label for="edit-${field.name}" class="form-label text-capitalize">${field.name.replace(/_/g, ' ')}</label>
                                    <input type="text" class="form-control" id="edit-${field.name}" name="${field.name}" value="${field.value}">
                                </div>
                            `);
                        }
                    });
                    $('#editUserId').val(userId);
                    const hasGovtId = fieldsResponse.data.some(field => field.field_name === 'govt_id_link');
                    if (hasGovtId && rowData.govt_id_link) {
                        $('#currentGovtIdPreview').html(`
                            <p>Current Government ID:</p>
                            <img src="${rowData.govt_id_link}" alt="Current Govt ID" class="img-thumbnail" style="max-width: 200px;">
                        `);
                    } else {
                        $('#currentGovtIdPreview').empty();
                    }
                    $('#editUserModal').modal('show');
                },
                error: function() {
                    showSweetAlert('error', 'Failed to fetch registration fields.');
                }
            });
        });
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Updating User...',
                html: 'Please wait while the user data is being updated.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const formData = new FormData(this);
            formData.append('uniqueFields', JSON.stringify(currentUniqueFields));
            $.ajax({
                url: 'api/edit_user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#editUserModal').modal('hide');
                    if (response.success) {
                        showSweetAlert('success', response.message, true);
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.close();
                    Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
                    console.error("AJAX Error: ", textStatus, errorThrown);
                }
            });
        });
        $('#usersTable').on('click', '.delete-btn', function() {
            const userId = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting User...',
                        html: 'Please wait...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: 'api/delete_user.php',
                        type: 'POST',
                        data: {
                            user_id: userId
                        },
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                showSweetAlert('success', response.message, true);
                            } else {
                                showSweetAlert('error', response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.close();
                            Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
                            console.error("AJAX Error: ", textStatus, errorThrown);
                        }
                    });
                }
            });
        });

        // --- NEW EVENT MANAGEMENT CODE ---

        // Handle Event Date Form Submission
        $('#eventDateForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                url: 'api/update_event_details.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#eventDateModal').modal('hide');
                    if (response.success) {
                        showSweetAlert('success', response.message, true);
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'An error occurred. Please try again.');
                }
            });
        });

        // Handle Event Venue Form Submission
        $('#eventVenueForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                url: 'api/update_event_details.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#eventVenueModal').modal('hide');
                    if (response.success) {
                        showSweetAlert('success', response.message, true);
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'An error occurred. Please try again.');
                }
            });
        });
    }); // This is the closing of the main $(document).ready function
</script>
    

   
</body>
</html>
