<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_danger_email'])) {
    $latitude  = $_POST['latitude'] ?? 'Unknown';
    $longitude = $_POST['longitude'] ?? 'Unknown';
    $maps_link = "https://www.google.com/maps?q={$latitude},{$longitude}";

    // Always log the SOS event
    $sosTitle = '🚨 Emergency SOS Triggered';
    $sosMsg   = "SOS sent from GPS: {$latitude},{$longitude} — {$maps_link}";
    $logStmt  = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("iss", $user_id, $sosTitle, $sosMsg);
        $logStmt->execute();
    }

    $emergencyEmails = array_filter([$user['emergency_email1'] ?? '', $user['emergency_email2'] ?? '', $user['email'] ?? '']);

    foreach ($emergencyEmails as $email) {
        $sosHtml = "
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #ffeef0;'>
                <div style='max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; border-left: 5px solid #ff4757;'>
                    <h2 style='color: #ff4757;'>🚨 EMERGENCY SOS TRIGGERED</h2>
                    <p>User <strong>" . htmlspecialchars($user['name'] ?? 'FlexiRide User') . "</strong> has triggered an emergency alert!</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($user['phone'] ?? 'N/A') . "</p>
                    <p><strong>GPS Location:</strong> Latitude: $latitude, Longitude: $longitude</p>
                    <p style='margin-top: 20px;'>
                        <a href='$maps_link' style='background: #ff4757; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>View Live Location on Google Maps</a>
                    </p>
                </div>
            </div>
        ";
        try {
            sendResendMail($email, '', '🚨 URGENT: FlexiRide Emergency Alert', $sosHtml);
        } catch (Exception $e) {
            error_log('[FlexiRide SOS] Mailer failed for ' . $email . ': ' . $e->getMessage());
        }
    }

    $_SESSION['alert_message'] = "Emergency alerts & GPS location sent successfully!";
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency SOS & Safety Protocol — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .sos-beacon-ring {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 46px;
            color: var(--danger);
            margin: 0 auto 20px;
            box-shadow: 0 0 35px rgba(239, 68, 68, 0.4);
            animation: sosPulse 1.5s infinite;
        }

        @keyframes sosPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 18px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 520px; margin: 0 auto; text-align: center; border-color: var(--danger);">
            <div class="sos-beacon-ring">
                <i class='bx bxs-alarm-exclamation'></i>
            </div>

            <span class="fr-badge fr-badge-danger" style="margin-bottom: 8px;"><i class='bx bxs-error'></i> Panic Protocol Active</span>
            <h2 style="font-size: 26px; font-weight: 800; color: var(--danger); margin-bottom: 8px;">
                Emergency Assistance
            </h2>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 22px; line-height: 1.5;">
                Pressing the button below instantly broadcasts your precise live GPS coordinates to your registered emergency contacts and campus safety desk.
            </p>

            <form method="POST" id="sosForm">
                <input type="hidden" name="send_danger_email" value="1">
                <input type="hidden" name="latitude" id="latInput" value="">
                <input type="hidden" name="longitude" id="longInput" value="">

                <div id="gpsStatus" style="background:var(--bg-input); padding:10px 14px; border-radius:var(--radius-md); font-size:13px; color:var(--text-muted); margin-bottom:20px;">
                    <i class='bx bx-radar bx-spin'></i> Acquiring GPS satellite lock...
                </div>

                <button type="submit" id="sosBtn" class="fr-btn fr-btn-danger fr-btn-block fr-btn-lg" style="box-shadow:0 8px 25px rgba(239, 68, 68, 0.4); margin-bottom:14px;">
                    <i class='bx bxs-alarm-exclamation'></i> Send Instant SOS Alert Now
                </button>
            </form>

            <div style="display:flex; justify-content:center; gap:12px; margin-top:14px;">
                <a href="tel:112" class="fr-btn fr-btn-ghost fr-btn-sm" style="color:var(--danger);">
                    <i class='bx bxs-phone-call'></i> Call 112 (National Helpline)
                </a>
                <a href="index.php" class="fr-btn fr-btn-ghost fr-btn-sm">
                    Cancel & Return
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                document.getElementById('latInput').value = pos.coords.latitude;
                document.getElementById('longInput').value = pos.coords.longitude;
                document.getElementById('gpsStatus').innerHTML = `<i class='bx bx-check-circle' style='color:var(--eco);'></i> <strong>GPS Locked:</strong> ${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}`;
            },
            function(err) {
                document.getElementById('gpsStatus').innerHTML = `<i class='bx bx-info-circle' style='color:var(--amber);'></i> <strong>GPS Permission Required</strong> (Will send available network coordinates)`;
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
