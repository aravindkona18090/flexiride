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
    <title>Manage Users — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
    <style>
        .admin-user-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 22px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }
        .admin-user-card:hover {
            border-color: var(--border-strong);
            box-shadow: var(--shadow-md);
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-user-account' style="color:var(--primary);"></i> Commuter Account Directory
                </h1>
                <p style="font-size:14px; color:var(--text-muted);">Manage member profiles, trust tiers, and contact records.</p>
            </div>
            <a href="admin_dashboard.php" class="fr-btn fr-btn-ghost fr-btn-sm"><i class='bx bx-left-arrow-alt'></i> Operations Console</a>
        </div>

        <!-- Search Bar -->
        <div class="fr-card" style="padding:14px 20px; margin-bottom:24px;">
            <input type="text" id="userSearch" class="fr-input" placeholder="Search commuter by name, email, or mobile..." onkeyup="filterUsers()">
        </div>

        <div class="fr-grid-3" id="userGrid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="admin-user-card">
                        <div>
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                                <?php if (!empty($row['profile_photo'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['profile_photo']); ?>" style="width:46px; height:46px; border-radius:50%; object-fit:cover; border:2px solid var(--primary);" alt="Avatar">
                                <?php else: ?>
                                    <div style="width:46px; height:46px; border-radius:50%; background:var(--primary-gradient); color:white; display:flex; align-items:center; justify-content:center; font-size:22px;">
                                        <i class='bx bxs-user'></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 style="font-size:16px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></h3>
                                    <span style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></span>
                                </div>
                            </div>

                            <div style="font-size:13px; color:var(--text-muted); display:flex; flex-direction:column; gap:4px; margin-bottom:16px;">
                                <div><i class='bx bxs-phone'></i> <strong>Phone:</strong> <?php echo htmlspecialchars($row['phone'] ?: 'N/A'); ?></div>
                                <div><i class='bx bxs-graduation'></i> <strong>Campus:</strong> <?php echo htmlspecialchars($row['campus_name'] ?: 'Not set'); ?></div>
                                <div>
                                    <i class='bx bxs-shield-check'></i> <strong>Aadhaar:</strong> 
                                    <?php if ($row['is_aadhaar_verified'] ?? 0): ?>
                                        <span class="fr-badge fr-badge-eco" style="font-size:11px;">Verified</span>
                                    <?php else: ?>
                                        <span class="fr-badge fr-badge-ghost" style="font-size:11px;">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; gap:8px; border-top:1px solid var(--border-subtle); padding-top:14px;">
                            <a href="admin_edit_user.php?id=<?php echo $row['id']; ?>" class="fr-btn fr-btn-primary fr-btn-sm" style="flex:1;">
                                <i class='bx bxs-edit'></i> Edit
                            </a>
                            <a href="admin_verify_docs.php?user_id=<?php echo $row['id']; ?>" class="fr-btn fr-btn-ghost fr-btn-sm" style="flex:1;">
                                <i class='bx bx-id-card'></i> Docs
                            </a>
                            <a href="admin_delete_user.php?id=<?php echo $row['id']; ?>" class="fr-btn fr-btn-danger fr-btn-sm" onclick="return confirm('Permanently remove this account?');">
                                <i class='bx bx-trash'></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="fr-card" style="grid-column:1/-1; text-align:center; padding:40px;">No registered accounts found.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function filterUsers() {
        const q = document.getElementById('userSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.admin-user-card');
        cards.forEach(c => {
            const txt = c.textContent.toLowerCase();
            c.style.display = txt.includes(q) ? 'flex' : 'none';
        });
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
