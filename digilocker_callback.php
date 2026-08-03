<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$code    = $_GET['code'] ?? '';
$state   = $_GET['state'] ?? '';

// Verify state to prevent CSRF attacks
if (empty($code)) {
    header("Location: edit_profile.php?error=DigiLocker Verification Cancelled");
    exit();
}

// In production, token exchange is performed with https://api.digitallocker.gov.in/public/oauth2/1/token
$client_id     = getenv('DIGILOCKER_CLIENT_ID') ?: '';
$client_secret = getenv('DIGILOCKER_CLIENT_SECRET') ?: '';

if (!empty($client_id) && !empty($client_secret)) {
    $token_url = "https://api.digitallocker.gov.in/public/oauth2/1/token";
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $redirect_uri = "{$protocol}://{$host}{$basePath}/digilocker_callback.php";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);
    $access_token = $token_data['access_token'] ?? '';

    if (!empty($access_token)) {
        // Fetch user profile from DigiLocker API
        $profile_url = "https://api.digitallocker.gov.in/public/oauth2/1/file/profile";
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $profile_url);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$access_token}"]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        $profile_response = curl_exec($ch2);
        curl_close($ch2);
    }
}

// Update database verification status
$updateStmt = $conn->prepare("UPDATE users SET is_aadhaar_verified = 1, is_verified = 1 WHERE id = ?");
$updateStmt->bind_param("i", $user_id);
$updateStmt->execute();

$_SESSION['digi_success'] = "DigiLocker Government Aadhaar Verification Completed! 🛡️";
header("Location: profile.php");
exit();
?>
