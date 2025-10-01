<?php
// close_chat.php - API endpoint for an admin to close a chat session.

// --- 1. SESSION AND SECURITY ---
// Start the session to verify the admin's login credentials.
session_start();

// Include the main configuration file for the database connection.
require_once '../../config/config.php';

// Set the content type to JSON for the response.
header('Content-Type: application/json');

// Security Check: Ensure the user is a logged-in admin.
// If not, send an authentication error and stop the script.
if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== TRUE) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

// --- 2. GET AND VALIDATE INPUT ---
// Get the JSON data sent from the admin's browser.
$input = json_decode(file_get_contents('php://input'), true);
$user_session_id = $input['user_session_id'] ?? null;

// Validate that the user_session_id was provided.
// If not, the script cannot know which chat to close.
if (empty($user_session_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Session ID is missing.']);
    exit;
}

// --- 3. DATABASE OPERATION ---
// Prepare an SQL statement to update the status of all messages for a given session.
// This is more efficient than updating row by row.
$stmt = $conn->prepare("UPDATE chats SET status = 'closed' WHERE user_session_id = ?");

// Bind the user_session_id to the query to prevent SQL injection.
$stmt->bind_param("s", $user_session_id);

// --- 4. EXECUTE AND RESPOND ---
// Execute the query and check if it was successful.
if ($stmt->execute()) {
    // If the update was successful, send a success status back to the JavaScript.
    echo json_encode(['status' => 'success', 'message' => 'Chat has been closed.']);
} else {
    // If the database query failed, send an error status.
    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
}

// Close the statement and the database connection.
$stmt->close();
$conn->close();
?>
