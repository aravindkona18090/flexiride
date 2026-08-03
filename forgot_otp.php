<?php
include "db.php";
session_start();
$completed = False;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pass']) && isset($_POST['repass'])) {
        $password = $_POST['pass'];
        $repassword = $_POST['repass'];
        if ($password === $repassword) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if (isset($_GET['messege']) && isset($_GET['email'])) {
                $email = $_GET['email'];
                $successMessage = $_GET['messege'];
            }
            $sql = "UPDATE users SET password='$hashedPassword' WHERE email='$email'";
            if ($conn->query($sql) === TRUE) {
                $successMessage = "Password Updated successfully!";
                $homeLink = '<a href="login.php" style="font-size: medium;text-decoration: none;" >Go to login Page</a>';
                $completed = True;
                // You can redirect the user to the login page or display a success message
            } else {
                $errorMessage = "Error updating password!";
                $completed = False;
            }
        } else {
            $errorMessage = "Passwords do not match";
            $completed = False;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .otp {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10px;
            background: #00000000;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8.2px);
            -webkit-backdrop-filter: blur(8.2px);
            border: 1px solid #369eff66;
            width: 25em;
            height: 25em;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .otp .content {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
            margin-bottom: auto;
        }

        .otp p {
            color: #000000;
            font-weight: bolder;
            font-size: 26px; /* Increased font size */
        }

        .otp .path {
            fill: #369eff;
        }

        .otp .svg {
            filter: blur(20px);
            z-index: -1;
            position: absolute;
            opacity: 50%;
            animation: anim 3s infinite linear;
        }

        .otp .inp {
            margin-left: auto;
            margin-right: auto;
            white-space: 4px;
            position: relative;
        }

        .otp .input + .input {
            margin-left: 0.3em;
        }

        .otp .input {
            color: black;
            height: 3em;
            width: 15em;
            float: left;
            text-align: center;
            background: #00000000;
            outline: none;
            margin-top: 10px;
            border: 1px #369eff solid;
            font-size: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .otp .input:focus {
            outline: none;
            border: 1px #fff solid;
        }

        .otp .input:not(:placeholder-shown) {
            opacity: 100%;
        }

        .otp button {
            margin-left: auto;
            margin-right: auto;
            background-color: #00000000;
            color: #000000;
            width: 10em;
            height: 3em;
            border: #369eff 0.2em solid;
            border-radius: 11px;
            transition: all 0.5s ease;
            font-size: 1em;
        }

        .otp button:hover {
            background-color: #369eff;
        }

        @keyframes anim {
            0% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(180deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .eye-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            font-size: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<form class="otp" method="POST">
    <?php if (!$completed): ?>
        <div class="content">
            <p align="center">Change Password</p>
            <div class="inp" style="position: relative;">
                <!-- Password Input -->
                <input placeholder="Enter Password" name="pass" type="password" class="input" id="password"><br><br>
                <!-- Eye Icon for Password Field -->
                <span id="eye-icon" class="eye-icon">&#128586;</span>
            </div>
            <div class="inp" style="position: relative;">
                <!-- Re-enter Password Input -->
                <input placeholder="ReEnter Password" name="repass" type="password" class="input" id="repassword"><br><br>
                <!-- Eye Icon for Re-enter Password Field -->
                <span id="eye-icon-re" class="eye-icon">&#128586;</span>
            </div>
            <button>Change</button>
        </div>
    <?php endif; ?>
    <?php if (isset($successMessage)): ?>
        <div class="message">
            <?php echo $successMessage; ?>
            <?php if (isset($homeLink)): ?>
              <p><?php echo $homeLink; ?></p>
            <?php endif; ?>
        </div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="message"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    <svg class="svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <path fill="#4073ff"
              d="M56.8,-23.9C61.7,-3.2,45.7,18.8,26.5,31.7C7.2,44.6,-15.2,48.2,-35.5,36.5C-55.8,24.7,-73.9,-2.6,-67.6,-25.2C-61.3,-47.7,-30.6,-65.6,-2.4,-64.8C25.9,-64.1,51.8,-44.7,56.8,-23.9Z"
              transform="translate(100 100)" class="path"></path>
    </svg>
</form>

<script>
    // Toggle password visibility for the first input field
    const eyeIcon = document.getElementById('eye-icon');
    const passwordField = document.getElementById('password');
    eyeIcon.addEventListener('click', () => {
        if (passwordField.type === 'password') {
            passwordField.type = 'text'; // Show password
            eyeIcon.innerHTML = '&#128065;'; // Change to open eye
        } else {
            passwordField.type = 'password'; // Hide password
            eyeIcon.innerHTML = '&#128586;'; // Change to closed eye
        }
    });

    // Toggle password visibility for the second input field
    const eyeIconRe = document.getElementById('eye-icon-re');
    const repasswordField = document.getElementById('repassword');
    eyeIconRe.addEventListener('click', () => {
        if (repasswordField.type === 'password') {
            repasswordField.type = 'text'; // Show password
            eyeIconRe.innerHTML = '&#128065;'; // Change to open eye
        } else {
            repasswordField.type = 'password'; // Hide password
            eyeIconRe.innerHTML = '&#128586;'; // Change to closed eye
        }
    });
</script>
</body>
</html>
