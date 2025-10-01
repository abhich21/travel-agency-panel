<?php
// admin_chat.php - Interface for admins to chat with users

session_start(); // This is the new line you just added

// This is the security check that was failing before
if (!isset($_SESSION['adminLoggedIn']) || !$_SESSION['adminLoggedIn'] === TRUE) {
    header('Location: login.php');
    exit;
}
require_once '../../config/config.php'; 

// --- Get organization context if available ---
// --- Get Organization ID Automatically from Admin Session ---
$organization_id = null; // Start with a null value

// Check if the admin's user ID is stored in the session
if (isset($_SESSION['admin_user_id'])) {
    $admin_id = $_SESSION['admin_user_id'];

    // Prepare a query to find which organization this admin belongs to
    $stmt_org = $conn->prepare("SELECT id FROM organizations WHERE user_id = ? LIMIT 1");
    $stmt_org->bind_param("i", $admin_id);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();

    // If we found the organization, get its ID
    if ($org_details = $result_org->fetch_assoc()) {
        $organization_id = $org_details['id'];
    }
    $stmt_org->close();
}

// --- Fetch active user sessions that have been escalated ---
$active_chats = [];
if ($organization_id) {
    $stmt = $conn->prepare("SELECT DISTINCT user_session_id FROM chats WHERE organization_id = ? AND status = 'open' ORDER BY created_at DESC");
    $stmt->bind_param("i", $organization_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $active_chats[] = $row['user_session_id'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .chat-layout { display: flex; height: 95vh; }
        .sidebar { flex: 0 0 300px; background: #fff; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.25rem; border-bottom: 1px solid #ddd; font-weight: bold; }
        .chat-list { overflow-y: auto; flex-grow: 1; }
        .chat-list-item { padding: 1rem; border-bottom: 1px solid #eee; cursor: pointer; }
        .chat-list-item.active, .chat-list-item:hover { background-color: #e9ecef; }
        .main-chat { flex-grow: 1; display: flex; flex-direction: column; }
        .chat-container { max-width: 900px; margin: auto; width: 100%; display: flex; flex-direction: column; height: 100%;}
        /* Using the same chat styles from helpdesk.php */
        .chat-box { flex-grow: 1; padding: 1.5rem; overflow-y: auto; background-color: #fff; border-radius: 0.5rem; }
        .chat-message { padding: 0.75rem 1.25rem; border-radius: 1.25rem; margin-bottom: 1rem; max-width: 75%; line-height: 1.5; }
        .chat-message.user { background-color: #e9ecef; color: #333; align-self: flex-start; border-bottom-left-radius: 0.25rem; }
        .chat-message.admin { background-color: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 0.25rem; }
        .chat-input-form { display: flex; padding: 1rem; background-color: #f8f9fa; }
        .chat-input-form input { flex-grow: 1; border-radius: 2rem; padding: 0.75rem 1.25rem; border: 1px solid #ccc;}
    </style>
</head>
<body>
    <?php include '../../admin/navbar.php'; ?>

<div class="chat-layout">
    <div class="sidebar">
        <div class="sidebar-header">Active Chats</div>
        <div class="list-group list-group-flush chat-list">
            <?php foreach ($active_chats as $chat_session): ?>
                <a href="#" class="list-group-item list-group-item-action chat-list-item" data-session-id="<?php echo htmlspecialchars($chat_session); ?>">
                    <?php echo htmlspecialchars($chat_session); ?>
                </a>
            <?php endforeach; ?>
            <?php if (empty($active_chats)): ?>
                <div class="p-3 text-muted">No active chats requiring attention.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="main-chat p-3">
        <div class="chat-container" id="chat-container" style="display: none;">
             <div class="d-flex justify-content-center align-items-center mb-3">
    <h3 id="current-chat-id" class="mb-0"></h3>
    <button id="close-chat-btn" class="btn btn-sm btn-danger ms-3">Close Chat</button>
</div>
            <div class="chat-box d-flex flex-column" id="chat-box">
                <!-- Messages load here -->
            </div>
            <form class="chat-input-form" id="admin-chat-form">
                <input type="hidden" id="active-session-id" value="">
                <input type="text" class="form-control" id="admin-message-input" placeholder="Type your response...">
                <button type="submit" class="btn btn-primary ms-2"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
        <div id="welcome-message" class="text-center m-auto">
            <h2>Select a chat to begin.</h2>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chatListItems = document.querySelectorAll('.chat-list-item');
    const chatContainer = document.getElementById('chat-container');
    const welcomeMessage = document.getElementById('welcome-message');
    const chatBox = document.getElementById('chat-box');
    const adminChatForm = document.getElementById('admin-chat-form');
    const adminMessageInput = document.getElementById('admin-message-input');
    const activeSessionIdInput = document.getElementById('active-session-id');
    const currentChatIdHeader = document.getElementById('current-chat-id');
    const closeChatBtn = document.getElementById('close-chat-btn');
    let activeSessionId = null;
    let messagePollingInterval = null;

    closeChatBtn.addEventListener('click', async () => {
    const sessionId = activeSessionIdInput.value;
    if (!sessionId) return;

    if (confirm('Are you sure you want to close this chat?')) {
        try {
            // We will create this file in the next step
            const response = await fetch('close_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_session_id: sessionId })
            });

            const result = await response.json();

            if (result.status === 'success') {
                // Visually remove the chat from the list
                document.querySelector(`.chat-list-item[data-session-id="${sessionId}"]`).remove();

                // Go back to the welcome screen
                chatContainer.style.display = 'none';
                welcomeMessage.style.display = 'block';
                if (messagePollingInterval) clearInterval(messagePollingInterval);
            } else {
                alert('Error: Could not close chat.');
            }
        } catch (error) {
            console.error('Error closing chat:', error);
            alert('A technical error occurred.');
        }
    }
});

    function appendMessage(sender, message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', sender.toLowerCase());
        messageElement.textContent = message;
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    async function fetchMessagesForSession(sessionId) {
        try {
            const response = await fetch(`get_messages.php?session_id=${sessionId}`);
            const messages = await response.json();
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                appendMessage(msg.sender_type, msg.message);
            });
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    }

    chatListItems.forEach(item => {
        item.addEventListener('click', e => {
            e.preventDefault();
            
            chatListItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            activeSessionId = item.getAttribute('data-session-id');
            activeSessionIdInput.value = activeSessionId;
            
            welcomeMessage.style.display = 'none';
            chatContainer.style.display = 'flex';
            currentChatIdHeader.textContent = `Chatting with: ${activeSessionId}`;
            
            fetchMessagesForSession(activeSessionId);

            if(messagePollingInterval) clearInterval(messagePollingInterval);
            messagePollingInterval = setInterval(() => fetchMessagesForSession(activeSessionId), 3000);
        });
    });

    adminChatForm.addEventListener('submit', async e => {
        e.preventDefault();
        const message = adminMessageInput.value.trim();
        const sessionId = activeSessionIdInput.value;

        if (!message || !sessionId) return;
        
        appendMessage('admin', message);
        adminMessageInput.value = '';

        try {
            await fetch('chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    message: message, 
                    user_session_id: sessionId,
                    sender: 'admin' 
                })
            });
        } catch (error) {
            console.error('Error sending message:', error);
        }
    });
});
</script>
</body>
</html>
