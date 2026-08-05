<?php
session_start();
include_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy & Safety Terms - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; flex: 1; }
        .policy-card {
            background: var(--card-bg); backdrop-filter: blur(12px); border: 1px solid var(--card-border);
            border-radius: 20px; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); margin-bottom: 30px;
        }
        h1 { font-size: 28px; font-weight: 800; color: var(--text-color); margin-bottom: 10px; }
        .last-updated { font-size: 13px; color: var(--text-muted); margin-bottom: 25px; display: block; }
        
        .section-block { margin-bottom: 25px; }
        .section-title { font-size: 18px; font-weight: 700; color: var(--primary-color); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .section-body { font-size: 14px; color: var(--text-muted); line-height: 1.6; }
        .section-body ul { padding-left: 20px; margin-top: 8px; }
        .section-body li { margin-bottom: 6px; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="policy-card">
        <h1>🔒 Privacy Policy & Safety Guidelines</h1>
        <span class="last-updated">Last Updated: August 2026 | Compliant with Indian Digital Data Protection Act</span>

        <div class="section-block">
            <h2 class="section-title"><i class='bx bxs-shield-check'></i> 1. Information Collection & Usage</h2>
            <div class="section-body">
                FlexiRide collects minimal necessary data to ensure safe commuter matching:
                <ul>
                    <li><strong>Account Credentials:</strong> Full name, verified email address, and active mobile number.</li>
                    <li><strong>Identity Verification:</strong> Aadhaar number and Driving License details for safety gatekeeper checks. Data is encrypted and used strictly for identity validation.</li>
                    <li><strong>Location Data:</strong> Pick-up and drop-off coordinates accessed via HTML5 Geolocation strictly during route search or ride posting.</li>
                </ul>
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title"><i class='bx bxs-lock-alt'></i> 2. Data Protection & Encryption</h2>
            <div class="section-body">
                We prioritize user privacy above all:
                <ul>
                    <li>User passwords are hashed using industry-standard BCRYPT algorithms.</li>
                    <li>Emergency contacts are stored in 3NF relational structures and accessible only during explicit SOS triggers.</li>
                    <li>We never sell or distribute commuter data to third-party advertisers.</li>
                </ul>
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title"><i class='bx bxs-alarm-exclamation'></i> 3. Safety & Emergency Assistance</h2>
            <div class="section-body">
                FlexiRide provides instant SOS Emergency features. When triggered, real-time GPS coordinates are dispatched to registered emergency contacts and campus safety channels.
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title"><i class='bx bxs-check-shield'></i> 4. Code of Conduct</h2>
            <div class="section-body">
                All drivers and passengers must adhere to campus vehicle safety guidelines, respect helmet mandates for two-wheeler pooling, and observe gender preference filters set by ride hosts.
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
