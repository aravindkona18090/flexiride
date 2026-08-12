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
    <title>Admin System Analytics & Control Dashboard - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; width: 100%; }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-box h2 { font-size: 28px; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
        }
        .stat-card h3 { font-size: 32px; font-weight: 800; color: var(--primary-color); margin-bottom: 4px; }
        .stat-card p { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .stat-icon { position: absolute; right: 20px; bottom: 20px; font-size: 45px; opacity: 0.15; color: var(--text-color); }

        .dashboard-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 35px; }
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .section-card h3 { font-size: 18px; margin-bottom: 20px; color: var(--primary-color); display: flex; align-items: center; justify-content: space-between; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        .data-table th, .data-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--card-border); }
        .data-table th { color: var(--text-muted); font-weight: 600; font-size: 12px; text-transform: uppercase; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-verified { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }

        .admin-nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .nav-card-btn {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            padding: 18px;
            border-radius: 16px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 700;
            transition: all 0.3s;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
        }
        .nav-card-btn:hover { border-color: var(--primary-color); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        .nav-card-btn i { font-size: 28px; color: var(--primary-color); }

        @media (max-width: 768px) {
            .dashboard-sections { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>🛡️ FlexiRide System Control & Analytics</h2>
        <span style="background:var(--primary-gradient); color:white; padding:8px 16px; border-radius:12px; font-size:13px; font-weight:700;">
            <i class='bx bxs-user-badge'></i> Logged as System Administrator
        </span>
    </div>

    <!-- Quick Action Admin Links -->
    <div class="admin-nav-grid">
        <a href="admin_manage_users.php" class="nav-card-btn">
            <i class='bx bxs-user-account'></i> Manage Users
        </a>
        <a href="admin_verify_docs.php" class="nav-card-btn">
            <i class='bx bxs-shield-quarter' style="color:var(--success-color);"></i> Document Queue
        </a>
        <a href="admin_broadcast.php" class="nav-card-btn">
            <i class='bx bxs-megaphone' style="color:var(--primary-color);"></i> Broadcast Alert
        </a>
        <a href="admin_sos_logs.php" class="nav-card-btn">
            <i class='bx bxs-alarm-exclamation' style="color:var(--danger-color);"></i> SOS Incident Logs
        </a>
        <a href="admin_queries.php" class="nav-card-btn">
            <i class='bx bxs-message-square-detail'></i> User Queries
        </a>
        <a href="admin_feedback.php" class="nav-card-btn">
            <i class='bx bxs-star'></i> Platform Feedback
        </a>
    </div>

    <!-- Live Analytics Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo number_format($usersCount); ?></h3>
            <p>Total Registered Commuters</p>
            <i class='bx bxs-group stat-icon'></i>
        </div>
        <div class="stat-card">
            <h3><?php echo number_format($ridesCount); ?></h3>
            <p>Offered Commute Rides</p>
            <i class='bx bxs-navigation stat-icon'></i>
        </div>
        <div class="stat-card">
            <h3><?php echo number_format($bookingsCount); ?></h3>
            <p>Confirmed Passenger Bookings</p>
            <i class='bx bxs-coupon stat-icon'></i>
        </div>
        <div class="stat-card">
            <h3><?php echo number_format($verifiedCount); ?></h3>
            <p>Identity Verified Commuters</p>
            <i class='bx bxs-shield-quarter stat-icon'></i>
        </div>
    </div>

    <!-- Recent Platform Activity Section -->
    <div class="dashboard-sections">
        <div class="section-card">
            <h3>
                <span><i class='bx bxs-user-plus'></i> Recently Registered Users</span>
                <a href="admin_manage_users.php" style="font-size:12px; color:var(--primary-color); text-decoration:none;">View All →</a>
            </h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                        <?php while ($u = $recentUsers->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if ($u['is_aadhaar_verified'] || $u['is_dl_verified']): ?>
                                        <span class="badge badge-verified">Verified</span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:12px;">Standard</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No users registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-card">
            <h3>
                <span><i class='bx bxs-car'></i> Latest Offered Commutes</span>
                <a href="../find_ride.php" style="font-size:12px; color:var(--primary-color); text-decoration:none;">View All →</a>
            </h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Fare</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentRides && $recentRides->num_rows > 0): ?>
                        <?php while ($r = $recentRides->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['origin']); ?> ➔ <?php echo htmlspecialchars($r['destination']); ?></strong></td>
                                <td style="color:var(--text-muted);"><?php echo htmlspecialchars($r['driver_name']); ?></td>
                                <td style="color:var(--success-color); font-weight:700;">₹<?php echo $r['price']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No rides published yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
