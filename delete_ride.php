<?php
require_once __DIR__ . '/includes/resend.php';
require_once __DIR__ . '/includes/db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    if (!empty($row['booked_email'])) {
        $emails[] = $row['booked_email'];
    }
}

$stmt->close();

// Delete the ride from the database
$sql = "DELETE FROM rides WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $_SESSION['user_id']);

if ($stmt->execute()) {

    // Notify booked users
    if (!empty($emails)) {

        $emailBody = "
        <h2>Ride Cancellation Notification</h2>

        <p>Dear User,</p>

        <p>We regret to inform you that the ride you booked has been cancelled.</p>

        <h3>Cancelled Ride Details</h3>

        <ul>
            <li><strong>Origin:</strong> {$ride['origin']}</li>
            <li><strong>Destination:</strong> {$ride['destination']}</li>
            <li><strong>Ride Date:</strong> {$ride['ride_date']}</li>
            <li><strong>Ride Time:</strong> {$ride['ride_time']}</li>
        </ul>

        <p>We sincerely apologize for the inconvenience.</p>

        <p>If you need assistance, please contact the FlexiRide support team.</p>

        <br>

        <p>Regards,<br>
        <strong>FlexiRide Team</strong></p>
        ";

        foreach ($emails as $email) {

            try {

                sendResendEmail(
                    $email,
                    "FlexiRide User",
                    "Ride Cancellation Notification",
                    $emailBody
                );

            } catch (Exception $e) {

                error_log("Resend Error: " . $e->getMessage());

            }

        }
    }

    header("Location: ride_deleted.php");
    exit();

} else {

    echo "Error deleting ride: " . $stmt->error;

}

$stmt->close();
?>