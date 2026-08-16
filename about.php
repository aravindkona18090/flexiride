<?php
session_start();
include_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — FlexiRide Campus Commute Protocol</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container">
        <!-- Hero Header -->
        <div class="fr-card" style="text-align: center; padding: 48px 24px; margin-bottom: 30px;">
            <span class="fr-badge fr-badge-eco" style="margin-bottom: 12px;"><i class='bx bxs-leaf'></i> Velocity Eco-Pulse Commute</span>
            <h1 style="font-size: 36px; font-weight: 800; color: var(--text-main); margin-bottom: 14px;">
                About <span style="color:var(--primary);">FlexiRide</span>
            </h1>
            <p style="font-size: 16px; color: var(--text-muted); max-width: 680px; margin: 0 auto 20px; line-height: 1.6;">
                FlexiRide is a peer-to-peer bike-first commute network engineered specifically for university campuses and student tech corridors. We empower daily commuters to fill empty pillion seats, split petrol costs transparently 50/50, and eliminate unnecessary carbon emissions.
            </p>
        </div>

        <!-- Impact Numbers -->
        <div class="fr-grid-4" style="margin-bottom: 30px;">
            <div class="fr-card" style="text-align:center; padding:24px;">
                <div style="font-size:36px; font-weight:800; color:var(--primary);">50%</div>
                <div style="font-size:13.5px; color:var(--text-muted); margin-top:4px;">Direct Fuel Cost Split</div>
            </div>
            <div class="fr-card" style="text-align:center; padding:24px;">
                <div style="font-size:36px; font-weight:800; color:var(--eco);">100%</div>
                <div style="font-size:13.5px; color:var(--text-muted); margin-top:4px;">Aadhaar & DL Verified</div>
            </div>
            <div class="fr-card" style="text-align:center; padding:24px;">
                <div style="font-size:36px; font-weight:800; color:var(--primary);">0%</div>
                <div style="font-size:13.5px; color:var(--text-muted); margin-top:4px;">Middleman Commissions</div>
            </div>
            <div class="fr-card" style="text-align:center; padding:24px;">
                <div style="font-size:36px; font-weight:800; color:var(--eco);">-1.2kg</div>
                <div style="font-size:13.5px; color:var(--text-muted); margin-top:4px;">Avg. CO₂ Saved / Trip</div>
            </div>
        </div>

        <!-- Pillars Grid -->
        <div class="fr-grid-3" style="margin-bottom: 30px;">
            <div class="fr-card">
                <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-glow); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:26px; margin-bottom:14px;">
                    <i class='bx bx-navigation'></i>
                </div>
                <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:8px;">Live Wayfinder Matcher</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">
                    Extracts real geographic waypoints and transit corridors so commuters along intermediate pickup towns can hop on seamlessly.
                </p>
            </div>

            <div class="fr-card">
                <div style="width:48px; height:48px; border-radius:12px; background:var(--eco-bg); color:var(--eco); display:flex; align-items:center; justify-content:center; font-size:26px; margin-bottom:14px;">
                    <i class='bx bxs-shield-check'></i>
                </div>
                <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:8px;">Campus Trust Shield</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">
                    UIDAI Verhoeff Aadhaar checks, DL validation, campus email tags, and 4-digit departure OTPs safeguard every trip.
                </p>
            </div>

            <div class="fr-card">
                <div style="width:48px; height:48px; border-radius:12px; background:var(--primary-glow); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:26px; margin-bottom:14px;">
                    <i class='bx bx-coin-stack'></i>
                </div>
                <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:8px;">Zero-Cut UPI Escrow</h3>
                <p style="font-size:14px; color:var(--text-muted); line-height:1.5;">
                    Direct P2P UPI settlements (Google Pay, PhonePe, Paytm). No corporate surge commissions, 100% goes to driver petrol.
                </p>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
