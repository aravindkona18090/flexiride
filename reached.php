<?php
session_start();
require_once __DIR__ . '/includes/mailer.php';
include_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $latitude = isset($data['latitude']) ? $data['latitude'] : 'No Latitude';
    $longitude = isset($data['longitude']) ? $data['longitude'] : 'No Longitude';

    $emailBody = "
        <h2>Commuter Reached Destination Safely</h2>
        <p>A commuter has successfully reached their drop-off point.</p>
        <p><strong>GPS Coordinates:</strong> Latitude: {$latitude}, Longitude: {$longitude}</p>
        <p><a href='https://www.google.com/maps?q={$latitude},{$longitude}' target='_blank'>View on Google Maps</a></p>
    ";

    try {
        $adminEmail = getenv('ADMIN_EMAIL') ?: (getenv('SENDER_EMAIL') ?: 'admin@flexiride.com');
        sendResendMail($adminEmail, "Admin", "User Reached Destination Safely", $emailBody);

        echo json_encode(["status" => "success", "message" => "Location and arrival confirmation sent successfully."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to send arrival notice: " . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Safe Arrival Confirmation — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0; min-height: 75vh; display:flex; align-items:center;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 480px; margin: 0 auto; text-align: center;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--eco-bg); color: var(--eco); display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                <i class='bx bx-check-double'></i>
            </div>

            <span class="fr-badge fr-badge-eco" style="margin-bottom:8px;">Trip Safety Protocol</span>
            <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
                Reached Destination?
            </h2>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">
                Click below to notify your emergency contacts and driver that you have safely arrived at your drop-off location.
            </p>

            <button type="button" class="fr-btn fr-btn-eco fr-btn-block fr-btn-lg" id="reached-btn">
                <i class='bx bx-navigation'></i> I Have Reached Safely
            </button>

            <div id="reached-status" style="margin-top: 16px; font-size: 14px;"></div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#reached-btn').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        btn.prop('disabled', true).html("<i class='bx bx-loader-alt bx-spin'></i> Acquiring GPS & Dispatching...");

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(sendLocation, function(err) {
                sendLocation({ coords: { latitude: 'Manual', longitude: 'Manual' } });
            });
        } else {
            sendLocation({ coords: { latitude: 'Manual', longitude: 'Manual' } });
        }
    });

    function sendLocation(pos) {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }),
            success: function(res) {
                let data = (typeof res === 'object') ? res : JSON.parse(res);
                if (data.status === 'success') {
                    $('#reached-status').html("<div style='color:var(--eco); font-weight:700;'>✅ Safe arrival broadcast sent successfully!</div>");
                    $('#reached-btn').html("<i class='bx bx-check'></i> Arrival Confirmed");
                } else {
                    $('#reached-status').html("<div style='color:var(--danger);'>⚠️ " + data.message + "</div>");
                    $('#reached-btn').prop('disabled', false).text("Try Again");
                }
            },
            error: function() {
                $('#reached-status').html("<div style='color:var(--danger);'>⚠️ Network error. Please try again.</div>");
                $('#reached-btn').prop('disabled', false).text("Try Again");
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
