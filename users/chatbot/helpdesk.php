<?php
// helpdesk.php - User-facing helpdesk chat interface

session_start();
require_once '../../config/config.php'; // Assuming this has your DB connection ($conn)

// --- 1. THEME AND ORGANIZATION DATA ---
$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$org_title = 'Event Portal';
$organization_id = null;

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    $organization_title = $_GET['view'];
    $stmt_org = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt_org->bind_param("s", $organization_title);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();
    
    if ($org_details = $result_org->fetch_assoc()) {
        $organization_id = $org_details['id'];
        $org_title = htmlspecialchars($org_details['title']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
    }
    $stmt_org->close();
}

// --- 2. USER/SESSION IDENTIFICATION ---
// Use session ID for guests and a more permanent ID for logged-in users.
if (isset($_SESSION['attendee_id'])) {
    $user_session_id = 'user_' . $_SESSION['attendee_id'];
} else {
    $user_session_id = 'guest_' . session_id();
}

// Store necessary info in the session to be used by the API
$_SESSION['chat_user_session_id'] = $user_session_id;
$_SESSION['chat_organization_id'] = $organization_id;


// --- 3. START OUTPUT BUFFERING ---
ob_start();
?>

<style>
    .chat-container {
        max-width: 800px;
        margin: 2rem auto;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 75vh;
        background-color: #f9f9f9;
    }
    .chat-header {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        padding: 1rem;
        text-align: center;
        font-weight: bold;
        font-size: 1.25rem;
    }
    .chat-box {
        flex-grow: 1;
        padding: 1.5rem;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    .chat-message {
        padding: 0.75rem 1.25rem;
        border-radius: 1.25rem;
        margin-bottom: 1rem;
        max-width: 75%;
        line-height: 1.5;
    }
    .chat-message.user {
        background-color: #007bff;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 0.25rem;
    }
    .chat-message.bot, .chat-message.admin {
        background-color: #e9ecef;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 0.25rem;
    }
     .chat-message.system {
        background-color: #fff3cd;
        color: #664d03;
        align-self: center;
        font-size: 0.85rem;
        border-radius: 0.5rem;
        text-align: center;
    }
    .chat-input-form {
        display: flex;
        padding: 1rem;
        border-top: 1px solid #ddd;
        background-color: #fff;
    }
    .chat-input-form input {
        flex-grow: 1;
        border: 1px solid #ccc;
        border-radius: 2rem;
        padding: 0.75rem 1.25rem;
        margin-right: 0.5rem;
    }
    .chat-input-form button {
        border-radius: 50%;
        width: 50px;
        height: 50px;
        border: none;
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        font-size: 1.25rem;
    }
</style>

<main class="container py-4">
    <div class="chat-container">
        <div class="chat-header">
            Helpdesk & Support
        </div>
        <div class="chat-box" id="chat-box">
            <!-- Messages will be loaded here -->
        </div>
        <form class="chat-input-form" id="chat-form">
            <input type="text" id="message-input" placeholder="Type your message..." autocomplete="off">
            <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');

    // Function to append a message to the chat box
    function appendMessage(sender, message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', sender.toLowerCase());
        // Sanitize message to prevent HTML injection
        const content = document.createElement('div');
        content.textContent = message;
        messageElement.innerHTML = content.innerHTML.replace(/\n/g, '<br>'); // Alow line breaks
        chatBox.appendChild(messageElement);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Function to fetch chat history
    async function fetchMessages() {
        try {
            const response = await fetch('get_messages.php');
            const messages = await response.json();
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                appendMessage(msg.sender_type, msg.message);
            });
        } catch (error) {
            console.error('Error fetching messages:', error);
            appendMessage('system', 'Could not load chat history.');
        }
    }

    // Function to send a message
    async function sendMessage(message) {
        appendMessage('user', message);
        messageInput.value = '';

        try {
            const response = await fetch('chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            const result = await response.json();
            if(result.reply) {
                 setTimeout(() => { // Simulate bot "thinking"
                    appendMessage(result.sender, result.reply);
                }, 500);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            appendMessage('system', 'Message could not be sent.');
        }
    }

    // Event listener for form submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (message) {
            sendMessage(message);
        }
    });
    
    // Initial fetch of messages
    fetchMessages();

    // Poll for new messages every 3 seconds
    setInterval(fetchMessages, 3000);
});
</script>

<?php
// --- 4. RENDER THE PAGE ---
$page_content_html = ob_get_clean();
$page_title = 'Helpdesk';
include '../../users/layout.php'; // Your master layout file
?>
