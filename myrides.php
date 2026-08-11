<?php
include_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

// Handle Cancel Ride by Driver (Releases Seats & Notifies Passengers)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_ride_id'])) {
    $ride_id = (int)$_POST['cancel_ride_id'];
    
    // Update ride status to cancelled
    $cStmt = $conn->prepare("UPDATE rides SET trip_status = 'cancelled' WHERE id = ? AND user_id = ?");
    $cStmt->bind_param("ii", $ride_id, $user_id);
    if ($cStmt->execute()) {
        // Update all bookings for this ride to cancelled
        $bUp = $conn->prepare("UPDATE bookings SET trip_status = 'cancelled' WHERE ride_id = ?");
        $bUp->bind_param("i", $ride_id);
        $bUp->execute();

        // Notify booked passengers
        $passengers = $conn->query("SELECT user_id FROM bookings WHERE ride_id = $ride_id");
        while ($p = $passengers->fetch_assoc()) {
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Ride Cancelled by Driver', 'The driver has cancelled this ride. Any payment will be refunded.')");
            $nStmt->bind_param("i", $p['user_id']);
            $nStmt->execute();
        }

        $successMsg = "Ride has been cancelled and booked passengers have been notified.";
    }
}

// Fetch unread notifications for Driver
$notifStmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$notifications = $notifStmt->get_result();

// Fetch rides offered by this driver
$stmt = $conn->prepare("SELECT r.*, 
    (SELECT COUNT(*) FROM bookings b WHERE b.ride_id = r.id AND b.trip_status != 'cancelled') as booked_count 
    FROM rides r WHERE r.user_id = ? ORDER BY r.ride_date DESC, r.ride_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_rides = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Offered Rides - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 950px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { font-size: 28px; color: var(--text-color); }
        .btn-post { background: var(--primary-gradient); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; }

        .alerts-widget-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .widget-header h3 { font-size: 16px; color: var(--primary-color); display: flex; align-items: center; gap: 8px; }
        .notif-item { font-size: 14px; color: var(--text-muted); padding: 8px 0; border-bottom: 1px solid var(--card-border); }
        .notif-item:last-child { border-bottom: none; }

        .ride-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex; justify-content: space-between; align-items: center;
        }

        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .status-active { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }
        .status-cancelled { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); }

        .btn-wa-share { background: #25D366; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; }
        .btn-chat { background: var(--primary-color); color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-cancel { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>🏍️ My Offered Rides & Trips</h2>
        <a href="post_ride.php" class="btn-post">➕ Offer New Ride</a>
    </div>

    <!-- Instant Search & Status Filter Bar -->
    <div style="background:var(--card-bg); border:1px solid var(--card-border); padding:15px 20px; border-radius:16px; margin-bottom:25px; display:flex; gap:15px; align-items:center; flex-wrap:wrap; box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
        <div style="flex:1; position:relative; min-width:220px;">
            <i class='bx bx-search' style="position:absolute; left:14px; top:12px; color:var(--text-muted); font-size:18px;"></i>
            <input type="text" id="tripSearch" placeholder="🔍 Instant search by city, location or date..." onkeyup="filterTrips()" style="width:100%; padding:10px 14px 10px 40px; border-radius:10px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-color); outline:none; font-size:14px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="button" class="filter-btn active" onclick="setFilter('all', this)" style="padding:8px 14px; border-radius:10px; border:1px solid var(--primary-color); background:var(--primary-color); color:white; font-size:13px; font-weight:600; cursor:pointer;">All Trips</button>
            <button type="button" class="filter-btn" onclick="setFilter('active', this)" style="padding:8px 14px; border-radius:10px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer;">Active</button>
            <button type="button" class="filter-btn" onclick="setFilter('cancelled', this)" style="padding:8px 14px; border-radius:10px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-muted); font-size:13px; font-weight:600; cursor:pointer;">Cancelled</button>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <!-- Live Activity & Alerts Widget -->
    <?php if ($notifications->num_rows > 0): ?>
        <div class="alerts-widget-card">
            <div class="widget-header">
                <h3><i class='bx bxs-bell-ring' style="color:var(--primary-color);"></i> 🔔 Live Driver Activity & Booking Alerts</h3>
                <a href="notifications.php" style="color:var(--primary-color); font-size:13px; text-decoration:none; font-weight:600;">View All Alerts →</a>
            </div>
            <?php while ($notif = $notifications->fetch_assoc()): ?>
                <div class="notif-item">
                    <strong><?php echo htmlspecialchars($notif['title']); ?>:</strong> <?php echo htmlspecialchars($notif['message']); ?>
                    <span style="font-size:12px; color:var(--text-muted); float:right;"><?php echo $notif['created_at']; ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <?php if ($my_rides->num_rows > 0): ?>
        <?php while ($ride = $my_rides->fetch_assoc()): ?>
            <?php 
                $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($ride['origin']);
                $waText = "🏍️ *FlexiRide Trip Offer*\n" .
                          "📍 Pickup: " . $ride['origin'] . "\n" .
                          "🏁 Drop: " . $ride['destination'] . "\n" .
                          "📅 Date & Time: " . $ride['ride_date'] . " at " . $ride['ride_time'] . "\n" .
                          "🚘 Vehicle: " . ($ride['vehicle_model'] ?: $ride['vehicle_type']) . "\n" .
                          "💺 Available Seats: " . ($ride['seats_available'] - $ride['booked_count']) . "\n" .
                          "📍 Pickup Location Map: " . $mapsUrl;
                $waUrl = "https://api.whatsapp.com/send?text=" . urlencode($waText);
            ?>
            <div class="ride-card">
                <div>
                    <div style="margin-bottom:8px;">
                        <?php if (($ride['trip_status'] ?? 'active') === 'cancelled'): ?>
                            <span class="status-badge status-cancelled"><i class='bx bxs-x-circle'></i> Status: Cancelled</span>
                        <?php else: ?>
                            <span class="status-badge status-active"><i class='bx bxs-check-circle'></i> Status: Active</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size:20px; margin-bottom:6px;"><?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></h3>
                    <p style="font-size:14px; color:var(--text-muted); margin-bottom:6px;">
                        🚘 Vehicle: <strong><?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_type']); ?></strong>
                        | Fare: <strong>₹<?php echo $ride['price']; ?>/seat</strong>
                    </p>
                    <p style="font-size:13px; color:var(--text-muted);">
                        📅 <?php echo $ride['ride_date']; ?> at <?php echo $ride['ride_time']; ?> | 
                        💺 Bookings: <strong><?php echo $ride['booked_count']; ?> / <?php echo $ride['seats_available']; ?> seats booked</strong>
                    </p>
                </div>

                <div style="text-align:right;">
                    <div style="font-size:24px; font-weight:700; color:var(--primary-color); margin-bottom:10px;">₹<?php echo $ride['price']; ?></div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end;">
                        <!-- WhatsApp Share Trip Button -->
                        <a href="<?php echo $waUrl; ?>" target="_blank" class="btn-wa-share" title="Share Trip on WhatsApp">
                            <i class='bx bxl-whatsapp'></i> Share WhatsApp
                        </a>

                        <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="btn-chat"><i class='bx bx-message-rounded-dots'></i> Passenger Chat</a>

                        <?php if (($ride['trip_status'] ?? 'active') !== 'cancelled'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this offered ride? All passengers will be notified.');">
                                <input type="hidden" name="cancel_ride_id" value="<?php echo $ride['id']; ?>">
                                <button type="submit" class="btn-cancel"><i class='bx bx-x-circle'></i> Cancel Ride</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding:40px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px;">
            <h3>You haven't offered any rides yet!</h3>
            <p style="color:var(--text-muted); margin:10px 0 20px;">Share your daily commute with fellow students & commuters to split fuel costs.</p>
            <a href="post_ride.php" class="btn-post">Offer Your First Ride →</a>
        </div>
    <?php endif; ?>
</div>

<script>
    let currentFilter = 'all';
    function setFilter(status, btn) {
        currentFilter = status;
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.style.background = 'var(--input-bg)';
            b.style.color = 'var(--text-muted)';
            b.style.borderColor = 'var(--input-border)';
        });
        btn.style.background = 'var(--primary-color)';
        btn.style.color = 'white';
        btn.style.borderColor = 'var(--primary-color)';
        filterTrips();
    }

    function filterTrips() {
        const q = document.getElementById('tripSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.ride-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const matchesSearch = text.includes(q);
            const matchesStatus = (currentFilter === 'all') || text.includes('status: ' + currentFilter);
            card.style.display = (matchesSearch && matchesStatus) ? 'flex' : 'none';
        });
    }
</script>
</body>
</html>
