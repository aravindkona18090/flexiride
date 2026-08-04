<?php
require 'resend.php';

session_start();
include 'db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if any user is found
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $user = [];
}

// Handle the danger button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_danger_email'])) {

    $emergencyEmail1 = $user['emergency_email1'];
    $emergencyEmail2 = $user['emergency_email2'];

    $emergencyEmails = [
        $emergencyEmail1,
        $emergencyEmail2,
        $user['email']
    ];

    $latitude = $_POST['latitude'] ?? 'Unknown';
    $longitude = $_POST['longitude'] ?? 'Unknown';

    foreach ($emergencyEmails as $email) {

        if (!empty($email)) {
            sendEmergencyEmail($email, $latitude, $longitude);
        }
    }

    $_SESSION['alert_message'] = "Emergency emails have been sent successfully!";

    header("Location: index.php");
    exit();
}

/**
 * Send Emergency Email using Resend
 */
function sendEmergencyEmail($toEmail, $latitude, $longitude)
{
    $googleMapsLink = "https://www.google.com/maps?q={$latitude},{$longitude}";

    $emailBody = "
        <h1 style='color:red;'>🚨 Emergency Alert</h1>

        <p>
            A FlexiRide user has triggered an <strong>Emergency Alert</strong>.
        </p>

        <p><strong>Current Location:</strong></p>

        <ul>
            <li><strong>Latitude:</strong> {$latitude}</li>
            <li><strong>Longitude:</strong> {$longitude}</li>
        </ul>

        <p>
            <a href='{$googleMapsLink}' target='_blank'
               style='background:#d32f2f;color:#fff;padding:10px 18px;
               text-decoration:none;border-radius:5px;'>
               View Live Location
            </a>
        </p>

        <br>

        <p>
            Please contact the user immediately if necessary.
        </p>

        <p>
            Regards,<br>
            <strong>FlexiRide Emergency System</strong>
        </p>
    ";

    try {

        sendResendEmail(
            $toEmail,
            "Emergency Contact",
            "🚨 Emergency Alert - FlexiRide",
            $emailBody
        );

    } catch (Exception $e) {

        error_log("Resend Error: " . $e->getMessage());

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Alert</title>
    <script>
        // JavaScript to fetch the user's current location and swap latitude and longitude
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Swap latitude and longitude and send them via POST
                    var latitude = position.coords.latitude;
                    var longitude = position.coords.longitude;

                    // Automatically trigger the form submission with the fetched location
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';

                    var latitudeInput = document.createElement('input');
                    latitudeInput.type = 'hidden';
                    latitudeInput.name = 'latitude';
                    latitudeInput.value = latitude;
                    form.appendChild(latitudeInput);

                    var longitudeInput = document.createElement('input');
                    longitudeInput.type = 'hidden';
                    longitudeInput.name = 'longitude';
                    longitudeInput.value = longitude;
                    form.appendChild(longitudeInput);

                    var sendButton = document.createElement('input');
                    sendButton.type = 'hidden';
                    sendButton.name = 'send_danger_email';
                    form.appendChild(sendButton);

                    document.body.appendChild(form);
                    form.submit(); // Automatically submit the form
                }, function(error) {
                    alert('Unable to fetch location: ' + error.message);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        // Fetch location and send email as soon as the page loads
        window.onload = getLocation;
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1>Emergency Alert</h1>
        <p>Your emergency location is being sent to your emergency contacts...</p>
    </div>

    <?php if (isset($_SESSION['alert_message'])): ?>
    <script>
        alert("<?php echo $_SESSION['alert_message']; ?>");
    </script>
    <?php unset($_SESSION['alert_message']); // Clear the message after showing ?>
    <?php endif; ?>

</body>
</html>
