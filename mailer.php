<?php
/**
 * FlexiRide Resend Email Mailer Helper
 * Uses the Resend API (https://resend.com) to send transactional emails.
 * Replace RESEND_API_KEY in your .env or define it here manually.
 *
 * Usage:
 *   sendResendMail($toEmail, $toName, $subject, $htmlBody);
 *   Returns true on success, false on failure.
 */

function sendResendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $apiKey = getenv('RESEND_API_KEY');

    // From address — must be a verified domain in Resend
    $fromName  = 'FlexiRide';
    $fromEmail = getenv('RESEND_FROM_EMAIL') ?: 'onboarding@resend.dev';

    $payload = json_encode([
        'from'    => "{$fromName} <{$fromEmail}>",
        'to'      => ["{$toName} <{$toEmail}>"],
        'subject' => $subject,
        'html'    => $htmlBody,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        return true;
    } else {
        error_log("Resend Mail Error [{$httpCode}]: " . $response);
        return false;
    }
}
?>
