<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

// Check if admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    header('Location: login.php');
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];
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

// Get the organization_id for the current admin
$organization_id = null;
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if ($org_data) {
    $organization_id = $org_data['id'];
} else {
    $_SESSION['error'] = "Organization details not found for this user.";
    header('Location: index.php');
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
    $_SESSION['error'] = "No registration fields configured.";
    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include 'head_includes.php'; ?>
    <title>Users</title>
    <style>
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0 py-4">
        <div class="container">
            <h1 class="mb-4">Users</h1>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="add-single-tab" data-bs-toggle="tab" data-bs-target="#add-single-tab-pane" type="button" role="tab" aria-controls="add-single-tab-pane" aria-selected="true">Add Single User</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-excel-tab" data-bs-toggle="tab" data-bs-target="#add-excel-tab-pane" type="button" role="tab" aria-controls="add-excel-tab-pane" aria-selected="false">Add Users from Excel</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <!-- Add Single User Tab Pane -->
                <div class="tab-pane fade show active p-4 border border-top-0" id="add-single-tab-pane" role="tabpanel" aria-labelledby="add-single-tab" tabindex="0">
                    <form id="addUserForm" enctype="multipart/form-data">
                        <div class="row">
                            <?php foreach ($fields_to_display as $field): ?>
                                <?php if ($field['field'] === 'name'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                <?php elseif ($field['field'] === 'email'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                <?php elseif ($field['field'] === 'phone'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10,15}" required>
                                        <small class="form-text text-muted">Enter a valid phone number (10-15 digits).</small>
                                    </div>
                                <?php elseif ($field['field'] === 'gender'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-control" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                <?php elseif ($field['field'] === 'govt_id'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="govt_id" class="form-label">Government ID</label>
                                        <input type="text" class="form-control" id="govt_id" name="govt_id">
                                    </div>
                                <?php elseif ($field['field'] === 'govt_id_link'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="govt_id_link" class="form-label">Government ID File</label>
                                        <input type="file" class="form-control" id="govt_id_link" name="govt_id_link" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                <?php elseif ($field['field'] === 'food'): ?>
                                    <div class="col-md-6 mb-3">
                                        <label for="food" class="form-label">Food Preference</label>
                                        <select class="form-control" id="food" name="food">
                                            <option value="">Select Preference</option>
                                            <option value="Vegetarian">Vegetarian</option>
                                            <option value="Non-Vegetarian">Non-Vegetarian</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Add User</button>
                    </form>
                </div>

                <!-- Add Users from Excel Tab Pane -->
                <div class="tab-pane fade p-4 border border-top-0" id="add-excel-tab-pane" role="tabpanel" aria-labelledby="add-excel-tab" tabindex="0">
                    <form id="addExcelForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excelFile" class="form-label">Upload Excel File</label>
                            <input type="file" class="form-control" id="excelFile" name="excelFile" required accept=".csv, .xlsx">
                            <div class="form-text">
                                Please ensure the first row of your Excel file contains headers that match your selected registration fields (e.g., 'name', 'email', 'phone').
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">Upload and Add Users</button>
                    </form>
                </div>
            </div>

            <!-- Modal for user details -->
            <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modalQrCode" src="" alt="QR Code" class="img-fluid mb-3">
                            <p><strong>Name:</strong> <span id="modalName"></span></p>
                            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
                            <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <script>
        $(document).ready(function() {
            <?php
            if (!empty($success)) {
                echo "showSweetAlert('success', '" . addslashes($success) . "');";
            }
            if (!empty($error)) {
                echo "showSweetAlert('error', '" . addslashes($error) . "');";
            }
            ?>

            // Handle form submission with AJAX for single user
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Creating User...',
                    text: 'Please wait while user is being created.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData(this);

                $.ajax({
                    url: 'api/add_user.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            showSweetAlert('success', response.message);
                            $('#addUserForm')[0].reset();
                        } else {
                            showSweetAlert('error', response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.close();
                        showSweetAlert('error', 'An error occurred. Please try again.');
                    }
                });
            });

            // Handle form submission with AJAX for Excel upload
            $('#addExcelForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Adding Users from Excel...',
                    html: 'This may take a moment. Please do not close this window.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData(this);

                $.ajax({
                    url: 'api/add_users_from_excel.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            showSweetAlert('success', response.message);
                            $('#addExcelForm')[0].reset();
                        } else {
                            showSweetAlert('error', response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.close();
                        showSweetAlert('error', 'An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>

</html>
