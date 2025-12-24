<?php
/**
 * Brevo Email Helper
 * Sends transactional emails using Brevo API
 */

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

/**
 * Send registration confirmation email with QR code
 * 
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $qrCodeUrl URL of the QR code image (S3)
 * @param string $orgTitle Organization title for branding
 * @param string $orgBgColor Organization theme color (hex)
 * @param string $logoUrl Organization logo URL
 * @return array ['success' => bool, 'message' => string]
 */
function sendRegistrationConfirmationEmail($toEmail, $toName, $qrCodeUrl, $orgTitle, $orgBgColor = '#dd1d21', $logoUrl = '')
{

    // Get Brevo credentials from environment
    $apiKey = $_ENV['BREVO_API_KEY'] ?? '';
    $senderEmail = $_ENV['BREVO_SENDER_EMAIL'] ?? 'noreply@event.com';
    $senderName = $_ENV['BREVO_SENDER_NAME'] ?? 'Event Registration';

    if (empty($apiKey)) {
        error_log("Brevo API key not configured");
        return ['success' => false, 'message' => 'Email service not configured'];
    }

    if (empty($toEmail)) {
        return ['success' => false, 'message' => 'Recipient email is required'];
    }

    try {
        // Configure Brevo API
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $apiInstance = new TransactionalEmailsApi(new Client(), $config);

        // Build the beautiful HTML email
        $htmlContent = buildRegistrationEmailHtml($toName, $qrCodeUrl, $orgTitle, $orgBgColor, $logoUrl);

        // Create email object
        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => "🎉 Welcome to {$orgTitle} - Your Registration is Confirmed!",
            'htmlContent' => $htmlContent,
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'replyTo' => ['email' => $senderEmail, 'name' => $senderName]
        ]);

        // Send the email
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);

        error_log("Registration email sent successfully to: {$toEmail}, MessageId: " . $result->getMessageId());
        return ['success' => true, 'message' => 'Email sent successfully', 'messageId' => $result->getMessageId()];

    } catch (Exception $e) {
        error_log("Brevo email error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
    }
}

/**
 * Build the HTML email template with Shell Helix theming
 */
function buildRegistrationEmailHtml($userName, $qrCodeUrl, $orgTitle, $primaryColor = '#dd1d21', $logoUrl = '')
{

    // Shell Helix theme colors
    $shellRed = '#dd1d21';
    $shellYellow = '#fbce07';
    $darkBg = '#1a1a1a';
    $lightText = '#ffffff';
    $grayText = '#b0b0b0';

    // Use org color if provided, otherwise default to Shell Red
    $accentColor = !empty($primaryColor) ? $primaryColor : $shellRed;

    // Build the event URL with URL-encoded org title
    $eventUrl = 'https://virtday.com/travel-agency-panel/users/home.php?view=' . urlencode($orgTitle);

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmed - {$orgTitle}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    
    <!-- Main Container -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                
                <!-- Email Card -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: {$darkBg}; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    
                    <!-- Header with Gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, {$shellRed} 0%, {$shellYellow} 100%); padding: 40px 40px 30px 40px; text-align: center;">
                            <!-- Logo -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
HTML;

    // Add logo if available
    if (!empty($logoUrl)) {
        $html .= <<<HTML
                                        <img src="{$logoUrl}" alt="{$orgTitle}" style="max-height: 60px; margin-bottom: 20px;">
HTML;
    }

    $html .= <<<HTML
                                        <h1 style="margin: 0; color: {$darkBg}; font-size: 28px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;">
                                            Registration Confirmed
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Welcome Message -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: center;">
                            <h2 style="margin: 0 0 15px 0; color: {$shellYellow}; font-size: 24px; font-weight: 600;">
                                🎉 Welcome, {$userName}!
                            </h2>
                            <p style="margin: 0; color: {$lightText}; font-size: 16px; line-height: 1.6;">
                                Your registration for <strong style="color: {$shellYellow};">{$orgTitle}</strong> has been successfully completed.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- QR Code Section -->
                    <tr>
                        <td style="padding: 20px 40px 30px 40px; text-align: center;">
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background: linear-gradient(135deg, {$shellRed}, {$shellYellow}); padding: 4px; border-radius: 20px;">
                                        <div style="background-color: {$lightText}; padding: 20px; border-radius: 16px;">
                                            <img src="{$qrCodeUrl}" alt="Your QR Code" style="width: 200px; height: 200px; display: block;">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 20px 0 0 0; color: {$grayText}; font-size: 14px;">
                                Your personal event QR code
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Instructions -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: rgba(251, 206, 7, 0.1); border-radius: 12px; border-left: 4px solid {$shellYellow};">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 10px 0; color: {$shellYellow}; font-size: 16px; font-weight: 600;">
                                            📱 Important Instructions
                                        </h3>
                                        <ul style="margin: 0; padding-left: 20px; color: {$lightText}; font-size: 14px; line-height: 1.8;">
                                            <li>Save this email or take a screenshot of your QR code</li>
                                            <li>Present this QR code at the registration desk upon arrival</li>
                                            <li>You can also access your QR code anytime from your account</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- CTA Button -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <a href="{$eventUrl}" style="display: inline-block; background: linear-gradient(135deg, {$shellRed}, {$shellYellow}); color: {$darkBg}; text-decoration: none; padding: 16px 40px; border-radius: 50px; font-weight: 700; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">
                                View Event Details
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: rgba(0,0,0,0.3); padding: 30px 40px; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: {$grayText}; font-size: 14px;">
                                We're excited to see you at the event!
                            </p>
                            <p style="margin: 0; color: {$grayText}; font-size: 12px;">
                                If you have any questions, please contact our support team.
                            </p>
                            <p style="margin: 15px 0 0 0; color: {$grayText}; font-size: 11px;">
                                © 2025 {$orgTitle}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>
HTML;

    return $html;
}
?>