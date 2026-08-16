<?php
session_start();
include_once __DIR__ . '/includes/db.php';

$name = "";
$email = "";
$successMsg = "";
$errorMsg = "";

// Auto-fill logged in user info
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result()->fetch_assoc();
    if ($u_res) {
        $name = $u_res['name'];
        $email = $u_res['email'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $feedback = trim($_POST["Feedback"]);

    if (!empty($name) && !empty($email) && !empty($feedback)) {
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, feedback) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $feedback);
        if ($stmt->execute()) {
            $successMsg = "Thank you for your valuable feedback! Your thoughts help us make FlexiRide better for everyone. ⭐";
        } else {
            $errorMsg = "Database error: " . $conn->error;
        }
    } else {
        $errorMsg = "Please complete all fields before submitting.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Community Suggestions — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 540px; margin: 0 auto;">
            <div style="text-align:center; margin-bottom: 24px;">
                <span class="fr-badge fr-badge-primary" style="margin-bottom:8px;">Community Voice</span>
                <h1 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Share Your Feedback
                </h1>
                <p style="font-size: 14px; color: var(--text-muted);">
                    Help us refine commute matching, driver safety, and route coverage.
                </p>
            </div>

            <?php if ($successMsg): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:14px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                    ✅ <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="fr-grid-2">
                    <div class="fr-form-group">
                        <label class="fr-label">Your Name</label>
                        <input type="text" name="name" class="fr-input" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">Email Address</label>
                        <input type="email" name="email" class="fr-input" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>

                <div class="fr-form-group">
                    <label class="fr-label">Your Suggestions & Thoughts</label>
                    <textarea name="Feedback" class="fr-textarea" rows="4" placeholder="Tell us what you love or how we can improve your campus commutes..." required></textarea>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Submit Feedback <i class='bx bx-send'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
