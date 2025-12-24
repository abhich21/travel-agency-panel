<?php
/**
 * WhatsApp Helper - Twilio Integration for Cab Notifications
 * 
 * Usage:
 *   require_once 'config/whatsapp_helper.php';
 *   $result = sendCabAssignmentWhatsApp($userPhone, $cabDetails, $scheduleDetails);
 */

use Twilio\Rest\Client;

/**
 * Send WhatsApp notification for new cab assignment
 * 
 * @param string $phone User's phone number (with country code, e.g., +917905411328)
 * @param array $cabDetails ['cab_name', 'cab_number', 'cab_type', 'driver_name', 'driver_phone']
 * @param array $scheduleDetails ['schedule_date', 'pickup_time', 'pickup_location', 'drop_location', 'trip_type']
 * @param string $orgName Organization/Event name
 * @return array ['success' => bool, 'message' => string, 'sid' => string|null]
 */
function sendCabAssignmentWhatsApp($phone, $cabDetails, $scheduleDetails, $orgName = 'Event') {
    // Load environment variables
    $accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? null;
    $authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? null;
    $fromNumber = $_ENV['TWILIO_WHATSAPP_FROM'] ?? 'whatsapp:+14155238886';
    
    if (!$accountSid || !$authToken) {
        return [
            'success' => false,
            'message' => 'Twilio credentials not configured',
            'sid' => null
        ];
    }
    
    // Format phone number for WhatsApp
    $toNumber = formatPhoneForWhatsApp($phone);
    if (!$toNumber) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format',
            'sid' => null
        ];
    }
    
    // Build message
    $message = buildCabAssignmentMessage($cabDetails, $scheduleDetails, $orgName);
    
    try {
        $client = new Client($accountSid, $authToken);
        
        $result = $client->messages->create(
            $toNumber,
            [
                'from' => $fromNumber,
                'body' => $message
            ]
        );
        
        return [
            'success' => true,
            'message' => 'WhatsApp sent successfully',
            'sid' => $result->sid
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'WhatsApp failed: ' . $e->getMessage(),
            'sid' => null
        ];
    }
}

/**
 * Send WhatsApp notification for cab assignment update
 */
function sendCabUpdateWhatsApp($phone, $cabDetails, $scheduleDetails, $changes, $orgName = 'Event') {
    $accountSid = $_ENV['TWILIO_ACCOUNT_SID'] ?? null;
    $authToken = $_ENV['TWILIO_AUTH_TOKEN'] ?? null;
    $fromNumber = $_ENV['TWILIO_WHATSAPP_FROM'] ?? 'whatsapp:+14155238886';
    
    if (!$accountSid || !$authToken) {
        return [
            'success' => false,
            'message' => 'Twilio credentials not configured',
            'sid' => null
        ];
    }
    
    $toNumber = formatPhoneForWhatsApp($phone);
    if (!$toNumber) {
        return [
            'success' => false,
            'message' => 'Invalid phone number format',
            'sid' => null
        ];
    }
    
    $message = buildCabUpdateMessage($cabDetails, $scheduleDetails, $changes, $orgName);
    
    try {
        $client = new Client($accountSid, $authToken);
        
        $result = $client->messages->create(
            $toNumber,
            [
                'from' => $fromNumber,
                'body' => $message
            ]
        );
        
        return [
            'success' => true,
            'message' => 'Update notification sent',
            'sid' => $result->sid
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'WhatsApp failed: ' . $e->getMessage(),
            'sid' => null
        ];
    }
}

/**
 * Format phone number for WhatsApp
 */
function formatPhoneForWhatsApp($phone) {
    // Remove spaces, dashes, etc.
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // If no country code, assume India (+91)
    if (!str_starts_with($phone, '+')) {
        if (strlen($phone) === 10) {
            $phone = '+91' . $phone;
        } else {
            $phone = '+' . $phone;
        }
    }
    
    // Validate minimum length
    if (strlen($phone) < 10) {
        return null;
    }
    
    return 'whatsapp:' . $phone;
}

/**
 * Build cab assignment message
 */
function buildCabAssignmentMessage($cab, $schedule, $orgName) {
    $date = date('F j, Y', strtotime($schedule['schedule_date']));
    $time = date('h:i A', strtotime($schedule['pickup_time']));
    $tripType = ucfirst(str_replace('_', ' ', $schedule['trip_type'] ?? 'Pickup'));
    
    $msg = "🚗 *Cab Assigned - {$orgName}*\n\n";
    $msg .= "Your cab has been arranged:\n\n";
    $msg .= "📅 *Date:* {$date}\n";
    $msg .= "⏰ *{$tripType} Time:* {$time}\n";
    
    if (!empty($schedule['pickup_location'])) {
        $msg .= "📍 *Pickup:* {$schedule['pickup_location']}\n";
    }
    if (!empty($schedule['drop_location'])) {
        $msg .= "📍 *Drop:* {$schedule['drop_location']}\n";
    }
    
    $msg .= "\n🚕 *Cab:* " . ($cab['cab_name'] ?: $cab['cab_number']) . "\n";
    
    if (!empty($cab['driver_name'])) {
        $msg .= "👨‍✈️ *Driver:* {$cab['driver_name']}\n";
    }
    if (!empty($cab['driver_phone'])) {
        $msg .= "📞 *Driver Phone:* {$cab['driver_phone']}\n";
    }
    
    $msg .= "\nFor any queries, contact the event team.";
    
    return $msg;
}

/**
 * Build cab update message
 */
function buildCabUpdateMessage($cab, $schedule, $changes, $orgName) {
    $date = date('F j, Y', strtotime($schedule['schedule_date']));
    $time = date('h:i A', strtotime($schedule['pickup_time']));
    
    $msg = "🔄 *Cab Details Updated - {$orgName}*\n\n";
    $msg .= "Your cab assignment has been updated.\n\n";
    
    if (!empty($changes)) {
        $msg .= "*What changed:*\n";
        foreach ($changes as $change) {
            $msg .= "• {$change}\n";
        }
        $msg .= "\n";
    }
    
    $msg .= "*Updated Details:*\n";
    $msg .= "📅 {$date} at {$time}\n";
    $msg .= "🚕 Cab: " . ($cab['cab_name'] ?: $cab['cab_number']) . "\n";
    
    if (!empty($cab['driver_name'])) {
        $msg .= "👨‍✈️ Driver: {$cab['driver_name']}\n";
    }
    if (!empty($cab['driver_phone'])) {
        $msg .= "📞 {$cab['driver_phone']}\n";
    }
    
    return $msg;
}
