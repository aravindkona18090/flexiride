<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';

$errorMessage = "";
$successMessage = "";
$showVerifyStep = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        $email = trim($_POST['email'] ?? '');
        $_SESSION['reset_email'] = $email;

        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $otp = random_int(100000, 999999);
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_time'] = time();

            $emailBody = "
                <div style='font-family:Arial,sans-serif; padding:20px; background:#0f172a; color:#f8fafc; border-radius:12px;'>
                    <h2 style='color:#38bdf8;'>FlexiRide Password Reset</h2>
                    <p>Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                    <p>Your password reset verification code is:</p>
                    <div style='font-size:32px; font-weight:800; color:#38bdf8; letter-spacing:5px; margin:20px 0;'>{$otp}</div>
                    <p style='color:#94a3b8; font-size:13px;'>Valid for 10 minutes. If you did not request this, please ignore.</p>
                </div>
            ";

            if (sendResendMail($email, $user['name'], 'FlexiRide - Password Reset OTP', $emailBody)) {
                $showVerifyStep = true;
                $successMessage = "6-digit password reset code sent to your email!";
            } else {
                $errorMessage = "Failed to send reset email. Please try again.";
            }
        } else {
            $errorMessage = "No registered account found with that email address.";
        }
    } elseif (isset($_POST['verify_otp'])) {
        $enteredOtp = trim($_POST['otp'] ?? '');
        $savedOtp   = (string)($_SESSION['reset_otp'] ?? '');
        $otpTime    = (int)($_SESSION['reset_otp_time'] ?? 0);

        if ($savedOtp && $enteredOtp === $savedOtp) {
            if ((time() - $otpTime) > 600) {
                $errorMessage = "Reset OTP has expired. Please request a new code.";
            } else {
                $_SESSION['otp_verified_email'] = $_SESSION['reset_email'];
                header("Location: forgot_otp.php");
                exit();
            }
        } else {
            $showVerifyStep = true;
            $errorMessage = "Invalid verification code! Please check and try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0; min-height: 75vh; display:flex; align-items:center;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 440px; margin: 0 auto; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--primary-glow); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px;">
                <i class='bx bxs-key'></i>
            </div>

            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                Password Recovery
            </h2>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 20px;">
                Enter your registered account email to receive a secure reset OTP.
            </p>

            <?php if ($errorMessage): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px; border-radius:var(--radius-md); margin-bottom:18px; font-size:14px;">
                    ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px; border-radius:var(--radius-md); margin-bottom:18px; font-size:14px; font-weight:600;">
                    ✅ <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($showVerifyStep): ?>
                <form method="POST">
                    <div class="fr-form-group" style="text-align:left;">
                        <label class="fr-label" style="text-align:center;">Enter 6-Digit Reset OTP</label>
                        <input type="text" name="otp" class="fr-input" placeholder="• • • • • •" maxlength="6" style="text-align:center; font-size:26px; letter-spacing:8px; font-family:monospace; font-weight:800;" required autocomplete="off">
                    </div>
                    <button type="submit" name="verify_otp" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                        Verify OTP Code <i class='bx bx-check'></i>
                    </button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <div class="fr-form-group" style="text-align:left;">
                        <label class="fr-label">Registered Account Email</label>
                        <input type="email" name="email" class="fr-input" placeholder="name@domain.com" required value="<?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="send_otp" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                        Send Reset Code <i class='bx bx-mail-send'></i>
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 18px;">
                <a href="login.php" style="font-size: 13.5px; color: var(--primary); text-decoration: none; font-weight: 600;">
                    <i class='bx bx-left-arrow-alt'></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
