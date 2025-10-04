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

// Get the organization_id 
$organization_id = $_SESSION['organization_id'];


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
            <h1 class="mb-4">Promoters</h1>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="add-single-tab" data-bs-toggle="tab" data-bs-target="#add-single-tab-pane" type="button" role="tab" aria-controls="add-single-tab-pane" aria-selected="true">Add Promoter</button>
                </li>
                
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <!-- Add Single User Tab Pane -->
                <div class="tab-pane fade show active p-4 border border-top-0" id="add-single-tab-pane" role="tabpanel" aria-labelledby="add-single-tab" tabindex="0">
                    <form id="addPromoterForm" enctype="multipart/form-data">
                        <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="promoter_username" class="form-label">Promoter Username</label>
                                        <input type="text" class="form-control" id="promoter_username" name="promoter_username" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="promoter_password" class="form-label">Promoter Password</label>
                                        <input type="text" class="form-control" id="promoter_password" name="promoter_password">
                                    </div>       
                        </div>
                        <button type="submit" class="btn btn-primary mt-3 btn-custom">Add Promoter</button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4>Promoters</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="promotersTable" class="table table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>Promoter Username</th>
                                    <th>Promoter Password</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
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
    <div class="modal fade" id="editPromoterModal" tabindex="-1" aria-labelledby="editPromoterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPromoterModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="promoter_id" id="editPromoterId">
                        <div id="editFieldsContainer">
                            <!-- Dynamic fields will be populated here -->
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
    <style>
        .btn-custom {
            background-color: <?php echo $nav_bg_color; ?> !important;
            color: <?php echo $nav_text_color; ?> !important;
        }
        .btn-custom:hover {
        /* Mix the base color with 10% black to darken it */
        background-color: color-mix(in srgb, <?php echo $nav_bg_color; ?>, black 10%) !important;
        color: color-mix(in srgb, <?php echo $nav_text_color; ?>, black 10%) !important;
        }

        .btn-custom:active {
        /* Mix the base color with 20% black for an "active" or "pressed" state */
        background-color: color-mix(in srgb, <?php echo $nav_bg_color; ?>, black 20%) !important;
        color: color-mix(in srgb, <?php echo $nav_text_color; ?>, black 20%) !important;
        }
    </style>
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
            let promotersTable;
            let currentUniqueFields = {};
            let isDataTableInitialized = false;

            // Load data into the DataTable via AJAX
            $.ajax({
                url: 'api/get_promoters.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.data && response.data.length > 0) {
                        const firstRow = response.data[0];
                        
                        // Filter out govt_id_link from the dynamic fields array to prevent duplication
                        const filteredFields = firstRow.fields.filter(field => field.name !== 'govt_id_link');

                        // Dynamically create headers and columns based on the filtered 'fields' array
                        const columns = filteredFields.map(field => ({
                            // Use a function to correctly access the nested data
                            data: function(row, type, set, meta) {
                                const foundField = row.fields.find(f => f.name === field.name);
                                return foundField ? foundField.value : '';
                            },
                            title: field.name.replace(/_/g, ' ').toUpperCase()
                        }));

                        // Add top-level fields like qr_code and govt_id_link as separate columns
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

                        // Add the 'Actions' column
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

                        
                        // Destroy existing DataTable instance if it exists
                        if ($.fn.DataTable.isDataTable('#promotersTable')) {
                            promotersTable.destroy();
                        }
                        
                        promotersTable = $('#promotersTable').DataTable({
                            data: response.data,
                            columns: columns,
                            pageLength: 10,
                            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                            responsive: true
                        });
                        isDataTableInitialized = true;
                    } else {
                        // Display "No data found" if the response is empty
                        $('#promotersTable').html('<p class="text-center mt-4">No data found.</p>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: ", textStatus, errorThrown);
                    $('#promotersTable').html('<p class="text-center mt-4 text-danger">An error occurred while fetching data. Please try again later.</p>');
                }
            });

            // Handle form submission with AJAX for single user
            $('#addPromoterForm').on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Creating Promoter...',
                    text: 'Please wait while promoter is being created.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData(this);

                $.ajax({
                    url: 'api/add_promoter.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            showSweetAlert('success', response.message);
                            $('#addPromoterForm')[0].reset();
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
