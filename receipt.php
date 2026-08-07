<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

$sql = "SELECT b.id as booking_id, b.trip_status, b.created_at as booking_time,
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
$fare = (float)$trip['price'];
$co2Saved = round($distanceKm * 0.045, 2); // 0.045 kg CO2 saved per km on bike sharing
$moneySaved = round($fare * 1.8, 2); // Saved vs taxi
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Trip Receipt #FR-<?php echo $trip['booking_id']; ?> - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; padding: 40px 20px; display: flex; justify-content: center; }

        .receipt-card {
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            border-radius: 24px;
            max-width: 600px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            position: relative;
        }

        .receipt-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed var(--card-border); padding-bottom: 20px; margin-bottom: 25px; }
        .receipt-logo { font-size: 26px; font-weight: 800; color: var(--primary-color); }
        .receipt-logo span { color: var(--success-color); }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px; }
        .info-row span:first-child { color: var(--text-muted); }
        .info-row span:last-child { font-weight: 600; color: var(--text-color); }

        .fare-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .fare-table th, .fare-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--card-border); font-size: 14px; }
        .fare-table th { color: var(--text-muted); }

        .eco-badge-box {
            background: var(--success-bg);
            border: 1px solid var(--success-color);
            color: var(--success-color);
            padding: 14px; border-radius: 14px; text-align: center; font-weight: 600; font-size: 14px; margin: 20px 0;
        }

        .btn-print {
            display: flex; justify-content: center; align-items: center; gap: 8px;
            width: 100%; padding: 16px; border-radius: 12px; border: none;
            background: var(--primary-gradient); color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-print:hover { transform: translateY(-2px); }

        @media print {
            .btn-print { display: none; }
            body { background: white !important; color: black !important; }
            .receipt-card { border: 1px solid #ccc; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="receipt-header">
        <div class="receipt-logo"><i class='bx bxs-navigation'></i> Flexi<span>Ride</span></div>
        <div style="text-align:right;">
            <div style="font-size:12px; color:var(--text-muted);">INVOICE NO.</div>
            <strong style="color:var(--primary-color);">#FR-2026-<?php echo sprintf('%04d', $trip['booking_id']); ?></strong>
        </div>
    </div>

    <div class="info-row"><span>Passenger</span><span><?php echo htmlspecialchars($trip['passenger_name']); ?></span></div>
    <div class="info-row"><span>Driver</span><span><?php echo htmlspecialchars($trip['driver_name']); ?> <?php if ($trip['driver_verified']) echo '🛡️'; ?></span></div>
    <div class="info-row"><span>Date & Time</span><span><?php echo $trip['ride_date']; ?> at <?php echo $trip['ride_time']; ?></span></div>
    <div class="info-row"><span>Vehicle Category</span><span><?php echo strtoupper($trip['vehicle_category'] ?? 'BIKE'); ?></span></div>
    <div class="info-row"><span>Trip Status</span><span style="color:var(--success-color);"><?php echo strtoupper($trip['trip_status']); ?></span></div>

    <hr style="border:none; border-top:1px solid var(--card-border); margin:20px 0;">

    <div style="margin-bottom:15px;">
        <div style="font-size:13px; color:var(--text-muted); margin-bottom:4px;">TRIP ROUTE</div>
        <h4 style="font-size:17px; color:var(--text-color);"><?php echo htmlspecialchars($trip['origin']); ?> ➔ <?php echo htmlspecialchars($trip['destination']); ?></h4>
    </div>

    <table class="fare-table">
        <thead>
            <tr><th>Description</th><th>Qty / Dist</th><th>Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Fuel-Split Rate (₹3.00/km)</td>
                <td><?php echo $distanceKm; ?> km</td>
                <td>₹<?php echo number_format($fare, 2); ?></td>
            </tr>
            <tr>
                <td>FlexiRide Service Charge</td>
                <td>Free Platform</td>
                <td>₹0.00</td>
            </tr>
            <tr style="font-weight:700; font-size:16px;">
                <td style="color:var(--text-color);">Total Fare Paid</td>
                <td></td>
                <td style="color:var(--success-color);">₹<?php echo number_format($fare, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="eco-badge-box">
        🌱 Eco Savings: Saved ~<strong><?php echo $co2Saved; ?> kg $CO_2$</strong> emissions & saved ~<strong>₹<?php echo $moneySaved; ?></strong> vs taxis!
    </div>

    <button onclick="window.print()" class="btn-print"><i class='bx bxs-printer'></i> Print / Download PDF Receipt</button>
</div>

</body>
</html>
