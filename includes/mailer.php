<?php
/**
 * FlexiRide Brevo & Resend API Mailer Helper
 * Uses Brevo HTTP API (https://api.brevo.com/v3/smtp/email) or Resend API
 * - Works 100% on Railway (HTTP/HTTPS port 443 is never blocked)
 * - 300 free emails/day to ANY real user
 * - No domain required
 */

// OPTIONAL: Paste your API Key directly below if not using environment variables
define('CONFIG_BREVO_API_KEY', '');   // e.g. 'xkeysib-...'
define('CONFIG_RESEND_API_KEY', '');  // e.g. 're_...'
define('CONFIG_SENDER_EMAIL', 'admin@flexiride.com');

$GLOBALS['lastMailerError'] = '';

function sendResendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $GLOBALS['lastMailerError'] = '';

    // Check Brevo API Key first, then Resend Key
    $brevoKey  = getenv('BREVO_API_KEY') ?: (defined('CONFIG_BREVO_API_KEY') ? CONFIG_BREVO_API_KEY : '');
    $resendKey = getenv('RESEND_API_KEY') ?: (defined('CONFIG_RESEND_API_KEY') ? CONFIG_RESEND_API_KEY : '');

    // Sender email address
    $senderEmail = getenv('SENDER_EMAIL') ?: (getenv('RESEND_FROM_EMAIL') ?: (defined('CONFIG_SENDER_EMAIL') && !empty(CONFIG_SENDER_EMAIL) ? CONFIG_SENDER_EMAIL : 'admin@flexiride.com'));
    $senderName  = 'FlexiRide';

    // ------------------------------------------------------------
    // 1. IF BREVO API KEY IS PROVIDED -> USE BREVO HTTP API
    // ------------------------------------------------------------
    if (!empty(trim($brevoKey))) {
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
            $GLOBALS['lastMailerError'] = "Brevo cURL Error: " . $curlError;
            error_log($GLOBALS['lastMailerError']);
            return false;
        }

        $resData = json_decode($response, true);
        if ($httpCode === 200 || $httpCode === 201) {
            return true;
        } else {
            $errMsg = $resData['message'] ?? "HTTP {$httpCode}";
            $GLOBALS['lastMailerError'] = "Brevo API Error [{$httpCode}]: " . $errMsg;
            error_log($GLOBALS['lastMailerError']);
            return false;
        }
    }

    // ------------------------------------------------------------
    // 2. FALLBACK TO RESEND API
    // ------------------------------------------------------------
    if (!empty(trim($resendKey))) {
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
            $GLOBALS['lastMailerError'] = "Resend cURL Error: " . $curlError;
            error_log($GLOBALS['lastMailerError']);
            return false;
        }

        $resData = json_decode($response, true);
        if ($httpCode === 200 || $httpCode === 201) {
            return true;
        } else {
            $errMsg = $resData['message'] ?? ($resData['name'] ?? "HTTP Status {$httpCode}");
            $GLOBALS['lastMailerError'] = "Resend API Error [{$httpCode}]: " . $errMsg;
            error_log($GLOBALS['lastMailerError']);
            return false;
        }
    }

    $GLOBALS['lastMailerError'] = "No API key found! Please add BREVO_API_KEY to your environment variables or mailer.php.";
    error_log($GLOBALS['lastMailerError']);
    return false;
}

function getLastMailerError(): string {
    return $GLOBALS['lastMailerError'] ?? 'Unknown mailer error.';
}
?>
