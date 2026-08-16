<?php
include_once __DIR__ . '/includes/db.php';

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
    <title>Review & Publish Ride — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 620px; margin: 0 auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> Fair Cost Split</span>
                <span class="fr-badge fr-badge-primary">Estimated <?php echo $distance_km; ?> km</span>
            </div>

            <h2 style="font-size:24px; font-weight:800; color:var(--text-main); margin-bottom:6px;">
                Trip & Fare Review
            </h2>
            <p style="font-size:14px; color:var(--text-muted); margin-bottom:20px;">
                Confirm your ride details and passenger contribution before publishing.
            </p>

            <?php if (!empty($error)): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Fair-Share Fare Tag -->
            <div class="fair-fare-meter" style="margin-bottom: 20px;">
                <div>
                    <div class="fare-subtext">Suggested Fair Contribution per Seat</div>
                    <div class="fare-amount">₹<?php echo $suggested_fare; ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:12px; color:var(--text-muted);">Route Fuel Cost: ₹<?php echo $total_fuel_cost; ?></div>
                    <div style="font-size:11.5px; color:var(--eco); font-weight:700; margin-top:2px;">50% Driver / 50% Passenger Split</div>
                </div>
            </div>

            <!-- Route Nodes -->
            <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:18px; margin-bottom:20px;">
                <div class="wayfinder-route" style="margin: 0 0 14px 0;">
                    <div class="route-stop origin">
                        <div class="stop-beacon"></div>
                        <div class="stop-label">Pickup Location</div>
                        <div class="stop-name"><?php echo htmlspecialchars($origin); ?></div>
                    </div>
                    <?php if (!empty($via_route_name)): ?>
                        <div class="route-stop waypoint">
                            <div class="stop-beacon"></div>
                            <div class="stop-label">Corridor Waypoint</div>
                            <div class="stop-name" style="font-size:13.5px; color:var(--primary);">Via <?php echo htmlspecialchars($via_route_name); ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="route-stop destination">
                        <div class="stop-beacon"></div>
                        <div class="stop-label">Dropoff Location</div>
                        <div class="stop-name"><?php echo htmlspecialchars($destination); ?></div>
                    </div>
                </div>

                <div class="fr-grid-2" style="border-top:1px solid var(--border-subtle); padding-top:12px; font-size:13px; color:var(--text-muted);">
                    <div>📅 <?php echo $ride_date; ?> at <?php echo $ride_time; ?></div>
                    <div>🏎️ <?php echo htmlspecialchars($vehicle_model); ?></div>
                    <div>💺 <?php echo $seats_available; ?> Seat(s) Available</div>
                    <div>🪖 <?php echo $helmet_provided ? 'Spare Helmet Provided' : 'Bring Own Helmet'; ?></div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="final_price" value="<?php echo $suggested_fare; ?>">
                <button type="submit" name="confirm_post" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg" style="margin-bottom: 12px;">
                    Publish Ride to Marketplace <i class='bx bx-check-circle'></i>
                </button>
            </form>

            <a href="<?php echo $waShareUrl; ?>" target="_blank" class="fr-btn fr-btn-eco fr-btn-block">
                <i class='bx bxl-whatsapp' style="font-size:20px;"></i> Share Preview to WhatsApp Campus Groups
            </a>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
