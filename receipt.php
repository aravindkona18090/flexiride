<?php
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

$sql = "SELECT b.id as booking_id, b.trip_status, b.created_at as booking_time, b.txn_ref, b.seats_booked, b.total_price,
               r.*, 
               u_driver.name as driver_name, u_driver.phone as driver_phone, u_driver.upi_id as driver_upi, u_driver.is_aadhaar_verified as driver_verified,
               u_pass.name as passenger_name, u_pass.phone as passenger_phone
        FROM bookings b
        JOIN rides r ON b.ride_id = r.id
        JOIN users u_driver ON r.user_id = u_driver.id
        JOIN users u_pass ON b.user_id = u_pass.id
        WHERE b.id = ? AND (b.user_id = ? OR r.user_id = ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $booking_id, $user_id, $user_id);
$stmt->execute();
$trip = $stmt->get_result()->fetch_assoc();

if (!$trip) {
    die("Receipt not found or permission denied.");
}

$distanceKm = (float)($trip['route_distance'] ?? 25.0);
$fare = (float)($trip['total_price'] ?: $trip['price']);
$co2Saved = round($distanceKm * 0.045, 2);
$moneySaved = round($fare * 1.8, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Trip Invoice #FLX-<?php echo $trip['booking_id']; ?> — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .invoice-card {
            background: var(--bg-surface);
            border: 2px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            max-width: 620px;
            width: 100%;
            margin: 40px auto;
            padding: 36px;
            box-shadow: var(--shadow-lg);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed var(--border-subtle);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .invoice-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14.5px;
        }
        .invoice-row span:first-child { color: var(--text-muted); }
        .invoice-row span:last-child { font-weight: 700; color: var(--text-main); }

        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .invoice-card { border: 1px solid #ddd; box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 20px 0;">
    <div class="fr-container">
        <div class="no-print" style="max-width:620px; margin:0 auto 16px; display:flex; justify-content:space-between; align-items:center;">
            <a href="my_booked_rides.php" class="fr-btn fr-btn-ghost fr-btn-sm">
                <i class='bx bx-left-arrow-alt'></i> Back to My Bookings
            </a>
            <button onclick="window.print()" class="fr-btn fr-btn-primary fr-btn-sm">
                <i class='bx bx-printer'></i> Print / Save PDF
            </button>
        </div>

        <div class="invoice-card">
            <!-- Header -->
            <div class="invoice-header">
                <div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--primary); display:flex; align-items:center; gap:6px;">
                        <i class='bx bxs-navigation'></i> FlexiRide
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top:2px;">Fair-Share Campus Commute Protocol</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size: 11px; font-weight:700; color: var(--text-muted); text-transform:uppercase;">INVOICE ID</div>
                    <div style="font-size: 15px; font-weight: 800; color: var(--text-main);">#FLX-<?php echo str_pad($trip['booking_id'], 6, '0', STR_PAD_LEFT); ?></div>
                </div>
            </div>

            <!-- Wayfinder Route Recap -->
            <div class="wayfinder-route" style="margin: 0 0 20px 0;">
                <div class="route-stop origin">
                    <div class="stop-beacon"></div>
                    <div class="stop-label">Pickup Location</div>
                    <div class="stop-name"><?php echo htmlspecialchars($trip['origin']); ?></div>
                </div>
                <div class="route-stop destination">
                    <div class="stop-beacon"></div>
                    <div class="stop-label">Dropoff Location</div>
                    <div class="stop-name"><?php echo htmlspecialchars($trip['destination']); ?></div>
                </div>
            </div>

            <!-- Metadata Rows -->
            <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px; margin-bottom: 18px;">
                <div class="invoice-row">
                    <span>📅 Trip Date & Time</span>
                    <span><?php echo $trip['ride_date']; ?> at <?php echo date('h:i A', strtotime($trip['ride_time'])); ?></span>
                </div>
                <div class="invoice-row">
                    <span>👤 Passenger Name</span>
                    <span><?php echo htmlspecialchars($trip['passenger_name']); ?></span>
                </div>
                <div class="invoice-row">
                    <span>🚘 Driver & Vehicle</span>
                    <span><?php echo htmlspecialchars($trip['driver_name']); ?> (<?php echo htmlspecialchars($trip['vehicle_model'] ?: $trip['vehicle_type']); ?>)</span>
                </div>
                <div class="invoice-row">
                    <span>🔑 Transaction Reference</span>
                    <span style="font-family:monospace;"><?php echo htmlspecialchars($trip['txn_ref'] ?? 'TXN-FLX'); ?></span>
                </div>
                <div class="invoice-row">
                    <span>💺 Seats Reserved</span>
                    <span><?php echo (int)($trip['seats_booked'] ?? 1); ?> Seat(s)</span>
                </div>
            </div>

            <!-- Cost Split Table -->
            <div class="fair-fare-meter" style="margin-bottom: 20px;">
                <div>
                    <div class="fare-subtext">Total Fuel Contribution Paid</div>
                    <div class="fare-amount">₹<?php echo number_format($fare, 2); ?></div>
                </div>
                <div style="text-align:right;">
                    <span class="fr-badge fr-badge-eco"><i class='bx bx-check'></i> 0% Platform Fee</span>
                    <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">100% Goes to Driver's Petrol Cost</div>
                </div>
            </div>

            <!-- Eco Impact Capsule -->
            <div style="background:var(--eco-bg); border:1px solid var(--eco-border); border-radius:var(--radius-md); padding:14px; text-align:center; color:var(--eco); font-size:13.5px; font-weight:700;">
                🌱 Green Impact: Saved <strong><?php echo $co2Saved; ?> kg CO₂</strong> & approx <strong>₹<?php echo $moneySaved; ?></strong> vs single-passenger cab.
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
