<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';
session_start();

require 'vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle ride deletion
if (isset($_GET['delete_id'])) {
    $booking_id = intval($_GET['delete_id']); // Ensure integer type for safety

    // Fetch booking details and ride details to restore seats and notify
    $stmt = $conn->prepare("
        SELECT b.*, r.origin, r.destination, r.ride_date, r.ride_time, r.user_id AS poster_id, r.seats_available, r.vehicle_type, u.email AS posted_email
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u ON r.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ride = $result->fetch_assoc();
        $ride_id = $ride['ride_id'];
        $seats_booked = $ride['seats_booked'];
        $email = $ride['posted_email'];

        // Restore seats in the rides table
        $update_stmt = $conn->prepare("UPDATE rides SET seats_available = seats_available + ? WHERE id = ?");
        $update_stmt->bind_param("ii", $seats_booked, $ride_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Delete the booking
        $delete_stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $booking_id, $user_id);
        if ($delete_stmt->execute()) {
            $delete_stmt->close();

            // Fetch user details for email notifications
            $user_stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $detail = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            // Send notification to the ride poster
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'flexiride247@gmail.com'; // Use a secure source
                $mail->Password = 'lhyzlfabuyopgkqo';      // Use environment variables instead
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('flexiride247@gmail.com', 'FlexiRide');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Ride Cancellation Notification';
                $mail->Body = "<h2>Dear User,</h2>
                    <p>We regret to inform you that a ride you posted has had a cancellation. Below are the details of the canceled user:</p>
                    <ul>
                        <li><strong>Name:</strong> {$detail['name']}</li>
                        <li><strong>Email:</strong> {$detail['email']}</li>
                        <li><strong>Phone:</strong> {$detail['phone']}</li>
                    </ul>
                    <p>Here are the ride details:</p>
                    <ul>
                        <li><strong>Origin:</strong> {$ride['origin']}</li>
                        <li><strong>Destination:</strong> {$ride['destination']}</li>
                        <li><strong>Ride Date:</strong> {$ride['ride_date']}</li>
                        <li><strong>Ride Time:</strong> {$ride['ride_time']}</li>
                        <li><strong>Seats Canceled:</strong> {$seats_booked}</li>
                    </ul>
                    <p>We apologize for any inconvenience caused. For further assistance, please contact our support team.</p>
                    <p>Thank you for choosing FlexiRide!</p>
                    <p>Regards,<br>FlexiRide Team</p>";
                $mail->send();
            } catch (Exception $e) {
                echo "Error sending email: {$mail->ErrorInfo}";
            }

            // Send notification to the user who canceled the ride
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
                $mail->addAddress($detail['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Ride Booking Cancellation Confirmation';
                $mail->Body = "<h2>Dear {$detail['name']},</h2>
                    <p>Your booking for the following ride has been canceled. If you did not initiate this cancellation, please contact us immediately:</p>
                    <ul>
                        <li><strong>Origin:</strong> {$ride['origin']}</li>
                        <li><strong>Destination:</strong> {$ride['destination']}</li>
                        <li><strong>Ride Date:</strong> {$ride['ride_date']}</li>
                        <li><strong>Ride Time:</strong> {$ride['ride_time']}</li>
                        <li><strong>Seats Canceled:</strong> {$seats_booked}</li>
                    </ul>
                    <p>For any questions or assistance, feel free to reach out to our support team.</p>
                    <p>Thank you for choosing FlexiRide!</p>
                    <p>Regards,<br>FlexiRide Team</p>";
                $mail->send();

                echo "<script>alert('Ride booking deleted successfully! Email notifications sent.'); window.location.href = 'my_booked_rides.php';</script>";
            } catch (Exception $e) {
                echo "Error sending email to user: {$mail->ErrorInfo}";
            }
        } else {
            echo "Error deleting booking: " . $conn->error;
        }
    } else {
        echo "No booking found.";
    }
    $stmt->close();
}

// Fetch the rides booked by the user
$sql = "
    SELECT b.id AS booking_id, r.origin, r.destination, r.ride_date, r.vehicle_type, r.price, b.seats_booked, u.name AS driver_name, u.phone AS driver_phone 
    FROM bookings b 
    JOIN rides r ON b.ride_id = r.id 
    JOIN users u ON r.user_id = u.id 
    WHERE b.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booked Rides</title>
    <style>
        body {
            background-image: url("images/op.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0px;
            padding: 0px;
            font-family: "Josefin Sans", sans-serif;
            color: #000;
            font-size: large;
            height: 100vh;
            align-items: center;
            overflow: scroll; 
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Navigation Bar */
        .navbar {
            font-family: "Josefin Sans", sans-serif;
            background-color: #000;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0px 0px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .logo a {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            letter-spacing: 2px;
            transition: color 0.3s ease;
        }

        .logo a:hover {
            color: #ff9800;
        }

        .nav-links {
            list-style: none;
            display: flex;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-size: 18px;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 8px 16px;
            border-radius: 4px;
        }

        .nav-links a:hover {
    color: white; /* Text color on hover */
    background: linear-gradient(135deg, #4a4a8a, #6767b3); /* Gradient matching the button's color */
    border-radius: 30%;
}

        /* Active/Current Page Link */
        .nav-links a.active {
            color: #ff9800;
            background-color:linear-gradient(135deg, #4a4a8a, #6767b3); 
        }

        /* Responsive Design for smaller screens */
        @media screen and (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }

            .nav-links {
                flex-direction: column;
                align-items: center;
                margin-top: 10px;
            }

            .nav-links li {
                margin-left: 0;
                margin-bottom: 0;
            }
        }
        button {
            font-family: "Josefin Sans", sans-serif;
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: white;
            background-color: #000000; /* Vibrant button color */
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Button shadow */
            transition: background-color 0.3s ease;
        }
        .table {
            font-family: "Josefin Sans", sans-serif;
            margin: 0 auto;
            padding: 20px;
            width: 80%;
        }

        .table table {
            width: 100%;
            border-collapse: collapse;
            backdrop-filter: blur(10px);
        }

        .table th, .table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ffffffdd;
            background-color: rgba(255, 255, 255, 0.6);
            color: black;
        }

        .table th {
            background-color: rgba(255, 255, 255, 0.8);
        }

        .table tr:nth-child(even) td {
            background-color: rgba(255, 255, 255, 0.6);
        }

        .table tr:hover td {
            background-color: rgba(255, 255, 255, 0.8);
            color: black;
        }

        .table p {
            text-align: center;
            font-size: 1.2em;
            color: black;
        }

        .table a{
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: white;
            background-color: #000000;
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }
        .table button {
            font-family: "Josefin Sans", sans-serif;
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: black;
            background-color: #000000;
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }


        .table a:hover, button:hover {
            background: linear-gradient(135deg, #4a4a8a, #6767b3);
            color: #fff;
        }
        .book{
            width: fit-content;
            justify-content: center;
            margin: 0 auto;
            padding: 0px 15px;
            margin-top: 3px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 15px black;
            
        }
        .danger-btn-floating {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: red;
            color: white;
            border: none;
            padding: 30px;
            font-size: 18px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <img src="images/logo1.png" alt="logo" height="30%" width="30%">
    </div>
    <img src="images/name.png" alt="logo" height="20%" width="20%">
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="rides.php">Find</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="post_ride.php">Post</a></li>
            <li><a href="myrides.php">MyRides</a></li>
            <li><a href="#" id="logout-link">Logout</a></li>
            <li><a href="profile.php"><i class='bx bxs-user bx-sm'></i></a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
<script>
    document.getElementById('logout-link').addEventListener('click', function(e) {
        e.preventDefault();
        var confirmed = confirm('Are you sure you want to logout?');
        if (confirmed) {
            window.location.href = 'logout.php';
        }
    });
</script>
<br>
<div class="book">
<h2 style="text-align:center;font-family:font-family: 'Josefin Sans', sans-serif;">
    My Booked Rides
    <a href="myrides.php">
        <button style="text-align:center;font-family: 'Josefin Sans', sans-serif;">My Posted rides</button>
    </a>
</h2>
</div>
<div class="table">
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Origin</th>
                <th>Destination</th>
                <th>Ride Date</th>
                <th>Vehicle Type</th>
                <th>Seats Booked</th>
                <th>Price</th>
                <th>Driver Name</th>
                <th>Driver Phone</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['origin']; ?></td>
                    <td><?php echo $row['destination']; ?></td>
                    <td><?php echo $row['ride_date']; ?></td>
                    <td><?php echo $row['vehicle_type']; ?></td>
                    <td><?php echo $row['seats_booked']; ?></td>
                    <td>₹<?php echo $row['price'] * $row['seats_booked']; ?></td>
                    <td><?php echo $row['driver_name']; ?></td>
                    <td><?php echo $row['driver_phone']; ?></td>
                    <td>
                        <a href="my_booked_rides.php?delete_id=<?php echo $row['booking_id']; ?>" onclick="return confirm('Are you sure you want to delete this booking?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No rides booked yet.</p>
    <?php endif; ?>
</div>
<?php if (isset($_SESSION['user_id'])): ?>
    <button id="danger-link" class="danger-btn-floating" onclick="sendEmergencyAlert()">Emergency</button>
    <?php endif; ?>



<script>
    // Add an event listener to the logout link
    document.getElementById('danger-link').addEventListener('click', function(e) {
        // Prevent the default action
        e.preventDefault();
        
        // Display confirmation alert
        var confirmed = confirm('Do you really want to share your location with your emergency emails');
        
        // If confirmed, redirect to logout.php
        if (confirmed) {
            window.location.href = 'danger.php';
        }
    });
</script>
</body>
</html>
