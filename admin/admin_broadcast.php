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
        // Dispatch to all users in notifications table
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
    <title>Broadcast System Announcement - Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 750px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h2 { font-size: 26px; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        .btn-back { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--card-border); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; }

        .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--text-color); margin-bottom: 8px; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); outline: none; font-size: 15px;
        }

        .btn-broadcast {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient); color: white; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
        }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>📢 Send Global Broadcast Alert</h2>
        <div>
            <a href="admin_dashboard.php" class="btn-back"><i class='bx bx-left-arrow-alt'></i> Back to Dashboard</a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3 style="font-size:18px; margin-bottom:20px; color:var(--primary-color);">📣 Send Announcement Alert to All Users</h3>

        <form method="POST">
            <div class="form-group">
                <label>Announcement Title *</label>
                <input type="text" name="broadcast_title" placeholder="e.g. 🌟 New Safety Update or Weekend Festival Offer" required>
            </div>

            <div class="form-group">
                <label>Message Content *</label>
                <textarea name="broadcast_message" rows="5" placeholder="Write your announcement details here..." required></textarea>
            </div>

            <button type="submit" class="btn-broadcast"><i class='bx bxs-megaphone'></i> Broadcast to All Users →</button>
        </form>
    </div>
</div>
</body>
</html>
