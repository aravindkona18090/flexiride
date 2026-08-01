<?php
require 'resend.php';
include 'db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from the database
$user_id = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $user = [];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $emergency_email1 = trim($_POST['emergency_email1']);
    $emergency_email2 = trim($_POST['emergency_email2']);

    // Update profile
    $update_query = "UPDATE users
                     SET name = ?, email = ?, phone = ?, emergency_email1 = ?, emergency_email2 = ?
                     WHERE id = ?";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param(
        "sssssi",
        $name,
        $email,
        $phone,
        $emergency_email1,
        $emergency_email2,
        $user_id
    );

    if ($update_stmt->execute()) {

        $emailBody = "
        <h2>Profile Updated Successfully</h2>

        <p>Dear <strong>{$name}</strong>,</p>

        <p>Your FlexiRide profile has been updated successfully.</p>

        <h3>Your Updated Details</h3>

        <ul>
            <li><strong>Name:</strong> {$name}</li>
            <li><strong>Email:</strong> {$email}</li>
            <li><strong>Phone:</strong> {$phone}</li>
            <li><strong>Emergency Email 1:</strong> {$emergency_email1}</li>
            <li><strong>Emergency Email 2:</strong> {$emergency_email2}</li>
        </ul>

        <p>
            If you did not make these changes, please contact the FlexiRide support team immediately.
        </p>

        <br>

        <p>
            Regards,<br>
            <strong>FlexiRide Team</strong>
        </p>
        ";

        try {

            sendResendEmail(
                $email,
                $name,
                "Profile Updated Successfully",
                $emailBody
            );

        } catch (Exception $e) {

            error_log("Resend Error: " . $e->getMessage());

        }

        header("Location: profile.php?message=Profile updated successfully!");
        exit();

    } else {

        $message = "Error updating profile.";

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
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
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
    font-size: large;
}

/* Container for Form */
.edit-profile-container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter:blur(10px);
    width: 90%;
    max-width: 500px;  /* Reduced width for smaller form */
    padding: 30px;  /* Reduced padding */
    border-radius: 10px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    opacity: 0;  /* Initially hidden */
    animation: fadeInOpacity 1s ease-in-out forwards; /* Apply the opacity transition */
}

/* Title Styling */
h2 {
    text-align: center;
    color: #333;;  /* Reduced font size */
    font-weight: 600;
    margin-bottom: 15px;  /* Reduced margin */
}

/* Input Fields Styling */
label {  /* Smaller font size */
    color: #333;
    display: block;
    margin: 8px 0 5px;  /* Reduced margins */
    font-family: 'Josefin Sans', sans-serif;
    font-weight: 500;
}

input[type="email"], input[type="text"] {
    width: 98%;
    padding: 8px 12px;  /* Reduced padding */
    margin-bottom: 15px;  /* Reduced margin */
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f9f9f9; /* Reduced font size */
    font-family: 'Josefin Sans', sans-serif;
    transition: border 0.3s ease, box-shadow 0.3s ease;
    font-size: large;
}

input[type="email"]:focus, input[type="text"]:focus {
    outline: none;
    border: 1px solid #4a4a8a;
    box-shadow: 0 0 10px rgba(74, 74, 138, 0.3);
}

/* Button Styling */
button[type="submit"] {
    font-family: "Josefin Sans", sans-serif;
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 95%;
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

/* Simple Opacity Animation */
@keyframes fadeInOpacity {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

/* Focus Effects */
input[type="email"]:focus + label,
input[type="text"]:focus + label {
    color: #4a4a8a;
    font-family: "Josefin Sans", sans-serif;
    transform: translateY(-10px);
}

/* General Text Styling for Form */
p {  /* Smaller font size */
    color: #666;
    text-align: center;
    margin-top: 20px;
    font-family: 'Josefin Sans', sans-serif;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .edit-profile-container {
        padding: 15px;
    }

    h2 {
        font-size: 24px;  /* Adjusted font size for smaller screens */
    }

    button[type="submit"] {
        padding: 10px;  /* Reduced padding for buttons on small screens */
    }
}
.back-home-btn {
            display: inline-block;
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: bold;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .back-home-btn:hover {
            background-color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
<div class="edit-profile-container">
    <h2>Edit Your Profile</h2>
    <form action="edit_profile.php" method="POST">
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

        <label for="phone">Phone Number:</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required><br><br>

        <label for="emergency_email1">Emergency Email 1:</label>
        <input type="email" id="emergency_email1" name="emergency_email1" value="<?php echo htmlspecialchars($user['emergency_email1']); ?>" required><br><br>

        <label for="emergency_email2">Emergency Email 2:</label>
        <input type="email" id="emergency_email2" name="emergency_email2" value="<?php echo htmlspecialchars($user['emergency_email2']); ?>" required><br><br>

        <button type="submit">Update Profile</button>
    </form>
</div>

<a href="profile.php" class="back-home-btn">Back to Home</a>
</body>
</html>