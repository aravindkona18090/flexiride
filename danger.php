<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure this file exists and PHPMailer is installed

session_start();
include 'db.php';  // Include database if required
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
    // Handle case where user is not found
    $user = []; // Ensure $user is an empty array if no user is found
}

// Handle the danger button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_danger_email'])) {
    $emergencyEmail1 = $user['emergency_email1'];
    $emergencyEmail2 = $user['emergency_email2'];
    $emergencyEmails = [$emergencyEmail1, $emergencyEmail2, $user['email']];
    $latitude = $_POST['latitude'] ?? 'Unknown';
    $longitude = $_POST['longitude'] ?? 'Unknown';

    foreach ($emergencyEmails as $email) {
        sendEmergencyEmail($email, $latitude, $longitude);
    }
    
    // After sending the emails, set the session alert message
    $_SESSION['alert_message'] = "Emergency emails have been sent successfully!";
    // Redirect after sending emails
    header('Location: index.php'); // Redirect to index.php after the operation
    exit(); // Ensure no further code is executed after the redirect
}

// Function to send the emergency email
function sendEmergencyEmail($toEmail, $latitude, $longitude) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'flexiride247@gmail.com';
        $mail->Password   = 'lhyzlfabuyopgkqo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('flexiride247@gmail.com', 'FlexiRide Emergency');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Emergency Alert: Location Details';
        $mail->Body    = "
            <h1>Emergency Alert</h1>
            <p>A user has triggered an emergency alert. Here are the location details:</p>
            <ul>
                <li><strong>Latitude:</strong> $latitude</li>
                <li><strong>Longitude:</strong> $longitude</li>
                <li><a href='https://www.google.com/maps?q=$latitude,$longitude' target='_blank'>View on Google Maps</a></li>
            </ul>
        ";

        $mail->send();
        
    } catch (Exception $e) {
        echo "Error sending email: {$mail->ErrorInfo}";
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
