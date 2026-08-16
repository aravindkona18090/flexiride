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

$rideStmt = $conn->prepare("SELECT r.*, u.name as driver_name, u.email as driver_email, u.profile_photo as driver_photo FROM rides r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
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
            $bStmt = $conn->prepare("SELECT user_id FROM bookings WHERE ride_id = ? AND trip_status != 'Cancelled' ORDER BY id DESC LIMIT 1");
            $bStmt->bind_param("i", $ride_id);
            $bStmt->execute();
            $bRes = $bStmt->get_result()->fetch_assoc();
            $receiver_id = $bRes ? (int)$bRes['user_id'] : (int)$ride['user_id'];
        } else {
            $receiver_id = (int)$ride['user_id'];
        }
    }

    if (!empty($msg_text)) {
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

$isOwner = ($user_id == $ride['user_id']);
$backUrl = $isOwner ? 'myrides.php' : 'my_booked_rides.php';
$backText = $isOwner ? 'Back to Offered Rides' : 'Back to Booked Trips';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Trip Chat — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .chat-app-box {
            max-width: 820px;
            margin: 20px auto;
            border-radius: var(--radius-xl);
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            height: 75vh;
            min-height: 520px;
        }

        .chat-app-header {
            padding: 16px 22px;
            background: var(--bg-surface-elevated);
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-msg-stream {
            flex: 1;
            overflow-y: auto;
            padding: 22px;
            background: var(--bg-input);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .msg-bubble {
            max-width: 72%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 14.5px;
            line-height: 1.45;
            position: relative;
            word-wrap: break-word;
        }

        .msg-bubble.sent {
            align-self: flex-end;
            background: var(--primary-gradient);
            color: #ffffff;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2);
        }

        .msg-bubble.received {
            align-self: flex-start;
            background: var(--bg-surface);
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
            border-bottom-left-radius: 4px;
            box-shadow: var(--shadow-sm);
        }

        .chat-app-input {
            padding: 16px 20px;
            background: var(--bg-surface);
            border-top: 1px solid var(--border-subtle);
            display: flex;
            gap: 12px;
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 20px 0;">
    <div class="fr-container">
        <div class="chat-app-box">
            <!-- Header -->
            <div class="chat-app-header">
                <div>
                    <div style="font-size:16px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                        <span>📍 <?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></span>
                    </div>
                    <div style="font-size:12.5px; color:var(--text-muted); display:flex; align-items:center; gap:10px; margin-top:2px;">
                        <span>Driver: <strong><?php echo htmlspecialchars($ride['driver_name']); ?></strong></span>
                        <span class="fr-badge fr-badge-eco" style="font-size:11px; padding:2px 8px;"><i class='bx bxs-circle'></i> Live Channel</span>
                    </div>
                </div>

                <a href="<?php echo $backUrl; ?>" class="fr-btn fr-btn-ghost fr-btn-sm">
                    <i class='bx bx-left-arrow-alt'></i> <?php echo $backText; ?>
                </a>
            </div>

            <!-- Messages Stream -->
            <div class="chat-msg-stream" id="msgBox">
                <?php if ($messagesRes->num_rows == 0): ?>
                    <div id="emptyTxt" style="text-align:center; padding:50px 20px; color:var(--text-muted);">
                        <i class='bx bx-chat' style="font-size:36px; color:var(--primary); margin-bottom:8px;"></i>
                        <p style="font-size:14px;">No messages yet. Send a note to coordinate pickup spot and timings!</p>
                    </div>
                <?php else: ?>
                    <?php while ($m = $messagesRes->fetch_assoc()): ?>
                        <div class="msg-bubble <?php echo ($m['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                            <div style="font-size:11.5px; font-weight:700; opacity:0.85; margin-bottom:3px;">
                                <?php echo htmlspecialchars($m['sender_name']); ?>
                            </div>
                            <?php echo htmlspecialchars($m['message']); ?>
                            <div style="font-size:10.5px; opacity:0.7; text-align:right; margin-top:4px;">
                                <?php echo date('H:i', strtotime($m['sent_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <!-- Input Bar -->
            <form class="chat-app-input" id="chatForm">
                <input type="text" id="msgInput" name="message" class="fr-input" placeholder="Type a message to driver or passengers..." required autocomplete="off">
                <button type="submit" id="chatSendBtn" class="fr-btn fr-btn-primary" style="padding:0 24px;">
                    <i class='bx bxs-send'></i> Send
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    const msgBox = document.getElementById('msgBox');
    const chatForm = document.getElementById('chatForm');
    const msgInput = document.getElementById('msgInput');
    const rideId = <?php echo $ride_id; ?>;
    const currentUserId = <?php echo $user_id; ?>;

    function scrollToBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }
    scrollToBottom();

    // AJAX Form Submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = msgInput.value.trim();
        if (!text) return;

        msgInput.value = '';
        fetch(`chat.php?ride_id=${rideId}&ajax=1`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `message=${encodeURIComponent(text)}`
        }).then(r => r.json()).then(data => {
            fetchMessages();
        });
    });

    // Background Poll
    function fetchMessages() {
        fetch(`chat.php?ride_id=${rideId}&action=fetch_messages`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.messages) {
                    if (data.messages.length > 0) {
                        const emptyTxt = document.getElementById('emptyTxt');
                        if (emptyTxt) emptyTxt.remove();
                    }
                    msgBox.innerHTML = '';
                    data.messages.forEach(m => {
                        const isMe = (m.sender_id == currentUserId);
                        const bubble = document.createElement('div');
                        bubble.className = `msg-bubble ${isMe ? 'sent' : 'received'}`;
                        bubble.innerHTML = `
                            <div style="font-size:11.5px; font-weight:700; opacity:0.85; margin-bottom:3px;">${m.sender_name}</div>
                            ${m.message}
                            <div style="font-size:10.5px; opacity:0.7; text-align:right; margin-top:4px;">${m.sent_time}</div>
                        `;
                        msgBox.appendChild(bubble);
                    });
                    scrollToBottom();
                }
            }).catch(e => console.error(e));
    }

    setInterval(fetchMessages, 3500);
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
