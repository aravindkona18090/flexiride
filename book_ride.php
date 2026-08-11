<?php
session_start();
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;
$current_user_id = $_SESSION['user_id'];
$successMessage = "";
$errorMessage = "";

$rideStmt = $conn->prepare("SELECT r.*, u.name as driver_name, u.email as driver_email, u.phone as driver_phone FROM rides r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$rideStmt->bind_param("i", $ride_id);
$rideStmt->execute();
$ride = $rideStmt->get_result()->fetch_assoc();

if (!$ride) {
    echo "Ride not found.";
    exit();
}

$userStmt = $conn->prepare("SELECT email, name, phone FROM users WHERE id = ?");
$userStmt->bind_param("i", $current_user_id);
$userStmt->execute();
$currentUser = $userStmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $seats_booked = (int)($_POST['seats_booked'] ?? 1);

    if ($ride['seats_available'] >= $seats_booked && $seats_booked > 0) {
        $new_seats = $ride['seats_available'] - $seats_booked;
        $total_price = $ride['price'] * $seats_booked;

        $updateStmt = $conn->prepare("UPDATE rides SET seats_available = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $new_seats, $ride_id);
        $updateStmt->execute();

        // 3NF Safe Booking Insertion
        safeAddColumn($conn, 'bookings', 'total_price', "DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        safeAddColumn($conn, 'bookings', 'posted_email', "VARCHAR(150) NULL");
        safeAddColumn($conn, 'bookings', 'booked_email', "VARCHAR(150) NULL");

        $bookStmt = $conn->prepare("INSERT INTO bookings (user_id, ride_id, seats_booked, total_price, posted_email, booked_email, trip_status) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')");
        $bookStmt->bind_param("iiidss", $current_user_id, $ride_id, $seats_booked, $total_price, $ride['driver_email'], $currentUser['email']);

        if ($bookStmt->execute()) {
            $booking_id = $bookStmt->insert_id;

            // Trigger Notification for Driver
            $n1 = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $n1_title = "🎉 New Ride Booking Request!";
            $n1_msg = "{$currentUser['name']} booked {$seats_booked} seat(s) for your trip from {$ride['origin']} to {$ride['destination']}.";
            $n1->bind_param("iss", $ride['user_id'], $n1_title, $n1_msg);
            $n1->execute();

            // Trigger Notification for Passenger
            $n2 = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $n2_title = "✅ Booking Confirmed!";
            $n2_msg = "Your booking for {$ride['origin']} to {$ride['destination']} with {$ride['driver_name']} is confirmed.";
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
            $errorMessage = "Booking failed: " . $conn->error;
        }
    } else {
        $errorMessage = "Requested seats exceed available seat capacity.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ride - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }
        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            width: 100%;
        }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .tag { display: inline-block; background: var(--success-bg); color: var(--success-color); padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; margin-bottom: 15px; }
        h2 { font-size: 24px; margin-bottom: 15px; text-align: center; color: var(--text-color); }
        .info-box { background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; padding: 20px; margin: 20px 0; }
        .info-box p { margin-bottom: 10px; color: var(--text-muted); font-size: 15px; }
        .btn-confirm { width: 100%; padding: 15px; border: none; border-radius: 12px; background: var(--primary-gradient); color: white; font-weight: 600; font-size: 16px; cursor: pointer; transition: 0.3s; }
        .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(22, 163, 74, 0.4); }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <div style="text-align: center;">
            <span class="tag">🏍️ <?php echo strtoupper($ride['vehicle_category'] ?? 'BIKE'); ?> RIDE</span>
        </div>
        <h2>Book Your Seat</h2>

        <?php if ($errorMessage): ?>
            <div class="alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <p><strong>Driver:</strong> <?php echo htmlspecialchars($ride['driver_name']); ?></p>
            <p><strong>Route:</strong> <?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></p>
            <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($ride['ride_date']); ?> at <?php echo htmlspecialchars($ride['ride_time']); ?></p>
            <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_type']); ?></p>
            <?php if (($ride['vehicle_category'] ?? 'bike') === 'bike'): ?>
                <p><strong>Spare Helmet:</strong> <?php echo ($ride['helmet_provided'] ?? 1) ? '🪖 Provided' : 'Bring Own'; ?></p>
            <?php endif; ?>
            <p><strong>Price per Seat:</strong> <span style="color:var(--success-color); font-weight:700; font-size:18px;">₹<?php echo htmlspecialchars($ride['price']); ?></span></p>
        </div>

        <form method="POST" onsubmit="if (!navigator.onLine) { alert('⚠️ Cannot book while offline! Please check your internet connection.'); return false; } const btn = this.querySelector('button[type=submit]'); btn.style.pointerEvents = 'none'; btn.style.opacity = '0.85'; btn.innerHTML = `<i class='bx bx-loader-alt bx-spin' style='font-size:18px;'></i> ⏳ Securing seat & notifying driver...`;">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; color:var(--text-muted); font-size:14px;">Select Seats to Book</label>
                <input type="number" name="seats_booked" value="1" min="1" max="<?php echo htmlspecialchars($ride['seats_available']); ?>" style="width:100%; padding:14px; border-radius:10px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-color); font-size:16px; outline:none;" required>
            </div>
            <button type="submit" class="btn-confirm">Confirm Seat Booking Now</button>
        </form>
    </div>
</div>

</body>
</html>