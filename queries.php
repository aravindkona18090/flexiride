<?php
session_start();
include_once __DIR__ . '/includes/db.php';

$name = $email = $query = "";
$successMessage = "";
$errorMessage = "";

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
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $query = isset($_POST['query']) ? htmlspecialchars(trim($_POST['query'])) : '';

    if (!empty($name) && !empty($email) && !empty($query)) {
        $stmt = $conn->prepare("INSERT INTO queries (name, email, query) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $query);

        if ($stmt->execute()) {
            $successMessage = "Thank you, $name! Your inquiry has been submitted to the FlexiRide support desk.";
        } else {
            $errorMessage = "An error occurred while submitting your query. Please try again.";
        }
    } else {
        $errorMessage = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Helpdesk & Inquiries — FlexiRide</title>
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
                <span class="fr-badge fr-badge-primary" style="margin-bottom:8px;">Campus Help Desk</span>
                <h1 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                    Support & Inquiries
                </h1>
                <p style="font-size: 14px; color: var(--text-muted);">
                    Need help with a trip, UPI refund, or account verification? Let us know.
                </p>
            </div>

            <?php if ($successMessage): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:14px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                    ✅ <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
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
                    <label class="fr-label">Describe Your Inquiry</label>
                    <textarea name="query" class="fr-textarea" rows="4" placeholder="How can our support team assist you today?" required></textarea>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Send Support Request <i class='bx bx-send'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
