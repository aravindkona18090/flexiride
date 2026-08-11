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
    <title>Emergency SOS Control Center - Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h2 { font-size: 26px; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        .btn-back { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--card-border); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; }

        .card { background: var(--card-bg); border: 1px solid var(--danger-color); border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(239,68,68,0.2); }

        .sos-item {
            background: var(--input-bg);
            border: 1px solid var(--danger-color);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge-sos { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>🚨 Emergency SOS Safety Control Center</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href='../index.php' class="btn-back"><i class='bx bx-home-alt'></i> 🏠 Home</a>
            <a href="admin_dashboard.php" class="btn-back"><i class='bx bx-left-arrow-alt'></i> Admin Dashboard</a>
        </div>
    </div>

    <div class="card">
        <h3 style="font-size:18px; margin-bottom:20px; color:var(--danger-color); display:flex; align-items:center; gap:8px;">
            <i class='bx bxs-alarm-exclamation'></i> Real-Time Emergency SOS Logs
        </h3>

        <?php if ($sosLogs && $sosLogs->num_rows > 0): ?>
            <?php while ($log = $sosLogs->fetch_assoc()): ?>
                <div class="sos-item">
                    <div>
                        <span class="badge-sos"><i class='bx bxs-error'></i> <?php echo htmlspecialchars($log['title']); ?></span>
                        <h4 style="margin:10px 0 4px; font-size:16px;">
                            Rider: <?php echo htmlspecialchars($log['user_name']); ?> (📞 <?php echo htmlspecialchars($log['user_phone']); ?>)
                        </h4>
                        <p style="font-size:14px; color:var(--text-muted);"><?php echo htmlspecialchars($log['message']); ?></p>
                        <span style="font-size:12px; color:var(--text-muted); margin-top:6px; display:block;">
                            📅 Timestamp: <?php echo $log['created_at']; ?>
                        </span>
                    </div>
                    <div>
                        <a href="../danger.php" style="background:#ef4444; color:white; padding:8px 16px; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                            <i class='bx bxs-map-pin'></i> Inspect Location
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:30px; color:var(--text-muted);">
                <i class='bx bxs-shield-check' style="font-size:45px; color:var(--success-color); margin-bottom:10px; display:block;"></i>
                <p style="font-size:15px; font-weight:600; color:var(--success-color);">All Clear! No emergency SOS incidents logged.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
