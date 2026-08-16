<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';

$error = '';
$success = '';
$showOtpStep = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'send_otp') {
        $name     = trim($_POST['name']);
        $email    = trim($_POST['email']);
        $phone    = trim($_POST['phone']);
        $password = $_POST['password'];

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $checkStmt->bind_param("ss", $email, $phone);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "Account with this Email or Phone already exists!";
        } else {
            // Generate 6-Digit Random Security OTP
            $otp = random_int(100000, 999999);
            $_SESSION['temp_reg'] = [
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'otp'      => (string)$otp,
                'expire'   => time() + 600 // 10 mins expiry
            ];

            // Send Email OTP via Resend
            $otpHtml = "
                <div style='font-family:Arial,sans-serif; padding:20px; background:#0f172a; color:#f8fafc; border-radius:12px;'>
                    <h2 style='color:#38bdf8;'>FlexiRide Security OTP</h2>
                    <p>Hi <strong>{$name}</strong>,</p>
                    <p>Your 6-digit OTP code to verify your FlexiRide account is:</p>
                    <div style='font-size:32px; font-weight:800; color:#4ade80; letter-spacing:5px; margin:20px 0;'>{$otp}</div>
                    <p style='color:#94a3b8; font-size:13px;'>Valid for 10 minutes. Do not share this code with anyone.</p>
                </div>
            ";
            $sent = sendResendMail($email, $name, 'FlexiRide - Account Verification OTP Code', $otpHtml);

            if ($sent) {
                $showOtpStep = true;
                $success = "6-Digit Verification OTP sent to {$email}!";
            } else {
                $error = "Email Delivery Failed: " . getLastMailerError();
            }
        }
    } elseif ($action === 'verify_otp') {
        $enteredOtp = trim($_POST['otp_code']);
        $temp = $_SESSION['temp_reg'] ?? null;

        if ($temp && (string)$temp['otp'] === (string)$enteredOtp && time() <= $temp['expire']) {
            // OTP Verified! Create user in database
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $temp['name'], $temp['email'], $temp['phone'], $temp['password']);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name']    = $temp['name'];
                unset($_SESSION['temp_reg']);
                header("Location: profile.php");
                exit();
            } else {
                $error = "Account creation failed: " . $conn->error;
            }
        } else {
            $showOtpStep = true;
            $error = "Invalid or expired OTP code! Please check your email or resend.";
        }
    } else {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        // Security Throttling Rate Limiter
        $failedAttempts  = $_SESSION['login_failed_count'] ?? 0;
        $lastAttemptTime = $_SESSION['last_login_attempt'] ?? 0;

        if ($failedAttempts >= 5 && (time() - $lastAttemptTime) < 900) {
            $remainingMins = ceil((900 - (time() - $lastAttemptTime)) / 60);
            $error = "🔒 Security Lockout: Too many failed login attempts! Try again in {$remainingMins} minutes.";
        } else {
            if ($failedAttempts >= 5 && (time() - $lastAttemptTime) >= 900) {
                $_SESSION['login_failed_count'] = 0;
            }

            $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@flexiride.com';
            $adminPass  = getenv('ADMIN_PASS')  ?: 'Admin@123';

            // Check if log in is for Admin Dashboard
            if ($email === $adminEmail && $password === $adminPass) {
                $_SESSION['login_failed_count'] = 0;
                $_SESSION['is_admin'] = true;
                $_SESSION['user_id']  = 9999;
                $_SESSION['name']     = 'System Admin';
                header("Location: admin/admin_dashboard.php");
                exit();
            }

            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['login_failed_count'] = 0;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                
                if (($user['email'] ?? '') === $adminEmail) {
                    $_SESSION['is_admin'] = true;
                }
                
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['login_failed_count'] = ($failedAttempts + 1);
                $_SESSION['last_login_attempt'] = time();
                $attemptsLeft = 5 - $_SESSION['login_failed_count'];
                if ($attemptsLeft > 0) {
                    $error = "Invalid Email or Password! ({$attemptsLeft} attempt(s) remaining before temporary lockout)";
                } else {
                    $error = "🔒 Account locked for 15 minutes due to 5 consecutive failed login attempts.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In & Register — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0; min-height: 80vh; display:flex; align-items:center;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 460px; margin: 0 auto;">
            <?php if (!$showOtpStep): ?>
                <div class="vehicle-segmented-tab" style="margin-bottom: 24px;">
                    <button type="button" class="seg-btn active" id="btn-tab-login" onclick="switchAuth('login')">Login</button>
                    <button type="button" class="seg-btn" id="btn-tab-register" onclick="switchAuth('register')">Sign Up & OTP</button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- OTP Verification Step -->
            <?php if ($showOtpStep): ?>
                <form method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = `<i class='bx bx-loader-alt bx-spin'></i> Verifying...`;">
                    <input type="hidden" name="action" value="verify_otp">
                    <div style="text-align:center; margin-bottom:20px;">
                        <i class='bx bxs-envelope-open' style="font-size:48px; color:var(--primary);"></i>
                        <h3 style="font-size:22px; font-weight:800; color:var(--text-main); margin-top:8px;">Enter 6-Digit OTP</h3>
                        <p style="font-size:13.5px; color:var(--text-muted); margin-top:4px;">Check your email inbox for the security verification code.</p>
                    </div>

                    <div class="fr-form-group">
                        <label class="fr-label" style="text-align:center;">6-Digit OTP Code</label>
                        <input type="text" name="otp_code" class="fr-input" placeholder="• • • • • •" maxlength="6" style="text-align:center; font-size:28px; letter-spacing:10px; font-family:monospace; font-weight:800;" required autocomplete="off">
                    </div>

                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                        Verify & Activate Account <i class='bx bx-check-circle'></i>
                    </button>
                </form>
            <?php else: ?>
                <!-- Login Form -->
                <form method="POST" id="form-login" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = `<i class='bx bx-loader-alt bx-spin'></i> Signing in...`;">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="fr-form-group">
                        <label class="fr-label">Email Address</label>
                        <input type="email" name="email" class="fr-input" placeholder="name@domain.com" required autocomplete="email">
                    </div>
                    
                    <div class="fr-form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label class="fr-label" style="margin-bottom:0;">Password</label>
                            <a href="forgot_password.php" style="font-size:12.5px; color:var(--primary); text-decoration:none;">Forgot password?</a>
                        </div>
                        <input type="password" name="password" class="fr-input" placeholder="••••••••" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg" style="margin-top:10px;">
                        Sign In to FlexiRide <i class='bx bx-right-arrow-alt'></i>
                    </button>
                </form>

                <!-- Register & Request OTP Form -->
                <form method="POST" id="form-register" style="display:none;" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = `<i class='bx bx-loader-alt bx-spin'></i> Dispatching OTP...`;">
                    <input type="hidden" name="action" value="send_otp">
                    
                    <div class="fr-form-group">
                        <label class="fr-label">Full Name</label>
                        <input type="text" name="name" class="fr-input" placeholder="e.g. Rahul Sharma" required autocomplete="name">
                    </div>
                    
                    <div class="fr-form-group">
                        <label class="fr-label">Email Address</label>
                        <input type="email" name="email" class="fr-input" placeholder="name@domain.com" required autocomplete="email">
                    </div>
                    
                    <div class="fr-form-group">
                        <label class="fr-label">Mobile Phone Number</label>
                        <input type="tel" name="phone" class="fr-input" placeholder="10-digit mobile number" required autocomplete="tel">
                    </div>
                    
                    <div class="fr-form-group">
                        <label class="fr-label">Create Password</label>
                        <input type="password" name="password" class="fr-input" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg" style="margin-top:10px;">
                        Send 6-Digit Email OTP <i class='bx bx-mail-send'></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function switchAuth(type) {
        if (type === 'login') {
            document.getElementById('form-login').style.display = 'block';
            document.getElementById('form-register').style.display = 'none';
            document.getElementById('btn-tab-login').classList.add('active');
            document.getElementById('btn-tab-register').classList.remove('active');
        } else {
            document.getElementById('form-login').style.display = 'none';
            document.getElementById('form-register').style.display = 'block';
            document.getElementById('btn-tab-register').classList.add('active');
            document.getElementById('btn-tab-login').classList.remove('active');
        }
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>