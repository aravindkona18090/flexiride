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

// Fetch the ride details
$sql = "SELECT * FROM rides WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ride_id, $_SESSION['user_id']);
$stmt->execute();
$ride = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ride) {
    echo "Ride not found or you do not have permission to edit this ride.";
    exit();
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $origin = trim($_POST['origin']);
    $destination = trim($_POST['destination']);
    $ride_date = $_POST['ride_date'];
    $ride_time = $_POST['ride_time']; // Get ride time from the form
    $seats_available = (int)$_POST['seats_available'];

    // Check for missing or invalid values
    if (empty($origin) || empty($destination) || empty($ride_date) || empty($ride_time)) {
        $error = "All fields are required.";
    } else {
        // Update the ride in the database
        $sql = "UPDATE rides 
                SET origin = ?, destination = ?, ride_date = ?, ride_time = ?, seats_available = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiii", $origin, $destination, $ride_date, $ride_time, $seats_available, $ride_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            // Fetch emails of users with bookings on this ride
            $sql = "SELECT booked_email ,posted_email FROM bookings WHERE ride_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $ride_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $emails = [];
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['booked_email'];
                
            }
            if(isset($_SESSION['email'])){
            $posted_email = $_SESSION['email'];
            $stmt->close();
            echo "<script>alert($posted_email);</script>";
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
                $mail->addAddress($posted_email);
                $mail->isHTML(true);
                $mail->Subject = 'Ride Update Notification';
                $mail->Body = "<h2>Dear User,</h2>
                    <p>The ride you posted has been updated with the following details:</p>
                    <ul>
                        <li><strong>Origin:</strong> $origin</li>
                        <li><strong>Destination:</strong> $destination</li>
                        <li><strong>Ride Date:</strong> $ride_date</li>
                        <li><strong>Ride Time:</strong> $ride_time</li>
                        <li><strong>Seats Available:</strong> $seats_available</li>
                    </ul>
                    <p>For Queries, please visit our website.</p>
                    <p>Thank you for choosing FlexiRide!</p>
                    <p>Regards,<br>FlexiRide Team</p>";

                $mail->send();
            } catch (Exception $e) {
                echo "Error sending email: {$mail->ErrorInfo}";
            }
        }
            if (!empty($emails)) {
                // Send email notifications
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
                    $mail->Subject = 'Ride Update Notification';
                    $mail->Body = "<h2>Dear User,</h2>
                        <p>The ride you booked has been updated with the following details:</p>
                        <ul>
                            <li><strong>Origin:</strong> $origin</li>
                            <li><strong>Destination:</strong> $destination</li>
                            <li><strong>Ride Date:</strong> $ride_date</li>
                            <li><strong>Ride Time:</strong> $ride_time</li>
                            <li><strong>Seats Available:</strong> $seats_available</li>
                        </ul>
                        <p>For further updates or to cancel, please visit our website.</p>
                        <p>Thank you for choosing FlexiRide!</p>
                        <p>Regards,<br>FlexiRide Team</p>";

                    $mail->send();
                } catch (Exception $e) {
                    echo "Error sending email: {$mail->ErrorInfo}";
                }
            }

            header("Location: ride_edited.php");
            exit();
        } else {
            $error = "Error updating ride: " . $stmt->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ride</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">

    <style>
        body {
            background-image: url("images/edit.jpg");
            background-repeat: no-repeat; 
            background-size: cover; 
            background-position: center;
            background-attachment: fixed;
            font-family: "Josefin Sans", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

         /* Container */

         .container {
            margin-top: 40px;
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 20px;
            animation: fadeIn 1.5s ease-in-out;
            backdrop-filter: blur(15px);
            overflow: hidden;
            transform: translateY(-20px);
        }

        /* Animation for fade-in effect */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: center;
            color: #000;
            font-size: 2rem;
            margin-bottom: 20px;
            animation: slideIn 1s ease-in-out;
        }

        /* Slide-in animation for heading */
        @keyframes slideIn {
            from {
                transform: translateX(-100px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        form label {
            font-weight: 600;
            margin-bottom: 10px;
            color: #000;
            display: block;
        }

        form input{
            font-family: "Josefin Sans", sans-serif;

            width: 95%;
            padding: 12px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        form select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            outline: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        form input:focus,
        form select:focus {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        form input[type="date"],
        form input[type="time"] {
            font-family: "Josefin Sans", sans-serif;

        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #ff7e5f, #feb47b);
            border: none;
            color: #fff;
            border-radius: 5px;
            font-size: 16px;
            font-family: "Josefin Sans", sans-serif;

            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        button:hover {
            background: linear-gradient(to right, #feb47b, #ff7e5f);
            transform: scale(1.05);
        }

        .error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
        }

        /* Floating Animation for Form Elements */
        form label,
        form input,
        form select,
        button {
            animation: floatIn 1s ease-in-out;
        }

        @keyframes floatIn {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="post">
            <h2>Edit Ride</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <label for="origin">Origin:</label>
            <input type="text" name="origin" value="<?php echo htmlspecialchars($ride['origin']); ?>" required>

            <label for="destination">Destination:</label>
            <input type="text" name="destination" value="<?php echo htmlspecialchars($ride['destination']); ?>" required>

            <label for="ride_date">Ride Date:</label>
            <input type="date" name="ride_date" value="<?php echo htmlspecialchars($ride['ride_date']); ?>" required min="<?php echo date('Y-m-d'); ?>">

            <label for="ride_time">Ride Time:</label>
            <input type="time" name="ride_time" value="<?php echo htmlspecialchars($ride['ride_time']); ?>" required>

            <label for="seats_available">Seats Available:</label>
            <input type="number" name="seats_available" value="<?php echo htmlspecialchars($ride['seats_available']); ?>" required>

            <button type="submit">Update Ride</button>
        </form>
    </div>
</body>
</html>
