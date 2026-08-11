<?php
require_once __DIR__ . '/includes/resend.php';
include_once __DIR__ . '/includes/db.php';

session_start();

/**
 * Send OTP using Resend
 */
function sendOtp($email, $otp)
{
    $emailBody = "
    <h2>FlexiRide OTP Verification</h2>

    <p>Hello,</p>

    <p>Your One-Time Password (OTP) for verification is:</p>

    <h1 style='font-size:32px;color:#2563eb;letter-spacing:5px;'>{$otp}</h1>

    <p>This OTP is valid for a limited time.</p>

    <p><strong>Do not share this OTP with anyone.</strong></p>

    <br>

    <p>Regards,<br>
    <strong>FlexiRide Team</strong></p>
    ";

    try {

        sendResendEmail(
            $email,
            "FlexiRide User",
            "Your OTP for Verification",
            $emailBody
        );

        return true;

    } catch (Exception $e) {

        error_log("Resend Error: " . $e->getMessage());

        return false;

    }
}

$home = false;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['send_otp'])) {

        // Generate a 4-digit OTP
        $otp = rand(1000, 9999);

        $_SESSION['otp'] = $otp;
        $_SESSION['otp_timestamp'] = time();

        if (isset($_POST['email'])) {

            $email = trim($_POST['email']);
            $_SESSION['email'] = $email;

            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {

                if (sendOtp($email, $otp)) {

                    $successMessage = "OTP sent successfully.";

                } else {

                    $errorMessage = "Failed to send OTP. Please try again.";

                }

            } else {

                $errorMessage = "Entered Email is not registered.";

            }
        }

    } elseif (isset($_POST['verify_otp'])) {

        $enteredOtp = $_POST['otp'] ?? '';

        if (isset($_SESSION['otp']) && $enteredOtp == $_SESSION['otp']) {

            if (time() - $_SESSION['otp_timestamp'] > 3000) {

                $errorMessage = "OTP expired. Please request a new one.";

                $home = false;

                unset($_SESSION['otp'], $_SESSION['otp_timestamp']);

            } else {

                $successMessage = "OTP verified successfully!";

                $email = $_SESSION['email'];

                unset($_SESSION['otp'], $_SESSION['otp_timestamp']);

                header("Location: forgot_otp.php?messege=" . urlencode($successMessage) . "&email=" . urlencode($email));
                exit();

            }

        } else {

            $errorMessage = "Invalid OTP. Please try again.";

            $home = false;

        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification</title>
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
            width: 18em;
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
    <div class="content">
      <?php if (!isset($_SESSION['otp']) && $home == false): ?>
        <input type="email" name = "email" placeholder="Enter Registered email" class="input" required>
          <button type="submit" name="send_otp">Send OTP</button>
      <?php endif; ?>
      
      <!-- Display Messages -->
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
    </div>
    <svg class="svg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <path fill="#4073ff" d="M56.8,-23.9C61.7,-3.2,45.7,18.8,26.5,31.7C7.2,44.6,-15.2,48.2,-35.5,36.5C-55.8,24.7,-73.9,-2.6,-67.6,-25.2C-61.3,-47.7,-30.6,-65.6,-2.4,-64.8C25.9,-64.1,51.8,-44.7,56.8,-23.9Z" transform="translate(100 100)" class="path"></path>
      </svg>
  </form>
</body>
<script>
    // Handle OTP input field focus and combination
    const otpInputs = document.querySelectorAll('.otp .input');
    const form = document.querySelector('.otp');

    form.addEventListener('submit', (e) => {
      let otp = '';
      otpInputs.forEach(input => otp += input.value);
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'otp';
      hiddenInput.value = otp;
      form.appendChild(hiddenInput);
    });

    otpInputs.forEach((input, index) => {
      input.addEventListener('input', () => {
        if (input.value.length === 1 && otpInputs[index + 1]) {
          otpInputs[index + 1].focus();
        }
      });
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && input.value === '' && otpInputs[index - 1]) {
          otpInputs[index - 1].focus();
        }
      });
    });
  </script>
</html>
