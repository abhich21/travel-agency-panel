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

// Registered table fields for selection
$registered_fields = [
    ['column' => 'name', 'label' => 'Name'],
    ['column' => 'gender', 'label' => 'Gender'],
    ['column' => 'email', 'label' => 'Email'],
    ['column' => 'phone', 'label' => 'Phone'],
    ['column' => 'location', 'label' => 'Location'],
    ['column' => 'govt_id', 'label' => 'Government ID'],
    ['column' => 'govt_id_link', 'label' => 'Government ID Link'],
    ['column' => 'food', 'label' => 'Food Preference']
];

$organization_id = null;
$existing_fields_data = [];

// Get the organization_id for the current admin from the organizations table
$stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ?");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org_data = $result_org->fetch_assoc();

if ($org_data) {
    $organization_id = $org_data['id'];
    
    // Now, get the existing fields from the registration_fields table
    $stmt_reg_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
    $stmt_reg_fields->bind_param("i", $organization_id);
    $stmt_reg_fields->execute();
    $result_reg_fields = $stmt_reg_fields->get_result();
    $reg_fields_data = $result_reg_fields->fetch_assoc();

    if ($reg_fields_data && !empty($reg_fields_data['fields'])) {
        $existing_fields_data = json_decode($reg_fields_data['fields'], true);
        if ($existing_fields_data === null) {
            $existing_fields_data = [];
        }
    }
} else {
    // Handle the case where no organization is found for the user
    $_SESSION['error'] = "Organization details not found for this user.";
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['selected_fields']) || !is_array($_POST['selected_fields'])) {
        // If no fields are selected, we should delete the record or set an empty array
        $selected_fields_array = [];
    } else {
        $selected_fields_columns = $_POST['selected_fields'];
        $selected_fields_array = [];
    
        foreach ($registered_fields as $field) {
            if (in_array($field['column'], $selected_fields_columns)) {
                $selected_fields_array[] = ['field' => $field['column'], 'label' => $field['label']];
            }
        }
    }
    
    $fields_json = json_encode($selected_fields_array);

    // Check if a record for this organization already exists in registration_fields
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM registration_fields WHERE organization_id = ?");
    $stmt_check->bind_param("i", $organization_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_count = $result_check->fetch_row()[0];

    if ($row_count > 0) {
        // Update the existing record
        $stmt_update = $conn->prepare("UPDATE registration_fields SET fields = ? WHERE organization_id = ?");
        $stmt_update->bind_param("si", $fields_json, $organization_id);
        if ($stmt_update->execute()) {
            $_SESSION['success'] = "Registration fields updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating fields: " . $conn->error;
        }
    } else {
        // Insert a new record
        $stmt_insert = $conn->prepare("INSERT INTO registration_fields (organization_id, fields) VALUES (?, ?)");
        $stmt_insert->bind_param("is", $organization_id, $fields_json);
        if ($stmt_insert->execute()) {
            $_SESSION['success'] = "Registration fields saved successfully!";
        } else {
            $_SESSION['error'] = "Error saving fields: " . $conn->error;
        }
    }
    
    header('Location: registration_fields.php');
    exit;
}

$selected_fields_columns = array_column($existing_fields_data, 'field');

?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php include 'head_includes.php'; ?>
    <title>Manage Registration Fields</title>
    <style>
        .card {
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .05);
        }
        .form-check-label {
            cursor: pointer;
        }
        
    </style>
</head>
<body class="d-flex flex-column h-100">
    <?php include 'navbar.php'; ?>
    <main class="flex-shrink-0">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card p-4">
                        <h2 class="card-title text-center mb-4">Select Registration Fields</h2>
                        <form id="registration-fields-form" action="" method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select the fields you want to include in your registration form:</label>
                                <div class="row">
                                    <?php foreach ($registered_fields as $field): ?>
                                        <div class="col-sm-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="selected_fields[]" value="<?php echo htmlspecialchars($field['column']); ?>" id="field-<?php echo htmlspecialchars($field['column']); ?>"
                                                    <?php echo in_array($field['column'], $selected_fields_columns) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="field-<?php echo htmlspecialchars($field['column']); ?>">
                                                    <?php echo htmlspecialchars($field['label']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-custom btn-lg">Save Fields</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
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
    <?php include 'footer.php'; ?>
    <script>
        // Display one-time success or error toast on page load
        $(document).ready(function() {
            <?php
            if (!empty($success)) {
                echo "showSweetAlert('success', '" . addslashes($success) . "');";
            }
            if (!empty($error)) {
                echo "showSweetAlert('error', '" . addslashes($error) . "');";
            }
            ?>
        });
    </script>
</body>
</html>
