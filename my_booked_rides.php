<?php
include_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cancelMsg = "";

// Handle Cancel Booking by Passenger (Restores Seats & Notifies Driver)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking_id'])) {
    $b_id = (int)$_POST['cancel_booking_id'];
    
    $bQuery = $conn->prepare("SELECT b.*, r.user_id as driver_id, r.origin, r.destination FROM bookings b JOIN rides r ON b.ride_id = r.id WHERE b.id = ? AND b.user_id = ?");
    $bQuery->bind_param("ii", $b_id, $user_id);
    $bQuery->execute();
    $bData = $bQuery->get_result()->fetch_assoc();
    
    if ($bData && $bData['trip_status'] !== 'Cancelled') {
        $cUp = $conn->prepare("UPDATE bookings SET trip_status = 'Cancelled' WHERE id = ?");
        $cUp->bind_param("i", $b_id);
        $cUp->execute();
        
        $rUp = $conn->prepare("UPDATE rides SET seats_available = seats_available + ? WHERE id = ?");
        $rUp->bind_param("ii", $bData['seats_booked'], $bData['ride_id']);
        $rUp->execute();
        
        $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Passenger Cancelled Booking', 'A passenger cancelled their booking for your ride from {$bData['origin']} to {$bData['destination']}. Seats restored.')");
        $nStmt->bind_param("i", $bData['driver_id']);
        $nStmt->execute();
        
        $cancelMsg = "Your booking has been cancelled and seats restored to the ride.";
    }
}

// Fetch User Data for Emergency SOS WhatsApp
$uStmt = $conn->prepare("SELECT u.*, uc.emergency_phone, uc.emergency_email1 FROM users u LEFT JOIN user_emergency_contacts uc ON u.id = uc.user_id WHERE u.id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$userData = $uStmt->get_result()->fetch_assoc() ?: [];

// Fetch unread notifications for Passenger
$notifStmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$notifications = $notifStmt->get_result();

$sql = "SELECT b.id as booking_id, b.trip_status, r.*, u.name as driver_name, u.phone as driver_phone, u.upi_id as driver_upi
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u ON r.user_id = u.id
        WHERE b.user_id = ?
        ORDER BY r.ride_date DESC, r.ride_time DESC";

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
    <title>My Booked Rides & SOS - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 950px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h2 { font-size: 28px; color: var(--text-color); }
        .btn-find { background: var(--primary-gradient); color: white; padding: 12px 24px; border-radius: 12px; text-decoration: none; font-weight: 600; }

        /* Live Alerts Page Banner Widget */
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
        .status-confirmed { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }

        .vehicle-id-box {
            background: var(--input-bg);
            border: 1px solid var(--primary-color);
            border-radius: 10px;
            padding: 10px 14px;
            margin: 10px 0;
            display: inline-block;
        }

        .btn-sos-wa { background: #ef4444; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
        .btn-wa-share { background: #25D366; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; }
        .btn-pay-qr { background: #22c55e; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-size: 13px; }
        .btn-chat { background: var(--primary-color); color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-receipt { background: #818cf8; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-nav-map { background: #06b6d4; color: white; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }

        /* Modal styling */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: var(--card-bg); border: 1px solid var(--card-border); padding: 30px; border-radius: 20px; text-align: center; max-width: 400px; width: 90%; }

        @media (max-width: 768px) {
            .ride-card { flex-direction: column; text-align: left; }
            .ride-card > div:last-child { text-align: left; margin-top: 15px; }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h2>🎟️ My Booked Trips</h2>
        <a href="find_ride.php" class="btn-find"><i class='bx bx-search'></i> Find More Rides</a>
    </div>

    <!-- Live Activity & Alerts Widget -->
    <?php if ($notifications->num_rows > 0): ?>
        <div class="alerts-widget-card">
            <div class="widget-header">
                <h3><i class='bx bxs-bell-ring' style="color:var(--primary-color);"></i> 🔔 Live Ride Activity & Alerts</h3>
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

    <?php if ($result->num_rows > 0): ?>
        <?php while ($ride = $result->fetch_assoc()): ?>
            <?php 
                $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($ride['origin']);
                
                // Formatted WhatsApp Trip Details Share Text
                $waShareText = "🚗 *FlexiRide Commute Share*\n" .
                               "📍 Pickup: " . $ride['origin'] . "\n" .
                               "🏁 Drop: " . $ride['destination'] . "\n" .
                               "📅 Date & Time: " . $ride['ride_date'] . " at " . $ride['ride_time'] . "\n" .
                               "👤 Driver: " . $ride['driver_name'] . " (" . $ride['driver_phone'] . ")\n" .
                               "🚘 Vehicle: " . ($ride['vehicle_model'] ?: $ride['vehicle_type']) . "\n" .
                               "📍 GPS Pickup Map: " . $mapsUrl;
                
                $waShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($waShareText);

                // Emergency SOS WhatsApp Alert Text
                $sosText = "🚨 *EMERGENCY SOS ALERT - FlexiRide Safety*\n" .
                           "⚠️ I am currently on a ride and need urgent assistance!\n" .
                           "👤 Rider: " . ($userData['name'] ?? 'Rider') . " (" . ($userData['phone'] ?? '') . ")\n" .
                           "🚗 Driver: " . $ride['driver_name'] . " (" . $ride['driver_phone'] . ")\n" .
                           "🚘 Vehicle: " . ($ride['vehicle_model'] ?: $ride['vehicle_type']) . "\n" .
                           "📍 Route: " . $ride['origin'] . " ➔ " . $ride['destination'] . "\n" .
                           "📍 Live GPS Location: " . $mapsUrl;

                $emPhone = preg_replace('/[^0-9]/', '', $userData['emergency_phone'] ?? '');
                if (!empty($emPhone) && strlen($emPhone) == 10) {
                    $emPhone = '91' . $emPhone;
                }
                $sosWaUrl = !empty($emPhone) 
                    ? "https://api.whatsapp.com/send?phone=" . $emPhone . "&text=" . urlencode($sosText)
                    : "https://api.whatsapp.com/send?text=" . urlencode($sosText);
            ?>
            <?php
                $rideDateTime = strtotime($ride['ride_date'] . ' ' . $ride['ride_time']);
                $isPassed = ($rideDateTime < time());
                $rawStatus = strtolower($ride['trip_status'] ?? 'confirmed');
                if ($rawStatus === 'cancelled') {
                    $computedStatus = 'cancelled';
                    $badgeStyle = 'status-cancelled';
                    $statusIcon = 'bx bxs-x-circle';
                    $statusLabel = 'Cancelled';
                } elseif ($isPassed || $rawStatus === 'completed') {
                    $computedStatus = 'completed';
                    $badgeStyle = '';
                    $statusIcon = 'bx bxs-time-five';
                    $statusLabel = 'Completed';
                } else {
                    $computedStatus = 'active';
                    $badgeStyle = 'status-confirmed';
                    $statusIcon = 'bx bxs-check-circle';
                    $statusLabel = 'Confirmed';
                }
            ?>
            <div class="ride-card" data-status="<?php echo $computedStatus; ?>">
                <div>
                    <div style="margin-bottom:8px;">
                        <span class="status-badge <?php echo $badgeStyle; ?>" <?php if ($computedStatus === 'completed') echo 'style="background:rgba(148, 163, 184, 0.15); color:#94a3b8; border:1px solid #64748b;"'; ?>>
                            <i class='bx <?php echo $statusIcon; ?>'></i> Status: <?php echo $statusLabel; ?>
                        </span>
                    </div>
                    <h3 style="font-size:20px; margin-bottom:6px;"><?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></h3>
                    <p style="font-size:14px; color:var(--text-muted); margin-bottom:6px;">
                        👤 Driver: <strong><?php echo htmlspecialchars($ride['driver_name']); ?></strong> (📞 <?php echo htmlspecialchars($ride['driver_phone']); ?>)
                    </p>

                    <!-- Vehicle Identification Badge -->
                    <div class="vehicle-id-box">
                        <span style="font-size:14px; font-weight:700; color:var(--primary-color);">
                            <?php echo ($ride['vehicle_category'] === 'bike' ? '🏍️' : '🚗'); ?> 
                            Vehicle: <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_type']); ?>
                        </span>
                    </div>

                    <p style="font-size:13px; color:var(--text-muted);">
                        📅 <?php echo $ride['ride_date']; ?> at <?php echo $ride['ride_time']; ?>
                        <?php if (($ride['vehicle_category'] ?? 'bike') === 'bike'): ?>
                            | Spare Helmet: <strong><?php echo ($ride['helmet_provided'] ?? 1) ? '🪖 Provided' : 'Bring Own'; ?></strong>
                        <?php endif; ?>
                    </p>
                </div>

                <div style="text-align:right;">
                    <div style="font-size:24px; font-weight:700; color:var(--success-color); margin-bottom:10px;">₹<?php echo $ride['price']; ?></div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end;">
                        <!-- WhatsApp Emergency SOS Button -->
                        <a href="<?php echo $sosWaUrl; ?>" target="_blank" class="btn-sos-wa" title="Send Emergency SOS Alert via WhatsApp">
                            <i class='bx bxs-alarm-exclamation'></i> 🚨 SOS WhatsApp
                        </a>

                        <!-- WhatsApp Share Ride Button -->
                        <a href="<?php echo $waShareUrl; ?>" target="_blank" class="btn-wa-share" title="Share Ride Details on WhatsApp">
                            <i class='bx bxl-whatsapp'></i> Share
                        </a>

                        <?php if (!empty($ride['driver_upi'])): ?>
                            <button class="btn-pay-qr" onclick="showQrModal('<?php echo htmlspecialchars($ride['driver_upi']); ?>', '<?php echo $ride['price']; ?>', '<?php echo htmlspecialchars($ride['driver_name']); ?>')">
                                <i class='bx bx-qr-scan'></i> Pay UPI
                            </button>
                        <?php endif; ?>

                        <a href="<?php echo $mapsUrl; ?>" target="_blank" class="btn-nav-map"><i class='bx bxs-map-pin'></i> Pickup Spot</a>
                        <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="btn-chat"><i class='bx bx-message-rounded-dots'></i> Chat</a>
                        <a href="receipt.php?booking_id=<?php echo $ride['booking_id']; ?>" class="btn-receipt"><i class='bx bxs-file-pdf'></i> Receipt</a>
                        <a href="rate_ride.php?ride_id=<?php echo $ride['id']; ?>&driver_id=<?php echo $ride['user_id']; ?>" class="btn-chat" style="background:#f59e0b;">⭐ Rate</a>

                        <?php if ($ride['trip_status'] !== 'Cancelled'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking? Seats will be restored to the driver.');">
                                <input type="hidden" name="cancel_booking_id" value="<?php echo $ride['booking_id']; ?>">
                                <button type="submit" class="btn-sos-wa" style="background:#dc2626;" title="Cancel Booking"><i class='bx bx-x-circle'></i> Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding:40px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px;">
            <h3>No trips booked yet!</h3>
            <p style="color:var(--text-muted); margin:10px 0 20px;">Find convenient bike or car rides for your daily commute.</p>
            <a href="find_ride.php" class="btn-find">Browse Available Rides →</a>
        </div>
    <?php endif; ?>
</div>

<!-- QR Code Modal -->
<div id="qrModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom:10px;">📲 Pay Driver via UPI</h3>
        <p style="font-size:14px; color:var(--text-muted); margin-bottom:15px;" id="qrDriverTxt"></p>
        <img id="qrImage" src="" alt="UPI QR Code" style="width:200px; height:200px; border-radius:12px; margin-bottom:15px; border:2px solid var(--primary-color);">
        <p style="font-weight:700; color:var(--success-color); font-size:18px;" id="qrPriceTxt"></p>
        <button onclick="closeQrModal()" style="margin-top:15px; padding:10px 20px; background:#ef4444; color:white; border:none; border-radius:8px; cursor:pointer;">Close QR</button>
    </div>
</div>

<script>
    function showQrModal(upiId, price, driverName) {
        const upiUrl = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(driverName)}&am=${price}&cu=INR`;
        const qrApi = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(upiUrl)}`;
        document.getElementById('qrImage').src = qrApi;
        document.getElementById('qrDriverTxt').textContent = `Scan to pay ${driverName} (${upiId})`;
        document.getElementById('qrPriceTxt').textContent = `Amount: ₹${price}`;
        document.getElementById('qrModal').style.display = 'flex';
    }

    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
    }
</script>
</body>
</html>
