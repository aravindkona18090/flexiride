<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_GET['ride_id'])) {
    header("Location: find_ride.php");
    exit();
}

$ride_id = (int)$_GET['ride_id'];

$sql = "SELECT r.id, r.origin, r.destination, r.ride_date, r.ride_time, r.price, r.vehicle_category, r.vehicle_model, r.helmet_provided, u.name AS posted_user_name, u.phone AS posted_user_phone 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body {
            background: var(--bg-color) !important;
            color: var(--text-color) !important;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
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
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .check-icon { font-size: 64px; color: #22c55e; margin-bottom: 15px; }
        h2 { font-size: 26px; color: var(--text-color) !important; margin-bottom: 10px; }
        .details-box { background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; padding: 20px; text-align: left; margin: 20px 0; }
        .details-box p { margin-bottom: 8px; color: var(--text-muted); font-size: 15px; }
        .btn-group { display: flex; gap: 15px; }
        .btn { flex: 1; padding: 14px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 15px; text-align: center; transition: 0.3s; }
        .btn-chat { background: var(--primary-gradient); color: white; }
        .btn-home { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--input-border); }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="card">
    <i class='bx bxs-check-circle check-icon'></i>
    <h2>Booking Confirmed!</h2>
    <p style="color:var(--text-muted); font-size:14px;">Your seat has been reserved successfully.</p>

    <?php if (!empty($_SESSION['last_sms_otp'])): 
        $sms = $_SESSION['last_sms_otp'];
    ?>
        <!-- 📱 Free SMS OTP Notification Toast -->
        <div style="background: rgba(34, 197, 94, 0.15); border: 1.5px solid #22c55e; border-radius: 14px; padding: 16px; margin-bottom: 20px; text-align: left;">
            <div style="font-weight: 700; color: #22c55e; font-size: 15px; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class='bx bxs-message-dots' style="font-size:20px;"></i> 📱 Free SMS Sent to +91 <?php echo htmlspecialchars($sms['phone']); ?>
            </div>
            <p style="font-size: 13px; color: var(--text-color); margin: 0; line-height: 1.5;">
                Trip OTP for Booking #<?php echo $ride_id; ?> has been dispatched to your mobile SMS inbox. Share code with driver upon pickup. Ref: <strong><?php echo $sms['txn_ref']; ?></strong>
            </p>
        </div>
    <?php endif; ?>

    <div class="details-box">
        <p><strong><i class='bx bxs-map-pin'></i> Route:</strong> <?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></p>
        <p><strong><i class='bx bxs-calendar'></i> Date & Time:</strong> <?php echo htmlspecialchars($ride['ride_date']); ?> at <?php echo htmlspecialchars($ride['ride_time']); ?></p>
        <p><strong><i class='bx bxs-user'></i> Driver:</strong> <?php echo htmlspecialchars($ride['posted_user_name']); ?></p>
        <p><strong><i class='bx bxs-phone'></i> Contact:</strong> <?php echo htmlspecialchars($ride['posted_user_phone']); ?></p>
        <p><strong><i class='bx bxs-car-wash'></i> Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></p>
        <?php if (($ride['vehicle_category'] ?? 'bike') === 'bike'): ?>
            <p><strong><i class='bx bxs-user-check'></i> Spare Helmet:</strong> <?php echo ($ride['helmet_provided'] ?? 1) ? '🪖 Provided' : 'Bring Own'; ?></p>
        <?php endif; ?>
        <p><strong><i class='bx bxs-purchase-tag'></i> Fare Amount:</strong> ₹<?php echo htmlspecialchars($ride['price']); ?> <span style="background:var(--success-bg); color:var(--success-color); font-weight:700; font-size:11px; padding:2px 8px; border-radius:6px; margin-left:6px;">⌛ Escrow Held</span></p>
    </div>

    <!-- 💳 Free UPI Payment Gateway Simulation -->
    <div style="background: var(--input-bg); border: 1px solid var(--primary-color); border-radius: 14px; padding: 16px; margin-bottom: 20px; text-align: center;">
        <div style="font-size: 13px; font-weight: 700; color: var(--primary-color); margin-bottom: 8px;">💳 Pay Directly via Any Free UPI App (0% Fees)</div>
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <a href="upi://pay?pa=7386614044@ybl&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" style="padding: 8px 14px; background: #0284c7; color: white; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700;">Google Pay</a>
            <a href="upi://pay?pa=7386614044@ybl&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" style="padding: 8px 14px; background: #5f259f; color: white; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700;">PhonePe</a>
            <a href="upi://pay?pa=7386614044@ybl&pn=FlexiRide&am=<?php echo $ride['price']; ?>&cu=INR" style="padding: 8px 14px; background: #00baf2; color: white; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700;">Paytm</a>
        </div>
    </div>

    <div class="btn-group">
        <a href="chat.php?ride_id=<?php echo $ride_id; ?>" class="btn btn-chat"><i class='bx bxs-chat'></i> Chat with Driver</a>
        <a href="my_booked_rides.php" class="btn btn-home">View My Bookings</a>
    </div>
</div>
</div>
</body>
</html>
