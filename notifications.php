<?php
include_once __DIR__ . '/includes/db.php';
session_start();

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

// Mark all as read when visited
$markStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$markStmt->bind_param("i", $user_id);
$markStmt->execute();

$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Center - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { font-size: 26px; color: var(--text-color); }

        .btn-clear {
            background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color);
            padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.3s;
        }
        .btn-clear:hover { background: var(--danger-color); color: white; }

        .notif-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex; gap: 15px; align-items: flex-start;
            transition: all 0.3s;
        }
        .notif-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(56, 189, 248, 0.2); color: var(--primary-color);
            display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;
        }
        .notif-content h4 { font-size: 16px; margin-bottom: 4px; color: var(--text-color); }
        .notif-content p { font-size: 14px; color: var(--text-muted); line-height: 1.4; }
        .notif-time { font-size: 12px; color: var(--text-muted); margin-top: 6px; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>🔔 Live Notification Center</h2>
        <?php if ($result->num_rows > 0): ?>
            <form method="POST">
                <input type="hidden" name="clear_all" value="1">
                <button type="submit" class="btn-clear"><i class='bx bx-trash'></i> Clear All</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($notif = $result->fetch_assoc()): ?>
            <div class="notif-card">
                <div class="notif-icon"><i class='bx bxs-bell'></i></div>
                <div class="notif-content">
                    <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                    <p><?php echo htmlspecialchars($notif['message']); ?></p>
                    <div class="notif-time">⏰ <?php echo $notif['created_at']; ?></div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding:40px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px;">
            <i class='bx bx-bell-off' style="font-size:48px; color:var(--text-muted); margin-bottom:10px;"></i>
            <h3>No notifications yet</h3>
            <p style="color:var(--text-muted); margin-top:6px;">You will receive alerts here when passengers book your rides or drivers update trip statuses.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
