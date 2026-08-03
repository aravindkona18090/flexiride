<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login1.html");
    exit();
}

// Fetch ride ID from query parameters
$ride_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ride_id) {
    echo "Invalid ride ID.";
    exit();
}

// Fetch the ride details to ensure it exists and belongs to the user
$sql = "SELECT * FROM rides WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $_SESSION['user_id']);
$stmt->execute();
$ride = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ride) {
    echo "Ride not found or you do not have permission to delete this ride.";
    exit();
}

// Fetch emails of users with bookings on this ride
$sql = "SELECT booked_email FROM bookings WHERE ride_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

$emails = [];
while ($row = $result->fetch_assoc()) {
    $emails[] = $row['booked_email'];
}
$stmt->close();

// Delete the ride from the database
$sql = "DELETE FROM rides WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    // Notify users with bookings about the ride cancellation
    if (!empty($emails)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'flexiride247@gmail.com';
            $mail->Password = 'lhyzlfabuyopgkqo';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('flexiride247@gmail.com', 'FlexiRide');

            foreach ($emails as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = 'Ride Cancellation Notification';
            $mail->Body = "<h2>Dear User,</h2>
                <p>We regret to inform you that the ride you booked has been canceled. Below are the details of the canceled ride:</p>
                <ul>
                    <li><strong>Origin:</strong> {$ride['origin']}</li>
                    <li><strong>Destination:</strong> {$ride['destination']}</li>
                    <li><strong>Ride Date:</strong> {$ride['ride_date']}</li>
                    <li><strong>Ride Time:</strong> {$ride['ride_time']}</li>
                </ul>
                <p>We apologize for the inconvenience caused. For further assistance, please contact our support team.</p>
                <p>Thank you for choosing FlexiRide!</p>
                <p>Regards,<br>FlexiRide Team</p>";

            $mail->send();
        } catch (Exception $e) {
            echo "Error sending email: {$mail->ErrorInfo}";
        }
    }

    header("Location: ride_deleted.php");
    exit();
} else {
    echo "Error deleting ride: " . $stmt->error;
}

$stmt->close();
?>
