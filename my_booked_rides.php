<?php
include_once __DIR__ . '/includes/db.php';

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

$sql = "SELECT b.id as booking_id, b.trip_status, b.trip_otp, b.txn_ref, r.*, u.name as driver_name, u.phone as driver_phone, u.upi_id as driver_upi, u.profile_photo as driver_photo
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
    <title>My Booked Trips — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .booking-mgmt-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: 1fr 240px;
            gap: 20px;
            align-items: center;
            transition: all 0.25s ease;
        }
        .booking-mgmt-card:hover {
            border-color: var(--border-strong);
            box-shadow: var(--shadow-md);
        }

        .modal-qr {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .booking-mgmt-card {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .booking-mgmt-card .action-box {
                border-top: 1px solid var(--border-subtle);
                padding-top: 14px;
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:14px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main);">🎟️ Passenger Commutes: Booked Trips</h1>
                <p style="font-size:14px; color:var(--text-muted); margin-top:2px;">Track your upcoming trips, departure OTPs, and ride receipts.</p>
            </div>
            <a href="find_ride.php" class="fr-btn fr-btn-primary"><i class='bx bx-search'></i> Find More Rides</a>
        </div>

        <?php if ($cancelMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600; font-size:14px;">
                ✅ <?php echo htmlspecialchars($cancelMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Booked Trips List -->
        <?php if ($result->num_rows > 0): ?>
            <div>
                <?php while ($ride = $result->fetch_assoc()): ?>
                    <?php 
                        $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($ride['origin']);
                        
                        $rideDateTime = strtotime($ride['ride_date'] . ' ' . $ride['ride_time']);
                        $isPassed = ($rideDateTime < time());
                        $rawStatus = strtolower($ride['trip_status'] ?? 'confirmed');
                        if ($rawStatus === 'cancelled') {
                            $computedStatus = 'cancelled';
                            $badgeClass = 'fr-badge-danger';
                            $statusLabel = 'Cancelled';
                        } elseif ($isPassed || $rawStatus === 'completed') {
                            $computedStatus = 'completed';
                            $badgeClass = 'fr-badge-ghost';
                            $statusLabel = 'Completed';
                        } else {
                            $computedStatus = 'active';
                            $badgeClass = 'fr-badge-eco';
                            $statusLabel = 'Confirmed';
                        }
                    ?>
                    <div class="booking-mgmt-card">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                                <span class="fr-badge <?php echo $badgeClass; ?>">
                                    <i class='bx bx-check-circle'></i> <?php echo $statusLabel; ?>
                                </span>
                                <?php if (!empty($ride['trip_otp']) && $computedStatus === 'active'): ?>
                                    <span class="fr-badge fr-badge-primary">
                                        <i class='bx bx-key'></i> Departure OTP: <strong><?php echo htmlspecialchars($ride['trip_otp']); ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Wayfinder Route Track -->
                            <div class="wayfinder-route" style="margin:10px 0;">
                                <div class="route-stop origin">
                                    <div class="stop-beacon"></div>
                                    <div class="stop-name"><?php echo htmlspecialchars($ride['origin']); ?></div>
                                </div>
                                <div class="route-stop destination">
                                    <div class="stop-beacon"></div>
                                    <div class="stop-name"><?php echo htmlspecialchars($ride['destination']); ?></div>
                                </div>
                            </div>

                            <div style="font-size:13px; color:var(--text-muted); display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:8px;">
                                <span><i class='bx bx-calendar'></i> <?php echo $ride['ride_date']; ?> at <?php echo date('h:i A', strtotime($ride['ride_time'])); ?></span>
                                <span>•</span>
                                <span><i class='bx bxs-user'></i> Driver: <strong><?php echo htmlspecialchars($ride['driver_name']); ?></strong> (📞 <?php echo htmlspecialchars($ride['driver_phone']); ?>)</span>
                                <span>•</span>
                                <span><i class='bx bxs-car'></i> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></span>
                            </div>
                        </div>

                        <div class="action-box" style="text-align:right;">
                            <div style="font-size:24px; font-weight:800; color:var(--eco); margin-bottom:8px;">₹<?php echo number_format($ride['price'], 2); ?></div>

                            <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:flex-end;">
                                <?php if ($computedStatus === 'completed'): ?>
                                    <a href="receipt.php?booking_id=<?php echo $ride['booking_id']; ?>" class="fr-btn fr-btn-ghost fr-btn-sm">
                                        <i class='bx bxs-file-pdf'></i> Receipt
                                    </a>
                                    <a href="rate_ride.php?ride_id=<?php echo $ride['id']; ?>&driver_id=<?php echo $ride['user_id']; ?>" class="fr-btn fr-btn-primary fr-btn-sm" style="background:#f59e0b;">
                                        <i class='bx bxs-star'></i> Rate Driver
                                    </a>
                                <?php elseif ($computedStatus === 'active'): ?>
                                    <a href="chat.php?ride_id=<?php echo $ride['id']; ?>" class="fr-btn fr-btn-primary fr-btn-sm">
                                        <i class='bx bx-message-rounded-dots'></i> Chat
                                    </a>

                                    <?php if (!empty($ride['driver_upi'])): ?>
                                        <button type="button" class="fr-btn fr-btn-eco fr-btn-sm" onclick="showQrModal('<?php echo htmlspecialchars($ride['driver_upi']); ?>', '<?php echo $ride['price']; ?>', '<?php echo htmlspecialchars($ride['driver_name']); ?>')">
                                            <i class='bx bx-qr-scan'></i> Pay UPI
                                        </button>
                                    <?php endif; ?>

                                    <a href="receipt.php?booking_id=<?php echo $ride['booking_id']; ?>" class="fr-btn fr-btn-ghost fr-btn-sm">
                                        <i class='bx bxs-file-pdf'></i> Receipt
                                    </a>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking? Seats will be returned to the driver.');">
                                        <input type="hidden" name="cancel_booking_id" value="<?php echo $ride['booking_id']; ?>">
                                        <button type="submit" class="fr-btn fr-btn-danger fr-btn-sm"><i class='bx bx-x-circle'></i> Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="fr-card" style="text-align:center; padding:50px 20px;">
                <i class='bx bxs-receipt' style="font-size:48px; color:var(--primary); margin-bottom:14px;"></i>
                <h3 style="font-size:20px; font-weight:700; color:var(--text-main); margin-bottom:6px;">No Booked Trips Found</h3>
                <p style="font-size:14px; color:var(--text-muted); max-width:400px; margin:0 auto 20px;">Browse campus commutes and reserve empty bike or carpool seats.</p>
                <a href="find_ride.php" class="fr-btn fr-btn-primary">Find Rides Now</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- UPI QR Modal -->
<div id="qrModal" class="modal-qr">
    <div class="fr-card" style="max-width:380px; width:100%; text-align:center; padding:30px;">
        <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:6px;">📲 Pay Driver via UPI</h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;" id="qrDriverTxt"></p>
        <img id="qrImage" src="" alt="UPI QR Code" style="width:200px; height:200px; border-radius:12px; margin:0 auto 16px; display:block; border:2px solid var(--primary);">
        <div style="font-size:22px; font-weight:800; color:var(--eco); margin-bottom:16px;" id="qrPriceTxt"></div>
        <button type="button" onclick="closeQrModal()" class="fr-btn fr-btn-ghost fr-btn-block">Close Window</button>
    </div>
</div>

<script>
    function showQrModal(upiId, price, driverName) {
        const upiUrl = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(driverName)}&am=${price}&cu=INR`;
        const qrApi = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(upiUrl)}`;
        document.getElementById('qrImage').src = qrApi;
        document.getElementById('qrDriverTxt').textContent = `Scan to pay ${driverName} (${upiId})`;
        document.getElementById('qrPriceTxt').textContent = `₹${price}`;
        document.getElementById('qrModal').style.display = 'flex';
    }

    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
