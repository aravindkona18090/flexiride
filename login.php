<?php
session_start();
include 'db.php';
include 'mailer.php';

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

        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@flexiride.com';
        $adminPass  = getenv('ADMIN_PASS')  ?: 'Admin@123';

        // Check if log in is for Admin Dashboard
        if ($email === $adminEmail && $password === $adminPass) {
            $_SESSION['is_admin'] = true;
            $_SESSION['user_id']  = 9999;
            $_SESSION['name']     = 'System Admin';
            header("Location: admin_dashboard.php");
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            
            // Check if user account has admin privileges
            if (($user['email'] ?? '') === $adminEmail) {
                $_SESSION['is_admin'] = true;
            }
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid Email or Password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & OTP Verification - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .auth-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }

        .auth-tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid var(--card-border); padding-bottom: 15px; }
        .tab-btn {
            flex: 1; padding: 12px; border: none; background: transparent; color: var(--text-muted);
            font-size: 16px; font-weight: 600; cursor: pointer; border-radius: 10px; transition: 0.3s;
        }
        .tab-btn.active { background: rgba(56, 189, 248, 0.15); color: var(--primary-color); }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .form-group input {
            width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); font-size: 15px; outline: none;
        }

        .btn-auth {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-auth:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(2, 132, 199, 0.4); }

        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <?php if (!$showOtpStep): ?>
            <div class="auth-tabs">
                <button type="button" class="tab-btn active" id="btn-tab-login" onclick="switchAuth('login')">Login</button>
                <button type="button" class="tab-btn" id="btn-tab-register" onclick="switchAuth('register')">Sign Up & OTP</button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- OTP Verification Step -->
        <?php if ($showOtpStep): ?>
            <form method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <div style="text-align:center; margin-bottom:20px;">
                    <i class='bx bxs-envelope-open' style="font-size:48px; color:var(--primary-color);"></i>
                    <h3 style="font-size:22px; color:var(--text-color); margin-top:10px;">Enter 6-Digit OTP</h3>
                    <p style="font-size:14px; color:var(--text-muted); margin-top:4px;">Check your email for the verification code.</p>
                </div>

                <div class="form-group">
                    <label>6-Digit Verification Code</label>
                    <input type="text" name="otp_code" placeholder="e.g. 584920" maxlength="6" style="text-align:center; font-size:24px; letter-spacing:8px; font-weight:700;" required>
                </div>

                <button type="submit" class="btn-auth">Verify OTP & Activate Account →</button>
            </form>
        <?php else: ?>
            <!-- Login Form -->
            <form method="POST" id="form-login">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@domain.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-auth">Login to Account →</button>
            </form>

            <!-- Register & Request OTP Form -->
            <form method="POST" id="form-register" style="display:none;">
                <input type="hidden" name="action" value="send_otp">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="name@domain.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" placeholder="9876543210" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-auth">Send 6-Digit OTP Verification Code →</button>
            </form>
        <?php endif; ?>
    </div>
</div>

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
</body>
</html>