<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

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

// Fetch users with 'admin' role and their organization details
$sql = "SELECT
            u.id,
            u.user_name,
            u.password,
            u.role,
            u.created_at,
            o.title AS organization_title,
            o.logo_url,
            o.bg_color,
            o.text_color
        FROM users u
        LEFT JOIN organizations o ON u.id = o.user_id
        WHERE u.role = 'admin'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'head_includes.php'; ?>
    <title>Admin Dashboard</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .logo-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .color-swatch {
            width: 30px;
            height: 30px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: inline-block;
            vertical-align: middle;
            margin: 0 5px;
        }
        .color-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .color-input-container input[type="text"] {
            flex-grow: 1;
        }
        .preview-logo-thumb {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            margin-top: 10px;
            display: block;
        }
        #loader {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            border: 8px solid #f3f3f3;
            border-radius: 50%;
            border-top: 8px solid #3498db;
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="card p-4">
            <h2 class="text-center mb-4">Admin User List</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="admin-table">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>User Name</th>
                            <th>Organization Title</th>
                            <th>Logo</th>
                            <th>Background Color</th>
                            <th>Text Color</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                        ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['organization_title']); ?></td>
                                    <td>
                                        <?php if ($row['logo_url']) { ?>
                                            <img src="<?php echo htmlspecialchars($row['logo_url']); ?>" alt="Logo" class="logo-thumb">
                                        <?php } else { ?>
                                            No Logo
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="color-swatch" style="background-color: <?php echo htmlspecialchars($row['bg_color']); ?>"></div>
                                        <span><?php echo htmlspecialchars($row['bg_color']); ?></span>
                                    </td>
                                    <td>
                                        <div class="color-swatch" style="background-color: <?php echo htmlspecialchars($row['text_color']); ?>"></div>
                                        <span><?php echo htmlspecialchars($row['text_color']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['created_at']); ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm edit-btn" data-id="<?php echo $row['id']; ?>" data-user-name="<?php echo htmlspecialchars($row['user_name']); ?>" data-org-title="<?php echo htmlspecialchars($row['organization_title']); ?>" data-logo-url="<?php echo htmlspecialchars($row['logo_url']); ?>" data-bg-color="<?php echo htmlspecialchars($row['bg_color']); ?>" data-text-color="<?php echo htmlspecialchars($row['text_color']); ?>">Edit</button>
                                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>" data-logo-url="<?php echo htmlspecialchars($row['logo_url']); ?>">Delete</button>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Loader -->
    <div id="loader"></div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit User and Organization</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="userId" name="userId">
                        <div class="mb-3">
                            <label for="userName" class="form-label">User Name</label>
                            <input type="text" class="form-control" id="userName" name="userName" required>
                        </div>
                        <div class="mb-3">
                            <label for="orgTitle" class="form-label">Organization Title</label>
                            <input type="text" class="form-control" id="orgTitle" name="orgTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="logoUrl" class="form-label">Logo</label>
                            <input type="file" class="form-control" id="logoUrl" name="logoUrl">
                            <input type="hidden" id="oldLogoUrl" name="oldLogoUrl">
                            <div id="logo-preview-container"></div>
                        </div>
                        
                        <!-- Background Color Input with Picker -->
                        <div class="mb-3">
                            <label for="bgColor" class="form-label">Background Color</label>
                            <div class="input-group color-input-container">
                                <input type="text" class="form-control" id="bgColor_hex" name="bgColor_hex" value="#000000">
                                <input type="color" class="form-control form-control-color" id="bgColor_picker" value="#000000">
                            </div>
                        </div>

                        <!-- Text Color Input with Picker -->
                        <div class="mb-3">
                            <label for="textColor" class="form-label">Text Color</label>
                            <div class="input-group color-input-container">
                                <input type="text" class="form-control" id="textColor_hex" name="textColor_hex" value="#ffffff">
                                <input type="color" class="form-control form-control-color" id="textColor_picker" value="#ffffff">
                            </div>
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

    <?php include 'footer.php'; ?>
    <script>
        // Display success or error toast
        <?php
        if (!empty($success)) {
            echo "showSweetAlert('success', '" . addslashes($success) . "');";
        }
        if (!empty($error)) {
            echo "showSweetAlert('error', '" . addslashes($error) . "');";
        }
        ?>

        // Script to handle the edit modal
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editModal');
            const userIdInput = document.getElementById('userId');
            const userNameInput = document.getElementById('userName');
            const orgTitleInput = document.getElementById('orgTitle');
            const oldLogoUrlInput = document.getElementById('oldLogoUrl');
            const logoPreviewContainer = document.getElementById('logo-preview-container');

            const bgColorHex = document.getElementById('bgColor_hex');
            const bgColorPicker = document.getElementById('bgColor_picker');
            const textColorHex = document.getElementById('textColor_hex');
            const textColorPicker = document.getElementById('textColor_picker');

            // Listen for clicks on all edit buttons
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const id = e.target.dataset.id;
                    const userName = e.target.dataset.userName;
                    const orgTitle = e.target.dataset.orgTitle;
                    const logoUrl = e.target.dataset.logoUrl;
                    const bgColor = e.target.dataset.bgColor;
                    const textColor = e.target.dataset.textColor;

                    // Populate the modal fields
                    userIdInput.value = id;
                    userNameInput.value = userName;
                    orgTitleInput.value = orgTitle;
                    oldLogoUrlInput.value = logoUrl;

                    // Show logo preview if available
                    logoPreviewContainer.innerHTML = '';
                    if (logoUrl) {
                        const img = document.createElement('img');
                        img.src = logoUrl;
                        img.alt = 'Logo Preview';
                        img.classList.add('preview-logo-thumb');
                        logoPreviewContainer.appendChild(img);
                    } else {
                        logoPreviewContainer.innerHTML = '<small class="text-muted">No logo saved</small>';
                    }

                    // Populate color inputs and sync them
                    bgColorHex.value = bgColor;
                    bgColorPicker.value = bgColor;
                    textColorHex.value = textColor;
                    textColorPicker.value = textColor;

                    // Show the modal
                    const modal = new bootstrap.Modal(editModal);
                    modal.show();
                });
            });

            // Synchronize the color picker and text input for background color
            bgColorPicker.addEventListener('input', (e) => {
                bgColorHex.value = e.target.value;
            });
            bgColorHex.addEventListener('input', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    bgColorPicker.value = e.target.value;
                }
            });

            // Synchronize the color picker and text input for text color
            textColorPicker.addEventListener('input', (e) => {
                textColorHex.value = e.target.value;
            });
            textColorHex.addEventListener('input', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    textColorPicker.value = e.target.value;
                }
            });

        });
    </script>
    <script src="main.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#admin-table').DataTable({
                "language": {
                    "emptyTable": "<div class='text-center'>No admin users found.</div>"
                },
                "initComplete": function() {
                    var api = this.api();
                    // Get the search input element
                    var searchInput = $('div.dataTables_filter input');

                    // Bind a keyup event to the search input
                    searchInput.off().on('keyup.DT', function() {
                        // Get the value and search only the second column (index 1) which is "Organization Title"
                        api.column(1).search(this.value).draw();
                    });
                }
            });
        });
    </script>
</body>
</html>
