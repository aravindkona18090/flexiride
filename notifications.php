<?php
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Clear All Notifications
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_all'])) {
    $cStmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $cStmt->bind_param("i", $user_id);
    $cStmt->execute();
}

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark all as read when visited
$markStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$markStmt->bind_param("i", $user_id);
$markStmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Center — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php 
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    include_once __DIR__ . '/includes/admin_navbar.php';
} else {
    include_once __DIR__ . '/includes/navbar.php';
}
?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container-sm">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:24px; font-weight:800; color:var(--text-main);">🔔 Notification Hub</h1>
                <p style="font-size:14px; color:var(--text-muted);">Real-time trip requests, departure OTPs, and system alerts.</p>
            </div>
            <?php if ($result->num_rows > 0): ?>
                <form method="POST">
                    <input type="hidden" name="clear_all" value="1">
                    <button type="submit" class="fr-btn fr-btn-danger fr-btn-sm"><i class='bx bx-trash'></i> Clear All</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php while ($notif = $result->fetch_assoc()): ?>
                    <div class="fr-card" style="padding:18px 22px; display:flex; gap:16px; align-items:flex-start;">
                        <div style="width:42px; height:42px; border-radius:12px; background:var(--primary-glow); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                            <i class='bx bxs-bell'></i>
                        </div>
                        <div style="flex:1;">
                            <h4 style="font-size:15.5px; font-weight:700; color:var(--text-main); margin-bottom:3px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                            <p style="font-size:13.5px; color:var(--text-muted); line-height:1.45; margin-bottom:6px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <span style="font-size:11.5px; color:var(--text-muted); display:inline-flex; align-items:center; gap:4px;">
                                <i class='bx bx-time-five'></i> <?php echo $notif['created_at']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="fr-card" style="text-align:center; padding:50px 20px;">
                <i class='bx bx-bell-off' style="font-size:44px; color:var(--text-muted); margin-bottom:12px;"></i>
                <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:6px;">No Notifications</h3>
                <p style="font-size:14px; color:var(--text-muted);">You're all caught up! Trip updates and messages will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
