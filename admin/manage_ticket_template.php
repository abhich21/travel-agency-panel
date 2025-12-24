<?php
// admin/manage_ticket_template.php - Configure ticket template design
session_start();
require_once '../config/config.php';

// Security Check
if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== TRUE) {
    header("Location: login.php");
    exit;
}

$admin_user_id = $_SESSION['admin_user_id'];

// Get Organization
$stmt_org = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE user_id = ? LIMIT 1");
$stmt_org->bind_param("i", $admin_user_id);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org = $result_org->fetch_assoc();
$organization_id = $org['id'] ?? 0;
$org_title = $org['title'] ?? 'Organization';
$nav_bg_color = $org['bg_color'] ?? '#1a1a1a';
$nav_text_color = $org['text_color'] ?? '#ffffff';
$logo_url = $org['logo_url'] ?? '';
$stmt_org->close();

// Get registration fields for checkbox options
$available_fields = [];
$stmt_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
$stmt_fields->bind_param("i", $organization_id);
$stmt_fields->execute();
$result_fields = $stmt_fields->get_result();
if ($fields_data = $result_fields->fetch_assoc()) {
    $decoded = json_decode($fields_data['fields'], true);
    foreach ($decoded as $field) {
        $available_fields[$field['field']] = $field['label'];
    }
}
$stmt_fields->close();

// Get existing template or set defaults
$template = [
    'id' => 0,
    'template_name' => 'Event Ticket',
    'background_color' => '#FFFFFF',
    'header_color' => $nav_bg_color,
    'text_color' => '#333333',
    'show_qr_code' => 1,
    'show_fields' => ['name', 'email'],
    'custom_message' => 'Thank you for registering!',
    'logo_position' => 'top'
];

$stmt_template = $conn->prepare("SELECT * FROM ticket_templates WHERE organization_id = ? LIMIT 1");
$stmt_template->bind_param("i", $organization_id);
$stmt_template->execute();
$result_template = $stmt_template->get_result();
if ($existing = $result_template->fetch_assoc()) {
    $template = $existing;
    $template['show_fields'] = json_decode($existing['show_fields'], true) ?? ['name', 'email'];
}
$stmt_template->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = trim($_POST['template_name'] ?? 'Event Ticket');
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $header_color = $_POST['header_color'] ?? $nav_bg_color;
    $text_color = $_POST['text_color'] ?? '#333333';
    $show_qr_code = isset($_POST['show_qr_code']) ? 1 : 0;
    $show_fields = isset($_POST['show_fields']) ? json_encode($_POST['show_fields']) : '["name"]';
    $custom_message = trim($_POST['custom_message'] ?? '');
    $logo_position = $_POST['logo_position'] ?? 'top';
    
    if ($template['id'] > 0) {
        // Update existing
        $stmt = $conn->prepare("UPDATE ticket_templates SET template_name=?, background_color=?, header_color=?, text_color=?, show_qr_code=?, show_fields=?, custom_message=?, logo_position=? WHERE id=?");
        $stmt->bind_param("ssssisisi", $template_name, $background_color, $header_color, $text_color, $show_qr_code, $show_fields, $custom_message, $logo_position, $template['id']);
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO ticket_templates (organization_id, template_name, background_color, header_color, text_color, show_qr_code, show_fields, custom_message, logo_position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssisss", $organization_id, $template_name, $background_color, $header_color, $text_color, $show_qr_code, $show_fields, $custom_message, $logo_position);
    }
    
    if ($stmt->execute()) {
        $success_message = "Ticket template saved successfully!";
        // Refresh template data
        header("Location: manage_ticket_template.php?saved=1");
        exit;
    } else {
        $error_message = "Failed to save: " . $stmt->error;
    }
    $stmt->close();
}

$current_page = 'manage_ticket_template.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Template - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .preview-card {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            overflow: hidden;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .preview-header {
            padding: 1.5rem;
            text-align: center;
        }
        .preview-body {
            padding: 1.5rem;
        }
        .preview-qr {
            width: 120px;
            height: 120px;
            background: #f0f0f0;
            margin: 1rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ccc;
        }
        .preview-field {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: rgba(0,0,0,0.03);
            border-radius: 4px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="fas fa-ticket-alt me-2"></i>Ticket Template Designer</h2>
                
                <?php if (isset($_GET['saved'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>Ticket template saved successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <!-- Settings Form -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Template Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="templateForm">
                            <div class="mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="template_name" class="form-control" value="<?php echo htmlspecialchars($template['template_name']); ?>">
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Header Color</label>
                                    <input type="color" name="header_color" class="form-control form-control-color w-100" id="headerColor" value="<?php echo $template['header_color']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Background</label>
                                    <input type="color" name="background_color" class="form-control form-control-color w-100" id="bgColor" value="<?php echo $template['background_color']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Text Color</label>
                                    <input type="color" name="text_color" class="form-control form-control-color w-100" id="textColor" value="<?php echo $template['text_color']; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Logo Position</label>
                                <select name="logo_position" class="form-select" id="logoPosition">
                                    <option value="top" <?php echo $template['logo_position'] === 'top' ? 'selected' : ''; ?>>Top</option>
                                    <option value="bottom" <?php echo $template['logo_position'] === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                                    <option value="none" <?php echo $template['logo_position'] === 'none' ? 'selected' : ''; ?>>Don't show logo</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_qr_code" id="showQrCode" <?php echo $template['show_qr_code'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="showQrCode">
                                        <i class="fas fa-qrcode me-1"></i>Show QR Code
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Fields to Display</label>
                                <div class="row">
                                    <?php foreach ($available_fields as $field => $label): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input field-checkbox" type="checkbox" name="show_fields[]" value="<?php echo $field; ?>" id="field_<?php echo $field; ?>"
                                                    <?php echo in_array($field, $template['show_fields']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="field_<?php echo $field; ?>">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Custom Message (bottom of ticket)</label>
                                <textarea name="custom_message" class="form-control" rows="2" id="customMessage"><?php echo htmlspecialchars($template['custom_message']); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Save Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Live Preview -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="preview-card" id="ticketPreview">
                            <div class="preview-header" id="previewHeader" style="background-color: <?php echo $template['header_color']; ?>; color: white;">
                                <?php if ($template['logo_position'] === 'top' && !empty($logo_url)): ?>
                                    <img src="<?php echo $logo_url; ?>" alt="Logo" style="max-height: 50px; margin-bottom: 0.5rem;">
                                <?php endif; ?>
                                <h4 class="mb-0" id="previewTitle"><?php echo $org_title; ?></h4>
                                <small>Event Ticket</small>
                            </div>
                            <div class="preview-body" id="previewBody" style="background-color: <?php echo $template['background_color']; ?>; color: <?php echo $template['text_color']; ?>;">
                                <div id="previewFields">
                                    <?php foreach ($template['show_fields'] as $field): ?>
                                        <div class="preview-field">
                                            <strong><?php echo $available_fields[$field] ?? ucfirst($field); ?>:</strong>
                                            <span class="text-muted">Sample Data</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="preview-qr" id="previewQr" style="<?php echo !$template['show_qr_code'] ? 'display:none;' : ''; ?>">
                                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                                </div>
                                
                                <p class="text-center small mt-3 mb-0" id="previewMessage"><?php echo htmlspecialchars($template['custom_message']); ?></p>
                                
                                <?php if ($template['logo_position'] === 'bottom' && !empty($logo_url)): ?>
                                    <div class="text-center mt-3">
                                        <img src="<?php echo $logo_url; ?>" alt="Logo" style="max-height: 40px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">This is a preview. Actual ticket will include real user data.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live preview updates
        document.getElementById('headerColor').addEventListener('input', function() {
            document.getElementById('previewHeader').style.backgroundColor = this.value;
        });
        
        document.getElementById('bgColor').addEventListener('input', function() {
            document.getElementById('previewBody').style.backgroundColor = this.value;
        });
        
        document.getElementById('textColor').addEventListener('input', function() {
            document.getElementById('previewBody').style.color = this.value;
        });
        
        document.getElementById('showQrCode').addEventListener('change', function() {
            document.getElementById('previewQr').style.display = this.checked ? 'flex' : 'none';
        });
        
        document.getElementById('customMessage').addEventListener('input', function() {
            document.getElementById('previewMessage').textContent = this.value;
        });
    </script>
</body>
</html>
