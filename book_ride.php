<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';
include_once __DIR__ . '/includes/trust_score.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token for this session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;
$current_user_id = $_SESSION['user_id'];
$successMessage = "";
$errorMessage = "";

$rideStmt = $conn->prepare("SELECT r.*, u.name as driver_name, u.email as driver_email, u.phone as driver_phone, u.profile_photo as driver_photo FROM rides r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$rideStmt->bind_param("i", $ride_id);
$rideStmt->execute();
$ride = $rideStmt->get_result()->fetch_assoc();

if (!$ride) {
    echo "Ride not found.";
    exit();
}

$trustInfo = calculateRiderAiSafetyScore($conn, (int)$ride['user_id']);

$userStmt = $conn->prepare("SELECT email, name, phone FROM users WHERE id = ?");
$userStmt->bind_param("i", $current_user_id);
$userStmt->execute();
$currentUser = $userStmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid request. Please go back and try again.");
    }

    $seats_booked = (int)($_POST['seats_booked'] ?? 1);

    if ($seats_booked > 0) {
        $total_price = $ride['price'] * $seats_booked;
        $txn_ref     = 'TXN-FLX-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $trip_otp    = (string)rand(1000, 9999);

        // Atomic seat reservation — prevents double-booking race condition
        $conn->begin_transaction();
        $updateStmt = $conn->prepare(
            "UPDATE rides SET seats_available = seats_available - ?
             WHERE id = ? AND seats_available >= ?"
        );
        $updateStmt->bind_param("iii", $seats_booked, $ride_id, $seats_booked);
        $updateStmt->execute();

        if ($conn->affected_rows === 0) {
            $conn->rollback();
            $errorMessage = "Not enough seats available. Please try with fewer seats.";
        } else {
            $bookStmt = $conn->prepare("INSERT INTO bookings (user_id, ride_id, seats_booked, total_price, posted_email, booked_email, trip_status, payment_status, txn_ref, trip_otp) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed', 'Escrow Held', ?, ?)");
            $bookStmt->bind_param("iiidssss", $current_user_id, $ride_id, $seats_booked, $total_price, $ride['driver_email'], $currentUser['email'], $txn_ref, $trip_otp);

            if ($bookStmt->execute()) {
                $conn->commit();
                $booking_id = $bookStmt->insert_id;

                if (!empty($currentUser['phone'])) {
                    $_SESSION['last_sms_otp'] = [
                        'phone'   => $currentUser['phone'],
                        'otp'     => $trip_otp,
                        'txn_ref' => $txn_ref,
                        'amount'  => $total_price
                    ];
                }

                // Notification for Driver
                $n1 = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $n1_title = "🎉 New Ride Booking Request!";
                $n1_msg   = "{$currentUser['name']} booked {$seats_booked} seat(s) for your trip from {$ride['origin']} to {$ride['destination']}.";
                $n1->bind_param("iss", $ride['user_id'], $n1_title, $n1_msg);
                $n1->execute();

                // Notification for Passenger
                $n2 = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $n2_title = "✅ Booking Confirmed!";
                $n2_msg   = "Your booking for {$ride['origin']} to {$ride['destination']} with {$ride['driver_name']} is confirmed.";
                $n2->bind_param("iss", $current_user_id, $n2_title, $n2_msg);
                $n2->execute();

                $bookingHtml = "
                    <h2>Booking Confirmed</h2>
                    <p>You have successfully booked <strong>$seats_booked seat(s)</strong> for the trip from <strong>{$ride['origin']}</strong> to <strong>{$ride['destination']}</strong>.</p>
                    <p><strong>Driver:</strong> {$ride['driver_name']} ({$ride['driver_phone']})</p>
                    <p><strong>Date &amp; Time:</strong> {$ride['ride_date']} at {$ride['ride_time']}</p>
                ";
                sendResendMail($currentUser['email'], $currentUser['name'], 'FlexiRide - Booking Confirmed!', $bookingHtml);

                header("Location: booking_success.php?ride_id=" . $ride_id . "&booking_id=" . $booking_id);
                exit();
            } else {
                $conn->rollback();
                $errorMessage = "Booking failed. Please try again.";
            }
        }
    } else {
        $errorMessage = "Please select at least 1 seat.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Seat — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 560px; margin: 0 auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <span class="fr-badge fr-badge-primary">
                    <?php echo (($ride['vehicle_category'] ?? 'bike') === 'bike') ? '<i class="bx bx-cycling"></i> TWO-WHEELER POOL' : '<i class="bx bxs-car"></i> CAR SHARING'; ?>
                </span>
                <span class="trust-shield <?php echo $trustInfo['badge_class']; ?>">
                    <i class='bx bxs-shield-check'></i> <?php echo $trustInfo['score']; ?>% Verified
                </span>
            </div>

            <h2 style="font-size:24px; font-weight:800; color:var(--text-main); margin-bottom:8px;">
                Reserve Your Seat
            </h2>
            <p style="font-size:14px; color:var(--text-muted); margin-bottom:20px;">
                Direct fuel cost split. Free cancellation before departure.
            </p>

            <?php if ($errorMessage): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:18px; font-size:14px; font-weight:600;">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Driver & Vehicle Details -->
            <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:16px; margin-bottom:18px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <?php if (!empty($ride['driver_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($ride['driver_photo']); ?>" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid var(--primary);" alt="Driver">
                    <?php else: ?>
                        <div style="width:44px; height:44px; border-radius:50%; background:var(--primary-gradient); color:white; display:flex; align-items:center; justify-content:center; font-size:20px;">
                            <i class='bx bxs-user'></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-size:15px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($ride['driver_name']); ?></div>
                        <div style="font-size:12.5px; color:var(--text-muted);">Vehicle: <strong><?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></strong></div>
                    </div>
                </div>

                <!-- Wayfinder Route Recap -->
                <div class="wayfinder-route" style="margin: 10px 0;">
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

                <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:var(--text-muted); border-top:1px solid var(--border-subtle); padding-top:10px; margin-top:10px;">
                    <div>📅 <?php echo htmlspecialchars($ride['ride_date']); ?> at <?php echo htmlspecialchars($ride['ride_time']); ?></div>
                    <?php if (($ride['vehicle_category'] ?? 'bike') === 'bike'): ?>
                        <div>🪖 <?php echo ($ride['helmet_provided'] ?? 1) ? 'Spare Helmet Provided' : 'Bring Own Helmet'; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Booking Form -->
            <form method="POST" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = `<i class='bx bx-loader-alt bx-spin'></i> Securing Seat...`;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="fr-form-group">
                    <label class="fr-label">Number of Seats to Reserve</label>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <input type="number" id="seats_booked" name="seats_booked" value="1" min="1" max="<?php echo htmlspecialchars($ride['seats_available']); ?>" class="fr-input" style="font-size:18px; font-weight:700;" onchange="updateTotal(this.value, <?php echo (float)$ride['price']; ?>)" required>
                        <span style="font-size:13px; color:var(--text-muted); white-space:nowrap;">Max <?php echo htmlspecialchars($ride['seats_available']); ?> seat(s) available</span>
                    </div>
                </div>

                <div class="fair-fare-meter" style="margin-bottom: 20px;">
                    <div>
                        <div class="fare-subtext">Total Contribution</div>
                        <div class="fare-amount" id="totalPriceDisplay">₹<?php echo htmlspecialchars($ride['price']); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <span class="fr-badge fr-badge-eco"><i class='bx bxs-lock-alt'></i> Escrow Held</span>
                        <div style="font-size:11.5px; color:var(--text-muted); margin-top:4px;">Released on Trip OTP Confirmation</div>
                    </div>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Confirm Seat Reservation <i class='bx bx-check-circle'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    function updateTotal(qty, unitPrice) {
        const count = Math.max(1, parseInt(qty) || 1);
        const total = count * unitPrice;
        document.getElementById('totalPriceDisplay').textContent = '₹' + total.toFixed(0);
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>