<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;

if ($ride_id <= 0) {
    header("Location: myrides.php");
    exit();
}

$rideStmt = $conn->prepare("SELECT r.*, u.name as driver_name, u.email as driver_email FROM rides r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$rideStmt->bind_param("i", $ride_id);
$rideStmt->execute();
$ride = $rideStmt->get_result()->fetch_assoc();

if (!$ride) {
    echo "Ride not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg_text = trim($_POST['message']);
    $receiver_id = ($user_id == $ride['user_id']) ? 0 : $ride['user_id'];

    if (!empty($msg_text)) {
        $msgStmt = $conn->prepare("INSERT INTO messages (ride_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $msgStmt->bind_param("iiis", $ride_id, $user_id, $receiver_id, $msg_text);
        $msgStmt->execute();
    }
    header("Location: chat.php?ride_id=" . $ride_id);
    exit();
}

$messagesStmt = $conn->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.ride_id = ? ORDER BY m.sent_at ASC");
$messagesStmt->bind_param("i", $ride_id);
$messagesStmt->execute();
$messagesRes = $messagesStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Chat - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 24px; font-weight: 700; color: #38bdf8; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .logo span { color: #22c55e; }
        .nav-links { display: flex; gap: 20px; list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; font-size: 16px; font-weight: 500; transition: 0.3s; }
        .nav-links a:hover { color: #38bdf8; }

        .chat-container {
            max-width: 700px;
            margin: 30px auto;
            width: 90%;
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            height: 70vh;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            overflow: hidden;
        }
        .chat-header {
            background: #1e293b;
            padding: 15px 25px;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .messages-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .msg {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.4;
        }
        .msg-sent {
            align-self: flex-end;
            background: #0284c7;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .msg-received {
            align-self: flex-start;
            background: #334155;
            color: #f8fafc;
            border-bottom-left-radius: 4px;
        }
        .msg-meta { font-size: 11px; opacity: 0.7; margin-top: 4px; text-align: right; }
        .chat-input-form {
            display: flex;
            padding: 15px;
            background: #1e293b;
            border-top: 1px solid #334155;
            gap: 10px;
        }
        .chat-input-form input {
            flex: 1;
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 15px;
            outline: none;
        }
        .btn-send {
            background: #0284c7;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo"><i class='bx bxs-navigation'></i> Flexi<span>Ride</span></a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="find_ride.php">Find Ride</a></li>
        <li><a href="post_ride.php">Post Ride</a></li>
        <li><a href="myrides.php">My Rides</a></li>
        <li><a href="my_booked_rides.php">Booked Trips</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<div class="chat-container">
    <div class="chat-header">
        <div>
            <h3 style="font-size:18px;"><?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></h3>
            <span style="font-size:13px; color:#94a3b8;">Driver: <?php echo htmlspecialchars($ride['driver_name']); ?></span>
        </div>
        <a href="my_booked_rides.php" style="color:#38bdf8; text-decoration:none; font-size:14px;"><i class='bx bx-left-arrow-alt'></i> Back to Booked Trips</a>
    </div>

    <div class="messages-box" id="msgBox">
        <?php if ($messagesRes->num_rows == 0): ?>
            <p style="text-align:center; color:#94a3b8; margin-top:40px;">No messages yet. Send a message to coordinate pickup!</p>
        <?php else: ?>
            <?php while ($m = $messagesRes->fetch_assoc()): ?>
                <div class="msg <?php echo ($m['sender_id'] == $user_id) ? 'msg-sent' : 'msg-received'; ?>">
                    <div style="font-size:12px; font-weight:600; margin-bottom:2px;"><?php echo htmlspecialchars($m['sender_name']); ?></div>
                    <?php echo htmlspecialchars($m['message']); ?>
                    <div class="msg-meta"><?php echo date('H:i', strtotime($m['sent_at'])); ?></div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <form class="chat-input-form" method="POST">
        <input type="text" name="message" placeholder="Type a message..." required autocomplete="off">
        <button type="submit" class="btn-send"><i class='bx bxs-send'></i> Send</button>
    </form>
</div>

<script>
    const box = document.getElementById('msgBox');
    box.scrollTop = box.scrollHeight;
</script>
</body>
</html>
