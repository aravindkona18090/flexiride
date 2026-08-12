<?php
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/geo_utils.php';
include_once __DIR__ . '/includes/dynamic_pricing.php';
include_once __DIR__ . '/includes/trust_score.php';
session_start();

$rides = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $origin           = trim($_POST['origin'] ?? '');
    $destination      = trim($_POST['destination'] ?? '');
    $vehicle_category = trim($_POST['vehicle_category'] ?? 'bike');
    $helmet_filter    = (int)($_POST['helmet_filter'] ?? 0);
    $female_filter    = trim($_POST['female_filter'] ?? '');
    $current_user_id  = $_SESSION['user_id'] ?? 0;

    // Helper to extract primary search keyword token (e.g. "Doddaballapura" from multi-word address strings)
    function extractPrimaryTerm($str) {
        if (empty($str)) return '';
        $parts = explode(',', $str);
        $clean = trim($parts[0]);
        return !empty($clean) ? $clean : trim($str);
    }

    $origTerm = extractPrimaryTerm($origin);
    $destTerm = extractPrimaryTerm($destination);

    // Multi-stop Waypoint & Tokenized Route Matching Engine
    $query = "SELECT r.*, u.name as driver_name, u.phone as driver_phone, 
              (SELECT AVG(rating) FROM reviews WHERE reviewee_id = r.user_id) as avg_rating
              FROM rides r 
              JOIN users u ON r.user_id = u.id 
              WHERE (r.origin LIKE ? OR r.route_via LIKE ? OR r.destination LIKE ? OR r.origin LIKE ?)
              AND (r.destination LIKE ? OR r.route_via LIKE ? OR r.origin LIKE ? OR r.destination LIKE ?)
              AND (r.vehicle_category = ? OR ? = '')
              AND (r.helmet_provided >= ? OR ? = 0)
              AND (r.gender_preference = ? OR ? = '' OR r.gender_preference = 'any')
              AND r.seats_available > 0 
              AND r.status = 'active'
              ORDER BY r.created_at DESC";

    $stmt = $conn->prepare($query);
    $origLike = "%$origTerm%";
    $destLike = "%$destTerm%";
    $stmt->bind_param("ssssssssssis", $origTerm, $origLike, $origLike, $origLike, $destTerm, $destLike, $destLike, $destLike, $vehicle_category, $vehicle_category, $helmet_filter, $female_filter);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['is_own_ride'] = ((int)$row['user_id'] === (int)$current_user_id);
        
        // Calculate AI Rider Safety Score
        $safetyInfo = calculateRiderAiSafetyScore($conn, (int)$row['user_id']);
        $row['ai_safety_score'] = $safetyInfo['score'];
        $row['ai_safety_badge'] = $safetyInfo['badge_title'];
        $row['ai_safety_color'] = $safetyInfo['badge_color'];

        // Calculate AI Match Confidence
        $matchInfo = calculateMatchConfidence(true, true, 10);
        $row['ai_match_score'] = $matchInfo['match_score'];
        $row['ai_match_badge'] = $matchInfo['badge'];

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
    <title>Find Rides & Route Matching - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 950px; margin: 30px auto; padding: 0 20px; width: 100%; }
        .search-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }
        .vehicle-tabs { display: flex; gap: 15px; margin-bottom: 20px; }
        .tab-btn {
            flex: 1; padding: 14px; border-radius: 12px; border: 2px solid var(--input-border);
            background: var(--input-bg); color: var(--text-muted); font-size: 16px; font-weight: 600;
            cursor: pointer; text-align: center; transition: all 0.3s;
        }
        .tab-btn.active { border-color: var(--primary-color); color: var(--primary-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 120px; gap: 15px; margin-bottom: 15px; }
        .form-group { position: relative; }
        .form-grid input { width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--input-border); background: var(--input-bg); color: var(--text-color); font-size: 15px; outline: none; }
        .btn-search { background: var(--primary-gradient); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        
        .filter-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .pill-btn {
            padding: 6px 14px; border-radius: 20px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-muted); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
        }
        .pill-btn.active { background: var(--success-bg); color: var(--success-color); border-color: var(--success-color); }

        .suggestions-list {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--input-bg); border: 1px solid var(--primary-color); border-radius: 10px;
            max-height: 200px; overflow-y: auto; z-index: 2000; display: none;
        }
        .suggestion-item { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid var(--input-border); font-size: 14px; color: var(--text-muted); }
        .suggestion-item:hover { background: var(--primary-color); color: white; }

        #map { height: 280px; border-radius: 14px; border: 1px solid var(--card-border); margin-bottom: 20px; }

        .ride-list { display: flex; flex-direction: column; gap: 15px; }
        .ride-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 20px;
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.3s ease;
        }
        .ride-card:hover { border-color: var(--primary-color); transform: translateY(-2px); }
        .driver-info { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .driver-avatar { width: 44px; height: 44px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
        .badge-tag { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-right: 6px; }
        .badge-helmet { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }
        .badge-category { background: rgba(56, 189, 248, 0.2); color: var(--primary-color); border: 1px solid var(--primary-color); }
        .price-tag { font-size: 24px; font-weight: 700; color: var(--success-color); text-align: right; }
        .btn-book { display: inline-block; background: var(--primary-gradient); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; margin-top: 8px; transition: 0.3s; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="search-card">
        <div class="vehicle-tabs">
            <div class="tab-btn active" id="tab-bike" onclick="setCategory('bike')">🏍️ Two-Wheeler / Bike Rides</div>
            <div class="tab-btn" id="tab-car" onclick="setCategory('car')">🚗 Car Sharing Rides</div>
        </div>

        <form id="searchForm">
            <input type="hidden" id="vehicle_category" value="bike">
            <input type="hidden" id="helmet_filter" value="0">
            <input type="hidden" id="female_filter" value="">

            <div class="form-grid">
                <div class="form-group">
                    <input type="text" id="origin" placeholder="📍 Type pickup address or click map" autocomplete="off">
                    <div class="suggestions-list" id="originSuggestions"></div>
                </div>
                <div class="form-group">
                    <input type="text" id="destination" placeholder="🏁 Type drop address or click map" autocomplete="off">
                    <div class="suggestions-list" id="destSuggestions"></div>
                </div>
                <button type="submit" class="btn-search">Search</button>
            </div>

            <!-- Instant Filter Pills -->
            <div class="filter-pills">
                <div class="pill-btn" id="pill-helmet" onclick="togglePill('helmet')">🪖 Spare Helmet Provided</div>
                <div class="pill-btn" id="pill-female" onclick="togglePill('female')">👩 Female-Only Rides</div>
            </div>
        </form>

        <div id="map"></div>
    </div>

    <div id="results" class="ride-list">
        <p style="text-align:center; color:var(--text-muted);">Enter locations above or click map pins to search matching routes.</p>
    </div>
</div>

<script>
    function setCategory(cat) {
        document.getElementById('vehicle_category').value = cat;
        if (cat === 'bike') {
            document.getElementById('tab-bike').classList.add('active');
            document.getElementById('tab-car').classList.remove('active');
        } else {
            document.getElementById('tab-car').classList.add('active');
            document.getElementById('tab-bike').classList.remove('active');
        }
        $('#searchForm').submit();
    }

    function togglePill(type) {
        if (type === 'helmet') {
            const h = document.getElementById('helmet_filter');
            const pill = document.getElementById('pill-helmet');
            if (h.value === '1') { h.value = '0'; pill.classList.remove('active'); }
            else { h.value = '1'; pill.classList.add('active'); }
        } else if (type === 'female') {
            const f = document.getElementById('female_filter');
            const pill = document.getElementById('pill-female');
            if (f.value === 'female_only') { f.value = ''; pill.classList.remove('active'); }
            else { f.value = 'female_only'; pill.classList.add('active'); }
        }
        $('#searchForm').submit();
    }

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
    }).addTo(map);

    // Auto-detect user's live GPS current location with high street precision
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            map.setView([userLat, userLng], 15);

            L.circle([userLat, userLng], {
                color: '#0284c7',
                fillColor: '#38bdf8',
                fillOpacity: 0.8,
                radius: 10
            }).addTo(map);

            L.marker([userLat, userLng]).addTo(map).bindPopup('📍 You Are Here').openPopup();
        }, function(error) {
            console.log("GPS Location permission fallback:", error);
        }, { enableHighAccuracy: true });
    }

    let originMarker = null;
    let destMarker = null;

    function cleanShortAddress(displayName) {
        if (!displayName) return '';
        const parts = displayName.split(',').map(s => s.trim());
        if (parts.length <= 2) return displayName;
        return `${parts[0]}, ${parts[1]}`;
    }

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (!originMarker) {
            setOriginPin(lat, lng, `GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`);
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { if(data.display_name) document.getElementById('origin').value = cleanShortAddress(data.display_name); });
        } else if (!destMarker) {
            setDestPin(lat, lng, `GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`);
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { if(data.display_name) document.getElementById('destination').value = cleanShortAddress(data.display_name); });
            $('#searchForm').submit();
        }
    });

    function setOriginPin(lat, lng, name) {
        if (originMarker) map.removeLayer(originMarker);
        originMarker = L.marker([lat, lng]).addTo(map).bindPopup(`📍 Pickup: ${cleanShortAddress(name)}`).openPopup();
    }

    function setDestPin(lat, lng, name) {
        if (destMarker) map.removeLayer(destMarker);
        destMarker = L.marker([lat, lng]).addTo(map).bindPopup(`🏁 Drop: ${cleanShortAddress(name)}`).openPopup();
    }

    function setupAutocomplete(inputId, suggestionsId, setPinFunc) {
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
                            results.slice(0, 5).forEach(item => {
                                const cleanName = cleanShortAddress(item.display_name);
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = cleanName;
                                div.onclick = function() {
                                    input.value = cleanName;
                                    suggList.style.display = 'none';
                                    const lat = parseFloat(item.lat);
                                    const lon = parseFloat(item.lon);
                                    map.setView([lat, lon], 13);
                                    setPinFunc(lat, lon, cleanName);
                                    $('#searchForm').submit();
                                };
                                suggList.appendChild(div);
                            });
                        } else { suggList.style.display = 'none'; }
                    });
            }, 300);
        });
    }

    setupAutocomplete('origin', 'originSuggestions', setOriginPin);
    setupAutocomplete('destination', 'destSuggestions', setDestPin);

    $(document).ready(function() {
        $('#searchForm').on('submit', function(e) {
            e.preventDefault();
            const origin = $('#origin').val();
            const destination = $('#destination').val();
            const vehicle_category = $('#vehicle_category').val();
            const helmet_filter = $('#helmet_filter').val();
            const female_filter = $('#female_filter').val();

            const searchBtn = $(this).find('button[type="submit"]');
            const originalBtnHtml = searchBtn.html();
            searchBtn.css('pointer-events', 'none').css('opacity', '0.85').html(`<i class='bx bx-loader-alt bx-spin' style='font-size:18px; vertical-align:middle; margin-right:6px;'></i> Searching...`);

            $.post('find_ride.php', { origin, destination, vehicle_category, helmet_filter, female_filter }, function(res) {
                const resultsDiv = $('#results');
                resultsDiv.empty();

                if (res.rides && res.rides.length > 0) {
                            const helmetBadge = ride.helmet_provided == 1 ? '<span class="badge-tag badge-helmet">🪖 Spare Helmet Provided</span>' : '';
                        const ownBadge = ride.is_own_ride ? '<span class="badge-tag" style="background:#0284c7; color:white;">👤 Your Posted Ride</span>' : '';
                        const aiSafetyBadge = ride.ai_safety_badge ? `<span class="badge-tag" style="background:rgba(34, 197, 94, 0.15); color:${ride.ai_safety_color || '#22c55e'}; border:1px solid ${ride.ai_safety_color || '#22c55e'}; font-weight:700;">${ride.ai_safety_badge}</span>` : '';
                        const aiMatchBadge = ride.ai_match_badge ? `<span class="badge-tag" style="background:rgba(56, 189, 248, 0.15); color:#38bdf8; border:1px solid #38bdf8; font-weight:700;">${ride.ai_match_badge}</span>` : '';

                        const actionBtn = ride.is_own_ride 
                            ? `<a href="myrides.php" class="btn-book" style="background:var(--input-bg); border:1px solid var(--primary-color); color:var(--primary-color);">Manage Ride</a>`
                            : `<a href="book_ride.php?ride_id=${ride.id}" class="btn-book">Book Seat</a>`;

                        const card = `
                            <div class="ride-card">
                                <div>
                                    <div class="driver-info">
                                        <div class="driver-avatar"><i class='bx bxs-user'></i></div>
                                        <div>
                                            <strong>${ride.driver_name} ${ride.is_own_ride ? '(You)' : ''}</strong>
                                            <div style="font-size:13px; color:var(--text-muted);">⭐ ${ride.avg_rating || '5.0'} Rating</div>
                                        </div>
                                    </div>
                                    <h3 style="font-size:18px; margin-bottom:6px;">${ride.origin} ➔ ${ride.destination}</h3>
                                    <p style="font-size:14px; color:var(--text-color); margin-bottom:8px;">
                                        📅 ${ride.ride_date} at ${ride.ride_time} | Vehicle: <strong>${ride.vehicle_model || ride.vehicle_type}</strong>
                                    </p>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <span class="badge-tag badge-category">${(ride.vehicle_category || ride.vehicle_type).toUpperCase()}</span>
                                        ${aiSafetyBadge}
                                        ${aiMatchBadge}
                                        ${helmetBadge}
                                        ${ownBadge}
                                    </div>
                                </div>`            </div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="price-tag">₹${ride.price}</div>
                                    <div style="font-size:12px; color:var(--text-muted); margin-bottom:6px;">${ride.seats_available} seat left</div>
                                    ${actionBtn}
                                </div>
                            </div>
                        `;
                        resultsDiv.append(card);
                    });
                } else {
                    resultsDiv.html('<div style="text-align:center; padding:30px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px;">No matching rides found along this route. Be the first to post a ride!</div>');
                }
            }, 'json').always(function() {
                searchBtn.css('pointer-events', 'auto').css('opacity', '1').html(originalBtnHtml);
            });
        });
        
        $('#searchForm').submit();
    });
</script>
</body>
</html>
