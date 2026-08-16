<?php
session_start();
include_once __DIR__ . '/includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy & Community Safety — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="padding: 36px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
                <span class="fr-badge fr-badge-eco"><i class='bx bxs-lock-alt'></i> Digital Privacy Standard</span>
                <span style="font-size:12.5px; color:var(--text-muted);">Updated: August 2026</span>
            </div>

            <h1 style="font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 20px;">
                Privacy Policy & Community Safety
            </h1>

            <div style="display:flex; flex-direction:column; gap:20px;">
                <div style="background:var(--bg-input); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <h3 style="font-size:16px; font-weight:700; color:var(--primary); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        <i class='bx bxs-shield-check'></i> 1. Minimal Data Collection
                    </h3>
                    <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5;">
                        FlexiRide collects only the credentials essential for safe commute matching: verified mobile numbers, college institute emails, and UIDAI Verhoeff Aadhaar / DL numbers for trust tiering.
                    </p>
                </div>

                <div style="background:var(--bg-input); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <h3 style="font-size:16px; font-weight:700; color:var(--primary); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        <i class='bx bxs-key'></i> 2. Encryption & Zero Data Brokerage
                    </h3>
                    <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5;">
                        Passwords are cryptographically secured using salted BCRYPT hashes. Your trip routes and personal contact numbers are never sold or shared with external advertising brokers.
                    </p>
                </div>

                <div style="background:var(--bg-input); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                    <h3 style="font-size:16px; font-weight:700; color:var(--primary); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                        <i class='bx bxs-alarm-exclamation'></i> 3. Emergency SOS Protocols
                    </h3>
                    <p style="font-size:13.5px; color:var(--text-muted); line-height:1.5;">
                        In panic situations, your live HTML5 GPS location is dispatched solely to your pre-configured parent/friend emergency contacts and campus safety desk.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
