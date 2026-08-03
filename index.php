<?php
session_start();
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlexiRide - India's Premier Bike-First Pooling Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color); color: var(--text-color); overflow-x: hidden; }

        /* Hero Section */
        .hero {
            padding: 60px 20px 40px;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-bg);
            border: 1px solid var(--success-color);
            color: var(--success-color);
            padding: 8px 18px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .hero h1 { font-size: 48px; font-weight: 800; line-height: 1.15; margin-bottom: 20px; color: var(--text-color); }
        .hero h1 span { color: var(--primary-color); }
        .hero p { font-size: 17px; color: var(--text-muted); line-height: 1.6; margin-bottom: 30px; }

        .search-box {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        .search-type { display: flex; gap: 15px; margin-bottom: 20px; }
        .type-tab {
            flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--input-border);
            background: var(--input-bg); color: var(--text-muted); font-weight: 600;
            text-align: center; cursor: pointer; transition: all 0.3s;
        }
        .type-tab.active { border-color: var(--primary-color); color: var(--primary-color); }
        .input-row { display: flex; flex-direction: column; gap: 15px; }
        .input-row input {
            width: 100%; padding: 15px; border-radius: 12px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); font-size: 16px; outline: none;
        }
        .btn-hero-search {
            width: 100%; padding: 16px; border-radius: 12px; border: none;
            background: var(--primary-gradient); color: white; font-size: 17px; font-weight: 700;
            cursor: pointer; transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
        }

        /* Live Platform Stats Counters */
        .stats-section {
            background: var(--card-bg);
            border-y: 1px solid var(--card-border);
            padding: 40px 20px;
            margin: 40px 0;
        }
        .stats-grid { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; text-align: center; }
        .stat-item h3 { font-size: 38px; font-weight: 800; color: var(--primary-color); margin-bottom: 6px; }
        .stat-item p { color: var(--text-muted); font-size: 15px; font-weight: 500; }

        .features { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 40px; }
        .section-title h2 { font-size: 32px; font-weight: 700; margin-bottom: 10px; color: var(--text-color); }
        .section-title p { color: var(--text-muted); font-size: 16px; }

        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .feature-card {
            background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 20px;
            padding: 30px 20px; text-align: center; transition: all 0.3s ease;
        }
        .feature-card:hover { border-color: var(--primary-color); transform: translateY(-5px); }
        .feature-icon { width: 60px; height: 60px; background: var(--success-bg); color: var(--success-color); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 20px; }
        .feature-card h3 { font-size: 20px; margin-bottom: 12px; color: var(--text-color); }
        .feature-card p { color: var(--text-muted); font-size: 15px; line-height: 1.5; }

        .reviews-section { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .review-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 18px; padding: 25px; }
        .review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .review-avatar { width: 44px; height: 44px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }

        .map-section { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        #heroMap { height: 350px; border-radius: 24px; border: 1px solid var(--card-border); }

        .floating-sos {
            position: fixed; bottom: 30px; right: 30px; background: var(--danger-color); color: white;
            padding: 16px 24px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 16px;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.5); display: flex; align-items: center; gap: 10px; z-index: 1000;
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .cards-grid, .stats-grid, .reviews-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 36px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<section class="hero">
    <div>
        <div class="hero-badge">🪖 Spare Helmets & Pillion Rides Included</div>
        <h1>India's Dedicated <span>Bike & Two-Wheeler</span> Sharing Platform</h1>
        <p>Share daily college & tech park commutes on motorcycles or scooters. Save up to 70% on fuel costs while traveling safely with verified riders.</p>
    </div>

    <div class="search-box">
        <div class="search-type">
            <div class="type-tab active" id="tab-bike" onclick="setSearchType('bike')">🏍️ Bike Pooling (Flagship)</div>
            <div class="type-tab" id="tab-car" onclick="setSearchType('car')">🚗 Car Sharing</div>
        </div>

        <form action="find_ride.php" method="GET" class="input-row">
            <input type="hidden" name="vehicle_category" id="search_cat" value="bike">
            <input type="text" name="origin" placeholder="📍 Enter Pickup Location (e.g. MBU / HSR Layout)" required>
            <input type="text" name="destination" placeholder="🏁 Enter Drop Location (e.g. Tirupati / Electronic City)" required>
            <button type="submit" class="btn-hero-search">Find Available Rides Now <i class='bx bx-right-arrow-alt'></i></button>
        </form>
    </div>
</section>

<!-- Live Platform Impact Counters -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-item">
            <h3>1,480+</h3>
            <p>Commutes & Rides Shared</p>
        </div>
        <div class="stat-item">
            <h3 style="color:var(--success-color);">₹65,000+</h3>
            <p>Total Student & Driver Fuel Saved</p>
        </div>
        <div class="stat-item">
            <h3>100%</h3>
            <p>Aadhaar & DL Verified Riders</p>
        </div>
    </div>
</section>

<section class="map-section">
    <div class="section-title">
        <h2>🗺️ Live Route Maps</h2>
        <p>Interactive OpenStreetMap route selection across cities, campuses & tech parks</p>
    </div>
    <div id="heroMap"></div>
</section>

<section class="features">
    <div class="section-title">
        <h2>Why Choose FlexiRide?</h2>
        <p>Built specifically for Indian commuters with safety, comfort & cost savings first</p>
    </div>

    <div class="cards-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-user-check'></i></div>
            <h3>🪖 Spare Helmet Guarantee</h3>
            <p>Drivers specify if a spare helmet is provided so pillions can ride comfortably and legally.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-zap'></i></div>
            <h3>⚡ Quick Campus & Office Commutes</h3>
            <p>Perfect for daily university students and IT tech park commuters splitting fuel costs.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-shield-alt-2'></i></div>
            <h3>🚨 WhatsApp SOS & Live Tracking</h3>
            <p>One-tap emergency GPS location sharing directly via WhatsApp to your emergency contacts.</p>
        </div>
    </div>
</section>

<!-- Recent Commuter Reviews -->
<section class="reviews-section">
    <div class="section-title">
        <h2>⭐ Commuter Community Reviews</h2>
        <p>What students and daily commuters say about FlexiRide</p>
    </div>

    <div class="reviews-grid">
        <div class="review-card">
            <div class="review-header">
                <div class="review-avatar"><i class='bx bxs-user'></i></div>
                <div>
                    <strong>Priya S.</strong>
                    <div style="color:#f59e0b; font-size:13px;">⭐⭐⭐⭐⭐</div>
                </div>
            </div>
            <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">"As a female student traveling to campus daily, the Aadhaar verification and female-only bike filter gives me 100% peace of mind."</p>
        </div>

        <div class="review-card">
            <div class="review-header">
                <div class="review-avatar"><i class='bx bxs-user'></i></div>
                <div>
                    <strong>Rahul K.</strong>
                    <div style="color:#f59e0b; font-size:13px;">⭐⭐⭐⭐⭐</div>
                </div>
            </div>
            <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">"I offer my bike pillion seat on my way to college every morning. Saved over ₹3,000 in petrol costs this month alone!"</p>
        </div>

        <div class="review-card">
            <div class="review-header">
                <div class="review-avatar"><i class='bx bxs-user'></i></div>
                <div>
                    <strong>Vikram M.</strong>
                    <div style="color:#f59e0b; font-size:13px;">⭐⭐⭐⭐⭐</div>
                </div>
            </div>
            <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">"The instant UPI QR code payment and PDF receipts make daily pooling super convenient and hassle-free."</p>
        </div>
    </div>
</section>

<?php if (isset($_SESSION['user_id'])): ?>
    <a href="danger.php" class="floating-sos"><i class='bx bxs-alarm-exclamation'></i> SOS Emergency</a>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
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

    const map = L.map('heroMap').setView([13.6288, 79.4192], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
</script>
</body>
</html>