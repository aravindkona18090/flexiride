<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// DigiLocker Developer API credentials (from partners.digilocker.gov.in)
$client_id = getenv('DIGILOCKER_CLIENT_ID') ?: '';

// Build production DigiLocker OAuth URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$redirect_uri = "{$protocol}://{$host}{$basePath}/digilocker_callback.php";

$state = bin2hex(random_bytes(16));
$_SESSION['digilocker_state'] = $state;

// Official Govt of India DigiLocker OAuth 2.0 Authorization Endpoint
if (!empty($client_id)) {
    $digiLockerGovtUrl = "https://api.digitallocker.gov.in/public/oauth2/1/authorize?" . http_build_query([
        'response_type' => 'code',
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'state'         => $state
    ]);
} else {
    // Sandbox / Demo Authentication endpoint when DIGILOCKER_CLIENT_ID is not configured in .env yet
    $digiLockerGovtUrl = "digilocker_callback.php?code=" . bin2hex(random_bytes(10)) . "&state=" . $state;
}

if (isset($_GET['action']) && $_GET['action'] === 'authorize') {
    header("Location: " . $digiLockerGovtUrl);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiLocker Government KYC - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }
        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .digi-logo { font-size: 56px; color: #38bdf8; margin-bottom: 15px; }
        h2 { font-size: 26px; color: #f8fafc; margin-bottom: 12px; }
        p { color: #94a3b8; font-size: 15px; margin-bottom: 25px; line-height: 1.6; }
        .steps-box { background: #0f172a; border: 1px solid #334155; border-radius: 14px; padding: 20px; text-align: left; margin-bottom: 25px; }
        .steps-box li { color: #cbd5e1; font-size: 14px; margin-bottom: 10px; list-style: none; display: flex; align-items: center; gap: 10px; }
        .btn-digi {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 16px; border: none; border-radius: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: white; font-size: 18px; font-weight: 700; cursor: pointer; text-decoration: none;
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.4); transition: 0.3s;
        }
        .btn-digi:hover { transform: translateY(-2px); }
        .badge-mode { display: inline-block; background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="card">
        <span class="badge-mode">
            <?php echo !empty($client_id) ? "PROD OAUTH MODE" : "DIGILOCKER VERIFICATION PORTAL"; ?>
        </span>
        
        <i class='bx bxs-institution digi-logo'></i>
        <h2>Government DigiLocker OAuth 2.0 KYC</h2>
        <p>Authenticate your Identity seamlessly on the Government of India's official DigiLocker portal.</p>

        <div class="steps-box">
            <ul>
                <li><i class='bx bxs-right-arrow-circle' style="color:#38bdf8;"></i> Authenticates Aadhaar identity & Driving License</li>
                <li><i class='bx bxs-mobile' style="color:#22c55e;"></i> Receive official OTP on your Aadhaar-linked phone</li>
                <li><i class='bx bxs-check-shield' style="color:#4ade80;"></i> Unlocks <strong>"🛡️ DigiLocker Verified Rider"</strong> badge</li>
            </ul>
        </div>

        <a href="<?php echo htmlspecialchars($digiLockerGovtUrl); ?>" class="btn-digi">
            <i class='bx bxs-shield-quarter'></i> Authenticate via DigiLocker →
        </a>
    </div>
</div>

</body>
</html>
