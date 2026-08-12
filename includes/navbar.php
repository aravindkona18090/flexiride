<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db.php';

$isSubfolder = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin');
$navRel = $isSubfolder ? '../' : '';
$adminRel = $isSubfolder ? '' : 'admin/';

// Fetch user profile photo and unread notifications if logged in
$navProfilePhoto = '';
$navUserName = 'User';
$navUnreadCount = 0;

if (isset($_SESSION['user_id'])) {
    $uId = $_SESSION['user_id'];

    $userStmt = $conn->prepare("SELECT name, profile_photo FROM users WHERE id = ?");
    if ($userStmt) {
        $userStmt->bind_param("i", $uId);
        $userStmt->execute();
        $userData = $userStmt->get_result()->fetch_assoc();
        if ($userData) {
            $navUserName = $userData['name'];
            $navProfilePhoto = $userData['profile_photo'] ?? '';
            if (!empty($navProfilePhoto) && !str_starts_with($navProfilePhoto, 'http') && !str_starts_with($navProfilePhoto, '/')) {
                $navProfilePhoto = $navRel . $navProfilePhoto;
            }
        }
    }

    $nStmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND (is_read IS NULL OR is_read = 0)");
    if ($nStmt) {
        $nStmt->bind_param("i", $uId);
        $nStmt->execute();
        $nRes = $nStmt->get_result()->fetch_assoc();
        $navUnreadCount = (int)($nRes['unread'] ?? 0);
    }
}
?>

<script src="<?php echo $navRel; ?>assets/js/theme.js?v=<?php echo time(); ?>"></script>

<style>
    :root, [data-theme="slate"] {
        --bg-color: #0f172a;
        --card-bg: rgba(30, 41, 59, 0.85);
        --card-border: rgba(255, 255, 255, 0.1);
        --primary-color: #38bdf8;
        --primary-gradient: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
        --success-color: #4ade80;
        --success-bg: rgba(34, 197, 94, 0.15);
        --danger-color: #f87171;
        --danger-bg: rgba(239, 68, 68, 0.15);
        --text-color: #f8fafc;
        --text-muted: #94a3b8;
        --input-bg: #0f172a;
        --input-border: #334155;
    }

    [data-theme="emerald"] {
        --bg-color: #062c1e;
        --card-bg: rgba(11, 62, 43, 0.88);
        --card-border: rgba(52, 211, 153, 0.25);
        --primary-color: #34d399;
        --primary-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
        --success-color: #34d399;
        --text-color: #ecfdf5;
        --text-muted: #6ee7b7;
        --input-bg: #062c1e;
        --input-border: #047857;
    }

    [data-theme="cyberpunk"] {
        --bg-color: #180b28;
        --card-bg: rgba(39, 17, 63, 0.88);
        --card-border: rgba(236, 72, 153, 0.3);
        --primary-color: #f472b6;
        --primary-gradient: linear-gradient(135deg, #c084fc 0%, #db2777 100%);
        --success-color: #a855f7;
        --text-color: #fdf4ff;
        --text-muted: #e879f9;
        --input-bg: #180b28;
        --input-border: #7e22ce;
    }

    [data-theme="royal"] {
        --bg-color: #f8fafc;
        --card-bg: #ffffff;
        --card-border: #e2e8f0;
        --primary-color: #0284c7;
        --primary-gradient: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
        --success-color: #16a34a;
        --text-color: #0f172a;
        --text-muted: #64748b;
        --input-bg: #f1f5f9;
        --input-border: #cbd5e1;
    }

    [data-theme="amber"] {
        --bg-color: #1c1917;
        --card-bg: rgba(44, 36, 32, 0.88);
        --card-border: rgba(245, 158, 11, 0.3);
        --primary-color: #fbbf24;
        --primary-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
        --success-color: #10b981;
        --text-color: #fffbeb;
        --text-muted: #fde68a;
        --input-bg: #1c1917;
        --input-border: #78350f;
    }

    .navbar {
        background: var(--card-bg);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--card-border);
        padding: 14px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }
    .logo {
        font-size: 24px;
        font-weight: 800;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.5px;
    }
    .logo-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 4px 10px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .logo-text {
        color: var(--text-color);
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .logo-text span {
        color: var(--primary-color);
    }
    .nav-links {
        display: flex;
        gap: 22px;
        list-style: none;
        align-items: center;
    }
    .nav-links a {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        padding: 6px 12px;
        border-radius: 8px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .nav-links a:hover {
        color: var(--primary-color);
        background: rgba(56, 189, 248, 0.1);
    }
    .btn-login {
        background: var(--primary-gradient);
        color: white !important;
        padding: 8px 18px !important;
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
    }
    .theme-selector {
        background: var(--input-bg);
        color: var(--text-color);
        border: 1px solid var(--input-border);
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
    }
    .profile-dropdown-wrapper { position: relative; }
    .profile-avatar-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
    }
    .nav-avatar-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
    }
    .nav-avatar-fallback {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .profile-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 50px;
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 12px 0;
        min-width: 220px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 1001;
        animation: fadeIn 0.2s ease-in-out;
    }
    .profile-menu-dropdown.show { display: block; }
    .dropdown-header-name {
        padding: 8px 16px 12px 16px;
        border-bottom: 1px solid var(--card-border);
        font-size: 14px;
        font-weight: 700;
        color: var(--text-color);
    }
    .dropdown-item-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        color: var(--text-color) !important;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: 0.2s;
    }
    .dropdown-item-link:hover {
        background: rgba(56, 189, 248, 0.15);
        color: var(--primary-color) !important;
    }

    .nav-unread-dot {
        width: 10px;
        height: 10px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #ef4444;
        animation: pulseRedDot 1.5s infinite;
    }
    @keyframes pulseRedDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    @keyframes fadeIn { 0% { opacity: 0; transform: translateY(-8px); } 100% { opacity: 1; transform: translateY(0); } }

    .mobile-btn { display: none; background: none; border: none; color: var(--text-color); font-size: 24px; cursor: pointer; }

    @media (max-width: 768px) {
        .nav-links { display: none; flex-direction: column; position: absolute; top: 100%; left: 0; right: 0; background: var(--card-bg); padding: 20px; border-bottom: 1px solid var(--card-border); }
        .nav-links.active { display: flex; }
        .mobile-btn { display: block; }
    }
</style>

<?php if (!empty($_SESSION['alert_message'])): ?>
    <div style="background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px 20px; text-align: center; font-weight: 600; font-size: 14px; position: relative;">
        <?php echo htmlspecialchars($_SESSION['alert_message']); ?>
        <?php unset($_SESSION['alert_message']); ?>
    </div>
<?php endif; ?>

<nav class="navbar">
    <a href="<?php echo $navRel; ?>index.php" class="logo">
        <span class="logo-badge"><i class='bx bxs-bolt'></i></span>
        <span class="logo-text">Flexi<span>Ride</span></span>
    </a>

    <button class="mobile-btn" onclick="toggleMobileMenu()"><i class='bx bx-menu'></i></button>

    <ul class="nav-links" id="navLinks">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <li><a href="<?php echo $adminRel; ?>admin_dashboard.php">🛡️ Admin Dashboard</a></li>
        <?php else: ?>
            <li><a href="<?php echo $navRel; ?>index.php">🏠 Home</a></li>
            <li><a href="<?php echo $navRel; ?>find_ride.php">Find Ride</a></li>
            <li><a href="<?php echo $navRel; ?>post_ride.php">Offer Ride</a></li>
            <li><a href="<?php echo $navRel; ?>about.php">About</a></li>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <li>
                <a href="<?php echo $navRel; ?>notifications.php" style="position:relative; display:inline-flex; align-items:center; gap:6px;">
                    🔔 Alerts
                    <?php if ($navUnreadCount > 0): ?>
                        <span class="nav-unread-dot" title="<?php echo $navUnreadCount; ?> unread alert(s)"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <select id="themeSelector" class="theme-selector" onchange="changeTheme(this.value)">
                    <option value="slate">🌌 Slate Dark</option>
                    <option value="emerald">💚 Emerald Eco</option>
                    <option value="cyberpunk">💜 Cyberpunk</option>
                    <option value="royal">☀️ Royal Light</option>
                    <option value="amber">🌅 Sunset Amber</option>
                </select>
            </li>

            <li class="profile-dropdown-wrapper">
                <button type="button" class="profile-avatar-btn" onclick="toggleProfileMenu(event)">
                    <?php if (!empty($navProfilePhoto)): ?>
                        <img src="<?php echo htmlspecialchars($navProfilePhoto); ?>" class="nav-avatar-img" alt="Profile Picture">
                    <?php else: ?>
                        <div class="nav-avatar-fallback"><i class='bx bxs-user'></i></div>
                    <?php endif; ?>
                </button>

                <div class="profile-menu-dropdown" id="profileMenu">
                    <div class="dropdown-header-name">Hi, <?php echo htmlspecialchars($navUserName); ?> 👋</div>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="<?php echo $adminRel; ?>admin_dashboard.php" class="dropdown-item-link" style="color:var(--primary-color) !important; font-weight:700;"><i class='bx bxs-shield-quarter' style="color:var(--primary-color);"></i> 🛡️ Admin Dashboard</a>
                        <a href="<?php echo $adminRel; ?>admin_manage_users.php" class="dropdown-item-link"><i class='bx bxs-user-account' style="color:var(--success-color);"></i> 👥 Manage Users</a>
                        <a href="<?php echo $adminRel; ?>admin_verify_docs.php" class="dropdown-item-link"><i class='bx bxs-file-find' style="color:#38bdf8;"></i> 📋 Document Queue</a>
                        <a href="<?php echo $adminRel; ?>admin_broadcast.php" class="dropdown-item-link"><i class='bx bxs-megaphone' style="color:#f59e0b;"></i> 📢 Broadcast Alerts</a>
                        <a href="<?php echo $adminRel; ?>admin_sos_logs.php" class="dropdown-item-link"><i class='bx bxs-alarm-exclamation' style="color:#ef4444;"></i> 🚨 SOS Logs</a>
                        <div style="border-top:1px solid var(--card-border); margin:5px 0;"></div>
                        <a href="<?php echo $navRel; ?>profile.php" class="dropdown-item-link"><i class='bx bxs-user-detail' style="color:var(--primary-color);"></i> My Profile</a>
                    <?php else: ?>
                        <a href="<?php echo $navRel; ?>profile.php" class="dropdown-item-link"><i class='bx bxs-user-detail' style="color:var(--primary-color);"></i> My Profile</a>
                        <a href="<?php echo $navRel; ?>myrides.php" class="dropdown-item-link"><i class='bx bxs-notepad' style="color:var(--success-color);"></i> My Offered Rides</a>
                        <a href="<?php echo $navRel; ?>my_booked_rides.php" class="dropdown-item-link"><i class='bx bxs-receipt' style="color:#818cf8;"></i> My Booked Trips</a>
                    <?php endif; ?>
                    <a href="<?php echo $navRel; ?>notifications.php" class="dropdown-item-link" style="display:flex; justify-content:space-between; align-items:center;">
                        <span><i class='bx bxs-bell' style="color:#f59e0b;"></i> Activity & Alerts</span>
                        <?php if ($navUnreadCount > 0): ?>
                            <span class="nav-unread-dot" title="<?php echo $navUnreadCount; ?> unread alert(s)"></span>
                        <?php endif; ?>
                    </a>
                    <div style="border-top:1px solid var(--card-border); margin:5px 0;"></div>
                    <a href="<?php echo $navRel; ?>logout.php" class="dropdown-item-link" style="color:var(--danger-color) !important;"><i class='bx bx-log-out'></i> Logout</a>
                </div>
            </li>
        <?php else: ?>
            <li>
                <select id="themeSelector" class="theme-selector" onchange="changeTheme(this.value)">
                    <option value="slate">🌌 Slate Dark</option>
                    <option value="emerald">💚 Emerald Eco</option>
                    <option value="cyberpunk">💜 Cyberpunk</option>
                    <option value="royal">☀️ Royal Light</option>
                    <option value="amber">🌅 Sunset Amber</option>
                </select>
            </li>
            <li><a href="<?php echo $navRel; ?>login.php" class="btn-login">Login / Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    function toggleMobileMenu() {
        document.getElementById('navLinks').classList.toggle('active');
    }

    function toggleProfileMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('profileMenu');
        menu.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('profileMenu');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    });
</script>
