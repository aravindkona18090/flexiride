<?php
session_start();
include_once __DIR__ . '/includes/db.php';

$errorMessage = "";
$successMessage = "";
$completed = false;

$email = $_SESSION['otp_verified_email'] ?? ($_GET['email'] ?? '');

if (empty($email)) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['pass'] ?? '';
    $repass = $_POST['repass'] ?? '';

    if (strlen($pass) < 6) {
        $errorMessage = "Password must be at least 6 characters long.";
    } elseif ($pass !== $repass) {
        $errorMessage = "Passwords do not match. Please re-enter.";
    } else {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);

        if ($stmt->execute()) {
            $completed = true;
            $successMessage = "Your password has been successfully updated!";
            unset($_SESSION['otp_verified_email'], $_SESSION['reset_otp'], $_SESSION['reset_email']);
        } else {
            $errorMessage = "Database update error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0; min-height: 75vh; display:flex; align-items:center;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 440px; margin: 0 auto; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--eco-bg); color: var(--eco); display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px;">
                <i class='bx bxs-lock-open'></i>
            </div>

            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                Set New Password
            </h2>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 20px;">
                Choose a strong new password for <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <?php if ($errorMessage): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px; border-radius:var(--radius-md); margin-bottom:18px; font-size:14px;">
                    ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:14px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:700;">
                    ✅ <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <a href="login.php" class="fr-btn fr-btn-primary fr-btn-block">Sign In with New Password →</a>
            <?php else: ?>
                <form method="POST">
                    <div class="fr-form-group" style="text-align:left;">
                        <label class="fr-label">New Password</label>
                        <input type="password" name="pass" class="fr-input" placeholder="Minimum 6 characters" required autocomplete="new-password">
                    </div>
                    <div class="fr-form-group" style="text-align:left;">
                        <label class="fr-label">Confirm New Password</label>
                        <input type="password" name="repass" class="fr-input" placeholder="Re-enter password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                        Update Password <i class='bx bx-check-shield'></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
