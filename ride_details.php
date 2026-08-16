<?php
include_once __DIR__ . '/includes/db.php';

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
    <title>Trip Inspection — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container-sm">
        <a href="<?php echo $isDriver ? 'myrides.php' : 'my_booked_rides.php'; ?>" class="fr-btn fr-btn-ghost fr-btn-sm" style="margin-bottom:18px;">
            <i class='bx bx-left-arrow-alt'></i> Back to <?php echo $isDriver ? 'My Offered Rides' : 'My Booked Rides'; ?>
        </a>

        <!-- Main Ride Card -->
        <div class="fr-card" style="margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <?php if ($computedStatus === 'cancelled'): ?>
                    <span class="fr-badge fr-badge-danger"><i class='bx bxs-x-circle'></i> Status: Cancelled</span>
                <?php elseif ($computedStatus === 'completed'): ?>
                    <span class="fr-badge fr-badge-ghost"><i class='bx bxs-time-five'></i> Status: Completed</span>
                <?php else: ?>
                    <span class="fr-badge fr-badge-eco"><i class='bx bxs-check-circle'></i> Status: Active Ride</span>
                <?php endif; ?>

                <span class="fr-badge fr-badge-primary">
                    <?php echo (($ride['vehicle_category'] ?? 'bike') === 'bike') ? '🏍️ Two-Wheeler' : '🚗 Carpool'; ?>
                </span>
            </div>

            <!-- Wayfinder Route Line -->
            <div class="wayfinder-route" style="margin: 20px 0;">
                <div class="route-stop origin">
                    <div class="stop-beacon"></div>
                    <div class="stop-label">Pickup Location</div>
                    <div class="stop-name"><?php echo htmlspecialchars($ride['origin']); ?></div>
                </div>
                <?php if (!empty($ride['via_route_name'])): ?>
                    <div class="route-stop waypoint">
                        <div class="stop-beacon"></div>
                        <div class="stop-label">Via Corridor</div>
                        <div class="stop-name" style="font-size:13.5px; color:var(--primary);"><?php echo htmlspecialchars($ride['via_route_name']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="route-stop destination">
                    <div class="stop-beacon"></div>
                    <div class="stop-label">Dropoff Location</div>
                    <div class="stop-name"><?php echo htmlspecialchars($ride['destination']); ?></div>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="fr-grid-3" style="margin: 20px 0;">
                <div style="background:var(--bg-input); padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <div class="fr-label" style="margin-bottom:2px;">📅 Date & Time</div>
                    <div style="font-size:14.5px; font-weight:700; color:var(--text-main);"><?php echo $ride['ride_date']; ?> @ <?php echo date('h:i A', strtotime($ride['ride_time'])); ?></div>
                </div>

                <div style="background:var(--bg-input); padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <div class="fr-label" style="margin-bottom:2px;">🏎️ Vehicle Model</div>
                    <div style="font-size:14.5px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_type']); ?></div>
                </div>

                <div style="background:var(--bg-input); padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <div class="fr-label" style="margin-bottom:2px;">💰 Fare per Seat</div>
                    <div style="font-size:16px; font-weight:800; color:var(--eco);">₹<?php echo number_format($ride['price'], 2); ?></div>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:20px;">
                <a href="<?php echo $mapsUrl; ?>" target="_blank" class="fr-btn fr-btn-primary fr-btn-sm">
                    <i class='bx bxs-map-pin'></i> Open Pickup Map
                </a>
                <?php if ($computedStatus === 'active'): ?>
                    <a href="<?php echo $waShareUrl; ?>" target="_blank" class="fr-btn fr-btn-eco fr-btn-sm">
                        <i class='bx bxl-whatsapp'></i> Share WhatsApp
                    </a>
                    <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="fr-btn fr-btn-ghost fr-btn-sm">
                        <i class='bx bx-message-rounded-dots'></i> Passenger Chat
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Booked Passengers Roster -->
        <div class="fr-card">
            <h3 style="font-size:18px; font-weight:800; color:var(--text-main); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class='bx bxs-user-check' style="color:var(--primary);"></i> Booked Passengers Manifest
            </h3>

            <?php if ($bookings->num_rows > 0): ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php while ($b = $bookings->fetch_assoc()): ?>
                        <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:14px 18px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:15px; font-weight:700; color:var(--text-main);">
                                    👤 <?php echo htmlspecialchars($b['passenger_name']); ?>
                                </div>
                                <div style="font-size:12.5px; color:var(--text-muted); margin-top:2px;">
                                    📞 <?php echo htmlspecialchars($b['passenger_phone']); ?> • Seats: <strong><?php echo $b['seats_booked'] ?? 1; ?></strong>
                                </div>
                            </div>
                            <span class="fr-badge fr-badge-eco"><?php echo htmlspecialchars($b['trip_status'] ?? 'Confirmed'); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:24px; color:var(--text-muted); font-size:14px;">
                    No passenger reservations yet for this ride.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
