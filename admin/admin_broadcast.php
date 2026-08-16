<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$successMsg = "";
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['broadcast_title'])) {
    $title   = trim($_POST['broadcast_title']);
    $message = trim($_POST['broadcast_message']);

    if (!empty($title) && !empty($message)) {
        $allUsers = $conn->query("SELECT id FROM users");
        $count = 0;
        if ($allUsers) {
            while ($u = $allUsers->fetch_assoc()) {
                $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $nStmt->bind_param("iss", $u['id'], $title, $message);
                $nStmt->execute();
                $count++;
            }
        }
        $successMsg = "Broadcast message successfully dispatched to $count active commuters! 📢";
    } else {
        $errorMsg = "Please provide both an announcement title and message content.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Notification Broadcast — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container-sm">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:24px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-megaphone' style="color:var(--primary);"></i> Global Broadcast Dispatcher
                </h1>
                <p style="font-size:14px; color:var(--text-muted);">Send real-time alerts to the notification hubs of all registered commuters.</p>
            </div>
            <a href="admin_dashboard.php" class="fr-btn fr-btn-ghost fr-btn-sm"><i class='bx bx-left-arrow-alt'></i> Operations Console</a>
        </div>

        <?php if ($successMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ✅ <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="fr-card">
            <form method="POST">
                <div class="fr-form-group">
                    <label class="fr-label">Broadcast Headline</label>
                    <input type="text" name="broadcast_title" class="fr-input" placeholder="e.g. 🌟 Weekend Tech Fest Commute Corridor or Weather Alert" required>
                </div>

                <div class="fr-form-group">
                    <label class="fr-label">Announcement Content</label>
                    <textarea name="broadcast_message" class="fr-textarea" rows="5" placeholder="Write message to be delivered to all user inboxes..." required></textarea>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Dispatch Global Alert <i class='bx bxs-megaphone'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
