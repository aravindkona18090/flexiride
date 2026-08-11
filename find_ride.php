<?php
include 'db.php';
session_start();

$rides = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $origin           = trim($_POST['origin'] ?? '');
    $destination      = trim($_POST['destination'] ?? '');
    $vehicle_category = trim($_POST['vehicle_category'] ?? 'bike');
    $helmet_filter    = (int)($_POST['helmet_filter'] ?? 0);
    $female_filter    = trim($_POST['female_filter'] ?? '');
    $current_user_id  = $_SESSION['user_id'] ?? 0;

    $query = "SELECT r.*, u.name as driver_name, u.avg_rating, u.is_verified 
              FROM rides r 
              JOIN users u ON r.user_id = u.id 
              WHERE (r.origin LIKE ? OR r.destination LIKE ? OR ? = '' OR ? = '') 
                AND (r.vehicle_category = ? OR r.vehicle_type = ?) 
                AND (? = 0 OR r.helmet_provided = 1)
                AND (? = '' OR r.gender_preference = 'female_only')
                AND r.user_id != ? 
                AND r.seats_available > 0 
                AND (r.trip_status IS NULL OR r.trip_status != 'cancelled')
                AND r.ride_date >= CURDATE()
              ORDER BY r.ride_date ASC, r.ride_time ASC";

    $stmt = $conn->prepare($query);
    $origLike = "%$origin%";
    $destLike = "%$destination%";
    $stmt->bind_param("ssssssisi", $origLike, $destLike, $origin, $destination, $vehicle_category, $vehicle_category, $helmet_filter, $female_filter, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
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

<?php include 'navbar.php'; ?>

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

    const map = L.map('map').setView([13.6288, 79.4192], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let originMarker = null;
    let destMarker = null;

    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (!originMarker) {
            setOriginPin(lat, lng, `GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`);
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { if(data.display_name) document.getElementById('origin').value = data.display_name; });
        } else if (!destMarker) {
            setDestPin(lat, lng, `GPS (${lat.toFixed(4)}, ${lng.toFixed(4)})`);
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => { if(data.display_name) document.getElementById('destination').value = data.display_name; });
            $('#searchForm').submit();
        }
    });

    function setOriginPin(lat, lng, name) {
        if (originMarker) map.removeLayer(originMarker);
        originMarker = L.marker([lat, lng]).addTo(map).bindPopup(`📍 Pickup: ${name}`).openPopup();
    }

    function setDestPin(lat, lng, name) {
        if (destMarker) map.removeLayer(destMarker);
        destMarker = L.marker([lat, lng]).addTo(map).bindPopup(`🏁 Drop: ${name}`).openPopup();
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
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.textContent = item.display_name;
                                div.onclick = function() {
                                    input.value = item.display_name;
                                    suggList.style.display = 'none';
                                    const lat = parseFloat(item.lat);
                                    const lon = parseFloat(item.lon);
                                    map.setView([lat, lon], 13);
                                    setPinFunc(lat, lon, item.display_name);
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

            $.post('find_ride.php', { origin, destination, vehicle_category, helmet_filter, female_filter }, function(res) {
                const resultsDiv = $('#results');
                resultsDiv.empty();

                if (res.rides && res.rides.length > 0) {
                    res.rides.forEach(ride => {
                        const helmetBadge = ride.helmet_provided == 1 ? '<span class="badge-tag badge-helmet">🪖 Spare Helmet Provided</span>' : '';
                        const card = `
                            <div class="ride-card">
                                <div>
                                    <div class="driver-info">
                                        <div class="driver-avatar"><i class='bx bxs-user'></i></div>
                                        <div>
                                            <strong>${ride.driver_name}</strong>
                                            <div style="font-size:13px; color:var(--text-muted);">⭐ ${ride.avg_rating || '5.0'} Rating</div>
                                        </div>
                                    </div>
                                    <h3 style="font-size:18px; margin-bottom:6px;">${ride.origin} ➔ ${ride.destination}</h3>
                                    <p style="font-size:14px; color:var(--text-color); margin-bottom:8px;">
                                        📅 ${ride.ride_date} at ${ride.ride_time} | Vehicle: <strong>${ride.vehicle_model || ride.vehicle_type}</strong>
                                    </p>
                                    <div>
                                        <span class="badge-tag badge-category">${(ride.vehicle_category || ride.vehicle_type).toUpperCase()}</span>
                                        ${helmetBadge}
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="price-tag">₹${ride.price}</div>
                                    <div style="font-size:12px; color:var(--text-muted); margin-bottom:6px;">${ride.seats_available} seat left</div>
                                    <a href="book_ride.php?ride_id=${ride.id}" class="btn-book">Book Seat</a>
                                </div>
                            </div>
                        `;
                        resultsDiv.append(card);
                    });
                } else {
                    resultsDiv.html('<div style="text-align:center; padding:30px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:16px;">No matching rides found along this route. Be the first to post a ride!</div>');
                }
            }, 'json');
        });
        
        $('#searchForm').submit();
    });
</script>
</body>
</html>
