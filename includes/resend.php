<?php

function sendResendEmail($toEmail, $toName, $subject, $html)
{
    $apiKey = getenv('RESEND_API_KEY');

    if (!$apiKey) {
        throw new Exception("RESEND_API_KEY is not set.");
    }

    $data = [
        "from" => "FlexiRide <onboarding@resend.dev>", // Replace with your verified sender later
        "to" => [$toEmail],
        "subject" => $subject,
        "html" => $html
    ];

    $ch = curl_init("https://api.resend.com/emails");

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($status >= 400) {
        throw new Exception($response);
    }

    return json_decode($response, true);
}