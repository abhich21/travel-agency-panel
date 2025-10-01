<?php
// chatbot.php - Simple Keyword-based Chatbot Logic
require_once '../../config/config.php';
function get_bot_response($message) {
    $message = strtolower(trim($message));
    $response = "I'm not sure how to help with that. Type 'help' to see what I can answer, or type 'admin' to speak with a human.";
    $escalate_to_admin = false;

    // Keyword matching
    $keywords = [
        'dress code' => 'The dress code for the event is Business Casual.',
        'parking' => 'Yes, ample on-site parking is available in Garage B and Garage C for a flat rate of ₹200.',
        'ticket include' => 'Your ticket includes full access to all sessions, the exhibition hall, and networking lunch.',
        'refund' => 'We do not offer refunds, but you can transfer your ticket. Please contact the helpdesk to request a name change.',
        'help' => "I can answer questions about:\n- Dress Code\n- Parking\n- What tickets include\n- Refunds\n\nType 'admin' to be connected to a live agent.",
        'admin' => 'Please wait while I connect you to an available admin.'
    ];

    foreach ($keywords as $key => $value) {
        if (strpos($message, $key) !== false) {
            $response = $value;
            break;
        }
    }
    
    if (strpos($message, 'admin') !== false) {
        $escalate_to_admin = true;
    }

    return [
        'reply' => $response,
        'escalate' => $escalate_to_admin
    ];
}
?>
