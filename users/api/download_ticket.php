<?php
// users/api/download_ticket.php - Generate and download PDF ticket

session_start();
require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

// Security Check - must be logged in
if (!isset($_SESSION['attendee_logged_in']) || $_SESSION['attendee_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Please login to download your ticket']);
    exit;
}

// Get organization from view parameter
if (!isset($_GET['view']) || empty($_GET['view'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Organization not specified']);
    exit;
}

$organization_title = $_GET['view'];

// Fetch organization details
$stmt_org = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
$stmt_org->bind_param("s", $organization_title);
$stmt_org->execute();
$result_org = $stmt_org->get_result();
$org = $result_org->fetch_assoc();

if (!$org) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Organization not found']);
    exit;
}

$organization_id = $org['id'];
$org_title = $org['title'];
$logo_url = $org['logo_url'];
$stmt_org->close();

// Fetch user data
$user_email = $_SESSION['attendee_email'];
$stmt_user = $conn->prepare("SELECT * FROM registered WHERE email = ? AND organization_id = ? ORDER BY id DESC LIMIT 1");
$stmt_user->bind_param("si", $user_email, $organization_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if (!$user) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'User not found']);
    exit;
}
$stmt_user->close();

// Fetch field labels
$field_labels = [];
$stmt_fields = $conn->prepare("SELECT fields FROM registration_fields WHERE organization_id = ?");
$stmt_fields->bind_param("i", $organization_id);
$stmt_fields->execute();
$result_fields = $stmt_fields->get_result();
if ($fields_data = $result_fields->fetch_assoc()) {
    $decoded = json_decode($fields_data['fields'], true);
    foreach ($decoded as $field) {
        $field_labels[$field['field']] = $field['label'];
    }
}
$stmt_fields->close();

// Fetch ticket template (or use defaults)
$template = [
    'template_name' => 'Event Ticket',
    'background_color' => '#FFFFFF',
    'header_color' => $org['bg_color'] ?? '#1a1a1a',
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

// Helper function to convert hex to RGB
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}

// Create PDF using TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Event Management System');
$pdf->SetAuthor($org_title);
$pdf->SetTitle('Event Ticket - ' . ($user['name'] ?? 'Attendee'));

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Add a page
$pdf->AddPage();

// Colors
$headerRgb = hexToRgb($template['header_color']);
$bgRgb = hexToRgb($template['background_color']);
$textRgb = hexToRgb($template['text_color']);

// Draw ticket border/background
$pdf->SetFillColor($bgRgb[0], $bgRgb[1], $bgRgb[2]);
$pdf->Rect(15, 15, 180, 250, 'F');

// Draw header section
$pdf->SetFillColor($headerRgb[0], $headerRgb[1], $headerRgb[2]);
$pdf->Rect(15, 15, 180, 45, 'F');

// Logo (if top position)
$yPos = 20;
if ($template['logo_position'] === 'top' && !empty($logo_url)) {
    // Try to add logo from URL
    try {
        $pdf->Image($logo_url, 85, $yPos, 40, 0, '', '', '', false, 300, '', false, false, 0);
        $yPos = 50;
    } catch (Exception $e) {
        // Skip logo if image fails
        $yPos = 25;
    }
} else {
    $yPos = 25;
}

// Organization title in header
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetXY(15, $yPos);
$pdf->Cell(180, 10, $org_title, 0, 1, 'C');

// "Event Ticket" subtitle
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(180, 8, 'Event Ticket', 0, 1, 'C');

// Content section
$yPos = 70;
$pdf->SetTextColor($textRgb[0], $textRgb[1], $textRgb[2]);

// User fields
$pdf->SetFont('helvetica', '', 12);
foreach ($template['show_fields'] as $field) {
    if (isset($user[$field]) && !empty($user[$field])) {
        $label = $field_labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
        
        // Handle special display for "name" field
        if ($field === 'name') {
            $label = 'Full Name';
        }
        
        $pdf->SetXY(25, $yPos);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(50, 8, $label . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(110, 8, $user[$field], 0, 1, 'L');
        $yPos += 10;
    }
}

// QR Code
if ($template['show_qr_code'] && !empty($user['qr_code'])) {
    $yPos += 10;
    $pdf->SetXY(15, $yPos);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(180, 8, 'Your Check-in QR Code', 0, 1, 'C');
    
    try {
        // QR code from URL
        $pdf->Image($user['qr_code'], 75, $yPos + 10, 60, 60, '', '', '', false, 300, '', false, false, 0);
        $yPos += 75;
    } catch (Exception $e) {
        // If QR image fails, show placeholder text
        $pdf->SetXY(15, $yPos + 10);
        $pdf->Cell(180, 30, '[QR Code]', 1, 1, 'C');
        $yPos += 45;
    }
}

// Custom message
if (!empty($template['custom_message'])) {
    $yPos += 10;
    $pdf->SetXY(25, $yPos);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->MultiCell(160, 6, $template['custom_message'], 0, 'C');
}

// Logo at bottom (if bottom position)
if ($template['logo_position'] === 'bottom' && !empty($logo_url)) {
    try {
        $pdf->Image($logo_url, 80, 240, 50, 0, '', '', '', false, 300, '', false, false, 0);
    } catch (Exception $e) {
        // Skip if fails
    }
}

// Add border around ticket
$pdf->SetDrawColor(200, 200, 200);
$pdf->Rect(15, 15, 180, 250, 'D');

// Output PDF
$filename = 'Ticket_' . preg_replace('/[^a-zA-Z0-9]/', '_', $user['name'] ?? 'Attendee') . '.pdf';
$pdf->Output($filename, 'D'); // D = Download
exit;
