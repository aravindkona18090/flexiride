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

    // Always log the SOS event, regardless of email success
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
            // Log mailer failure but do NOT block the SOS flow
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
    <title>Emergency SOS - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 24px; font-weight: 700; color: #38bdf8; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logo span { color: #22c55e; }
        .nav-links { display: flex; gap: 20px; list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; font-size: 16px; font-weight: 500; transition: 0.3s; }
        .nav-links a:hover { color: #38bdf8; }

        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .sos-card {
            background: rgba(30, 41, 59, 0.9);
            border: 2px solid #ef4444;
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.4);
        }
        .sos-icon { font-size: 64px; color: #ef4444; margin-bottom: 15px; animation: pulse 1.5s infinite; }
        h2 { font-size: 26px; color: #f8fafc; margin-bottom: 10px; }
        p { color: #94a3b8; font-size: 15px; margin-bottom: 25px; }
        .btn-sos {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: #ef4444;
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-sos:hover { background: #dc2626; transform: scale(1.02); }
        .btn-whatsapp {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: #22c55e;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo"><i class='bx bxs-navigation'></i> Flexi<span>Ride</span></a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="find_ride.php">Find Ride</a></li>
        <li><a href="post_ride.php">Post Ride</a></li>
        <li><a href="myrides.php">My Rides</a></li>
        <li><a href="my_booked_rides.php">Booked Trips</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="sos-card">
        <i class='bx bxs-error-circle sos-icon'></i>
        <h2>EMERGENCY ASSISTANCE</h2>
        <p>Transmit your live GPS coordinates immediately to your emergency contacts & WhatsApp.</p>

        <button onclick="triggerSOS()" class="btn-sos"><i class='bx bxs-bell-ring'></i> Send Emergency Email Alert</button>
        <a id="whatsapp-sos" href="#" target="_blank" class="btn-whatsapp"><i class='bx bxl-whatsapp'></i> Share GPS via WhatsApp</a>
    </div>
</div>

<script>
    let currentLat = 'Unknown';
    let currentLng = 'Unknown';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            currentLat = pos.coords.latitude;
            currentLng = pos.coords.longitude;

            const mapsLink = `https://www.google.com/maps?q=${currentLat},${currentLng}`;
            const text = encodeURIComponent(`🚨 EMERGENCY! I need help. My current GPS location: ${mapsLink}`);
            document.getElementById('whatsapp-sos').href = `https://wa.me/?text=${text}`;
        });
    }

    function triggerSOS() {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        var latInp = document.createElement('input');
        latInp.type = 'hidden';
        latInp.name = 'latitude';
        latInp.value = currentLat;
        form.appendChild(latInp);

        var lngInp = document.createElement('input');
        lngInp.type = 'hidden';
        lngInp.name = 'longitude';
        lngInp.value = currentLng;
        form.appendChild(lngInp);

        var sendBtn = document.createElement('input');
        sendBtn.type = 'hidden';
        sendBtn.name = 'send_danger_email';
        sendBtn.value = '1';
        form.appendChild(sendBtn);

        document.body.appendChild(form);
        form.submit();
    }
</script>
</body>
</html>
