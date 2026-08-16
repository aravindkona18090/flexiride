<?php
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';

function sendOtpEmail($toEmail, $otp) {
    $html = "
        <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f6f9;'>
            <div style='max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
                <h2 style='color: #ff4757; text-align: center;'>FlexiRide Verification</h2>
                <p>Hello,</p>
                <p>Your One-Time Password (OTP) for registration verification is:</p>
                <div style='text-align: center; margin: 20px 0;'>
                    <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #2ed573; background: #e8fae8; padding: 10px 20px; border-radius: 8px;'>$otp</span>
                </div>
                <p style='color: #777; font-size: 13px; text-align: center;'>This OTP is valid for 5 minutes. Do not share it with anyone.</p>
            </div>
        </div>
    ";
    return sendResendMail($toEmail, '', 'FlexiRide - Verification OTP', $html);
}

$pending_user = $_SESSION['pending_registration'] ?? null;
$user_email = $pending_user['email'] ?? ($_GET['email'] ?? '');

$successMessage = "";
$errorMessage = "";
$verified = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        if (empty($user_email)) {
            $errorMessage = "No email address found for verification. Please register again.";
        } else {
            $otp = rand(1000, 9999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_timestamp'] = time();

            if (sendOtpEmail($user_email, $otp)) {
                $successMessage = "OTP sent successfully to " . htmlspecialchars($user_email);
            } else {
                $errorMessage = "Failed to send OTP. Please check email settings or try again.";
            }
        }
    } elseif (isset($_POST['verify_otp'])) {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if (isset($_SESSION['otp']) && $enteredOtp == $_SESSION['otp']) {
            if (time() - $_SESSION['otp_timestamp'] > 300) {
                $errorMessage = "OTP expired. Please request a new one.";
                unset($_SESSION['otp'], $_SESSION['otp_timestamp']);
            } else {
                unset($_SESSION['otp'], $_SESSION['otp_timestamp']);
                $verified = true;

                if ($pending_user) {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $pending_user['name'], $pending_user['email'], $pending_user['password'], $pending_user['phone']);
                    if ($stmt->execute()) {
                        $successMessage = "Account verified and created successfully!";
                        unset($_SESSION['pending_registration']);
                    } else {
                        $errorMessage = "Database insertion failed: " . $conn->error;
                    }
                } else {
                    $successMessage = "OTP verified successfully!";
                }
            }
        } else {
            $errorMessage = "Invalid OTP code. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification - FlexiRide</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #fff;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .card {
      background: rgba(30, 41, 59, 0.7);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 40px;
      width: 100%;
      max-width: 440px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    .card h2 { font-size: 24px; margin-bottom: 10px; color: #38bdf8; }
    .card p { font-size: 14px; color: #94a3b8; margin-bottom: 25px; }
    .otp-inputs { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
    .otp-inputs input {
      width: 50px;
      height: 60px;
      text-align: center;
      font-size: 24px;
      font-weight: 700;
      border-radius: 12px;
      border: 2px solid #334155;
      background: #0f172a;
      color: #38bdf8;
      outline: none;
      transition: all 0.3s ease;
    }
    .otp-inputs input:focus { border-color: #38bdf8; box-shadow: 0 0 10px rgba(56, 189, 248, 0.3); }
    .btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4); }
    .btn-secondary {
      background: #334155;
      margin-top: 15px;
    }
    .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
    .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; }
    .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; }
    a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="card">
    <h2>🔒 OTP Verification</h2>
    <p>Verify your email address for FlexiRide</p>

    <?php if ($successMessage): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($verified): ?>
      <div style="margin-top: 20px;">
        <p style="color: #4ade80; font-size: 18px; font-weight: 600;">✨ Email Successfully Verified!</p>
        <a href="login.php" class="btn" style="display: inline-block; text-align: center; margin-top: 15px;">Proceed to Login</a>
      </div>
    <?php else: ?>
      <form method="POST" id="otpForm">
        <?php if (!isset($_SESSION['otp'])): ?>
          <p>Click below to send a 4-digit OTP to <strong><?php echo htmlspecialchars($user_email ?: 'your email'); ?></strong></p>
          <button type="submit" name="send_otp" class="btn">Send Verification OTP</button>
        <?php else: ?>
          <div class="otp-inputs">
            <input type="text" maxlength="1" class="otp-box" autofocus>
            <input type="text" maxlength="1" class="otp-box">
            <input type="text" maxlength="1" class="otp-box">
            <input type="text" maxlength="1" class="otp-box">
          </div>
          <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
          <button type="submit" name="send_otp" class="btn btn-secondary">Resend OTP</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>

  <script>
    const boxes = document.querySelectorAll('.otp-box');
    const form = document.getElementById('otpForm');

    boxes.forEach((box, idx) => {
      box.addEventListener('input', (e) => {
        if (box.value.length === 1 && idx < boxes.length - 1) {
          boxes[idx + 1].focus();
        }
      });
      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && box.value === '' && idx > 0) {
          boxes[idx - 1].focus();
        }
      });
    });

    if (form) {
      form.addEventListener('submit', () => {
        if (boxes.length > 0) {
          let fullOtp = '';
          boxes.forEach(b => fullOtp += b.value);
          let hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'otp';
          hiddenInput.value = fullOtp;
          form.appendChild(hiddenInput);
        }
      });
    }
  </script>
</body>
</html>
