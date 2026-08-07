<?php
include 'db.php';
session_start();

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

$isAadhaarVerified = (!empty($userData['aadhaar_number']) && (($userData['is_aadhaar_verified'] ?? 0) || ($userData['is_verified'] ?? 0)));

// Fetch Saved Vehicles for Driver
$vStmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY id DESC");
$vStmt->bind_param("i", $user_id);
$vStmt->execute();
$userVehicles = $vStmt->get_result();
$savedVehiclesList = [];
while ($vRow = $userVehicles->fetch_assoc()) {
    $savedVehiclesList[] = $vRow;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_date = $_POST['ride_date'];
    $input_time = $_POST['ride_time'];
    $ride_datetime = strtotime($input_date . ' ' . $input_time);

    $selected_v_json = $_POST['selected_vehicle'] ?? '';
    $vehicle_data    = !empty($selected_v_json) ? json_decode($selected_v_json, true) : null;

    if (!$isAadhaarVerified) {
        $error = "Aadhaar Verification Required! Please verify your Aadhaar number in your profile before offering rides.";
    } elseif ($ride_datetime <= time()) {
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
    <title>Post a Ride & Real City Waypoint Builder - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Leaflet OpenStreetMap CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 850px; margin: 40px auto; padding: 0 20px; width: 100%; }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        h2 { font-size: 26px; text-align: center; margin-bottom: 25px; color: var(--text-color); }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); font-size: 15px; outline: none;
        }

        #map { height: 320px; border-radius: 14px; border: 1px solid var(--card-border); margin-bottom: 20px; }

        .suggestions-list {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--input-bg); border: 1px solid var(--primary-color); border-radius: 10px;
            max-height: 180px; overflow-y: auto; z-index: 2000; display: none;
        }
        .suggestion-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid var(--input-border); font-size: 14px; color: var(--text-muted); }
        .suggestion-item:hover { background: var(--primary-color); color: white; }

        .vehicle-select-box {
            background: rgba(56, 189, 248, 0.12); border: 1.5px solid var(--primary-color);
            border-radius: 16px; padding: 20px; margin-bottom: 25px;
        }
        .vehicle-badge-info {
            background: var(--input-bg); border: 1px solid var(--primary-color);
            border-radius: 12px; padding: 14px; margin-top: 12px; font-size: 14px; color: var(--text-color);
        }

        /* Alternate Route Pills Styling */
        .route-pills-box {
            background: var(--input-bg); border: 1.5px solid var(--primary-color);
            border-radius: 16px; padding: 20px; margin-bottom: 20px; display: none;
        }
        .route-pills-grid { display: flex; flex-direction: column; gap: 10px; margin-top: 12px; }
        .route-pill-item {
            padding: 14px 18px; border-radius: 12px; border: 1.5px solid var(--input-border);
            background: var(--card-bg); color: var(--text-color); font-size: 14px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; transition: all 0.3s;
        }
        .route-pill-item.active { border-color: var(--primary-color); background: rgba(56, 189, 248, 0.18); color: var(--primary-color); font-weight: 700; }

        .btn-submit {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }

        /* Aadhaar Verification Modal Gatekeeper */
        .modal-overlay {
            display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(12px);
            z-index: 9999; justify-content: center; align-items: center; padding: 20px;
        }
        .modal-card {
            background: var(--card-bg); border: 1px solid var(--danger-color);
            border-radius: 24px; padding: 35px; max-width: 440px; width: 100%; text-align: center;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Aadhaar Gatekeeper Modal -->
<?php if (!$isAadhaarVerified): ?>
    <div class="modal-overlay">
        <div class="modal-card">
            <i class='bx bxs-shield-x' style="font-size:54px; color:var(--danger-color); margin-bottom:10px;"></i>
            <h3 style="font-size:22px; color:var(--text-color); margin-bottom:10px;">Aadhaar Verification Required</h3>
            <p style="font-size:14px; color:var(--text-muted); line-height:1.5; margin-bottom:20px;">
                To keep the FlexiRide community 100% safe, drivers must verify their 12-digit UIDAI Aadhaar number before offering rides.
            </p>
            <a href="edit_profile.php" class="btn-submit" style="display:block; text-decoration:none;">Verify Aadhaar Now →</a>
        </div>
    </div>
<?php endif; ?>

<div class="container">
    <div class="card">
        <h2>🏍️ Offer a Ride / Multi-City Route Builder</h2>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="postRideForm">
            <input type="hidden" name="route_distance" id="route_distance" value="25.0">
            <input type="hidden" name="via_route_name" id="via_route_name" value="">

            <!-- Vehicle Selection Box (Derived from Garage) -->
            <div class="vehicle-select-box">
                <label style="font-size:15px; font-weight:700; color:var(--primary-color); display:block; margin-bottom:8px;">
                    🏎️ Select Vehicle from Your Garage *
                </label>

                <?php if (!empty($savedVehiclesList)): ?>
                    <select name="selected_vehicle" id="vehicleSelect" onchange="onVehicleSelected()" required style="width:100%; padding:14px; border-radius:10px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-color); font-size:15px; outline:none;">
                        <option value="">-- Choose your vehicle for this ride --</option>
                        <?php foreach ($savedVehiclesList as $sv): ?>
                            <option value="<?php echo htmlspecialchars(json_encode($sv)); ?>">
                                <?php echo ($sv['vehicle_category']==='bike'?'🏍️ Bike: ':'🚗 Car: ') . htmlspecialchars($sv['vehicle_model']) . ' (' . htmlspecialchars($sv['license_plate']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="vehicle-badge-info" id="vehicleInfoBox" style="display:none;">
                        <div id="vInfoCat" style="font-weight:700;"></div>
                        <div id="vInfoSeat" style="margin-top:4px; font-size:13px; color:var(--text-muted);"></div>
                    </div>
                <?php else: ?>
                    <p style="color:var(--danger-color); font-size:14px; margin-bottom:12px;">No vehicles found in your profile garage!</p>
                    <a href="profile.php" class="btn-submit" style="display:inline-block; width:auto; padding:10px 20px; font-size:14px; text-decoration:none;">➕ Add Vehicle to Profile First</a>
                <?php endif; ?>
            </div>

            <!-- Dynamic Seat Offered Dropdown -->
            <div class="form-group" id="seatsSelectGroup" style="display:none;">
                <label>💺 Select Number of Seats Offered to Passengers</label>
                <select name="offered_seats" id="offeredSeatsSelect">
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>📍 Pickup Location (Type or Click Map Pin)</label>
                    <input type="text" name="origin" id="origin" placeholder="e.g. Tirupati / MBU Campus" required autocomplete="off">
                    <div class="suggestions-list" id="origSuggestions"></div>
                </div>
                <div class="form-group">
                    <label>🏁 Drop Location (Type or Click Map Pin)</label>
                    <input type="text" name="destination" id="destination" placeholder="e.g. Bengaluru / Chennai" required autocomplete="off">
                    <div class="suggestions-list" id="destSuggestions"></div>
                </div>
            </div>

            <!-- Multi-Route Leaflet Map -->
            <label style="display:block; margin-bottom:8px; font-size:14px; color:var(--text-muted); font-weight:500;">
                🗺️ Interactive Multi-City Route Map (Extracts Real Major Towns & Pickup Cities)
            </label>
            <div id="map"></div>

            <!-- Major Cities Route Selection Pills -->
            <div class="route-pills-box" id="routePillsBox">
                <label style="font-weight:700; color:var(--primary-color); font-size:16px; display:block; margin-bottom:4px;">
                    📍 Select Your Travel Route via Real Intermediate Cities:
                </label>
                <p style="font-size:13px; color:var(--text-muted); margin-bottom:12px;">Selecting your intermediate cities allows passengers along those towns to book seats!</p>
                <div class="route-pills-grid" id="routePillsList">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>📅 Date of Travel</label>
                    <input type="date" name="ride_date" id="ride_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>⏰ Departure Time</label>
                    <input type="time" name="ride_time" id="ride_time" value="<?php echo date('H:i', strtotime('+30 minutes')); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Gender Preference</label>
                <select name="gender_preference">
                    <option value="any">Open to Anyone</option>
                    <option value="female_only">👩 Female Passengers Only</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Calculate Fare & Preview Route →</button>
        </form>
    </div>
</div>

<script>
    const map = L.map('map').setView([13.6288, 79.4192], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let origMarker = null;
    let destMarker = null;
    let routePolylines = [];

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (!origMarker) {
            origMarker = L.marker([lat, lng]).addTo(map).bindPopup('📍 Pickup Point').openPopup();
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { 
                    if (data.display_name) document.getElementById('origin').value = data.display_name;
                    geocodeRealCityRoutes();
                });
        } else if (!destMarker) {
            destMarker = L.marker([lat, lng]).addTo(map).bindPopup('🏁 Drop Point').openPopup();
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { 
                    if (data.display_name) document.getElementById('destination').value = data.display_name;
                    geocodeRealCityRoutes();
                });
        }
    });

    async function geocodeRealCityRoutes() {
        if (!origMarker || !destMarker) return;

        const p1 = origMarker.getLatLng();
        const p2 = destMarker.getLatLng();

        // Clear existing polylines
        routePolylines.forEach(l => map.removeLayer(l.line));
        routePolylines = [];

        // Calculate Haversine Base Distance
        const R = 6371;
        const dLat = (p2.lat - p1.lat) * Math.PI / 180;
        const dLon = (p2.lng - p1.lng) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(p1.lat * Math.PI / 180) * Math.cos(p2.lat * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const baseDist = Math.max(10, Math.round(R * c * 1.2));

        // Sample 3 intermediate coordinates along 3 distinct geographical arcs
        const midLat = (p1.lat + p2.lat) / 2;
        const midLng = (p1.lng + p2.lng) / 2;

        const waypoints = [
            { name: "Direct Highway Route", lat: midLat, lng: midLng, color: "#38bdf8", factor: 1.1 },
            { name: "Northern Corridor Route", lat: midLat + 0.08, lng: midLng - 0.05, color: "#a855f7", factor: 1.25 },
            { name: "Southern Corridor Route", lat: midLat - 0.08, lng: midLng + 0.05, color: "#22c55e", factor: 1.2 }
        ];

        const pillsBox = document.getElementById('routePillsBox');
        const pillsList = document.getElementById('routePillsList');
        pillsList.innerHTML = '<div style="color:var(--text-muted); font-size:14px; text-align:center; padding:10px;">🔍 Reverse-geocoding real intermediate cities along routes...</div>';
        pillsBox.style.display = 'block';

        const routesData = [];

        for (let i = 0; i < waypoints.length; i++) {
            const wp = waypoints[i];
            let cityName = "Major Transit Hub";

            try {
                const rRes = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${wp.lat}&lon=${wp.lng}&zoom=10`);
                const rData = await rRes.json();
                if (rData && rData.address) {
                    cityName = rData.address.city || rData.address.town || rData.address.county || rData.address.state_district || rData.address.suburb || "Intermediate Station";
                }
            } catch (err) {
                console.log("Geocode city fetch:", err);
            }

            const routeDist = Math.round(baseDist * wp.factor);
            const routeDur  = Math.round(routeDist * 1.4);
            const routeLabel = `Via ${cityName} (${wp.name})`;

            routesData.push({
                name: routeLabel,
                dist: routeDist,
                dur: routeDur,
                coords: [[p1.lat, p1.lng], [wp.lat, wp.lng], [p2.lat, p2.lng]],
                color: wp.color
            });
        }

        displayRoutePillsAndPolylines(routesData);
    }

    function displayRoutePillsAndPolylines(routesData) {
        const pillsBox = document.getElementById('routePillsBox');
        const pillsList = document.getElementById('routePillsList');
        pillsList.innerHTML = '';
        pillsBox.style.display = 'block';

        routesData.forEach((rOpt, idx) => {
            const isPrimary = (idx === 0);

            const polyline = L.polyline(rOpt.coords, {
                color: isPrimary ? rOpt.color : '#94a3b8',
                weight: isPrimary ? 6 : 4,
                dashArray: isPrimary ? null : '6, 6',
                opacity: isPrimary ? 0.95 : 0.6
            }).addTo(map);

            routePolylines.push({ line: polyline, opt: rOpt });

            if (isPrimary) {
                map.fitBounds(polyline.getBounds(), { padding: [35, 35] });
                document.getElementById('route_distance').value = rOpt.dist;
                document.getElementById('via_route_name').value = rOpt.name;
            }

            // Render Route Selection Pill
            const div = document.createElement('div');
            div.className = `route-pill-item ${isPrimary ? 'active' : ''}`;
            div.innerHTML = `
                <div>
                    <strong>📍 ${rOpt.name}</strong>
                    <div style="font-size:12px; opacity:0.85; margin-top:2px;">Est. Travel Time: ~${rOpt.dur} mins | Passengers in this city can book</div>
                </div>
                <span style="font-weight:700; font-size:16px;">${rOpt.dist} km</span>
            `;
            div.onclick = function() {
                document.querySelectorAll('.route-pill-item').forEach(p => p.classList.remove('active'));
                div.classList.add('active');

                routePolylines.forEach((rp, rIdx) => {
                    if (rIdx === idx) {
                        rp.line.setStyle({ color: rp.opt.color, weight: 6, dashArray: null, opacity: 0.95 });
                        rp.line.bringToFront();
                        map.fitBounds(rp.line.getBounds(), { padding: [35, 35] });
                    } else {
                        rp.line.setStyle({ color: '#94a3b8', weight: 4, dashArray: '6, 6', opacity: 0.5 });
                    }
                });

                document.getElementById('route_distance').value = rOpt.dist;
                document.getElementById('via_route_name').value = rOpt.name;
            };
            pillsList.appendChild(div);
        });
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
                                        origMarker = L.marker([lat, lon]).addTo(map).bindPopup('📍 Pickup Point').openPopup();
                                    } else {
                                        if (destMarker) map.removeLayer(destMarker);
                                        destMarker = L.marker([lat, lon]).addTo(map).bindPopup('🏁 Drop Point').openPopup();
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
</script>
</body>
</html>
