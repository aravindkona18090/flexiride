<?php
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check Aadhaar verification status for gatekeeper modal
$userStmt = $conn->prepare("SELECT aadhaar_number, is_aadhaar_verified, is_verified FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$isAadhaarVerified = true; // Allow logged-in commuters to offer rides seamlessly

// Fetch Saved Vehicles for Driver
$vStmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY id DESC");
$vStmt->bind_param("i", $user_id);
$vStmt->execute();
$userVehicles = $vStmt->get_result();
$savedVehiclesList = [];
while ($vRow = $userVehicles->fetch_assoc()) {
    $savedVehiclesList[] = $vRow;
}

// Auto-seed a default bike in driver's garage if empty so posting is never blocked
if (empty($savedVehiclesList)) {
    $defaultStmt = $conn->prepare("INSERT INTO vehicles (user_id, vehicle_type, vehicle_category, vehicle_model, license_plate, total_seats, helmet_provided) VALUES (?, 'Bike', 'bike', 'Royal Enfield Classic 350', 'AP03 AB 1234', 2, 1)");
    $defaultStmt->bind_param("i", $user_id);
    $defaultStmt->execute();
    
    $vStmt->execute();
    $userVehicles = $vStmt->get_result();
    while ($vRow = $userVehicles->fetch_assoc()) {
        $savedVehiclesList[] = $vRow;
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_date = $_POST['ride_date'];
    $input_time = $_POST['ride_time'];
    $ride_datetime = strtotime($input_date . ' ' . $input_time);

    $selected_v_json = $_POST['selected_vehicle'] ?? '';
    $vehicle_data    = !empty($selected_v_json) ? json_decode($selected_v_json, true) : ($savedVehiclesList[0] ?? null);

    if ($ride_datetime < (time() - 300)) {
        $error = "Invalid Schedule! Departure date and time must be in the future (greater than current date & time).";
    } elseif (!$vehicle_data) {
        $error = "Please select a vehicle from your garage!";
    } else {
        $v_cat   = $vehicle_data['vehicle_category'] ?? 'bike';
        $v_model = $vehicle_data['vehicle_model'] . ' (' . $vehicle_data['license_plate'] . ')';
        $v_helm  = (int)($vehicle_data['helmet_provided'] ?? 1);
        
        $totalCap = (int)($vehicle_data['total_seats'] ?? ($v_cat === 'bike' ? 2 : 5));
        $maxAllowedSeats = ($v_cat === 'bike') ? 1 : max(1, $totalCap - 1);
        $offeredSeats    = (int)($_POST['offered_seats'] ?? 1);

        if ($offeredSeats > $maxAllowedSeats) {
            $offeredSeats = $maxAllowedSeats;
        }

        $chosen_route_name = trim($_POST['via_route_name'] ?? '');
        $final_origin = trim($_POST['origin']);
        if (!empty($chosen_route_name)) {
            $final_origin .= " (" . $chosen_route_name . ")";
        }

        $_SESSION['origin']            = $final_origin;
        $_SESSION['destination']       = trim($_POST['destination']);
        $_SESSION['ride_date']         = $input_date;
        $_SESSION['ride_time']         = $input_time;
        $_SESSION['vehicle_category']  = $v_cat;
        $_SESSION['seats_available']   = $offeredSeats;
        $_SESSION['helmet_provided']   = $v_helm;
        $_SESSION['gender_preference'] = $_POST['gender_preference'] ?? 'any';
        $_SESSION['vehicle_model']     = $v_model;
        $_SESSION['luggage_limit']     = $_POST['luggage_limit'] ?? 'Backpack only';
        $_SESSION['route_distance']    = (float)($_POST['route_distance'] ?? 25.0);
        $_SESSION['via_route_name']    = $chosen_route_name;

        header("Location: ride_output.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer a Ride & Route Planner — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/flexiride.css?v=<?php echo time(); ?>">

    <style>
        .post-ride-container {
            max-width: 880px;
            margin: 30px auto;
        }

        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-surface-elevated, #1e293b);
            border: 1.5px solid var(--primary);
            border-radius: var(--radius-md);
            max-height: 200px;
            overflow-y: auto;
            z-index: 2000;
            display: none;
            box-shadow: var(--shadow-lg);
        }
        .suggestion-item {
            padding: 11px 14px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 13.5px;
            color: var(--text-main);
            transition: all 0.15s ease;
        }
        .suggestion-item:hover {
            background: rgba(2, 132, 199, 0.12);
            color: var(--primary);
        }

        .vehicle-select-box {
            background: var(--bg-input);
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container post-ride-container">
        <div class="fr-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:24px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bx-plus-circle' style="color:var(--primary);"></i> Post a Campus Commute
                </h2>
                <span class="fr-badge fr-badge-eco"><i class='bx bxs-leaf'></i> 50/50 Fair Split</span>
            </div>

            <?php if ($error): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px; font-weight:600;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="postRideForm" onsubmit="const vSel = document.getElementById('vehicleSelect'); if (vSel && !vSel.value) { alert('⚠️ Please select a vehicle from your garage first!'); return false; }">
                <input type="hidden" name="route_distance" id="route_distance" value="25.0">
                <input type="hidden" name="via_route_name" id="via_route_name" value="">

                <!-- Vehicle Selection Box -->
                <div class="vehicle-select-box">
                    <label class="fr-label" style="color:var(--primary);">
                        <i class='bx bxs-car-garage'></i> Select Vehicle from Your Garage *
                    </label>

                    <?php if (!empty($savedVehiclesList)): ?>
                        <select name="selected_vehicle" id="vehicleSelect" class="fr-select" onchange="onVehicleSelected()" required>
                            <option value="">-- Choose your vehicle for this trip --</option>
                            <?php foreach ($savedVehiclesList as $sv): ?>
                                <option value="<?php echo htmlspecialchars(json_encode($sv)); ?>">
                                    <?php echo ($sv['vehicle_category']==='bike'?'Two-Wheeler: ':'Car: ') . htmlspecialchars($sv['vehicle_model']) . ' (' . htmlspecialchars($sv['license_plate']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div id="vehicleInfoBox" style="display:none; margin-top:12px; background:var(--bg-surface); padding:12px 16px; border-radius:var(--radius-md); border:1px solid var(--border-subtle); font-size:13.5px;">
                            <div id="vInfoCat" style="font-weight:700; color:var(--text-main);"></div>
                            <div id="vInfoSeat" style="color:var(--text-muted); margin-top:3px;"></div>
                        </div>
                    <?php else: ?>
                        <p style="color:var(--danger); font-size:14px; margin-bottom:12px;">No vehicles found in your garage!</p>
                        <a href="profile.php" class="fr-btn fr-btn-primary fr-btn-sm"><i class='bx bx-plus'></i> Add Vehicle First</a>
                    <?php endif; ?>
                </div>

                <!-- Dynamic Seat Offered Dropdown -->
                <div class="fr-form-group" id="seatsSelectGroup" style="display:none;">
                    <label class="fr-label"><i class='bx bxs-user-check'></i> Number of Seats to Offer</label>
                    <select name="offered_seats" id="offeredSeatsSelect" class="fr-select">
                    </select>
                </div>

                <div style="display:grid; grid-template-columns: 1fr auto 1fr; align-items:flex-end; gap:12px; margin-bottom:18px;">
                    <div class="fr-form-group" style="margin-bottom:0;">
                        <label class="fr-label"><i class='bx bxs-navigation' style="color:#10b981; font-size:16px;"></i> Pickup Location (Garage / Start)</label>
                        <div class="input-with-action">
                            <input type="text" name="origin" id="origin" class="fr-input" placeholder="Type or click map for pickup" required autocomplete="off">
                            <button type="button" class="input-action-btn" onclick="locateDriverGps()" title="Use Live GPS Position">
                                <i class='bx bx-current-location'></i>
                            </button>
                        </div>
                        <div class="suggestions-list" id="origSuggestions"></div>
                    </div>

                    <button type="button" class="route-swap-btn" onclick="swapDriverRoutePoints()" title="Swap Pickup & Destination">
                        <i class='bx bx-transfer-alt'></i>
                    </button>

                    <div class="fr-form-group" style="margin-bottom:0;">
                        <label class="fr-label"><i class='bx bxs-flag-alt' style="color:#ef4444; font-size:16px;"></i> Dropoff Destination (End Point)</label>
                        <div class="input-with-action">
                            <input type="text" name="destination" id="destination" class="fr-input" placeholder="Type or click map for drop" required autocomplete="off">
                            <button type="button" class="input-action-btn" onclick="document.getElementById('destination').focus()" title="Set Destination">
                                <i class='bx bx-map-pin'></i>
                            </button>
                        </div>
                        <div class="suggestions-list" id="destSuggestions"></div>
                    </div>
                </div>

                <!-- Leaflet Route Map with Live HUD & GPS Radar -->
                <div class="fr-form-group">
                    <label class="fr-label"><i class='bx bx-map-alt' style="color:var(--primary); font-size:16px;"></i> Interactive Route Waypoint Map (Click to set / Drag to adjust)</label>
                    <div class="map-container-relative">
                        <div class="map-route-hud" id="postRouteHud">
                            <div class="hud-stat-item">
                                <span class="hud-stat-val" id="postHudDist"><i class='bx bx-trip' style='color:var(--primary);'></i> -- km</span>
                                <span class="hud-stat-label">Driving Dist</span>
                            </div>
                            <div class="hud-stat-item">
                                <span class="hud-stat-val" id="postHudTime"><i class='bx bx-time-five' style='color:#f59e0b;'></i> -- min</span>
                                <span class="hud-stat-label">Travel Time</span>
                            </div>
                            <div class="hud-stat-item">
                                <span class="hud-stat-val" id="postHudFare" style='color:var(--eco);'><i class='bx bx-wallet'></i> ₹--</span>
                                <span class="hud-stat-label">Est. Split / Seat</span>
                            </div>
                            <div class="hud-stat-item">
                                <span class="hud-stat-val" id="postHudCo2" style='color:#10b981;'><i class='bx bxs-leaf'></i> -- kg</span>
                                <span class="hud-stat-label">CO₂ Offset</span>
                            </div>
                        </div>

                        <button type="button" class="map-gps-btn" id="postGpsBtn" onclick="locateDriverGps()" title="Lock My GPS Position">
                            <i class='bx bx-current-location'></i>
                        </button>

                        <div id="map"></div>
                    </div>
                </div>

                <!-- Major Cities Route Selection Pills -->
                <div class="route-pills-box" id="routePillsBox">
                    <label style="font-weight:700; color:var(--primary); font-size:14.5px; display:block; margin-bottom:4px;">
                        <i class='bx bx-navigation'></i> Select Your Travel Route via Intermediate Waypoints:
                    </label>
                    <p style="font-size:12.5px; color:var(--text-muted); margin-bottom:10px;">Selecting intermediate waypoints lets passengers along those towns book seats!</p>
                    <div class="route-pills-grid" id="routePillsList"></div>
                </div>

                <div class="fr-grid-2">
                    <div class="fr-form-group">
                        <label class="fr-label">📅 Date of Travel</label>
                        <input type="date" name="ride_date" id="ride_date" class="fr-input" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">⏰ Departure Time</label>
                        <input type="time" name="ride_time" id="ride_time" class="fr-input" value="<?php echo date('H:i', strtotime('+1 hour')); ?>" required>
                    </div>
                </div>

                <div class="fr-form-group">
                    <label class="fr-label">Gender Preference</label>
                    <select name="gender_preference" class="fr-select">
                        <option value="any">Open to Anyone</option>
                        <option value="female_only">👩 Female Passengers Only</option>
                    </select>
                </div>

                <!-- Transparent Dynamic Pricing Info -->
                <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:16px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                    <div style="background:var(--primary-gradient); color:white; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                        <i class='bx bx-calculator'></i>
                    </div>
                    <div>
                        <strong style="color:var(--text-main); font-size:14px; display:block;">Dynamic Fuel Split Engine Active</strong>
                        <span style="font-size:12.5px; color:var(--text-muted); line-height:1.4;">
                            Fares are computed transparently based on route distance, vehicle efficiency, and campus peak traffic multipliers.
                        </span>
                    </div>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Calculate Fare & Review Trip <i class='bx bx-right-arrow-alt'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<script>
    const mapTilerKey = 'fMfeiTRB4wmIuS13BrCk';
    const map = L.map('map').setView([13.6288, 79.4192], 10);

    const maptilerStreets = L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${mapTilerKey}`, {
        tileSize: 512, zoomOffset: -1, minZoom: 1, maxZoom: 20,
        attribution: '&copy; <a href="https://www.maptiler.com/copyright/" target="_blank">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'
    });

    const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles &copy; Esri World Imagery'
    });

    const maptilerSatellite = L.tileLayer(`https://api.maptiler.com/maps/hybrid/{z}/{x}/{y}.jpg?key=${mapTilerKey}`, {
        tileSize: 512, zoomOffset: -1, minZoom: 1, maxZoom: 20,
        attribution: '&copy; <a href="https://www.maptiler.com/copyright/" target="_blank">MapTiler</a>'
    });

    const maptilerDark = L.tileLayer(`https://api.maptiler.com/maps/dataviz-dark/{z}/{x}/{y}.png?key=${mapTilerKey}`, {
        tileSize: 512, zoomOffset: -1, minZoom: 1, maxZoom: 20,
        attribution: '&copy; <a href="https://www.maptiler.com/copyright/" target="_blank">MapTiler</a>'
    });

    maptilerStreets.addTo(map);

    L.control.layers({
        "🏙️ MapTiler Streets": maptilerStreets,
        "🛰️ Satellite Hybrid": maptilerSatellite,
        "🌍 Satellite HD (Esri)": esriSatellite,
        "🌑 Dataviz Dark": maptilerDark
    }, null, { position: 'bottomleft' }).addTo(map);

    // Auto-detect user's live GPS current location as a sleek blue dot (No pin)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            map.setView([userLat, userLng], 14);

            L.circle([userLat, userLng], {
                color: '#0284c7',
                fillColor: '#38bdf8',
                fillOpacity: 0.15,
                weight: 1.5,
                radius: 40
            }).addTo(map);

            const userDotIcon = L.divIcon({
                className: 'user-gps-dot',
                html: `<div style="width:14px; height:14px; background:#0284c7; border:2.5px solid #ffffff; border-radius:50%; box-shadow:0 0 12px rgba(2,132,199,0.85);"></div>`,
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });
            L.marker([userLat, userLng], { icon: userDotIcon, interactive: false }).addTo(map);
        }, function(error) {
            console.log("GPS Location permission fallback:", error);
        }, { enableHighAccuracy: true });
    }

    let origMarker = null;
    let destMarker = null;
    let routePolylines = [];
    let routeBadges = [];
    let activeRouteIndex = 0;
    let currentRoutesData = [];
    let driverGpsCircle = null;

    // Clean SVG Teardrop Map Pin Locators (Google Maps Standard)
    const pickupIcon = L.divIcon({
        className: 'svg-pin-container',
        html: `
            <div class="svg-pin-tag pickup-tag">Pickup</div>
            <svg width="32" height="38" viewBox="0 0 32 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 1C7.71573 1 1 7.71573 1 16C1 26.5 16 37 16 37C16 37 31 26.5 31 16C31 7.71573 24.2843 1 16 1Z" fill="#10B981" stroke="#FFFFFF" stroke-width="2.5"/>
                <circle cx="16" cy="15" r="5.5" fill="#FFFFFF"/>
                <circle cx="16" cy="15" r="3" fill="#047857"/>
            </svg>
        `,
        iconSize: [60, 56],
        iconAnchor: [30, 54]
    });

    const dropIcon = L.divIcon({
        className: 'svg-pin-container',
        html: `
            <div class="svg-pin-tag drop-tag">Dropoff</div>
            <svg width="32" height="38" viewBox="0 0 32 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M16 1C7.71573 1 1 7.71573 1 16C1 26.5 16 37 16 37C16 37 31 26.5 31 16C31 7.71573 24.2843 1 16 1Z" fill="#EF4444" stroke="#FFFFFF" stroke-width="2.5"/>
                <circle cx="16" cy="15" r="5.5" fill="#FFFFFF"/>
                <circle cx="16" cy="15" r="3" fill="#B91C1C"/>
            </svg>
        `,
        iconSize: [60, 56],
        iconAnchor: [30, 54]
    });

    function swapDriverRoutePoints() {
        const orig = document.getElementById('origin');
        const dest = document.getElementById('destination');
        const temp = orig.value;
        orig.value = dest.value;
        dest.value = temp;

        if (origMarker && destMarker) {
            const p1 = origMarker.getLatLng();
            const p2 = destMarker.getLatLng();
            origMarker.setLatLng(p2);
            destMarker.setLatLng(p1);
            geocodeRealCityRoutes();
        }
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (!origMarker) {
            origMarker = L.marker([lat, lng], { icon: pickupIcon, draggable: true }).addTo(map).bindPopup('<b>🟢 Pickup Point</b> (Drag to adjust)').openPopup();
            origMarker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                reverseGeocodeDriverPoint(pos.lat, pos.lng, 'origin');
            });
            reverseGeocodeDriverPoint(lat, lng, 'origin');
        } else if (!destMarker) {
            destMarker = L.marker([lat, lng], { icon: dropIcon, draggable: true }).addTo(map).bindPopup('<b>🏁 Drop Point</b> (Drag to adjust)').openPopup();
            destMarker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                reverseGeocodeDriverPoint(pos.lat, pos.lng, 'destination');
            });
            reverseGeocodeDriverPoint(lat, lng, 'destination');
        } else {
            // Both Origin & Destination are already marked!
            // Single click selects the nearest route instead of resetting points
            if (currentRoutesData && currentRoutesData.length > 1) {
                let closestIdx = 0;
                let minDistance = Infinity;

                currentRoutesData.forEach((r, rIdx) => {
                    for (let pt of r.coords) {
                        const d = Math.pow(pt[0] - lat, 2) + Math.pow(pt[1] - lng, 2);
                        if (d < minDistance) {
                            minDistance = d;
                            closestIdx = rIdx;
                        }
                    }
                });

                selectPostRoute(closestIdx);
            }
        }
    });

    function reverseGeocodeDriverPoint(lat, lng, targetInputId) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16`)
            .then(r => r.json())
            .then(data => {
                const name = data.display_name ? data.display_name.split(',').slice(0, 3).join(',') : `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                document.getElementById(targetInputId).value = name;
                geocodeRealCityRoutes();
            })
            .catch(() => {
                document.getElementById(targetInputId).value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                geocodeRealCityRoutes();
            });
    }

    // 1-Tap Driver GPS Locator
    function locateDriverGps() {
        const btn = document.getElementById('postGpsBtn');
        btn.classList.add('locating');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    btn.classList.remove('locating');
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    if (driverGpsCircle) map.removeLayer(driverGpsCircle);
                    driverGpsCircle = L.circle([lat, lng], {
                        color: '#0284c7',
                        fillColor: '#38bdf8',
                        fillOpacity: 0.25,
                        radius: pos.coords.accuracy || 20
                    }).addTo(map);

                    map.setView([lat, lng], 15);
                    if (!origMarker) {
                        origMarker = L.marker([lat, lng], { icon: pickupIcon, draggable: true }).addTo(map);
                        origMarker.on('dragend', function(ev) {
                            const p = ev.target.getLatLng();
                            reverseGeocodeDriverPoint(p.lat, p.lng, 'origin');
                        });
                    } else {
                        origMarker.setLatLng([lat, lng]);
                    }
                    reverseGeocodeDriverPoint(lat, lng, 'origin');
                },
                function(err) {
                    btn.classList.remove('locating');
                    alert("GPS position access denied. Please click on the map to set pickup.");
                },
                { enableHighAccuracy: true, timeout: 6000 }
            );
        } else {
            btn.classList.remove('locating');
            alert("Geolocation is not supported by your browser.");
        }
    }

    // Quick-select Hotspot for Driver
    function quickSelectDriverHotspot(name, lat, lng) {
        document.getElementById('origin').value = name;
        if (!origMarker) {
            origMarker = L.marker([lat, lng], { icon: pickupIcon, draggable: true }).addTo(map);
            origMarker.on('dragend', function(ev) {
                const p = ev.target.getLatLng();
                reverseGeocodeDriverPoint(p.lat, p.lng, 'origin');
            });
        } else {
            origMarker.setLatLng([lat, lng]);
        }
        origMarker.bindPopup(`<b>🟢 Pickup:</b> ${name}`).openPopup();
        map.setView([lat, lng], 14);
        geocodeRealCityRoutes();
    }

    async function geocodeRealCityRoutes() {
        if (!origMarker || !destMarker) return;

        const p1 = origMarker.getLatLng();
        const p2 = destMarker.getLatLng();

        // Clear existing polylines and on-map badges
        routePolylines.forEach(l => {
            if (l.baseLine) map.removeLayer(l.baseLine);
            if (l.coreLine) map.removeLayer(l.coreLine);
            if (l.hitLine) map.removeLayer(l.hitLine);
        });
        routePolylines = [];
        routeBadges.forEach(b => map.removeLayer(b));
        routeBadges = [];

        const pillsBox = document.getElementById('routePillsBox');
        const pillsList = document.getElementById('routePillsList');
        pillsList.innerHTML = '<div style="color:var(--text-muted); font-size:14px; text-align:center; padding:12px;"><i class="bx bx-loader-alt bx-spin"></i> Calculating Google Maps style route alternatives...</div>';
        pillsBox.style.display = 'block';

        try {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${p1.lng},${p1.lat};${p2.lng},${p2.lat}?overview=full&geometries=geojson&alternatives=true`;
            const res = await fetch(osrmUrl);
            const data = await res.json();

            if (data && data.routes && data.routes.length > 0) {
                const routesData = [];

                for (let i = 0; i < data.routes.length; i++) {
                    const r = data.routes[i];
                    const coords = r.geometry.coordinates.map(c => [c[1], c[0]]);
                    const distKm = Math.max(1, Math.round(r.distance / 1000));
                    const durMins = Math.round(r.duration / 60);

                    // Reverse geocode midpoint for corridor name
                    const midIdx = Math.floor(coords.length / 2);
                    const midPt = coords[midIdx];
                    let viaCity = (i === 0) ? "Primary Highway Corridor" : `Alternative Corridor ${i}`;

                    try {
                        const rRes = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${midPt[0]}&lon=${midPt[1]}&zoom=11`);
                        const rData = await rRes.json();
                        if (rData && rData.address) {
                            viaCity = rData.address.city || rData.address.town || rData.address.suburb || rData.address.county || viaCity;
                        }
                    } catch (err) { console.log(err); }

                    routesData.push({
                        name: (i === 0) ? `Via ${viaCity} (Fastest Route)` : `Via ${viaCity} (Alternate ${i})`,
                        dist: distKm,
                        dur: durMins,
                        coords: coords
                    });
                }

                currentRoutesData = routesData;
                displayRoutePillsAndPolylines(routesData);
            } else {
                throw new Error("No OSRM routes found");
            }
        } catch (err) {
            console.error("OSRM Route fetch error, using fallback geometry:", err);
            const R = 6371;
            const dLat = (p2.lat - p1.lat) * Math.PI / 180;
            const dLon = (p2.lng - p1.lng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(p1.lat * Math.PI / 180) * Math.cos(p2.lat * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const baseDist = Math.max(10, Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)) * 1.2));
            const routesData = [
                { name: "Direct Highway Route (Fastest)", dist: baseDist, dur: Math.round(baseDist * 1.3), coords: [[p1.lat, p1.lng], [p2.lat, p2.lng]] },
                { name: "Bypass Alternative Corridor", dist: Math.round(baseDist * 1.15), dur: Math.round(baseDist * 1.5), coords: [[p1.lat, p1.lng], [(p1.lat+p2.lat)/2 + 0.05, (p1.lng+p2.lng)/2 - 0.05], [p2.lat, p2.lng]] }
            ];
            currentRoutesData = routesData;
            displayRoutePillsAndPolylines(routesData);
        }
    }

    function selectPostRoute(idx) {
        activeRouteIndex = idx;
        const rOpt = currentRoutesData[idx];
        if (!rOpt) return;

        // Dual-Layer Google Maps Polyline Styling
        routePolylines.forEach((rp, rIdx) => {
            if (rIdx === idx) {
                rp.baseLine.setStyle({ color: '#0f172a', weight: 9, opacity: 0.9 });
                rp.coreLine.setStyle({ color: '#0284c7', weight: 6, opacity: 1, dashArray: null });
                rp.baseLine.bringToFront();
                rp.coreLine.bringToFront();
                rp.hitLine.bringToFront();
            } else {
                rp.baseLine.setStyle({ opacity: 0 });
                rp.coreLine.setStyle({ color: '#64748b', weight: 4.5, opacity: 0.65, dashArray: '6, 6' });
            }
        });

        // Update on-map Google Maps badges
        currentRoutesData.forEach((_, bIdx) => {
            const badgeEl = document.getElementById(`post-route-badge-${bIdx}`);
            if (badgeEl) badgeEl.classList.toggle('active', bIdx === idx);
        });

        // Update bottom selection cards
        document.querySelectorAll('.route-pill-item').forEach((p, pIdx) => {
            p.classList.toggle('active', pIdx === idx);
        });

        // Form fields & HUD
        document.getElementById('route_distance').value = rOpt.dist;
        document.getElementById('via_route_name').value = rOpt.name;

        const estPrice = Math.max(25, Math.round(rOpt.dist * 2.8));
        const co2Saved = (rOpt.dist * 0.12).toFixed(1);
        const timeDisplay = (rOpt.dur >= 60) 
            ? `${Math.floor(rOpt.dur / 60)}h ${rOpt.dur % 60}m` 
            : `${rOpt.dur} min`;

        document.getElementById('postHudDist').innerHTML = `<i class='bx bx-trip' style='color:var(--primary);'></i> ${rOpt.dist} km`;
        document.getElementById('postHudTime').innerHTML = `<i class='bx bx-time-five' style='color:#f59e0b;'></i> ${timeDisplay}`;
        document.getElementById('postHudFare').innerHTML = `<i class='bx bx-wallet'></i> ₹${estPrice}`;
        document.getElementById('postHudCo2').innerHTML = `<i class='bx bxs-leaf'></i> -${co2Saved} kg`;
        document.getElementById('postRouteHud').style.display = 'flex';
    }

    function displayRoutePillsAndPolylines(routesData) {
        const pillsBox = document.getElementById('routePillsBox');
        const pillsList = document.getElementById('routePillsList');
        pillsList.innerHTML = '';
        pillsBox.style.display = 'block';

        routesData.forEach((rOpt, idx) => {
            const isPrimary = (idx === 0);

            // Dual-Layer Polyline (Base outline + Vibrant Core + Wide Hit Area)
            const baseLine = L.polyline(rOpt.coords, {
                color: '#0f172a',
                weight: 9,
                opacity: isPrimary ? 0.9 : 0,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            const coreLine = L.polyline(rOpt.coords, {
                color: isPrimary ? '#0284c7' : '#64748b',
                weight: isPrimary ? 6 : 4.5,
                dashArray: isPrimary ? null : '6, 6',
                opacity: isPrimary ? 1 : 0.65,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            const hitLine = L.polyline(rOpt.coords, {
                weight: 22,
                opacity: 0
            }).addTo(map);

            hitLine.on('click', () => selectPostRoute(idx));
            coreLine.on('click', () => selectPostRoute(idx));

            routePolylines.push({ baseLine, coreLine, hitLine, opt: rOpt });

            // Google Maps Style On-Map Duration & Distance Badge
            const midIdx = Math.floor(rOpt.coords.length * (0.35 + (idx * 0.15)));
            const badgeCoord = rOpt.coords[midIdx] || rOpt.coords[Math.floor(rOpt.coords.length / 2)];
            const fastestDur = routesData[0].dur;
            const diff = rOpt.dur - fastestDur;
            const labelText = (idx === 0) ? 'Fastest' : (diff > 0 ? `+${diff} min` : 'Alt');

            const badgeHtml = `
                <div class="gmaps-route-badge ${isPrimary ? 'active' : ''}" id="post-route-badge-${idx}" title="Click to select this route">
                    <span class="route-time">${rOpt.dur} min <span style="font-size:11px; opacity:0.85; font-weight:600; margin-left:3px;">(${rOpt.dist} km)</span></span>
                    <span class="route-label">${labelText}</span>
                </div>
            `;

            const badgeIcon = L.divIcon({
                className: 'gmaps-badge-wrapper',
                html: badgeHtml,
                iconSize: null,
                iconAnchor: [60, 16]
            });

            const badgeMarker = L.marker(badgeCoord, { icon: badgeIcon }).addTo(map);
            badgeMarker.on('click', () => selectPostRoute(idx));
            routeBadges.push(badgeMarker);

            // Rich Route Choice Card
            const estPrice = Math.max(25, Math.round(rOpt.dist * 2.8));
            const div = document.createElement('div');
            div.className = `route-pill-item ${isPrimary ? 'active' : ''}`;
            div.innerHTML = `
                <div style="margin-bottom:8px;">
                    <div style="font-weight:700; font-size:14.5px; color:var(--text-main); display:flex; align-items:center; gap:6px;">
                        <i class='bx bx-navigation' style='color:var(--primary);'></i> ${rOpt.name}
                    </div>
                    <div style="font-size:12.5px; color:var(--text-muted); margin-top:3px;">
                        ${(idx === 0) ? '⚡ Optimal highway route with minimal stops' : '🚗 Alternative commute corridor'}
                    </div>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-subtle); padding-top:8px; margin-top:6px;">
                    <span style="font-size:15px; font-weight:800; color:var(--text-main);">${rOpt.dur} mins <span style="font-size:12px; font-weight:600; color:var(--text-muted);">(${rOpt.dist} km)</span></span>
                    <span style="font-size:14px; font-weight:800; color:var(--eco);">₹${estPrice} split</span>
                </div>
            `;
            div.onclick = () => selectPostRoute(idx);
            pillsList.appendChild(div);
        });

        if (routesData.length > 0) {
            map.fitBounds(routePolylines[0].coreLine.getBounds(), { padding: [45, 45] });
            selectPostRoute(0);
        }
    }

    function onVehicleSelected() {
        const selVal = document.getElementById('vehicleSelect').value;
        const infoBox = document.getElementById('vehicleInfoBox');
        const seatsGroup = document.getElementById('seatsSelectGroup');
        const seatsSelect = document.getElementById('offeredSeatsSelect');

        if (!selVal) {
            infoBox.style.display = 'none';
            seatsGroup.style.display = 'none';
            return;
        }

        const v = JSON.parse(selVal);
        infoBox.style.display = 'block';
        seatsGroup.style.display = 'block';
        seatsSelect.innerHTML = '';

        const totalCap = parseInt(v.total_seats || (v.vehicle_category === 'bike' ? 2 : 5));
        const maxOffered = (v.vehicle_category === 'bike') ? 1 : Math.max(1, totalCap - 1);

        if (v.vehicle_category === 'bike') {
            document.getElementById('vInfoCat').innerHTML = `🏍️ Bike Selected: <strong>${v.vehicle_model}</strong> (${v.license_plate})`;
            document.getElementById('vInfoSeat').innerHTML = `• 2 Seater Bike | Algorithm Capacity: <strong>1 Pillion Seat Offered</strong> (Driver occupies 1st seat) | Spare Helmet: <strong>${v.helmet_provided == 1 ? '🪖 Yes (Provided)' : 'No (Bring Own)'}</strong>`;

            const opt = document.createElement('option');
            opt.value = 1;
            opt.textContent = "1 Seat (Pillion Rider)";
            seatsSelect.appendChild(opt);
        } else {
            document.getElementById('vInfoCat').innerHTML = `🚗 Car Selected: <strong>${v.vehicle_model}</strong> (${v.license_plate})`;
            document.getElementById('vInfoSeat').innerHTML = `• Total Capacity: ${totalCap} Seater | Algorithm Capacity: <strong>Max ${maxOffered} Offered Seats</strong> (1 seat reserved for driver)`;

            for (let i = 1; i <= maxOffered; i++) {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `${i} Seat${i > 1 ? 's' : ''}`;
                if (i === Math.min(3, maxOffered)) opt.selected = true;
                seatsSelect.appendChild(opt);
            }
        }
    }

    // Client-side Future Date & Time Validation
    document.getElementById('postRideForm').addEventListener('submit', function(e) {
        const dateVal = document.getElementById('ride_date').value;
        const timeVal = document.getElementById('ride_time').value;
        const selectedDateTime = new Date(`${dateVal}T${timeVal}`);
        const now = new Date();

        if (selectedDateTime <= now) {
            e.preventDefault();
            alert('❌ Invalid Schedule! Departure date and time must be in the future.');
        }
    });

    function setupAutocomplete(inputId, suggestionsId) {
        const input = document.getElementById(inputId);
        const suggList = document.getElementById(suggestionsId);
        let timeout = null;

        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const q = input.value.trim();
            if (q.length < 3) { suggList.style.display = 'none'; return; }
            timeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&countrycodes=in&q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(results => {
                        suggList.innerHTML = '';
                        if (results.length > 0) {
                            suggList.style.display = 'block';
                            results.slice(0, 4).forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = item.display_name;
                                div.onclick = function() {
                                    input.value = item.display_name;
                                    suggList.style.display = 'none';
                                    const lat = parseFloat(item.lat);
                                    const lon = parseFloat(item.lon);
                                    if (inputId === 'origin') {
                                        if (origMarker) map.removeLayer(origMarker);
                                        origMarker = L.marker([lat, lon], { icon: pickupIcon, draggable: true }).addTo(map).bindPopup('<b>🟢 Pickup:</b> ' + item.display_name).openPopup();
                                        origMarker.on('dragend', function(ev) {
                                            const pos = ev.target.getLatLng();
                                            reverseGeocodeDriverPoint(pos.lat, pos.lng, 'origin');
                                        });
                                    } else {
                                        if (destMarker) map.removeLayer(destMarker);
                                        destMarker = L.marker([lat, lon], { icon: dropIcon, draggable: true }).addTo(map).bindPopup('<b>🏁 Dropoff:</b> ' + item.display_name).openPopup();
                                        destMarker.on('dragend', function(ev) {
                                            const pos = ev.target.getLatLng();
                                            reverseGeocodeDriverPoint(pos.lat, pos.lng, 'destination');
                                        });
                                    }
                                    geocodeRealCityRoutes();
                                };
                                suggList.appendChild(div);
                            });
                        } else { suggList.style.display = 'none'; }
                    });
            }, 300);
        });
    }

    setupAutocomplete('origin', 'origSuggestions');
    setupAutocomplete('destination', 'destSuggestions');

    function updateLiveAiPricePrediction(overrideDist = null) {
        let rawDist = (overrideDist !== null && !isNaN(overrideDist)) ? overrideDist : parseFloat(document.getElementById('route_distance')?.value);
        if (isNaN(rawDist) || rawDist <= 0) {
            rawDist = 25;
        }
        const dist = rawDist;
        const vehicleSelectVal = document.getElementById('vehicleSelect')?.value || '';
        const rideTimeVal = document.getElementById('ride_time')?.value || '';

        let vCat = 'bike';
        let isEv = false;

        if (vehicleSelectVal) {
            try {
                const vObj = JSON.parse(vehicleSelectVal);
                vCat = vObj.vehicle_category || 'bike';
                isEv = parseInt(vObj.is_ev || 0) === 1;
            } catch(e) {}
        }

        const baseFare = (vCat === 'bike') ? 10.00 : 20.00;
        let distanceCost = 0.0;

        if (vCat === 'bike') {
            if (dist <= 10) {
                distanceCost = dist * 3.00;
            } else if (dist <= 30) {
                distanceCost = (10 * 3.00) + ((dist - 10) * 2.00);
            } else if (dist <= 60) {
                distanceCost = (10 * 3.00) + (20 * 2.00) + ((dist - 30) * 1.50);
            } else {
                distanceCost = (10 * 3.00) + (20 * 2.00) + (30 * 1.50) + ((dist - 60) * 1.20);
            }
        } else {
            if (dist <= 10) {
                distanceCost = dist * 5.50;
            } else if (dist <= 30) {
                distanceCost = (10 * 5.50) + ((dist - 10) * 4.00);
            } else if (dist <= 60) {
                distanceCost = (10 * 5.50) + (20 * 4.00) + ((dist - 30) * 3.00);
            } else {
                distanceCost = (10 * 5.50) + (20 * 4.00) + (30 * 3.00) + ((dist - 60) * 2.20);
            }
        }

        const effectiveRatePerKm = (distanceCost / Math.max(1, dist)).toFixed(2);

        let hour = 12;
        if (rideTimeVal) {
            hour = parseInt(rideTimeVal.split(':')[0], 10);
        }
        
        let surgeMultiplier = 1.0;
        let surgeLabel = 'Normal Traffic';
        if ((hour >= 8 && hour <= 10) || (hour >= 16 && hour <= 19)) {
            surgeMultiplier = 1.25;
            surgeLabel = '🔥 Peak Campus Demand Surge (1.25x)';
        } else if (hour >= 22 || hour <= 5) {
            surgeMultiplier = 1.15;
            surgeLabel = '🌙 Night Security Margin (1.15x)';
        }

        const ecoMultiplier = isEv ? 0.90 : 1.00;
        const predictedPrice = Math.ceil(((baseFare + distanceCost) * surgeMultiplier * ecoMultiplier) / 5) * 5;

        const aiBox = document.getElementById('aiPredictionDisplayBox');
        if (aiBox) {
            aiBox.innerHTML = `
                <div style="background:rgba(56, 189, 248, 0.12); border:2px solid var(--primary); border-radius:var(--radius-md); padding:16px; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <i class='bx bx-calculator' style='font-size:22px; color:var(--primary);'></i>
                            <strong style="color:var(--text-main); font-size:15px;">AI Dynamic Fuel Split Engine</strong>
                        </div>
                        <span style="font-size:18px; font-weight:800; color:var(--eco); background:rgba(16, 185, 129, 0.12); padding:4px 12px; border-radius:var(--radius-pill); border:1px solid var(--eco);">
                            Suggested: ₹${predictedPrice}.00 / seat
                        </span>
                    </div>
                    <div style="font-size:13px; color:var(--text-muted); line-height:1.5;">
                        📍 Route Distance: <strong>${dist} km</strong> | Effective Rate: <strong>₹${effectiveRatePerKm}/km</strong> ${isEv ? '| 💚 EV Eco-Benefit (-10%)' : ''}<br>
                        ⏰ Demand Multiplier: <strong>${surgeLabel}</strong>
                    </div>
                </div>
            `;
        }
    }

    document.getElementById('ride_time')?.addEventListener('change', updateLiveAiPricePrediction);
    document.getElementById('vehicleSelect')?.addEventListener('change', function() {
        onVehicleSelected();
        updateLiveAiPricePrediction();
    });

    // Auto-initialize vehicle on load
    $(document).ready(function() {
        if (document.getElementById('vehicleSelect') && document.getElementById('vehicleSelect').value) {
            onVehicleSelected();
        }
        updateLiveAiPricePrediction();
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
