<?php
// send_message.php - Handles sending a new message

session_start();
require_once '../../config/config.php'; 

// This is a simplified sender. The main logic is now in chat_api.php
// This file can be kept for other purposes or deprecated.
// For this implementation, we will rely on chat_api.php

header('Content-Type: application/json');

echo json_encode(['status' => 'deprecated', 'message' => 'Please use chat_api.php']);

?>
