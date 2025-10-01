<?php
// admin/manage_agenda.php

session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE || !isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];
$organization_id = null;

// Get Organization ID for the admin
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
if ($org = $result_org->fetch_assoc()) {
    $organization_id = $org['id'];
}
$stmt_org->close();

if (!$organization_id) {
    die("Could not find an organization for this admin.");
}

// Fetch all agenda items and group them by day
$agenda_by_day = [];
$stmt_agenda = $conn->prepare("SELECT * FROM agenda_items WHERE organization_id = ? ORDER BY day_number, sort_order ASC");
$stmt_agenda->bind_param("i", $organization_id);
$stmt_agenda->execute();
$result_agenda = $stmt_agenda->get_result();
while ($item = $result_agenda->fetch_assoc()) {
    $agenda_by_day[$item['day_number']][] = $item;
}
$stmt_agenda->close();

// Determine the total number of days
$total_days = !empty($agenda_by_day) ? max(array_keys($agenda_by_day)) : 1;

$user_name = $_SESSION['admin_user_name'] ?? 'Admin';

?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Manage Event Agenda</title>
    <script src="https://cdn.tiny.cloud/1/1lpfjgdxu7dqlbtcuzjkds9eot8zzw6q8hjuk7el0rl2cwft/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Manage Event Agenda</h4>
                    <div>
                        <button id="addNewItemBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Item
                        </button>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="dayTabs" role="tablist">
                        <?php for ($i = 1; $i <= $total_days; $i++): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $i === 1 ? 'active' : ''; ?>" id="day-<?php echo $i; ?>-tab" data-bs-toggle="tab" data-bs-target="#day-<?php echo $i; ?>-content" type="button" role="tab">Day <?php echo $i; ?></button>
                        </li>
                        <?php endfor; ?>
                    </ul>

                    <div class="tab-content" id="dayTabsContent">
                        <?php for ($i = 1; $i <= $total_days; $i++): ?>
                        <div class="tab-pane fade <?php echo $i === 1 ? 'show active' : ''; ?>" id="day-<?php echo $i; ?>-content" role="tabpanel">
                            <div class="table-responsive mt-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($agenda_by_day[$i])): ?>
                                            <?php foreach ($agenda_by_day[$i] as $item): ?>
                                                <tr data-id="<?php echo $item['id']; ?>">
                                                    <td><?php echo htmlspecialchars($item['item_time']); ?></td>
                                                    <td>
                                                        <i class="<?php echo htmlspecialchars($item['icon_class']); ?> me-2"></i>
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </td>
                                                    <td><?php echo substr(strip_tags($item['description']), 0, 50) . '...'; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info edit-btn"
                                                            data-id="<?php echo $item['id']; ?>"
                                                            data-day="<?php echo $item['day_number']; ?>"
                                                            data-time="<?php echo htmlspecialchars($item['item_time']); ?>"
                                                            data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                                            data-icon="<?php echo htmlspecialchars($item['icon_class']); ?>"
                                                            data-description="<?php echo htmlspecialchars($item['description']); ?>">
                                                            Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $item['id']; ?>">Delete</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No agenda items for this day.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <div class="modal fade" id="agendaItemModal" tabindex="-1" aria-labelledby="agendaItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="agendaItemForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="agendaItemModalLabel">Add New Agenda Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="item_id" id="itemId">
                        <div class="mb-3">
                            <label for="dayNumber" class="form-label">Day Number</label>
                            <input type="number" class="form-control" id="dayNumber" name="day_number" min="1" value="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="itemTime" class="form-label">Time (e.g., 11:00 – 12:00)</label>
                            <input type="text" class="form-control" id="itemTime" name="item_time">
                        </div>
                        <div class="mb-3">
                            <label for="itemTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="itemTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="itemDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="itemDescription" name="description" rows="3"></textarea>
                        </div>
                         <div class="mb-3">
                            <label for="itemIcon" class="form-label">Font Awesome Icon Class</label>
                            <input type="text" class="form-control" id="itemIcon" name="icon_class" placeholder="e.g., fas fa-utensils">
                            <small class="form-text text-muted">Find icons on the <a href="https://fontawesome.com/v5/search?m=free" target="_blank">Font Awesome</a> website.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Initialize TinyMCE on the description textarea
    tinymce.init({
        selector: 'textarea#itemDescription',
        plugins: 'lists link image table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code'
    });

    $(document).ready(function() {
        // Show modal for adding a new item
        $('#addNewItemBtn').on('click', function() {
            $('#agendaItemForm')[0].reset();
            tinymce.get('itemDescription').setContent(''); // Clear TinyMCE content
            $('#itemId').val('');
            $('#agendaItemModalLabel').text('Add New Agenda Item');
            $('#agendaItemModal').modal('show');
        });

        // Show modal for editing an existing item
        $('.edit-btn').on('click', function() {
            const button = $(this);
            $('#agendaItemForm')[0].reset();
            
            // Populate form with data from the button's data-* attributes
            $('#itemId').val(button.data('id'));
            $('#dayNumber').val(button.data('day'));
            $('#itemTime').val(button.data('time'));
            $('#itemTitle').val(button.data('title'));
            $('#itemIcon').val(button.data('icon'));
            tinymce.get('itemDescription').setContent(button.data('description'));

            $('#agendaItemModalLabel').text('Edit Agenda Item');
            $('#agendaItemModal').modal('show');
        });

        // Handle form submission for BOTH adding and editing
        $('#agendaItemForm').on('submit', function(e) {
            e.preventDefault();
            
            tinymce.triggerSave(); // IMPORTANT: Get content from TinyMCE
            
            const formData = new FormData(this);
            const itemId = $('#itemId').val();
            let apiUrl = (itemId) ? 'api/edit_agenda_item.php' : 'api/add_agenda_item.php';

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#agendaItemModal').modal('hide');
                    if (response.success) {
                        showSweetAlert('success', response.message, true);
                    } else {
                        showSweetAlert('error', response.message);
                    }
                },
                error: function() {
                    showSweetAlert('error', 'An error occurred.');
                }
            });
        });
        // --- PASTE THE NEW DELETE CODE HERE ---
    // Handle Delete button click
    $('tbody').on('click', '.delete-btn', function() {
        const itemId = $(this).data('id');

        // Show a confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            // If the user confirms, proceed with deletion
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/delete_agenda_item.php',
                    type: 'POST',
                    data: { item_id: itemId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message and reload the page
                            showSweetAlert('success', response.message, true); 
                        } else {
                            showSweetAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showSweetAlert('error', 'An error occurred.');
                    }
                });
            }
        });
    });
    // --- END OF NEW CODE ---
    });
    </script>
</body>
</html>