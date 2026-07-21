<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['ride_id'])) {
    $ride_id = $_GET['ride_id'];

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $seats_booked = $_POST['seats_booked'];

        $ride_sql = "SELECT * FROM rides WHERE id = $ride_id";
        $ride_result = $conn->query($ride_sql);
        
        if ($ride_result->num_rows > 0) {
            $ride = $ride_result->fetch_assoc();
            $current_user_id = $user_id = $_SESSION['user_id'];
            $user_sql = "SELECT email,name,phone FROM users WHERE id = $current_user_id";
            $user_result = $conn->query($user_sql);
            
            if ($user_result->num_rows > 0) {
                $user = $user_result->fetch_assoc();
                $user_email = $user['email'];
                $user_name = $user['name'];
                $user_phone = $user['phone'];
            } else {
                echo "User not found.";
                exit();
            }

            $posted_user_id = $ride['user_id']; 
            $posted_user_sql = "SELECT email,name,phone FROM users WHERE id = $posted_user_id";
            $posted_user_result = $conn->query($posted_user_sql);
            
            if ($posted_user_result->num_rows > 0) {
                $posted_user = $posted_user_result->fetch_assoc();
                $posted_user_email = $posted_user['email'];
                $posted_user_name  = $posted_user['name'];
                $posted_user_phone = $posted_user['phone']; 
            } else {
                echo "Posted user not found.";
                exit();
            }

            if ($ride['seats_available'] >= $seats_booked) {
                $new_seats = $ride['seats_available'] - $seats_booked;
                $update_sql = "UPDATE rides SET seats_available = $new_seats, posted_email = '$posted_user_email' WHERE id = $ride_id";
                $conn->query($update_sql);

                $booking_sql = "INSERT INTO bookings (user_id, ride_id, seats_booked, posted_email, booked_email) 
                                VALUES ('$current_user_id', '$ride_id', '$seats_booked', '$posted_user_email', '$user_email')";
                if ($conn->query($booking_sql) === TRUE) {
                    try {
                        $mail = new PHPMailer(true);
                        
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';               
                        $mail->SMTPAuth = true;                        
                        $mail->Username = 'flexiride247@gmail.com';   
                        $mail->Password = 'lhyzlfabuyopgkqo';          
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port = 587;                             
                        $mail->setFrom('flexiride247@gmail.com', 'FlexiRide'); 
                        $mail->addAddress($posted_user_email);                    
                    
                        
                        $mail->isHTML(true); 
                        $mail->Subject = 'Your Ride Details - FlexiRide';
                        $mail->Body = "
                            <h2>Dear User,</h2>
                            <p>Your ride is sucessfully booked on <strong>FlexiRide</strong>.</p>
                            <h3>Ride Details:</h3>
                            <ul>
                                <li><strong>Booked User name:</strong>  $user_name </li>
                                <li><strong>Booked User Phone Number:</strong> $user_phone</li>
                                <li><strong>Seats Booked: </strong>  $seats_booked</li>
                                <li><strong>Seats Available:</strong> $new_seats</li>
                            </ul>
                            <p>Be puntual, Ride safe, Follow traffic rules.</p>
                            <p>If any emergency click on emergency button in our website.</p>
                            <p>Thank you for using FlexiRide.</p>
                            <p>Regards,<br>FlexiRide Team</p>
                        ";
                    
                        $mail->send();
                        
                    } catch (Exception $e) {
                        
                        echo "Error sending email: " . $mail->ErrorInfo;
                        error_log("Email sending error: " . $mail->ErrorInfo);
                    }
                    try {
                        $mail = new PHPMailer(true);
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;         
                        $mail->Username = 'flexiride247@gmail.com';   
                        $mail->Password = 'lhyzlfabuyopgkqo';     
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port = 587;                             
                    
                        $mail->setFrom('flexiride247@gmail.com', 'FlexiRide'); 
                        $mail->addAddress($user_email);                        
                    
                        $mail->isHTML(true); 
                        $mail->Subject = 'Your Ride Details - FlexiRide';
                        $mail->Body = "
                            <h2>Dear User,</h2>
                            <p>Your ride is sucessfully booked on <strong>FlexiRide</strong>.</p>
                            <h3>Ride Details:</h3>
                            <ul>
                                <li><strong>Posted User name:</strong>  $posted_user_name </li>
                                <li><strong>Posted User Phone Number:</strong> $posted_user_phone</li>
                                <li><strong>Seats Booked: </strong>  $seats_booked</li>
                            </ul>
                            <p>Be puntual, Be safe .</p>
                            <p>If any emergency click on emergency button in our website.</p>
                            <p>Thank you for using FlexiRide.</p>
                            <p>Regards,<br>FlexiRide Team</p>
                        ";
                    
                        $mail->send();
                        header("Location: booking_success.php?ride_id=$ride_id");
                        exit();
                        
                    } catch (Exception $e) {
                        echo "Error sending email: " . $mail->ErrorInfo; 
                        error_log("Email sending error: " . $mail->ErrorInfo); 
                    }
                    header("Location: booking_success.php?ride_id=$ride_id");

                    exit();
                } else {
                    echo "Error: " . $booking_sql . "<br>" . $conn->error;
                }
                
            } else {
                echo "Not enough seats available!";
            }
        } else {
            echo "Ride not found.";
            exit();
        }
    }
} else {
    echo "Invalid Ride ID.";
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Ride</title>
    <style>
/* General Body Styling */
body {
    font-family: "Josefin Sans", sans-serif;
    color: black;
    margin: 0;
    padding: 0;
    background-image: url("images/sample-transformed.jpeg");
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    animation: gradientBackground 15s ease infinite; /* Background animation */
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
}

/* Background Animation */
@keyframes gradientBackground {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Booking Form Container */
.book {
    width: 100%;
    max-width: 400px;
    margin: auto;
    padding: 20px;
    text-align: center;
   
    position: relative;
    animation: fadeIn 2s ease-out; /* Fade-in effect */
}

@keyframes fadeIn {
    0% { opacity: 0; transform: translateY(-20px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Heading Animation */
h1 {
    font-size: 2.5rem;
    color: white;
    margin-bottom: 20px;
    text-shadow: 0 0 20px rgba(255, 255, 255, 0.6);
    animation: pulse 2s infinite;
}

/* Glowing Heading Animation */
@keyframes pulse {
    0%, 100% { text-shadow: 0 0 20px rgba(255, 255, 255, 0.6); }
    50% { text-shadow: 0 0 30px rgba(255, 255, 255, 0.8); }
}

/* Form Styling */
form {
    backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    padding: 20px;
    animation: slideIn 1.5s ease-out; /* Slide-in effect */
}

/* Slide-in Animation for the Form */
@keyframes slideIn {
    0% { transform: scale(0.9) translateY(20px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

/* Input Fields */
input[type="number"] {
    width: calc(100% - 20px);
    padding: 12px;
    margin-bottom: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    position: relative;
}

/* Focus Animation for Input */
input[type="number"]:focus {
    border-color: #4a4a8a;
    box-shadow: 0 0 10px rgba(74, 74, 138, 0.7);
    transform: scale(1.02);
    outline: none;
}

/* Button Styling */
button[type="submit"] {
    font-family: "Josefin Sans", sans-serif;
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 90%;
    position: relative;
    overflow: hidden;
}

button[type="submit"]:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.5);
}

button[type="submit"]:active {
    transform: translateY(1px);
    background: #39396b;
}

/* Button Ripple Effect */
button[type="submit"]::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transition: width 0.5s ease, height 0.5s ease, opacity 0.3s ease;
    z-index: 1;
}

button[type="submit"]:hover::after {
    width: 200%;
    height: 500%;
    opacity: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    h1 {
        font-size: 2rem;
    }

    form {
        padding: 15px;
    }

    button[type="submit"], input[type="number"] {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.8rem;
    }

    form {
        padding: 10px;
    }

    button[type="submit"] {
        font-size: 0.9rem;
        padding: 10px;
    }

    input[type="number"] {
        padding: 10px;
    }
}

    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
</head>
<body>
    <div class="book">
        <h1 style="color:black;">Book rides</h1>
    <form method="post" action="">
        <label for="seats_booked">Seats to Book:</label>
        <input type="number" name="seats_booked" required>
        <button type="submit">Book Ride</button>
    </form>
    <div class="error"></div>
    </div>
</body>
</html>  