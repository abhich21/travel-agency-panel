<?php
// admin/manage_faq.php

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

// Fetch all FAQs
$faqs = [];
$stmt_faq = $conn->prepare("SELECT * FROM faqs WHERE organization_id = ? ORDER BY sort_order ASC");
$stmt_faq->bind_param("i", $organization_id);
$stmt_faq->execute();
$result_faq = $stmt_faq->get_result();
while ($item = $result_faq->fetch_assoc()) {
    $faqs[] = $item;
}
$stmt_faq->close();

$user_name = $_SESSION['admin_user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Manage FAQs</title>
        <script src="https://cdn.tiny.cloud/1/1lpfjgdxu7dqlbtcuzjkds9eot8zzw6q8hjuk7el0rl2cwft/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>

</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Manage FAQs</h4>
                    <div>
                        <button id="addNewFaqBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New FAQ
                        </button>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Answer (Snippet)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($faqs)): ?>
                                    <?php foreach ($faqs as $faq): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($faq['question']); ?></td>
                                            <td><?php echo substr(strip_tags($faq['answer']), 0, 70) . '...'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info edit-btn" 
                                                    data-id="<?php echo $faq['id']; ?>"
                                                    data-question="<?php echo htmlspecialchars($faq['question']); ?>"
                                                    data-answer="<?php echo htmlspecialchars($faq['answer']); ?>">
                                                    Edit
                                                </button>
                                               <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $faq['id']; ?>">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No FAQs found. Add one to get started!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>

    <div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="faqForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="faqModalLabel">Add New FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="faq_id" id="faqId">
                        <div class="mb-3">
                            <label for="faqQuestion" class="form-label">Question</label>
                            <textarea class="form-control" id="faqQuestion" name="question" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="faqAnswer" class="form-label">Answer</label>
                            <textarea id="faqAnswer" name="answer"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Initialize TinyMCE on the answer textarea
    tinymce.init({
        selector: 'textarea#faqAnswer',
        plugins: 'lists link image table code help wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | code'
    });

    $(document).ready(function() {
        // Show modal for adding a new FAQ
        $('#addNewFaqBtn').on('click', function() {
            $('#faqForm')[0].reset();
            tinymce.get('faqAnswer').setContent('');
            $('#faqId').val('');
            $('#faqModalLabel').text('Add New FAQ');
            $('#faqModal').modal('show');
        });

        // NEW: Show modal for editing an existing FAQ
        $('tbody').on('click', '.edit-btn', function() {
            const button = $(this);
            $('#faqForm')[0].reset();
            
            // Populate form with data from the button
            $('#faqId').val(button.data('id'));
            $('#faqQuestion').val(button.data('question'));
            tinymce.get('faqAnswer').setContent(button.data('answer'));

            $('#faqModalLabel').text('Edit FAQ');
            $('#faqModal').modal('show');
        });

        // UPDATED: Handle form submission for BOTH adding and editing
        $('#faqForm').on('submit', function(e) {
            e.preventDefault();
            tinymce.triggerSave();
            const formData = new FormData(this);
            const faqId = $('#faqId').val();
            let apiUrl = (faqId) ? 'api/edit_faq_item.php' : 'api/add_faq_item.php';

            $.ajax({
                url: apiUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    $('#faqModal').modal('hide');
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
        
         // ADD THIS NEW CODE BLOCK for the delete button
    $('tbody').on('click', '.delete-btn', function() {
        const faqId = $(this).data('id');

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
                $.ajax({
                    url: 'api/delete_faq_item.php',
                    type: 'POST',
                    data: { faq_id: faqId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSweetAlert('success', response.message, true); // Reload on success
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

    });
    </script>
</body>
</html>