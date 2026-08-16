<?php
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/geo_utils.php';
include_once __DIR__ . '/includes/dynamic_pricing.php';
include_once __DIR__ . '/includes/trust_score.php';

$rides = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $origin           = trim($_POST['origin'] ?? '');
    $destination      = trim($_POST['destination'] ?? '');
    $vehicle_category = trim($_POST['vehicle_category'] ?? 'bike');
    $helmet_filter    = (int)($_POST['helmet_filter'] ?? 0);
    $female_filter    = trim($_POST['female_filter'] ?? '');
    $current_user_id  = $_SESSION['user_id'] ?? 0;

    // Helper to extract primary search keyword token
    function extractPrimaryTerm($str) {
        if (empty($str)) return '';
        $parts = explode(',', $str);
        $clean = trim($parts[0]);
        return !empty($clean) ? $clean : trim($str);
    }

    $origTerm = extractPrimaryTerm($origin);
    $destTerm = extractPrimaryTerm($destination);

    // Multi-stop Waypoint & Tokenized Route Matching Engine
    $query = "SELECT r.*, u.name as driver_name, u.phone as driver_phone, u.profile_photo as driver_photo,
              (SELECT AVG(rating) FROM ratings WHERE reviewed_id = r.user_id) as avg_rating
              FROM rides r 
              JOIN users u ON r.user_id = u.id 
              WHERE (r.origin LIKE ? OR r.via_route_name LIKE ? OR r.destination LIKE ? OR ? = '')
              AND (r.destination LIKE ? OR r.via_route_name LIKE ? OR r.origin LIKE ? OR ? = '')
              AND (r.vehicle_category = ? OR ? = '')
              AND (r.helmet_provided >= ? OR ? = 0)
              AND (r.gender_preference = ? OR ? = '' OR r.gender_preference = 'any')
              AND r.seats_available > 0 
              AND (r.trip_status = 'active' OR r.trip_status IS NULL OR r.trip_status = '')
              ORDER BY r.ride_date ASC, r.ride_time ASC";

    $stmt = $conn->prepare($query);
    $origLike = "%$origTerm%";
    $destLike = "%$destTerm%";
    $stmt->bind_param("ssssssssssis", $origLike, $origLike, $origLike, $origTerm, $destLike, $destLike, $destLike, $destTerm, $vehicle_category, $vehicle_category, $helmet_filter, $female_filter);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['is_own_ride'] = ((int)$row['user_id'] === (int)$current_user_id);
        
        // Calculate Trust Score
        $trustInfo = calculateRiderAiSafetyScore($conn, (int)$row['user_id']);
        $row['trust_score'] = $trustInfo['score'];
        $row['trust_badge'] = $trustInfo['badge_title'];
        $row['trust_color'] = $trustInfo['badge_color'];
        $row['trust_class'] = $trustInfo['badge_class'];

        // Calculate Match Confidence
        $matchInfo = calculateMatchConfidence(true, true, 10);
        $row['match_score'] = $matchInfo['match_score'];
        $row['match_badge'] = $matchInfo['badge'];

        $rides[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(['rides' => $rides]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Rides & Route Matching — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/flexiride.css">

    <style>
        .search-console-container {
            margin: 30px auto;
            max-width: 980px;
        }

        .filter-chip-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 16px 0;
        }

        .filter-chip {
            padding: 8px 16px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-subtle);
            background: var(--bg-input);
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-chip.active {
            background: var(--eco-bg);
            color: var(--eco);
            border-color: var(--eco-border);
        }

        .suggestions-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-surface-elevated);
            border: 1px solid var(--primary);
            border-radius: var(--radius-md);
            max-height: 220px;
            overflow-y: auto;
            z-index: 2000;
            display: none;
            box-shadow: var(--shadow-lg);
        }
        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 13.5px;
            color: var(--text-main);
        }
        .suggestion-item:hover {
            background: var(--primary-glow);
            color: var(--primary);
        }

        #map {
            height: 260px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
            margin: 18px 0;
        }

        .wayfinder-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 24px;
            align-items: center;
            transition: all 0.25s ease;
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
        }
        .wayfinder-card:hover {
            border-color: var(--border-strong);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .driver-mini-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .driver-avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .fare-cta-box {
            text-align: right;
            border-left: 1px solid var(--border-subtle);
            padding-left: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .wayfinder-card {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .fare-cta-box {
                border-left: none;
                padding-left: 0;
                border-top: 1px solid var(--border-subtle);
                padding-top: 16px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content">
    <div class="fr-container search-console-container">
        <!-- Search Controls -->
        <div class="fr-card" style="margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <h2 style="font-size:22px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bx-navigation' style="color:var(--primary);"></i> Discover Commutes & Rides
                </h2>
                <span class="fr-badge fr-badge-eco"><i class='bx bxs-bolt'></i> Live Geo-Matching</span>
            </div>

            <!-- Vehicle Segmented Switcher -->
            <?php 
                $initialCategory = (isset($_GET['vehicle_category']) && $_GET['vehicle_category'] === 'car') ? 'car' : 'bike'; 
            ?>
            <div class="vehicle-segmented-tab" style="margin-bottom:18px;">
                <button type="button" class="seg-btn <?php echo ($initialCategory === 'bike') ? 'active' : ''; ?>" id="tab-bike" onclick="setCategory('bike')">
                    <i class='bx bx-cycling'></i> Bike & Two-Wheeler Rides
                </button>
                <button type="button" class="seg-btn <?php echo ($initialCategory === 'car') ? 'active' : ''; ?>" id="tab-car" onclick="setCategory('car')">
                    <i class='bx bxs-car'></i> Car Sharing Rides
                </button>
            </div>

            <form id="searchForm">
                <input type="hidden" id="vehicle_category" value="<?php echo htmlspecialchars($initialCategory); ?>">
                <input type="hidden" id="helmet_filter" value="0">
                <input type="hidden" id="female_filter" value="">

                <div style="display:grid; grid-template-columns: 1fr auto 1fr auto; align-items:flex-end; gap:10px;" class="search-inputs-grid">
                    <div class="fr-form-group" style="margin-bottom:0;">
                        <label class="fr-label"><i class='bx bxs-navigation' style="color:#10b981; font-size:16px;"></i> Pickup Location</label>
                        <div class="input-with-action">
                            <input type="text" id="origin" class="fr-input" placeholder="Type or click map for pickup" value="<?php echo htmlspecialchars($_GET['origin'] ?? ''); ?>" autocomplete="off">
                            <button type="button" class="input-action-btn" onclick="locateUserGps()" title="Use Current GPS Location">
                                <i class='bx bx-current-location'></i>
                            </button>
                        </div>
                        <div class="suggestions-list" id="originSuggestions"></div>
                    </div>

                    <button type="button" class="route-swap-btn" onclick="swapOriginDestination()" title="Swap Pickup & Dropoff">
                        <i class='bx bx-transfer-alt'></i>
                    </button>

                    <div class="fr-form-group" style="margin-bottom:0;">
                        <label class="fr-label"><i class='bx bxs-flag-alt' style="color:#ef4444; font-size:16px;"></i> Dropoff Destination</label>
                        <div class="input-with-action">
                            <input type="text" id="destination" class="fr-input" placeholder="Type or click map for drop" value="<?php echo htmlspecialchars($_GET['destination'] ?? ''); ?>" autocomplete="off">
                            <button type="button" class="input-action-btn" onclick="document.getElementById('destination').focus()" title="Type Destination">
                                <i class='bx bx-map-pin'></i>
                            </button>
                        </div>
                        <div class="suggestions-list" id="destSuggestions"></div>
                    </div>

                    <button type="submit" class="fr-btn fr-btn-primary" style="height:48px; padding:0 22px;">
                        <i class='bx bx-search'></i> Search
                    </button>
                </div>

                <!-- Instant Filter Chips -->
                <div class="filter-chip-row">
                    <div class="filter-chip" id="pill-helmet" onclick="togglePill('helmet')" style="<?php echo ($initialCategory === 'car') ? 'display:none;' : ''; ?>">
                        <i class='bx bxs-check-shield'></i> Spare Helmet Provided
                    </div>
                    <div class="filter-chip" id="pill-female" onclick="togglePill('female')">
                        <i class='bx bxs-user'></i> Female-Only Rides
                    </div>
                </div>
            </form>

            <!-- Interactive Map Wrapper with Live Route HUD & GPS Radar -->
            <div class="map-container-relative">
                <div class="map-route-hud" id="routeHud">
                    <div class="hud-stat-item">
                        <span class="hud-stat-val" id="hudDistance"><i class='bx bx-trip' style='color:var(--primary);'></i> -- km</span>
                        <span class="hud-stat-label">Driving Dist</span>
                    </div>
                    <div class="hud-stat-item">
                        <span class="hud-stat-val" id="hudEta"><i class='bx bx-time-five' style='color:#f59e0b;'></i> -- min</span>
                        <span class="hud-stat-label">Est. Time</span>
                    </div>
                    <div class="hud-stat-item">
                        <span class="hud-stat-val" id="hudFare" style='color:var(--eco);'><i class='bx bx-wallet'></i> ₹--</span>
                        <span class="hud-stat-label">50/50 Split</span>
                    </div>
                    <div class="hud-stat-item">
                        <span class="hud-stat-val" id="hudCo2" style='color:#10b981;'><i class='bx bxs-leaf'></i> -- kg</span>
                        <span class="hud-stat-label">CO₂ Saved</span>
                    </div>
                </div>

                <button type="button" class="map-gps-btn" id="gpsBtn" onclick="locateUserGps()" title="Lock Live GPS Location">
                    <i class='bx bx-current-location'></i>
                </button>

                <div id="map"></div>
            </div>
        </div>

        <!-- Dynamic Results Container -->
        <div id="results">
            <!-- Populated dynamically via AJAX on load or search -->
        </div>
    </div>
</main>

<script>
    function swapOriginDestination() {
        const orig = document.getElementById('origin');
        const dest = document.getElementById('destination');
        const temp = orig.value;
        orig.value = dest.value;
        dest.value = temp;

        if (originMarker && destMarker) {
            const p1 = originMarker.getLatLng();
            const p2 = destMarker.getLatLng();
            originMarker.setLatLng(p2);
            destMarker.setLatLng(p1);
            checkAndDrawRoute();
        }
        $('#searchForm').submit();
    }

    function setCategory(cat) {
        document.getElementById('vehicle_category').value = cat;
        if (cat === 'bike') {
            document.getElementById('tab-bike').classList.add('active');
            document.getElementById('tab-car').classList.remove('active');
            document.getElementById('pill-helmet').style.display = 'inline-flex';
        } else {
            document.getElementById('tab-car').classList.add('active');
            document.getElementById('tab-bike').classList.remove('active');
            document.getElementById('pill-helmet').style.display = 'none';
        }
        $('#searchForm').submit();
    }

    function togglePill(type) {
        if (type === 'helmet') {
            const h = document.getElementById('helmet_filter');
            h.value = (h.value === '1') ? '0' : '1';
            document.getElementById('pill-helmet').classList.toggle('active', h.value === '1');
        } else if (type === 'female') {
            const f = document.getElementById('female_filter');
            f.value = (f.value === 'female_only') ? '' : 'female_only';
            document.getElementById('pill-female').classList.toggle('active', f.value === 'female_only');
        }
        $('#searchForm').submit();
    }

    // Leaflet Interactive Map Engine
    const mapTilerKey = 'fMfeiTRB4wmIuS13BrCk';
    const map = L.map('map').setView([13.6288, 79.4192], 12);

    L.tileLayer(`https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=${mapTilerKey}`, {
        tileSize: 512, zoomOffset: -1, minZoom: 1, maxZoom: 20, attribution: '&copy; MapTiler &copy; OpenStreetMap'
    }).addTo(map);

    let originMarker = null;
    let destMarker = null;
    let routePolylines = [];
    let routeBadges = [];
    let currentRoutesData = [];
    let activeRouteIndex = 0;
    let driverMarkersGroup = L.layerGroup().addTo(map);
    let gpsAccuracyCircle = null;

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

    function setOriginPin(lat, lon, name, triggerSearch = true) {
        if (originMarker) map.removeLayer(originMarker);
        originMarker = L.marker([lat, lon], { icon: pickupIcon, draggable: true }).addTo(map);
        originMarker.bindPopup(`<b>🟢 Pickup:</b> ${name}`).openPopup();

        originMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            reverseGeocode(pos.lat, pos.lng, 'origin');
        });

        checkAndDrawRoute();
        if (triggerSearch) $('#searchForm').submit();
    }

    function setDestPin(lat, lon, name, triggerSearch = true) {
        if (destMarker) map.removeLayer(destMarker);
        destMarker = L.marker([lat, lon], { icon: dropIcon, draggable: true }).addTo(map);
        destMarker.bindPopup(`<b>🏁 Destination:</b> ${name}`).openPopup();

        destMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            reverseGeocode(pos.lat, pos.lng, 'destination');
        });

        checkAndDrawRoute();
        if (triggerSearch) $('#searchForm').submit();
    }

    // Click-on-Map Pin Dropper / Route Selector
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (!originMarker) {
            reverseGeocode(lat, lng, 'origin');
        } else if (!destMarker) {
            reverseGeocode(lat, lng, 'destination');
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

                selectFindRoute(closestIdx);
            }
        }
    });

    function reverseGeocode(lat, lng, targetInputId) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16`)
            .then(r => r.json())
            .then(data => {
                const name = data.display_name ? data.display_name.split(',').slice(0, 3).join(',') : `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                document.getElementById(targetInputId).value = name;
                if (targetInputId === 'origin') {
                    setOriginPin(lat, lng, name, true);
                } else {
                    setDestPin(lat, lng, name, true);
                }
            })
            .catch(() => {
                const coordName = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                document.getElementById(targetInputId).value = coordName;
                if (targetInputId === 'origin') {
                    setOriginPin(lat, lng, coordName, true);
                } else {
                    setDestPin(lat, lng, coordName, true);
                }
            });
    }

    function selectFindRoute(idx) {
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
            const badgeEl = document.getElementById(`find-route-badge-${bIdx}`);
            if (badgeEl) badgeEl.classList.toggle('active', bIdx === idx);
        });

        // Calculate and display Route HUD Metrics
        const estPrice = Math.max(25, Math.round(rOpt.dist * 2.8));
        const co2Saved = (rOpt.dist * 0.12).toFixed(1);

        document.getElementById('hudDistance').innerHTML = `<i class='bx bx-trip' style='color:var(--primary);'></i> ${rOpt.dist} km`;
        document.getElementById('hudEta').innerHTML = `<i class='bx bx-time-five' style='color:#f59e0b;'></i> ${rOpt.dur} min`;
        document.getElementById('hudFare').innerHTML = `<i class='bx bx-wallet'></i> ₹${estPrice}`;
        document.getElementById('hudCo2').innerHTML = `<i class='bx bxs-leaf'></i> -${co2Saved} kg`;
        document.getElementById('routeHud').style.display = 'flex';
    }

    // Google Maps Style Multi-Route Alternative Generator
    async function checkAndDrawRoute() {
        if (!originMarker || !destMarker) return;

        const p1 = originMarker.getLatLng();
        const p2 = destMarker.getLatLng();

        // Clear existing polylines and on-map duration badges
        routePolylines.forEach(l => {
            if (l.baseLine) map.removeLayer(l.baseLine);
            if (l.coreLine) map.removeLayer(l.coreLine);
            if (l.hitLine) map.removeLayer(l.hitLine);
        });
        routePolylines = [];
        routeBadges.forEach(b => map.removeLayer(b));
        routeBadges = [];

        try {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${p1.lng},${p1.lat};${p2.lng},${p2.lat}?overview=full&geometries=geojson&alternatives=true`;
            const res = await fetch(osrmUrl);
            const data = await res.json();

            if (data && data.routes && data.routes.length > 0) {
                const routesData = [];

                for (let i = 0; i < data.routes.length; i++) {
                    const r = data.routes[i];
                    const coords = r.geometry.coordinates.map(c => [c[1], c[0]]);
                    const distKm = (r.distance / 1000).toFixed(1);
                    const durMins = Math.round(r.duration / 60);

                    routesData.push({
                        coords: coords,
                        dist: distKm,
                        dur: durMins
                    });
                }

                currentRoutesData = routesData;

                routesData.forEach((rOpt, idx) => {
                    const isPrimary = (idx === 0);

                    // Dual-Layer Polyline
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

                    hitLine.on('click', () => selectFindRoute(idx));
                    coreLine.on('click', () => selectFindRoute(idx));

                    routePolylines.push({ baseLine, coreLine, hitLine, opt: rOpt });

                    // Google Maps Style On-Map Duration Badge
                    const midIdx = Math.floor(rOpt.coords.length * (0.35 + (idx * 0.15)));
                    const badgeCoord = rOpt.coords[midIdx] || rOpt.coords[Math.floor(rOpt.coords.length / 2)];
                    const fastestDur = routesData[0].dur;
                    const diff = rOpt.dur - fastestDur;
                    const labelText = (idx === 0) ? 'Fastest' : (diff > 0 ? `+${diff} min` : 'Alt');

                    const badgeHtml = `
                        <div class="gmaps-route-badge ${isPrimary ? 'active' : ''}" id="find-route-badge-${idx}" title="Click to select this route">
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
                    badgeMarker.on('click', () => selectFindRoute(idx));
                    routeBadges.push(badgeMarker);
                });

                if (routePolylines.length > 0) {
                    map.fitBounds(routePolylines[0].coreLine.getBounds(), { padding: [50, 50] });
                    selectFindRoute(0);
                }
            }
        } catch (err) {
            console.log("OSRM routing fallback:", err);
        }
    }

    // 1-Tap GPS Geolocation
    function locateUserGps() {
        const btn = document.getElementById('gpsBtn');
        btn.classList.add('locating');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    btn.classList.remove('locating');
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    if (gpsAccuracyCircle) map.removeLayer(gpsAccuracyCircle);
                    gpsAccuracyCircle = L.circle([lat, lng], {
                        color: '#0284c7',
                        fillColor: '#38bdf8',
                        fillOpacity: 0.25,
                        radius: pos.coords.accuracy || 20
                    }).addTo(map);

                    map.setView([lat, lng], 15);
                    reverseGeocode(lat, lng, 'origin');
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

    // Quick-select Campus Hotspot
    function quickSelectHotspot(name, lat, lng) {
        document.getElementById('origin').value = name;
        setOriginPin(lat, lng, name, true);
        map.setView([lat, lng], 14);
    }

    // OpenStreetMap Autocomplete
    function setupAutocomplete(inputId, listId, pinCallback) {
        const inp = document.getElementById(inputId);
        const list = document.getElementById(listId);
        let timeout = null;

        inp.addEventListener('input', function() {
            clearTimeout(timeout);
            const q = this.value.trim();
            if (q.length < 3) { list.style.display = 'none'; return; }

            timeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&countrycodes=in&limit=5`)
                    .then(r => r.json())
                    .then(data => {
                        list.innerHTML = '';
                        if (data && data.length > 0) {
                            list.style.display = 'block';
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = item.display_name.split(',')[0] + ' (' + (item.display_name.split(',')[1] || '') + ')';
                                div.onclick = function() {
                                    inp.value = item.display_name.split(',')[0];
                                    list.style.display = 'none';
                                    pinCallback(parseFloat(item.lat), parseFloat(item.lon), inp.value);
                                    map.setView([parseFloat(item.lat), parseFloat(item.lon)], 13);
                                    $('#searchForm').submit();
                                };
                                list.appendChild(div);
                            });
                        } else {
                            list.style.display = 'none';
                        }
                    });
            }, 300);
        });
    }

    setupAutocomplete('origin', 'originSuggestions', setOriginPin);
    setupAutocomplete('destination', 'destSuggestions', setDestPin);

    // Live AJAX Search & Vehicle Radar Marker Plotter
    $(document).ready(function() {
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            const origin = $('#origin').val();
            const destination = $('#destination').val();
            const vehicle_category = $('#vehicle_category').val();
            const helmet_filter = $('#helmet_filter').val();
            const female_filter = $('#female_filter').val();

            const searchBtn = $(this).find('button[type="submit"]');
            searchBtn.css('pointer-events', 'none').css('opacity', '0.75');

            $.post('find_ride.php', { origin, destination, vehicle_category, helmet_filter, female_filter }, function(res) {
                const resultsDiv = $('#results');
                resultsDiv.empty();
                driverMarkersGroup.clearLayers();

                if (res.rides && res.rides.length > 0) {
                    const isSpecificSearch = origin || destination;
                    const headerHtml = isSpecificSearch 
                        ? `<div class="results-stream-header">
                            <div class="results-stream-title"><i class='bx bx-search' style='color:var(--primary);'></i> Matching Rides (${origin || 'Any'} → ${destination || 'Any'})</div>
                            <span class="fr-badge fr-badge-primary"><i class='bx bx-check-circle'></i> ${res.rides.length} matching ride(s)</span>
                           </div>`
                        : `<div class="results-stream-header">
                            <div class="results-stream-title"><i class='bx bxs-zap' style='color:#f59e0b;'></i> All Active Verified Commutes</div>
                            <span class="fr-badge fr-badge-eco"><i class='bx bx-check-circle'></i> ${res.rides.length} rides available</span>
                           </div>`;
                    resultsDiv.append(headerHtml);

                    res.rides.forEach((ride, index) => {
                        const isBike = (ride.vehicle_category || 'bike') === 'bike';
                        const vehicleIcon = isBike ? `<i class='bx bx-cycling'></i>` : `<i class='bx bxs-car'></i>`;
                        const vehicleLabel = ride.vehicle_model ? ride.vehicle_model : (isBike ? 'Two-Wheeler / Bike' : 'Car Pool');

                        // Plot Driver Vehicle Radar Marker on Map
                        const baseLat = 13.6288 + (Math.sin(index + 1) * 0.025);
                        const baseLng = 79.4192 + (Math.cos(index + 1) * 0.025);

                        const driverMarkerHtml = `
                            <div class="driver-radar-marker" title="${ride.driver_name}">
                                ${vehicleIcon} ₹${parseFloat(ride.price).toFixed(0)}
                            </div>
                        `;

                        const driverMarkerIcon = L.divIcon({
                            className: 'driver-marker-container',
                            html: driverMarkerHtml,
                            iconSize: [60, 26],
                            iconAnchor: [30, 13]
                        });

                        const dMarker = L.marker([baseLat, baseLng], { icon: driverMarkerIcon }).addTo(driverMarkersGroup);
                        dMarker.bindPopup(`
                            <div style="font-family:'Outfit',sans-serif; min-width:180px;">
                                <div style="font-weight:800; font-size:14px; color:var(--text-main); margin-bottom:4px;">
                                    ${ride.driver_name}
                                </div>
                                <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">
                                    ${vehicleIcon} ${vehicleLabel} • <strong>${ride.seats_available} seat(s)</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:16px; font-weight:800; color:var(--eco);">₹${parseFloat(ride.price).toFixed(0)}</span>
                                    <a href="book_ride.php?ride_id=${ride.id}" class="fr-btn fr-btn-primary fr-btn-sm" style="padding:4px 10px; font-size:11px;">Book</a>
                                </div>
                            </div>
                        `);

                        const helmetBadge = (ride.helmet_provided == 1 && isBike)
                            ? '<span class="fr-badge fr-badge-eco"><i class="bx bxs-check-shield"></i> Spare Helmet Provided</span>'
                            : '';
                        
                        const trustShield = `<span class="trust-shield ${ride.trust_class || 'blue'}"><i class='bx bxs-shield-check'></i> ${ride.trust_score || 70}% Verified Driver</span>`;
                        
                        const actionBtn = ride.is_own_ride
                            ? `<a href="myrides.php" class="fr-btn fr-btn-ghost fr-btn-sm" style="margin-top:8px;">Manage My Ride</a>`
                            : `<a href="book_ride.php?ride_id=${ride.id}" class="fr-btn fr-btn-primary fr-btn-sm" style="margin-top:8px;">Book Seat <i class='bx bx-right-arrow-alt'></i></a>`;

                        const cardHtml = `
                            <div class="wayfinder-card">
                                <div>
                                    <div class="driver-mini-profile">
                                        ${ride.driver_photo 
                                            ? `<img src="${ride.driver_photo}" class="driver-avatar-circle" alt="Driver">`
                                            : `<div class="driver-avatar-circle"><i class='bx bxs-user'></i></div>`}
                                        <div>
                                            <div style="font-size:15.5px; font-weight:700; color:var(--text-main); display:flex; align-items:center; gap:6px;">
                                                ${ride.driver_name} ${ride.is_own_ride ? '<span class="fr-badge fr-badge-primary">You</span>' : ''}
                                            </div>
                                            <div style="font-size:12.5px; color:var(--text-muted); display:flex; align-items:center; gap:6px; margin-top:2px;">
                                                <span><i class='bx bxs-star' style='color:#eab308;'></i> ${parseFloat(ride.avg_rating || 5.0).toFixed(1)} Rating</span>
                                                <span>•</span>
                                                <span>${vehicleLabel}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Wayfinder Route Line -->
                                    <div class="wayfinder-route">
                                        <div class="route-stop origin">
                                            <div class="stop-beacon"></div>
                                            <div class="stop-label">Departure</div>
                                            <div class="stop-name">${ride.origin} <span class="stop-time"><i class='bx bx-calendar'></i> ${ride.ride_date} at ${ride.ride_time}</span></div>
                                        </div>
                                        ${ride.via_route_name ? `
                                        <div class="route-stop waypoint">
                                            <div class="stop-beacon"></div>
                                            <div class="stop-label">Route Corridor</div>
                                            <div class="stop-name" style="font-size:13px; color:var(--primary);">Via ${ride.via_route_name}</div>
                                        </div>` : ''}
                                        <div class="route-stop destination">
                                            <div class="stop-beacon"></div>
                                            <div class="stop-label">Destination</div>
                                            <div class="stop-name">${ride.destination}</div>
                                        </div>
                                    </div>

                                    <!-- Feature Chips -->
                                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:14px;">
                                        ${trustShield}
                                        ${helmetBadge}
                                        <span class="eco-capsule"><i class='bx bxs-leaf'></i> -1.2kg CO₂</span>
                                    </div>
                                </div>

                                <!-- Fare & Booking Callout -->
                                <div class="fare-cta-box">
                                    <div>
                                        <div class="fare-amount">₹${parseFloat(ride.price).toFixed(0)}</div>
                                        <div class="fare-subtext">50/50 Petrol Split</div>
                                    </div>
                                    <div style="font-size:12px; font-weight:700; color:var(--eco); margin-top:8px;">
                                        <i class='bx bxs-circle' style='font-size:9px;'></i> ${ride.seats_available} seat(s) available
                                    </div>
                                    ${actionBtn}
                                </div>
                            </div>
                        `;
                        resultsDiv.append(cardHtml);
                    });
                } else {
                    const isSpecific = origin || destination;
                    resultsDiv.html(`
                        <div class="fr-card" style="text-align:center; padding:40px 20px;">
                            <div style="width:56px; height:56px; border-radius:50%; background:var(--primary-bg); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:28px; margin:0 auto 14px;">
                                <i class='bx bx-navigation'></i>
                            </div>
                            <h3 style="font-size:18px; color:var(--text-main); margin-bottom:6px;">${isSpecific ? 'No Rides Found Along This Exact Corridor' : 'No Active Commutes Available Right Now'}</h3>
                            <p style="font-size:14px; color:var(--text-muted); max-width:420px; margin:0 auto 18px;">${isSpecific ? 'Try expanding your search, click a campus hotspot pill above, or offer a ride to earn fuel splits.' : 'Be the first commuter to post a ride on this route!'}</p>
                            <a href="post_ride.php" class="fr-btn fr-btn-primary"><i class='bx bx-plus-circle'></i> Offer a Seat Now</a>
                        </div>
                    `);
                }
            }, 'json').always(function() {
                searchBtn.css('pointer-events', 'auto').css('opacity', '1');
            });
        });

        // Trigger auto search on load
        $('#searchForm').submit();
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
