<?php
session_start();
include_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FlexiRide Campus Pooling</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; width: 100%; flex: 1; }
        .hero-box {
            background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--card-border);
            border-radius: 24px; padding: 40px; text-align: center; margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .hero-title { font-size: 32px; font-weight: 800; margin-bottom: 12px; }
        .hero-title span { color: var(--primary-color); }
        .hero-sub { font-size: 16px; color: var(--text-muted); max-width: 700px; margin: 0 auto 25px; line-height: 1.6; }
        
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .feature-card {
            background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 18px; padding: 25px;
            transition: transform 0.3s;
        }
        .feature-card:hover { transform: translateY(-5px); border-color: var(--primary-color); }
        .feature-icon { width: 50px; height: 50px; border-radius: 12px; background: rgba(56, 189, 248, 0.15); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 15px; }
        .feature-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .feature-desc { font-size: 14px; color: var(--text-muted); line-height: 1.5; }
        
        .stats-banner {
            background: var(--primary-gradient); color: white; border-radius: 20px; padding: 30px;
            display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; text-align: center; margin-bottom: 30px;
        }
        .stat-num { font-size: 36px; font-weight: 800; }
        .stat-label { font-size: 14px; font-weight: 500; opacity: 0.9; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <!-- Hero Header -->
    <div class="hero-box">
        <h1 class="hero-title">About <span>FlexiRide</span></h1>
        <p class="hero-sub">
            FlexiRide is India's premier bike-first ride pooling network built specifically for college campuses like <strong>GITAM Bengaluru</strong> and tech corridors. We connect daily commuters to share rides, split fuel costs, and build greener communities.
        </p>
        <div style="display:inline-flex; gap:10px; align-items:center; background:var(--input-bg); border:1px solid var(--primary-color); padding:8px 16px; border-radius:30px; font-size:13px; font-weight:700; color:var(--primary-color);">
            <span>🚀 Engineered for Fast, Affordable & Safe Commutes</span>
        </div>
    </div>

    <!-- Impact Stats -->
    <div class="stats-banner">
        <div>
            <div class="stat-num">50%</div>
            <div class="stat-label">Commute Cost Savings</div>
        </div>
        <div>
            <div class="stat-num">100%</div>
            <div class="stat-label">Aadhaar Verified Profiles</div>
        </div>
        <div>
            <div class="stat-num">0%</div>
            <div class="stat-label">Platform Hidden Fees</div>
        </div>
        <div>
            <div class="stat-num">🌱 Green</div>
            <div class="stat-label">Zero Carbon Waste</div>
        </div>
    </div>

    <!-- Core Pillars -->
    <div class="grid-3">
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-shield-alt-2'></i></div>
            <h3 class="feature-title">Verified Safety</h3>
            <p class="feature-desc">Every driver and rider is verified with Aadhaar and Driving License credentials. Female-only ride filters ensure maximum comfort and peace of mind.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-calculator'></i></div>
            <h3 class="feature-title">Win-Win Dynamic Pricing</h3>
            <p class="feature-desc">Fair fuel cost splitting algorithms automatically compute fair per-seat pricing based on exact route distance, preventing overcharging.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class='bx bxs-map-alt'></i></div>
            <h3 class="feature-title">OSRM Smart Routing</h3>
            <p class="feature-desc">Interactive OpenStreetMap & OSRM routing matching passengers even from intermediate waypoints along the ride journey.</p>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
