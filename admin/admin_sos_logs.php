<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check admin authorization
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Fetch all emergency SOS logs from notifications table
$sosLogs = $conn->query("SELECT n.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                        FROM notifications n 
                        JOIN users u ON n.user_id = u.id 
                        WHERE n.title LIKE '%Emergency%' OR n.title LIKE '%SOS%' OR n.message LIKE '%SOS%' 
                        ORDER BY n.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency SOS Audit Logs — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-alarm-exclamation' style="color:var(--danger);"></i> Emergency SOS Audit Trail
                </h1>
                <p style="font-size:14px; color:var(--text-muted);">Real-time dispatch audit logs with GPS coordinates and timestamps.</p>
            </div>
            <a href="admin_dashboard.php" class="fr-btn fr-btn-ghost fr-btn-sm"><i class='bx bx-left-arrow-alt'></i> Operations Console</a>
        </div>

        <div class="fr-card" style="border-color:var(--danger-border);">
            <?php if ($sosLogs && $sosLogs->num_rows > 0): ?>
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <?php while ($log = $sosLogs->fetch_assoc()): ?>
                        <div style="background:var(--bg-input); border:1px solid var(--danger-border); border-radius:var(--radius-md); padding:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <span class="fr-badge fr-badge-danger" style="margin-bottom:6px;"><i class='bx bxs-error'></i> <?php echo htmlspecialchars($log['title']); ?></span>
                                <h4 style="font-size:15.5px; font-weight:700; color:var(--text-main); margin-bottom:4px;">
                                    Rider: <?php echo htmlspecialchars($log['user_name']); ?> (📞 <?php echo htmlspecialchars($log['user_phone']); ?>)
                                </h4>
                                <p style="font-size:13.5px; color:var(--text-muted); line-height:1.45;"><?php echo htmlspecialchars($log['message']); ?></p>
                                <span style="font-size:11.5px; color:var(--text-muted); margin-top:6px; display:block;">
                                    📅 Timestamp: <?php echo $log['created_at']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px;">
                    <i class='bx bxs-shield-check' style="font-size:48px; color:var(--eco); margin-bottom:10px; display:block;"></i>
                    <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:4px;">Zero Active SOS Incidents</h3>
                    <p style="font-size:14px; color:var(--text-muted);">All campus commute routes are safe and running normally.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
