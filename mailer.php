<?php
/**
 * FlexiRide Brevo API Mailer Helper
 * Uses Brevo HTTP API (https://api.brevo.com/v3/smtp/email)
 * - Works 100% on Railway (HTTP/HTTPS port 443 is never blocked)
 * - 300 free emails/day to ANY real user
 * - No domain required (uses your email as sender)
 */

$lastMailerError = '';

function sendResendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    global $lastMailerError;
    $lastMailerError = '';

    // Check Brevo API Key first, fallback to RESEND_API_KEY if using Resend
    $brevoKey = getenv('BREVO_API_KEY') ?: (defined('BREVO_API_KEY') ? BREVO_API_KEY : '');
    $resendKey = getenv('RESEND_API_KEY') ?: (defined('RESEND_API_KEY') ? RESEND_API_KEY : '');

    // Sender email address
    $senderEmail = getenv('SENDER_EMAIL') ?: (getenv('RESEND_FROM_EMAIL') ?: 'flexiride247@gmail.com');
    $senderName  = 'FlexiRide';

    // ------------------------------------------------------------
    // 1. IF BREVO API KEY IS PROVIDED -> USE BREVO HTTP API (RECOMMENDED FOR RAILWAY)
    // ------------------------------------------------------------
    if (!empty($brevoKey)) {
        $payload = json_encode([
            'sender'      => ['name' => $senderName, 'email' => $senderEmail],
            'to'          => [['email' => $toEmail, 'name' => !empty($toName) ? $toName : $toEmail]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . trim($brevoKey),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $lastMailerError = "Brevo cURL Error: " . $curlError;
            error_log($lastMailerError);
            return false;
        }

        $resData = json_decode($response, true);
        if ($httpCode === 200 || $httpCode === 201) {
            return true;
        } else {
            $errMsg = $resData['message'] ?? "HTTP {$httpCode}";
            $lastMailerError = "Brevo API Error [{$httpCode}]: " . $errMsg;
            error_log($lastMailerError);
            return false;
        }
    }

    // ------------------------------------------------------------
    // 2. FALLBACK TO RESEND API
    // ------------------------------------------------------------
    if (!empty($resendKey)) {
        $toRecipient = !empty(trim($toName)) ? "{$toName} <{$toEmail}>" : $toEmail;

        $payload = json_encode([
            'from'    => "{$senderName} <{$senderEmail}>",
            'to'      => [$toRecipient],
            'subject' => $subject,
            'html'    => $htmlBody,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . trim($resendKey),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $lastMailerError = "Resend cURL Error: " . $curlError;
            error_log($lastMailerError);
            return false;
        }

        $resData = json_decode($response, true);
        if ($httpCode === 200 || $httpCode === 201) {
            return true;
        } else {
            $errMsg = $resData['message'] ?? ($resData['name'] ?? "HTTP Status {$httpCode}");
            $lastMailerError = "Resend API Error [{$httpCode}]: " . $errMsg;
            error_log($lastMailerError);
            return false;
        }
    }

    $lastMailerError = "No Mailer API Key set. Please set BREVO_API_KEY or RESEND_API_KEY environment variable.";
    error_log($lastMailerError);
    return false;
}
?>
