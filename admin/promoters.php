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
        </div>
    </main>

    <?php include 'footer.php'; ?>
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
