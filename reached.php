<?php
// Include PHPMailer classes (Make sure PHPMailer is in the correct path)
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from the AJAX request (since it's sent as JSON, not as POST data)
    $data = json_decode(file_get_contents("php://input"), true);
    $latitude = isset($data['latitude']) ? $data['latitude'] : 'No Latitude';
    $longitude = isset($data['longitude']) ? $data['longitude'] : 'No Longitude';

    // Log the received coordinates for debugging
    error_log("Received Latitude: $latitude, Longitude: $longitude");

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Email setup
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'aravind18090@gmail.com';  // Your email
        $mail->Password = 'Aravind@1809';  // Your email app password (use App-specific password for Gmail)
        $mail->SMTPSecure = 'tls';  // TLS encryption
        $mail->Port = 587;  // SMTP port

        // Email content
        $mail->setFrom('no-reply@yourwebsite.com', 'Your Website');
        $mail->addAddress('rangareddyvenkata734@gmail.com');  // Recipient email

        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'User Reached Destination';
        $mail->Body = 'A user has reached their destination. Location: Latitude: ' . $latitude . ', Longitude: ' . $longitude;

        // Send the email
        if ($mail->send()) {
            echo json_encode(["status" => "success", "message" => "Location and email sent successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to send email."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Email error: {$mail->ErrorInfo}"]);
    }

    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Location Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .container {
            text-align: center;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .btn {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #218838;
        }
    </style>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container">
    <h2>Reached Your Destination?</h2>
    <p>Click the button below to send your location and notify someone that you have reached your destination.</p>
    <button class="btn" id="reached-link">Reached</button>
</div>

<script>
$(document).ready(function() {
    $('#reached-link').click(function(e) {
        e.preventDefault(); // Prevent default button behavior

        // Check if geolocation is available
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(sendLocation, showError);
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    });

    function sendLocation(position) {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;

        // Send the live location to the PHP backend via an AJAX request using jQuery
        $.ajax({
            url: window.location.href,  // Send the request to the same page
            type: 'POST',
            contentType: 'application/json',  // Correct content type for JSON
            data: JSON.stringify({
                latitude: latitude,
                longitude: longitude
            }),
            success: function(response) {
                console.log(response);  // For debugging
                const data = JSON.parse(response);
                if (data.status === "success") {
                    alert('Location sent! Email notification has been sent.');
                } else {
                    alert('Failed to send location or email.');
                }
            },
            error: function(error) {
                console.error('Error:', error);
                alert('Error sending location.');
            }
        });
    }

    function showError(error) {
        switch(error.code) {
            case error.PERMISSION_DENIED:
                alert("User denied the request for Geolocation.");
                break;
            case error.POSITION_UNAVAILABLE:
                alert("Location information is unavailable.");
                break;
            case error.TIMEOUT:
                alert("The request to get user location timed out.");
                break;
            case error.UNKNOWN_ERROR:
                alert("An unknown error occurred.");
                break;
        }
    }
});

</script>

</body>
</html>
