<?php
include_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve posting parameters from session
$origin            = $_SESSION['origin'] ?? 'Origin';
$destination       = $_SESSION['destination'] ?? 'Destination';
$ride_date         = $_SESSION['ride_date'] ?? date('Y-m-d');
$ride_time         = $_SESSION['ride_time'] ?? '08:30';
$vehicle_category  = $_SESSION['vehicle_category'] ?? 'bike';
$seats_available   = $_SESSION['seats_available'] ?? 1;
$helmet_provided   = $_SESSION['helmet_provided'] ?? 1;
$gender_preference = $_SESSION['gender_preference'] ?? 'any';
$vehicle_model     = $_SESSION['vehicle_model'] ?? 'Bike';
$luggage_limit     = $_SESSION['luggage_limit'] ?? 'Backpack only';
$distance_km       = (float)($_SESSION['route_distance'] ?? 25.0);
$via_route_name    = $_SESSION['via_route_name'] ?? '';

$error = "";

// Win-Win Dynamic Pricing Calculations
if ($vehicle_category === 'bike') {
    $cost_per_km = 4.0; // ₹4/km petrol running cost for bike
    $fair_share = 0.50; // Driver bears 50%, passenger pays 50%
} else {
    $cost_per_km = 9.0; // ₹9/km fuel cost for car
    $total_riders = max(2, $seats_available + 1);
    $fair_share = 1.0 / $total_riders;
}

$total_fuel_cost = round($distance_km * $cost_per_km, 2);
$suggested_fare = round(($total_fuel_cost * $fair_share) / $seats_available, 2);
if ($suggested_fare < 20) $suggested_fare = 20.0; // minimum floor fare

// Handle Post Confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_post'])) {
    $final_price = (float)($_POST['final_price'] ?? $suggested_fare);

    safeAddColumn($conn, 'rides', 'via_route_name', "VARCHAR(255) NULL");
    safeAddColumn($conn, 'rides', 'route_distance', "DECIMAL(8,2) NOT NULL DEFAULT 25.00");
    safeAddColumn($conn, 'rides', 'trip_status', "VARCHAR(50) NOT NULL DEFAULT 'active'");
    
    $stmt = $conn->prepare("INSERT INTO rides (user_id, origin, destination, via_route_name, ride_date, ride_time, vehicle_type, vehicle_category, vehicle_model, seats_available, price, helmet_provided, gender_preference, luggage_limit, route_distance, trip_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    
    if ($stmt) {
        $stmt->bind_param("issssssssidissd", $user_id, $origin, $destination, $via_route_name, $ride_date, $ride_time, $vehicle_category, $vehicle_category, $vehicle_model, $seats_available, $final_price, $helmet_provided, $gender_preference, $luggage_limit, $distance_km);

        if ($stmt->execute()) {
            header("Location: myrides.php");
            exit();
        } else {
            $error = "Error saving ride to database: " . $stmt->error;
        }
    } else {
        $error = "Database statement prepare failed: " . $conn->error;
    }
}

// Format WhatsApp preview share URL
$mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($origin);
$waText = "🏍️ *FlexiRide Trip Offer*\n" .
          "📍 Pickup: " . $origin . "\n" .
          "🏁 Drop: " . $destination . "\n" .
          "📅 Date & Time: " . $ride_date . " at " . $ride_time . "\n" .
          "🚘 Vehicle: " . $vehicle_model . "\n" .
          "💰 Fair Fare: ₹" . $suggested_fare . "/seat\n" .
          "📍 Pickup Location Map: " . $mapsUrl;
$waShareUrl = "https://api.whatsapp.com/send?text=" . urlencode($waText);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fare Confirmation & Route Preview - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; width: 100%; }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        h2 { font-size: 26px; text-align: center; margin-bottom: 25px; color: var(--text-color); }

        .fare-badge {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid var(--success-color);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .summary-item { background: var(--input-bg); padding: 14px; border-radius: 12px; border: 1px solid var(--input-border); }
        .summary-item label { font-size: 13px; color: var(--text-muted); display: block; margin-bottom: 4px; }
        .summary-item span { font-size: 16px; font-weight: 600; color: var(--text-color); }

        .btn-confirm {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-wa-share {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 14px; border-radius: 12px;
            background: #25D366; color: white; font-weight: 700; font-size: 15px; text-decoration: none; margin-top: 10px;
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <h2>⚡ Win-Win Fare Calculation & Route Confirmation</h2>

        <?php if (!empty($error)): ?>
            <div style="background:var(--danger-bg); color:var(--danger-color); border:1px solid var(--danger-color); padding:12px; border-radius:10px; margin-bottom:20px; text-align:center; font-weight:600; font-size:14px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="fare-badge">
            <label style="font-size:14px; color:var(--text-muted); display:block;">Suggested Win-Win Fair Fare (Per Seat)</label>
            <div style="font-size:36px; font-weight:800; color:var(--success-color); margin:6px 0;">₹<?php echo $suggested_fare; ?></div>
            <p style="font-size:13px; color:var(--text-muted);">
                Distance: <strong><?php echo $distance_km; ?> km</strong> | Total Route Fuel Cost: ₹<?php echo $total_fuel_cost; ?>
            </p>
        </div>

        <div class="summary-grid">
            <div class="summary-item"><label>📍 Pickup Location</label><span><?php echo htmlspecialchars($origin); ?></span></div>
            <div class="summary-item"><label>🏁 Drop Location</label><span><?php echo htmlspecialchars($destination); ?></span></div>
            <div class="summary-item"><label>📅 Date & Time</label><span><?php echo $ride_date; ?> at <?php echo $ride_time; ?></span></div>
            <div class="summary-item"><label>🚘 Vehicle Model</label><span><?php echo htmlspecialchars($vehicle_model); ?></span></div>
            <div class="summary-item"><label>💺 Seats Offered</label><span><?php echo $seats_available; ?> Seat(s)</span></div>
            <div class="summary-item"><label>🪖 Helmet Provided</label><span><?php echo $helmet_provided ? 'Yes (Spare Helmet Provided)' : 'Bring Own'; ?></span></div>
        </div>

        <form method="POST">
            <input type="hidden" name="final_price" value="<?php echo $suggested_fare; ?>">
            <button type="submit" name="confirm_post" class="btn-confirm">Publish Ride & Save Offer →</button>
        </form>

        <a href="<?php echo $waShareUrl; ?>" target="_blank" class="btn-wa-share">
            <i class='bx bxl-whatsapp' style="font-size:20px;"></i> Share Trip Preview on WhatsApp
        </a>
    </div>
</div>
</body>
</html>
