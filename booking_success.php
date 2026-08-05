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
        h2 { font-size: 26px; color: #f8fafc; margin-bottom: 10px; }
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
    <p style="color:#94a3b8; font-size:14px;">Your seat has been reserved successfully.</p>

    <div class="details-box">
        <p><strong><i class='bx bxs-map-pin'></i> Route:</strong> <?php echo htmlspecialchars($ride['origin']); ?> ➔ <?php echo htmlspecialchars($ride['destination']); ?></p>
        <p><strong><i class='bx bxs-calendar'></i> Date & Time:</strong> <?php echo htmlspecialchars($ride['ride_date']); ?> at <?php echo htmlspecialchars($ride['ride_time']); ?></p>
        <p><strong><i class='bx bxs-user'></i> Driver:</strong> <?php echo htmlspecialchars($ride['posted_user_name']); ?></p>
        <p><strong><i class='bx bxs-phone'></i> Contact:</strong> <?php echo htmlspecialchars($ride['posted_user_phone']); ?></p>
        <p><strong><i class='bx bxs-car-wash'></i> Vehicle:</strong> <?php echo htmlspecialchars($ride['vehicle_model'] ?: $ride['vehicle_category']); ?></p>
        <?php if (($ride['vehicle_category'] ?? 'bike') === 'bike'): ?>
            <p><strong><i class='bx bxs-user-check'></i> Spare Helmet:</strong> <?php echo ($ride['helmet_provided'] ?? 1) ? '🪖 Provided' : 'Bring Own'; ?></p>
        <?php endif; ?>
        <p><strong><i class='bx bxs-purchase-tag'></i> Price Paid:</strong> ₹<?php echo htmlspecialchars($ride['price']); ?></p>
    </div>

    <div class="btn-group">
        <a href="chat.php?ride_id=<?php echo $ride_id; ?>" class="btn btn-chat"><i class='bx bxs-chat'></i> Chat with Driver</a>
        <a href="index.php" class="btn btn-home">Return Home</a>
    </div>
</div>
</div>
</body>
</html>
