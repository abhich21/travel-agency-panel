<?php
// get_messages.php - Fetches chat history for BOTH users and admins

// DO NOT START THE SESSION YET.
// We will start it inside the logic blocks.

require_once '../../config/config.php';
header('Content-Type: application/json');

$user_session_id = null;

// --- SMART LOGIC: Check WHO is making the request ---

if (isset($_GET['session_id'])) {
    // --- THIS PATH IS FOR THE ADMIN ---
    // An admin is requesting messages for a specific session ID.

    session_start(); // Start session to check for admin login

    // Apply the admin security check
    if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== TRUE) {
        echo json_encode(['error' => 'Authentication failed']);
        exit;
    }

    // Get the session ID from the URL
    $user_session_id = $_GET['session_id'];

} else {
    // --- THIS PATH IS FOR THE PUBLIC USER ---
    // A regular user is requesting their own messages.

    session_start(); // Start session to get the user's chat ID

    // No admin security check needed here. Just get their session ID.
    $user_session_id = $_SESSION['chat_user_session_id'] ?? null;
}


if (!$user_session_id) {
    echo json_encode([]); // Send empty array if no session is identified
    exit;
}

// --- The rest of the file (fetching messages from the database) remains the same ---
// ...

// --- FETCH MESSAGES FROM DATABASE ---
$messages = [];
$stmt = $conn->prepare("SELECT message, sender_type, created_at FROM chats WHERE user_session_id = ? ORDER BY created_at ASC");
$stmt->bind_param("s", $user_session_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => $row['message'],
        'sender_type' => $row['sender_type'] // 'user', 'bot', or 'admin'
    ];
}

$stmt->close();
$conn->close();

echo json_encode($messages);
?>
