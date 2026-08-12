<?php
include_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ride_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0);

if ($ride_id <= 0) {
    header("Location: myrides.php");
    exit();
}

// Fetch Full Ride Details with Driver Info
$stmt = $conn->prepare("SELECT r.*, u.name as driver_name, u.phone as driver_phone, u.profile_photo as driver_photo, u.is_verified as driver_verified 
    FROM rides r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?");
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$ride = $stmt->get_result()->fetch_assoc();

if (!$ride) {
    echo "Ride not found.";
    exit();
}

$isDriver = ($user_id == $ride['user_id']);

// Fetch Bookings Roster for this Ride
$bStmt = $conn->prepare("SELECT b.*, u.name as passenger_name, u.phone as passenger_phone, u.profile_photo as passenger_photo 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.ride_id = ? 
    ORDER BY b.id DESC");
$bStmt->bind_param("i", $ride_id);
$bStmt->execute();
$bookings = $bStmt->get_result();

$cleanOrig = cleanShortAddress($ride['origin']);
$cleanDest = cleanShortAddress($ride['destination']);

$mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($ride['origin']);
$waText = "🏍️ *FlexiRide Trip Offer*\n" .
          "📍 Pickup: " . $cleanOrig . "\n" .
          "🏁 Drop: " . $cleanDest . "\n" .
          "📅 Date & Time: " . $ride['ride_date'] . " at " . date('h:i A', strtotime($ride['ride_time'])) . "\n" .
          "🚘 Vehicle: " . ($ride['vehicle_model'] ?: $ride['vehicle_type']) . "\n" .
          "💰 Fare: ₹" . number_format($ride['price'], 2) . "/seat\n" .
          "📍 Pickup Location Map: " . $mapsUrl;
$waShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($waText);

$rideDateTime = strtotime($ride['ride_date'] . ' ' . $ride['ride_time']);
$isPassed = ($rideDateTime < time());
$rawStatus = strtolower($ride['trip_status'] ?? 'active');
if ($rawStatus === 'cancelled') {
    $computedStatus = 'cancelled';
} elseif ($isPassed || $rawStatus === 'completed') {
    $computedStatus = 'completed';
} else {
    $computedStatus = 'active';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Details - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            margin-bottom: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .status-active { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }
        .status-completed { background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid #64748b; }
        .status-cancelled { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); }

        .route-header h2 { font-size: 22px; margin-bottom: 12px; line-height: 1.4; color: var(--text-color); }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }

        .info-box {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 16px;
            padding: 16px;
        }

        .info-box label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
        .info-box div { font-size: 15px; font-weight: 600; color: var(--text-color); }

        .passenger-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            margin-bottom: 10px;
            background: var(--input-bg);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary { background: var(--primary-gradient); color: white; }
        .btn-wa { background: #25D366; color: white; }
        .btn-danger { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <a href="<?php echo $isDriver ? 'myrides.php' : 'my_booked_rides.php'; ?>" class="back-link">
        <i class='bx bx-left-arrow-alt'></i> Back to <?php echo $isDriver ? 'My Offered Rides' : 'My Booked Rides'; ?>
    </a>

    <div class="card">
        <div class="route-header">
            <?php if ($computedStatus === 'cancelled'): ?>
                <span class="status-pill status-cancelled"><i class='bx bxs-x-circle'></i> Status: Cancelled</span>
            <?php elseif ($computedStatus === 'completed'): ?>
                <span class="status-pill status-completed"><i class='bx bxs-time-five'></i> Status: Completed</span>
            <?php else: ?>
                <span class="status-pill status-active"><i class='bx bxs-check-circle'></i> Status: Active Ride</span>
            <?php endif; ?>

            <h2>📍 <?php echo htmlspecialchars($ride['origin']); ?></h2>
            <h2 style="color:var(--primary-color);">➔ 🏁 <?php echo htmlspecialchars($ride['destination']); ?></h2>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <label>📅 Schedule Date & Time</label>
                <div><?php echo $ride['ride_date']; ?> at <?php echo date('h:i A', strtotime($ride['ride_time'])); ?></div>
            </div>
            <div class="info-box">
                <label>🚘 Vehicle Info</label>
                <div><?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_type']); ?></div>
            </div>
            <div class="info-box">
                <label>💰 Fare per Seat</label>
                <div>₹<?php echo number_format($ride['price'], 2); ?></div>
            </div>
            <div class="info-box">
                <label>💺 Seats Capacity</label>
                <div><?php echo max(1, (int)$ride['seats_available']); ?> Seats Offered</div>
            </div>
            <div class="info-box">
                <label>🪖 Spare Helmet</label>
                <div><?php echo ($ride['helmet_provided'] ?? 1) ? '🪖 Yes (Provided)' : 'No (Bring Own)'; ?></div>
            </div>
            <div class="info-box">
                <label>👩 Gender Preference</label>
                <div><?php echo ($ride['gender_preference'] === 'female_only') ? '👩 Females Only' : 'Open to Anyone'; ?></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:20px;">
            <a href="<?php echo $mapsUrl; ?>" target="_blank" class="btn-action btn-primary">
                <i class='bx bxs-map-pin'></i> Open Pickup Map
            </a>

            <?php if ($computedStatus === 'active'): ?>
                <a href="<?php echo $waShareUrl; ?>" target="_blank" class="btn-action btn-wa">
                    <i class='bx bxl-whatsapp'></i> Share WhatsApp
                </a>
                <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="btn-action btn-primary" style="background:#3b82f6;">
                    <i class='bx bx-message-rounded-dots'></i> Passenger Chat
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booked Passengers Roster -->
    <div class="card">
        <h3 style="font-size:20px; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
            <i class='bx bxs-user-check' style="color:var(--primary-color);"></i> Booked Passengers Roster
        </h3>

        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($b = $bookings->fetch_assoc()): ?>
                <div class="passenger-item">
                    <div>
                        <strong style="font-size:16px; display:block; color:var(--text-color);">
                            👤 <?php echo htmlspecialchars($b['passenger_name']); ?>
                        </strong>
                        <span style="font-size:13px; color:var(--text-muted);">
                            📞 <?php echo htmlspecialchars($b['passenger_phone']); ?> | Seats: <strong><?php echo $b['seats_booked'] ?? 1; ?></strong>
                        </span>
                    </div>
                    <span style="font-size:13px; font-weight:700; color:var(--success-color);">
                        <?php echo htmlspecialchars($b['trip_status'] ?? 'Confirmed'); ?>
                    </span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px 0;">No passenger bookings yet for this ride.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
