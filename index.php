<?php
session_start();
include_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlexiRide — Safe Campus & Commute Ride Sharing</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/css/flexiride.css?v=<?php echo time(); ?>">
    <style>
        .hero-section {
            padding: 60px 0 40px;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 40px;
            align-items: center;
        }

        .hero-title {
            font-size: 46px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.5px;
            margin-bottom: 18px;
            color: var(--text-main);
        }

        .hero-title span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 16.5px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 28px;
            max-width: 540px;
        }

        .hero-quick-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .search-console-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 30px;
            box-shadow: var(--shadow-lg);
        }

        .vehicle-segmented-tab {
            display: flex;
            gap: 8px;
            background: var(--bg-input);
            padding: 6px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            border: 1px solid var(--border-subtle);
        }

        .seg-btn {
            flex: 1;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .seg-btn.active {
            background: var(--primary-gradient);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .stats-banner {
            background: var(--bg-surface);
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
            padding: 36px 0;
            margin: 40px 0;
        }

        .stats-metric-val {
            font-size: 38px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 6px;
        }

        #heroMap {
            height: 340px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-subtle);
            z-index: 10;
        }

        .floating-sos-btn {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--danger);
            color: #ffffff;
            padding: 14px 22px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.45);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 999;
            transition: all 0.25s ease;
        }
        .floating-sos-btn:hover {
            transform: scale(1.05);
            background: #dc2626;
        }

        @media (max-width: 960px) {
            .hero-section {
                grid-template-columns: 1fr;
                padding: 40px 0 20px;
            }
            .hero-title {
                font-size: 34px;
            }
            .floating-sos-btn {
                bottom: calc(var(--dock-height) + 16px);
                right: 16px;
                padding: 10px 18px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content">
    <div class="fr-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div>
                <div class="hero-quick-chips">
                    <span class="fr-badge fr-badge-eco"><i class='bx bxs-leaf'></i> 0% Platform Commission</span>
                    <span class="fr-badge fr-badge-primary"><i class='bx bxs-shield-plus'></i> Verified Campus Peers</span>
                </div>
                <h1 class="hero-title">
                    Safe, Affordable <span>Bike & Car Carpooling</span> for Daily Commuters.
                </h1>
                <p class="hero-subtitle">
                    Connect directly with verified students and coworkers traveling the exact same route. Split fuel costs 50/50, travel with spare helmet protection, and reduce carbon footprint.
                </p>

                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <a href="find_ride.php" class="fr-btn fr-btn-primary fr-btn-lg"><i class='bx bx-search'></i> Find Available Ride</a>
                    <a href="post_ride.php" class="fr-btn fr-btn-ghost fr-btn-lg"><i class='bx bx-plus-circle'></i> Offer a Seat</a>
                </div>
            </div>

            <!-- Floating Search Console -->
            <div class="search-console-card">
                <div class="vehicle-segmented-tab" style="margin-bottom:16px;">
                    <button type="button" class="seg-btn active" id="tab-bike" onclick="setSearchType('bike')"><i class='bx bx-cycling'></i> Bike Pooling</button>
                    <button type="button" class="seg-btn" id="tab-car" onclick="setSearchType('car')"><i class='bx bxs-car'></i> Car Sharing</button>
                </div>

                <form action="find_ride.php" method="GET">
                    <input type="hidden" name="vehicle_category" id="search_cat" value="bike">
                    <div class="fr-form-group">
                        <label class="fr-label"><i class='bx bxs-navigation' style="color:#10b981; font-size:16px;"></i> Pickup / Origin</label>
                        <div class="input-with-action">
                            <input type="text" name="origin" id="origin" class="fr-input" placeholder="e.g. GITAM Campus, Doddaballapur" required>
                            <button type="button" class="input-action-btn" onclick="locateHomeGps()" title="Use My Live Location">
                                <i class='bx bx-current-location'></i>
                            </button>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:center; margin:-8px 0 8px;">
                        <button type="button" class="route-swap-btn" onclick="swapHomeInputs()" title="Swap Origin & Destination">
                            <i class='bx bx-transfer-alt'></i>
                        </button>
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label"><i class='bx bxs-flag-alt' style="color:#ef4444; font-size:16px;"></i> Dropoff / Destination</label>
                        <div class="input-with-action">
                            <input type="text" name="destination" id="destination" class="fr-input" placeholder="e.g. Majestic / Hebbal, Bengaluru" required>
                            <button type="button" class="input-action-btn" onclick="document.getElementById('destination').focus()" title="Set Destination">
                                <i class='bx bx-map-pin'></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg" style="margin-top: 8px;">
                        Search Live Rides <i class='bx bx-right-arrow-alt'></i>
                    </button>
                </form>
            </div>
        </section>
    </div>

    <!-- Live Platform Impact Stats -->
    <section class="stats-banner">
        <div class="fr-container">
            <div class="fr-grid-3" style="text-align: center;">
                <div>
                    <div class="stats-metric-val">1,480+</div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--text-muted);">Trips Successfully Shared</div>
                </div>
                <div>
                    <div class="stats-metric-val" style="color: var(--eco);">₹65,000+</div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--text-muted);">Direct Fuel Costs Saved by Peers</div>
                </div>
                <div>
                    <div class="stats-metric-val" style="color: var(--primary);">2.4 Tons</div>
                    <div style="font-size: 14px; font-weight: 600; color: var(--text-muted);">CO₂ Emissions Prevented</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Route Map -->
    <div class="fr-container" style="margin-bottom: 50px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <span class="fr-badge fr-badge-primary" style="margin-bottom: 8px;"><i class='bx bx-map-alt'></i> Interactive Geo-Grid</span>
            <h2 style="font-size: 28px; font-weight: 800; color: var(--text-main);">Live Transit & Campus Commute Grid</h2>
            <p style="color: var(--text-muted); font-size: 15px;">Explore verified student & commuter corridors. Click any corridor hub to jump directly into matching rides.</p>
        </div>
        <div class="map-container-relative">
            <div id="heroMap" style="height:420px; width:100%;"></div>
        </div>
    </div>

    <!-- Feature Pillars -->
    <div class="fr-container" style="margin-bottom: 50px;">
        <div class="fr-grid-3">
            <div class="fr-card fr-card-interactive">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--eco-bg); color: var(--eco); display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px;">
                    <i class='bx bxs-user-check'></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Spare Helmet Guarantee</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Drivers specify if spare helmet is provided so pillion riders travel safely, comfortably, and 100% legally.</p>
            </div>

            <div class="fr-card fr-card-interactive">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--primary-glow); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px;">
                    <i class='bx bxs-shield-alt-2'></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Verhoeff Aadhaar Verification</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Mathematical checksum validation prevents fake profiles. Plus driving licence and student email domain checks.</p>
            </div>

            <div class="fr-card fr-card-interactive">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--amber-bg); color: var(--amber); display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px;">
                    <i class='bx bxs-bolt'></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">0% Platform Cut Escrow</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">Direct peer-to-peer UPI settlements without commercial commissions or surge fees. Drivers get 100% fuel split.</p>
            </div>
        </div>
    </div>
</main>

<?php if (isset($_SESSION['user_id'])): ?>
    <a href="danger.php" class="floating-sos-btn"><i class='bx bxs-alarm-exclamation' style="font-size: 20px;"></i> Emergency SOS</a>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    function swapHomeInputs() {
        const orig = document.getElementById('origin');
        const dest = document.getElementById('destination');
        const temp = orig.value;
        orig.value = dest.value;
        dest.value = temp;
    }

    function locateHomeGps() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;
                heroMap.setView([userLat, userLng], 14);

                L.circle([userLat, userLng], {
                    color: '#10b981', fillColor: '#10b981', fillOpacity: 0.25, radius: 250
                }).addTo(heroMap);

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userLat}&lon=${userLng}`)
                    .then(r => r.json())
                    .then(data => {
                        const origInp = document.getElementById('origin');
                        if (origInp && data.display_name) {
                            origInp.value = data.display_name.split(',').slice(0, 2).join(',');
                        }
                    }).catch(e => console.log(e));
            }, function(err) {
                alert("Location access denied. Please type your pickup location.");
            }, { enableHighAccuracy: true });
        }
    }

    function setSearchType(cat) {
        document.getElementById('search_cat').value = cat;
        if (cat === 'bike') {
            document.getElementById('tab-bike').classList.add('active');
            document.getElementById('tab-car').classList.remove('active');
        } else {
            document.getElementById('tab-car').classList.add('active');
            document.getElementById('tab-bike').classList.remove('active');
        }
    }

    // Initialize Map with resilient tiles
    const heroMap = L.map('heroMap').setView([13.6288, 79.4192], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(heroMap);

    // Active Commute Corridor Hotspots
    const campusHubs = [
        { name: "Campus Main Gate", lat: 13.6288, lng: 79.4192, badge: "Main Hub", icon: "bxs-school" },
        { name: "Central Metro Terminal", lat: 13.6380, lng: 79.4280, badge: "Transit Hub", icon: "bx-train" },
        { name: "Tech Park Corridor", lat: 13.6180, lng: 79.4100, badge: "IT Tech Hub", icon: "bxs-buildings" },
        { name: "University Hostels Block", lat: 13.6240, lng: 79.4140, badge: "Student Zone", icon: "bxs-home-smile" }
    ];

    campusHubs.forEach(hub => {
        const hubIcon = L.divIcon({
            className: 'svg-pin-container',
            html: `
                <div class="svg-pin-tag pickup-tag">${hub.badge}</div>
                <svg width="28" height="34" viewBox="0 0 32 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 1C7.71573 1 1 7.71573 1 16C1 26.5 16 37 16 37C16 37 31 26.5 31 16C31 7.71573 24.2843 1 16 1Z" fill="#0284C7" stroke="#FFFFFF" stroke-width="2.5"/>
                    <circle cx="16" cy="15" r="5.5" fill="#FFFFFF"/>
                    <circle cx="16" cy="15" r="3" fill="#0369A1"/>
                </svg>
            `,
            iconSize: [60, 50],
            iconAnchor: [30, 48]
        });

        const marker = L.marker([hub.lat, hub.lng], { icon: hubIcon }).addTo(heroMap);
        marker.bindPopup(`
            <div style="font-family:'Outfit',sans-serif; min-width:180px;">
                <div style="font-size:11px; font-weight:700; color:var(--primary); text-transform:uppercase;">${hub.badge}</div>
                <strong style="font-size:15px; color:var(--text-main); display:block; margin:4px 0 10px;">${hub.name}</strong>
                <a href="find_ride.php?origin=${encodeURIComponent(hub.name)}" class="fr-btn fr-btn-primary fr-btn-sm" style="display:block; text-align:center;">Find Rides Here →</a>
            </div>
        `);
    });

    // Dual-Layer Commute Corridor Polyline
    const corridorCoords = [
        [13.6288, 79.4192],
        [13.6320, 79.4230],
        [13.6380, 79.4280]
    ];
    L.polyline(corridorCoords, {
        color: '#0f172a',
        weight: 8,
        opacity: 0.85,
        lineCap: 'round',
        lineJoin: 'round'
    }).addTo(heroMap);

    L.polyline(corridorCoords, {
        color: '#0284c7',
        weight: 5,
        opacity: 1,
        lineCap: 'round',
        lineJoin: 'round'
    }).addTo(heroMap);
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>