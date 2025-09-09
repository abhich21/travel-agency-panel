<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/helper_function.php';

$response = array('status' => 'error', 'message' => 'Invalid request.');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === TRUE) {
    $userId = $_POST['userId'] ?? null;
    $userName = $_POST['userName'] ?? '';
    $orgTitle = $_POST['orgTitle'] ?? '';
    $oldLogoUrl = $_POST['oldLogoUrl'] ?? '';
    // Use `??` to check for multiple possible keys to ensure color values are received
    $bgColor = $_POST['bgColor'] ?? $_POST['bgColor_hex'] ?? '';
    $textColor = $_POST['textColor'] ?? $_POST['textColor_hex'] ?? '';

    if ($userId) {
        $conn->begin_transaction();
        $logoUrl = $oldLogoUrl;

        try {
            // Check for new logo upload
            if (isset($_FILES['logoUrl']) && $_FILES['logoUrl']['error'] === UPLOAD_ERR_OK) {
                // Delete old logo from S3 if it exists
                if (!empty($oldLogoUrl)) {
                    if (!deleteFromS3($s3Client, $bucketName, $oldLogoUrl)) {
                        throw new Exception('Failed to delete old logo from S3.');
                    }
                }
                
                // Upload new logo to S3
                $uploadResult = uploadToS3($s3Client, $bucketName, $_FILES['logoUrl'], $orgTitle, 'logos');
                if (!$uploadResult['success']) {
                    throw new Exception('Failed to upload new logo to S3: ' . ($uploadResult['error'] ?? 'Unknown error.'));
                }
                $logoUrl = $uploadResult['url'];
            }

            // Update users table
            $sqlUser = "UPDATE users SET user_name = ? WHERE id = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("si", $userName, $userId);
            if (!$stmtUser->execute()) {
                throw new Exception('Database error: Failed to update user record.');
            }
            $stmtUser->close();

            // Update organizations table
            $sqlOrg = "UPDATE organizations SET title = ?, logo_url = ?, bg_color = ?, text_color = ? WHERE user_id = ?";
            $stmtOrg = $conn->prepare($sqlOrg);
            $stmtOrg->bind_param("ssssi", $orgTitle, $logoUrl, $bgColor, $textColor, $userId);
            if (!$stmtOrg->execute()) {
                throw new Exception('Database error: Failed to update organization record.');
            }
            $stmtOrg->close();

            $conn->commit();
            $response = array('status' => 'success', 'message' => 'User and organization updated successfully!');

        } catch (Exception $e) {
            $conn->rollback();
            $response = array('status' => 'error', 'message' => $e->getMessage());
        }
    }
}

echo json_encode($response);
$conn->close();
