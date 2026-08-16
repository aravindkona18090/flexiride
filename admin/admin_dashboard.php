<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check if the user is the admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Fetch Live Platform Statistics
$usersCount    = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'] ?? 0;
$ridesCount    = $conn->query("SELECT COUNT(*) as cnt FROM rides")->fetch_assoc()['cnt'] ?? 0;
$bookingsCount = $conn->query("SELECT COUNT(*) as cnt FROM bookings")->fetch_assoc()['cnt'] ?? 0;
$verifiedCount = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE is_aadhaar_verified = 1 OR is_dl_verified = 1")->fetch_assoc()['cnt'] ?? 0;

// Fetch Recent Users
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Fetch Recent Offered Rides
$recentRides = $conn->query("SELECT r.*, u.name as driver_name FROM rides r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin System Control & Operations — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
    <style>
        .admin-stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .admin-stat-card .stat-val {
            font-size: 34px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 2px;
        }
        .admin-stat-card .stat-icon-bg {
            position: absolute;
            right: 18px;
            bottom: 14px;
            font-size: 54px;
            opacity: 0.12;
            color: var(--text-main);
        }

        .admin-launcher-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            padding: 20px;
            border-radius: var(--radius-md);
            text-align: center;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .admin-launcher-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .admin-launcher-card i {
            font-size: 30px;
            color: var(--primary);
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-shield-plus' style="color:var(--primary);"></i> System Operations Console
                </h1>
                <p style="font-size:14px; color:var(--text-muted); margin-top:2px;">Live monitoring of campus pooling traffic, verifications, and SOS dispatch.</p>
            </div>
            <span class="fr-badge fr-badge-eco"><i class='bx bx-check-circle'></i> SuperAdmin Active</span>
        </div>

        <!-- Quick Launchers Grid -->
        <div class="fr-grid-4" style="margin-bottom: 28px;">
            <a href="admin_manage_users.php" class="admin-launcher-card">
                <i class='bx bxs-user-detail'></i>
                <span>Manage Users</span>
            </a>
            <a href="admin_verify_docs.php" class="admin-launcher-card">
                <i class='bx bxs-id-card'></i>
                <span>Verify Aadhaar/DL</span>
            </a>
            <a href="admin_broadcast.php" class="admin-launcher-card">
                <i class='bx bxs-megaphone'></i>
                <span>Broadcast Alert</span>
            </a>
            <a href="admin_sos_logs.php" class="admin-launcher-card" style="border-color:var(--danger-border);">
                <i class='bx bxs-alarm-exclamation' style="color:var(--danger);"></i>
                <span style="color:var(--danger);">Emergency SOS Logs</span>
            </a>
        </div>

        <!-- Metrics Grid -->
        <div class="fr-grid-4" style="margin-bottom: 28px;">
            <div class="admin-stat-card">
                <div class="stat-val"><?php echo number_format($usersCount); ?></div>
                <div style="font-size:13.5px; color:var(--text-muted);">Registered Commuters</div>
                <i class='bx bxs-user-badge stat-icon-bg'></i>
            </div>
            <div class="admin-stat-card">
                <div class="stat-val" style="color:var(--eco);"><?php echo number_format($ridesCount); ?></div>
                <div style="font-size:13.5px; color:var(--text-muted);">Offered Rides</div>
                <i class='bx bxs-car stat-icon-bg'></i>
            </div>
            <div class="admin-stat-card">
                <div class="stat-val"><?php echo number_format($bookingsCount); ?></div>
                <div style="font-size:13.5px; color:var(--text-muted);">Completed Bookings</div>
                <i class='bx bxs-receipt stat-icon-bg'></i>
            </div>
            <div class="admin-stat-card">
                <div class="stat-val" style="color:var(--eco);"><?php echo number_format($verifiedCount); ?></div>
                <div style="font-size:13.5px; color:var(--text-muted);">Verified Trust Profiles</div>
                <i class='bx bxs-shield-check stat-icon-bg'></i>
            </div>
        </div>

        <!-- Recent Logs Grid -->
        <div class="fr-grid-2">
            <!-- Recent Users -->
            <div class="fr-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:17px; font-weight:800; color:var(--text-main);">Recent User Registrations</h3>
                    <a href="admin_manage_users.php" style="font-size:12.5px; color:var(--primary); text-decoration:none; font-weight:700;">View All →</a>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php while ($u = $recentUsers->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:var(--bg-input); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                            <div>
                                <div style="font-size:14px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                            <?php if ($u['is_aadhaar_verified'] ?? 0): ?>
                                <span class="fr-badge fr-badge-eco">Verified</span>
                            <?php else: ?>
                                <span class="fr-badge fr-badge-ghost">Standard</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Recent Rides -->
            <div class="fr-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:17px; font-weight:800; color:var(--text-main);">Recent Published Rides</h3>
                    <a href="../find_ride.php" target="_blank" style="font-size:12.5px; color:var(--primary); text-decoration:none; font-weight:700;">Marketplace →</a>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php while ($r = $recentRides->fetch_assoc()): ?>
                        <div style="padding:10px 14px; background:var(--bg-input); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:13.5px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($r['origin']); ?> ➔ <?php echo htmlspecialchars($r['destination']); ?></span>
                                <span style="font-size:13px; font-weight:800; color:var(--eco);">₹<?php echo $r['price']; ?></span>
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:3px;">
                                Driver: <?php echo htmlspecialchars($r['driver_name']); ?> • <?php echo $r['ride_date']; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
