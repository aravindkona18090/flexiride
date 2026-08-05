<?php
session_start();
include_once __DIR__ . '/includes/db.php';

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

// Handle AJAX Live Fetch Messages Endpoint
if (isset($_GET['action']) && $_GET['action'] === 'fetch_messages') {
    header('Content-Type: application/json');
    $stmt = $conn->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.ride_id = ? ORDER BY m.sent_at ASC");
    $stmt->bind_param("i", $ride_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $msgs = [];
    while ($row = $res->fetch_assoc()) {
        $msgs[] = [
            'id'          => $row['id'],
            'sender_id'   => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'message'     => $row['message'],
            'sent_time'   => date('H:i', strtotime($row['sent_at'])),
            'is_me'       => ($row['sender_id'] == $user_id)
        ];
    }
    echo json_encode(['status' => 'success', 'messages' => $msgs, 'current_user_id' => $user_id]);
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

// Handle Send Message (Normal POST or AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg_text = trim($_POST['message']);
    $receiver_id = isset($_REQUEST['receiver_id']) ? (int)$_REQUEST['receiver_id'] : 0;

    if ($receiver_id <= 0) {
        if ($user_id == $ride['user_id']) {
            $bStmt = $conn->prepare("SELECT user_id FROM bookings WHERE ride_id = ? AND trip_status != 'Cancelled' LIMIT 1");
            $bStmt->bind_param("i", $ride_id);
            $bStmt->execute();
            $bRes = $bStmt->get_result()->fetch_assoc();
            $receiver_id = $bRes ? (int)$bRes['user_id'] : 0;
        } else {
            $receiver_id = (int)$ride['user_id'];
        }
    }

    if (!empty($msg_text) && $receiver_id > 0) {
        $msgStmt = $conn->prepare("INSERT INTO messages (ride_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $msgStmt->bind_param("iiis", $ride_id, $user_id, $receiver_id, $msg_text);
        $msgStmt->execute();
    }

    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit();
    }

    header("Location: chat.php?ride_id=" . $ride_id . ($receiver_id > 0 ? "&receiver_id=" . $receiver_id : ""));
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
    <title>Live Trip Chat - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }
        
        .chat-container {
            max-width: 750px;
            margin: 30px auto;
            width: 100%;
            padding: 0 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background: #1e293b;
            padding: 20px 25px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            border: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .messages-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-top: none;
            border-bottom: none;
            flex: 1;
            min-height: 380px;
            max-height: 500px;
            overflow-y: auto;
            padding: 25px;
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
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        .msg-sent {
            align-self: flex-end;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .msg-received {
            align-self: flex-start;
            background: #1e293b;
            color: #f8fafc;
            border: 1px solid #334155;
            border-bottom-left-radius: 4px;
        }
        .msg-meta {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
            text-align: right;
        }
        .chat-input-form {
            display: flex;
            gap: 12px;
            background: #1e293b;
            padding: 20px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            border: 1px solid #334155;
        }
        .chat-input-form input {
            flex: 1;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 15px;
            outline: none;
        }
        .btn-send {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2, 132, 199, 0.4); }

        .live-status { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #4ade80; }
        .live-dot { width: 8px; height: 8px; background: #4ade80; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="chat-container">
    <div class="chat-header">
        <div>
            <h3 style="font-size:18px; color:var(--text-color);"><?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></h3>
            <span style="font-size:13px; color:#94a3b8;">Driver: <strong><?php echo htmlspecialchars($ride['driver_name']); ?></strong></span>
            <div class="live-status" style="margin-left:12px;"><div class="live-dot"></div> Live Polling Active</div>
        </div>
        <a href="my_booked_rides.php" style="color:#38bdf8; text-decoration:none; font-size:14px; font-weight:600;"><i class='bx bx-left-arrow-alt'></i> Back to Trips</a>
    </div>

    <div class="messages-box" id="msgBox">
        <?php if ($messagesRes->num_rows == 0): ?>
            <p id="emptyTxt" style="text-align:center; color:#94a3b8; margin-top:40px;">No messages yet. Send a message to coordinate pickup!</p>
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

    <form class="chat-input-form" id="chatForm">
        <input type="text" id="msgInput" name="message" placeholder="Type a message..." required autocomplete="off">
        <button type="submit" class="btn-send"><i class='bx bxs-send'></i> Send</button>
    </form>
</div>

<script>
    const msgBox = document.getElementById('msgBox');
    const chatForm = document.getElementById('chatForm');
    const msgInput = document.getElementById('msgInput');
    const rideId = <?php echo $ride_id; ?>;
    let lastMsgCount = 0;

    function scrollToBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }
    scrollToBottom();

    // Auto-Polling for New Messages every 2 seconds
    async function fetchMessages() {
        try {
            const res = await fetch(`chat.php?action=fetch_messages&ride_id=${rideId}`);
            const data = await res.json();
            if (data.status === 'success') {
                if (data.messages.length !== lastMsgCount) {
                    lastMsgCount = data.messages.length;
                    renderMessages(data.messages);
                }
            }
        } catch (e) {
            console.error('Live chat poll error:', e);
        }
    }

    function renderMessages(messages) {
        if (messages.length === 0) return;
        msgBox.innerHTML = '';
        messages.forEach(m => {
            const div = document.createElement('div');
            div.className = `msg ${m.is_me ? 'msg-sent' : 'msg-received'}`;
            div.innerHTML = `
                <div style="font-size:12px; font-weight:600; margin-bottom:2px;">${escapeHtml(m.sender_name)}</div>
                ${escapeHtml(m.message)}
                <div class="msg-meta">${m.sent_time}</div>
            `;
            msgBox.appendChild(div);
        });
        scrollToBottom();
    }

    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Instant Instant Send without Page Reload
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = msgInput.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('message', text);
        formData.append('ajax', '1');

        msgInput.value = '';
        try {
            await fetch(`chat.php?ride_id=${rideId}`, {
                method: 'POST',
                body: formData
            });
            fetchMessages();
        } catch (err) {
            console.error('Error sending message:', err);
        }
    });

    // Poll every 2 seconds
    setInterval(fetchMessages, 2000);
</script>
</body>
</html>
