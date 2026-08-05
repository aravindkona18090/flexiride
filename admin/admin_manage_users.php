<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$sql = "SELECT u.*, uc.emergency_phone, uc.emergency_email1 
        FROM users u 
        LEFT JOIN user_emergency_contacts uc ON u.id = uc.user_id 
        ORDER BY u.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Commuter Accounts - Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 1100px; margin: 35px auto; padding: 0 20px; width: 100%; }

        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h2 { font-size: 26px; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        .btn-back { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--card-border); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Search & Filter Bar */
        .search-bar-box {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 15px 20px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex; gap: 15px; align-items: center;
        }
        .search-input-wrapper { flex: 1; position: relative; }
        .search-input-wrapper i { position: absolute; left: 14px; top: 12px; color: var(--text-muted); font-size: 18px; }
        .search-input-wrapper input {
            width: 100%; padding: 10px 14px 10px 40px; border-radius: 10px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); outline: none; font-size: 14px;
        }

        .user-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 20px; }
        .user-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex; flex-direction: column; justify-content: space-between;
        }

        .user-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .user-avatar-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); }
        .user-avatar-box { width: 50px; height: 50px; border-radius: 50%; background: var(--primary-gradient); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .badge-verified { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }

        .user-info-list { font-size: 13px; color: var(--text-muted); display: flex; flex-direction: column; gap: 6px; margin-bottom: 18px; }
        .user-info-list strong { color: var(--text-color); }

        .card-actions { display: flex; gap: 8px; }
        .btn-edit { flex: 1; text-align: center; background: var(--primary-gradient); color: white; padding: 10px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; }
        .btn-verify { flex: 1; text-align: center; background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 10px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; }
        .btn-delete { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 10px 14px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>👥 Commuter Account Management</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href='../index.php' class="btn-back"><i class='bx bx-home-alt'></i> 🏠 Home</a>
            <a href="admin_dashboard.php" class="btn-back"><i class='bx bx-left-arrow-alt'></i> Admin Dashboard</a>
        </div>
    </div>

    <!-- Instant Live Search Bar -->
    <div class="search-bar-box">
        <div class="search-input-wrapper">
            <i class='bx bx-search'></i>
            <input type="text" id="userSearch" placeholder="🔍 Search commuter by name, email, phone number..." onkeyup="filterUsers()">
        </div>
    </div>

    <div class="user-grid" id="userGrid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="user-card">
                    <div>
                        <div class="user-header">
                            <?php if (!empty($row['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_photo']); ?>" class="user-avatar-img" alt="User Photo">
                            <?php else: ?>
                                <div class="user-avatar-box"><i class='bx bxs-user'></i></div>
                            <?php endif; ?>
                            <div>
                                <h3 style="font-size:18px; color:var(--text-color);"><?php echo htmlspecialchars($row['name'] ?? 'User'); ?></h3>
                                <div style="margin-top:4px;">
                                    <?php if ($row['is_aadhaar_verified'] ?? 0): ?>
                                        <span class="badge-verified"><i class='bx bxs-check-circle'></i> Aadhaar</span>
                                    <?php endif; ?>
                                    <?php if ($row['is_dl_verified'] ?? 0): ?>
                                        <span class="badge-verified"><i class='bx bxs-check-circle'></i> Driver DL</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="user-info-list">
                            <div>📧 Email: <strong><?php echo htmlspecialchars($row['email'] ?? 'Not set'); ?></strong></div>
                            <div>📞 Phone: <strong><?php echo htmlspecialchars($row['phone'] ?? 'Not set'); ?></strong></div>
                            <div>🚨 SOS Phone: <strong><?php echo htmlspecialchars($row['emergency_phone'] ?? 'None'); ?></strong></div>
                            <div>🎓 College: <strong><?php echo htmlspecialchars($row['college_email'] ?? 'Standard'); ?></strong></div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="admin_edit_user.php?id=<?php echo $row['id']; ?>" class="btn-edit"><i class='bx bxs-edit'></i> Edit</a>
                        <a href="admin_verify_docs.php" class="btn-verify"><i class='bx bxs-shield-quarter'></i> Docs</a>
                        <a href="admin_delete_user.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete account for <?php echo htmlspecialchars($row['name']); ?>?');"><i class='bx bxs-trash'></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:var(--text-muted); text-align:center; grid-column:1/-1;">No users registered yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function filterUsers() {
        const q = document.getElementById('userSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.user-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(q) ? 'flex' : 'none';
        });
    }
</script>
</body>
</html>
