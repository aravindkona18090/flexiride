<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_GET['ride_id'])) {
    header("Location: find_ride.php");
    exit();
}

$ride_id = (int)$_GET['ride_id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

$sql = "SELECT r.id, r.origin, r.destination, r.ride_date, r.ride_time, r.price, r.vehicle_category, r.vehicle_model, r.helmet_provided, u.name AS posted_user_name, u.phone AS posted_user_phone, u.upi_id AS posted_user_upi
        FROM rides r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
} else {
    echo "Ride not found!";
    exit();
}
$driverUpi = !empty($ride['posted_user_upi']) ? $ride['posted_user_upi'] : 'flexiride@upi';
$sms = $_SESSION['last_sms_otp'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 520px; margin: 0 auto; text-align: center;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--eco-bg); color: var(--eco); display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 16px; border: 2px solid var(--eco-border); box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);">
                <i class='bx bx-check'></i>
            </div>

            <span class="fr-badge fr-badge-eco" style="margin-bottom: 8px;">Reservation Confirmed</span>
            <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                You're All Set to Ride!
            </h2>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">
                Booking confirmation email has been dispatched.
            </p>

            <?php if ($sms): ?>
                <!-- Trip OTP Box -->
                <div style="background: var(--bg-input); border: 1.5px solid var(--eco); border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px; text-align: left;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-size:12.5px; font-weight:700; color:var(--eco); text-transform:uppercase; letter-spacing:0.5px;">
                            📱 Departure Trip OTP
                        </span>
                        <span class="fr-badge fr-badge-primary">Ref: <?php echo htmlspecialchars($sms['txn_ref'] ?? 'FLX'); ?></span>
                    </div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--primary); letter-spacing: 4px; font-family: monospace;">
                        <?php echo htmlspecialchars($sms['otp'] ?? '----'); ?>
                    </div>
                    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 6px;">
                        Share this 4-digit code with <strong><?php echo htmlspecialchars($ride['posted_user_name']); ?></strong> at pickup time.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Route & Driver Recap -->
            <div style="background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 18px; text-align: left; margin-bottom: 20px;">
                <div class="wayfinder-route" style="margin: 0 0 14px 0;">
                    <div class="route-stop origin">
                        <div class="stop-beacon"></div>
                        <div class="stop-label">Pickup</div>
                        <div class="stop-name"><?php echo htmlspecialchars($ride['origin']); ?></div>
                    </div>
                    <div class="route-stop destination">
                        <div class="stop-beacon"></div>
                        <div class="stop-label">Dropoff</div>
                        <div class="stop-name"><?php echo htmlspecialchars($ride['destination']); ?></div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:13px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:12px;">
                    <div><i class='bx bx-calendar'></i> <?php echo htmlspecialchars($ride['ride_date']); ?> @ <?php echo htmlspecialchars($ride['ride_time']); ?></div>
                    <div><i class='bx bxs-user'></i> <?php echo htmlspecialchars($ride['posted_user_name']); ?></div>
                    <div><i class='bx bxs-car'></i> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></div>
                    <div style="color:var(--eco); font-weight:700;"><i class='bx bx-wallet'></i> ₹<?php echo htmlspecialchars($ride['price']); ?> Escrow Held</div>
                </div>
            </div>

            <!-- UPI Payment Launchpad -->
            <div style="background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 18px; margin-bottom: 24px;">
                <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 12px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class='bx bx-mobile-vibration' style="color:var(--primary);"></i> Pay Directly via Any UPI App (0% Fee)
                </div>
                <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <a href="upi://pay?pa=<?php echo urlencode($driverUpi); ?>&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" class="fr-btn fr-btn-sm" style="background:#0284c7; color:white;">
                        <i class='bx bxl-google'></i> Google Pay
                    </a>
                    <a href="upi://pay?pa=<?php echo urlencode($driverUpi); ?>&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" class="fr-btn fr-btn-sm" style="background:#5f259f; color:white;">
                        PhonePe
                    </a>
                    <a href="upi://pay?pa=<?php echo urlencode($driverUpi); ?>&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" class="fr-btn fr-btn-sm" style="background:#00baf2; color:white;">
                        Paytm
                    </a>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 12px;">
                <a href="chat.php?ride_id=<?php echo $ride_id; ?>" class="fr-btn fr-btn-primary" style="flex:1;">
                    <i class='bx bxs-chat'></i> Chat with Driver
                </a>
                <a href="my_booked_rides.php" class="fr-btn fr-btn-ghost" style="flex:1;">
                    View My Bookings
                </a>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
