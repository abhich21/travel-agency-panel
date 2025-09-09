<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/helper_function.php';

$response = array('status' => 'error', 'message' => 'Invalid request.');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === TRUE) {
    $userId = $_POST['userId'] ?? null;
    $logoUrl = $_POST['logoUrl'] ?? null;

    if ($userId) {
        $conn->begin_transaction();

        try {
            // Delete logo from S3 if a URL exists
            if (!empty($logoUrl)) {
                if (!deleteFromS3($s3Client, $bucketName, $logoUrl)) {
                    throw new Exception('Failed to delete old logo from S3.');
                }
            }

            // Delete from organizations table
            $sqlOrg = "DELETE FROM organizations WHERE user_id = ?";
            $stmtOrg = $conn->prepare($sqlOrg);
            $stmtOrg->bind_param("i", $userId);
            if (!$stmtOrg->execute()) {
                throw new Exception('Database error: Failed to delete organization record.');
            }
            $stmtOrg->close();

            // Delete from users table
            $sqlUser = "DELETE FROM users WHERE id = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("i", $userId);
            if (!$stmtUser->execute()) {
                throw new Exception('Database error: Failed to delete user record.');
            }
            $stmtUser->close();

            $conn->commit();
            $response = array('status' => 'success', 'message' => 'User and associated data deleted successfully!');

        } catch (Exception $e) {
            $conn->rollback();
            $response = array('status' => 'error', 'message' => $e->getMessage());
        }
    }
}

echo json_encode($response);
$conn->close();
