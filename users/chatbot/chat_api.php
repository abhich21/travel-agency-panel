<?php
// chat_api.php - Handles routing and saving of chat messages

session_start();
require_once '../../config/config.php'; 
require_once 'chatbot.php';

header('Content-Type: application/json');

// --- 1. GET INPUT AND IDENTIFY USER ---
$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

// Determine sender and session
$is_admin_sending = (isset($input['sender']) && $input['sender'] === 'admin');

if ($is_admin_sending) {
    // --- ADD THIS LINE ---
    // Start the session specifically to check for the admin login
    
    
    // The security check can now read the admin's session correctly
    if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== TRUE) {
        echo json_encode(['error' => 'Authentication failed']);
        exit;
    }

    // ... the rest of the code in this block stays the same
    
    $user_session_id = $input['user_session_id'];
    $sender_type = 'admin';
    $organization_id = 1; // This should be dynamically set in a real app
} else {
    // A regular user is sending a message
    $user_session_id = $_SESSION['chat_user_session_id'] ?? null;
    $organization_id = $_SESSION['chat_organization_id'] ?? null;
    $sender_type = 'user';
}

if (!$user_session_id || !$organization_id || empty($message)) {
    echo json_encode(['error' => 'Missing required data.']);
    exit;
}

// --- 2. SAVE USER/ADMIN MESSAGE TO DATABASE ---
$stmt = $conn->prepare("INSERT INTO chats (organization_id, user_session_id, message, sender_type, status) VALUES (?, ?, ?, ?, 'open')");
$stmt->bind_param("isss", $organization_id, $user_session_id, $message, $sender_type);
$stmt->execute();
$stmt->close();

// If admin is sending, we don't need to generate a bot response
if ($is_admin_sending) {
    echo json_encode(['status' => 'success', 'reply' => '']);
    exit;
}

// --- 3. DETERMINE CHAT STATUS (Is it with an admin?) ---
$stmt = $conn->prepare("
    SELECT 
        status,
        (SELECT COUNT(*) FROM chats WHERE user_session_id = ? AND sender_type = 'admin') as admin_msg_count 
    FROM chats 
    WHERE user_session_id = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->bind_param("ss", $user_session_id, $user_session_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// A chat is considered "live with an admin" ONLY if an admin has replied
// AND the status is still 'open'.
$is_with_admin = ($result && $result['admin_msg_count'] > 0 && $result['status'] === 'open');

$bot_response_data = get_bot_response($message);

// If chat is with an admin, OR if user types 'admin' AND the chat is not already with an admin
if ($is_with_admin || ($bot_response_data['escalate'] && !$is_with_admin)) {
    if ($bot_response_data['escalate'] && !$is_with_admin) {
        // First time escalating, send the confirmation message.
        $reply = $bot_response_data['reply'];
        $stmt = $conn->prepare("INSERT INTO chats (organization_id, user_session_id, message, sender_type, status) VALUES (?, ?, ?, 'bot', 'open')");
        $stmt->bind_param("iss", $organization_id, $user_session_id, $reply);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['status' => 'success', 'reply' => $reply, 'sender' => 'bot']);
    } else {
        // Already escalated, waiting for admin. No bot reply.
        echo json_encode(['status' => 'success', 'reply' => '']);
    }
} else {
    // --- 4. GET AND SAVE BOT RESPONSE ---
    $reply = $bot_response_data['reply'];
    $stmt = $conn->prepare("INSERT INTO chats (organization_id, user_session_id, message, sender_type, status) VALUES (?, ?, ?, 'bot', 'open')");
    $stmt->bind_param("iss", $organization_id, $user_session_id, $reply);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'reply' => $reply, 'sender' => 'bot']);
}

$conn->close();
?>
