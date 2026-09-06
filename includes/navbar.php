<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db.php';

$isSubfolder = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin');
$navRel = $isSubfolder ? '../' : '';
$adminRel = $isSubfolder ? '' : 'admin/';
$currentScript = basename($_SERVER['SCRIPT_NAME']);

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

<!-- Official FlexiRide Branded Favicon -->
<link rel="icon" type="image/png" href="<?php echo $navRel; ?>favicon.png?v=<?php echo time(); ?>">
<link rel="shortcut icon" href="<?php echo $navRel; ?>favicon.ico?v=<?php echo time(); ?>">
<link rel="apple-touch-icon" href="<?php echo $navRel; ?>favicon.png">

<!-- Boxicons Icons CDN with Fallback -->
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href='https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<!-- Master FlexiRide CSS Design System & Theme Engine -->
<link rel="stylesheet" href="<?php echo $navRel; ?>assets/css/flexiride.css?v=<?php echo time(); ?>">
<script src="<?php echo $navRel; ?>assets/js/theme.js?v=<?php echo time(); ?>"></script>

<style>
    /* Navbar Local Specific Overrides */
    .fr-navbar-wrap {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: var(--bg-surface);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-bottom: 1px solid var(--border-subtle);
    }

    .fr-navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
        max-width: 1240px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .fr-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 800;
        color: var(--text-main);
        text-decoration: none;
    }
    .fr-brand .brand-logo-glow {
        width: 38px;
        height: 38px;
        background: var(--primary-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        box-shadow: 0 0 16px var(--primary-glow);
        font-size: 22px;
    }
    .fr-brand span {
        color: var(--primary);
        transition: color 0.3s ease;
    }

    .brand-logo-light { display: none !important; }
    .brand-logo-dark { display: block !important; }

    [data-theme="royal"] .brand-logo-dark { display: none !important; }
    [data-theme="royal"] .brand-logo-light { display: block !important; }

    .fr-nav-menu {
        display: flex;
        align-items: center;
        gap: 6px;
        list-style: none;
    }

    .fr-nav-link {
        padding: 8px 16px;
        border-radius: var(--radius-pill);
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-muted);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .fr-nav-link:hover {
        color: var(--primary);
        background: var(--bg-surface-elevated);
    }

    .fr-nav-link.active {
        color: var(--primary);
        background: var(--bg-surface-elevated);
    }

    .theme-selector {
        background: var(--bg-input);
        color: var(--text-main);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-pill);
        padding: 7px 12px;
        font-size: 13px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
    }

    .profile-dropdown-wrapper {
        position: relative;
    }

    .profile-avatar-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
    }

    .nav-avatar-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }

    .nav-avatar-fallback {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: var(--primary-gradient);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .profile-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 50px;
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: 16px;
        width: 240px;
        padding: 12px;
        box-shadow: var(--shadow-lg);
        z-index: 2000;
    }
    .profile-menu-dropdown.show {
        display: block;
        animation: fadeIn 0.2s ease forwards;
    }

    .dropdown-header-name {
        padding: 8px 12px 10px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-main);
        border-bottom: 1px solid var(--border-subtle);
        margin-bottom: 6px;
    }

    .dropdown-item-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        color: var(--text-muted);
        font-size: 13.5px;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .dropdown-item-link:hover {
        background: var(--bg-input);
        color: var(--primary);
    }

    .nav-unread-dot {
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 8px #ef4444;
    }

    .fr-navbar-mobile-actions {
        display: none;
        align-items: center;
        gap: 8px;
    }
    .theme-selector-mobile {
        background: var(--bg-input);
        color: var(--text-main);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-pill);
        padding: 5px 8px;
        font-size: 13px;
        outline: none;
        cursor: pointer;
    }
    .mobile-notif-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--bg-surface-elevated);
        border: 1px solid var(--border-subtle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--text-main);
        position: relative;
        text-decoration: none;
    }
    @media (max-width: 860px) {
        .fr-navbar-mobile-actions {
            display: flex;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<?php if (!empty($_SESSION['alert_message'])): ?>
    <div style="background: var(--eco-bg); color: var(--eco); border-bottom: 1px solid var(--eco-border); padding: 12px 20px; text-align: center; font-weight: 700; font-size: 14px; position: relative;">
        ⚡ <?php echo htmlspecialchars($_SESSION['alert_message']); ?>
        <?php unset($_SESSION['alert_message']); ?>
    </div>
<?php endif; ?>

<header class="fr-navbar-wrap">
    <div class="fr-navbar">
        <a href="<?php echo $navRel; ?>index.php" class="fr-brand">
            <img src="<?php echo $navRel; ?>images/logo_dark.png" class="brand-logo-dark" alt="FlexiRide" style="width:40px; height:40px; object-fit:contain; border-radius:10px; filter: drop-shadow(0 2px 8px rgba(2,132,199,0.3));">
            <img src="<?php echo $navRel; ?>images/logo_light.png" class="brand-logo-light" alt="FlexiRide" style="width:40px; height:40px; object-fit:contain; border-radius:10px; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.1));">
            <div>Flexi<span>Ride</span></div>
        </a>

        <!-- 📱 Mobile Top Header Actions -->
        <div class="fr-navbar-mobile-actions">
            <select class="theme-selector-mobile" onchange="changeTheme(this.value)" aria-label="Theme Selector">
                <option value="slate">🌌</option>
                <option value="emerald">💚</option>
                <option value="cyberpunk">💜</option>
                <option value="royal">☀️</option>
            </select>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $navRel; ?>notifications.php" class="mobile-notif-btn" title="Notifications">
                    <i class='bx bxs-bell'></i>
                    <?php if ($navUnreadCount > 0): ?><span class="nav-unread-dot" style="position:absolute; top:6px; right:6px;"></span><?php endif; ?>
                </a>
            <?php else: ?>
                <a href="<?php echo $navRel; ?>login.php" class="fr-btn fr-btn-primary fr-btn-sm" style="padding: 5px 12px; font-size: 12px;">Login</a>
            <?php endif; ?>
        </div>

        <ul class="fr-nav-menu" id="navLinks">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <li><a href="<?php echo $adminRel; ?>admin_dashboard.php" class="fr-nav-link <?php echo ($currentScript === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
                <li><a href="<?php echo $adminRel; ?>admin_manage_users.php" class="fr-nav-link <?php echo ($currentScript === 'admin_manage_users.php') ? 'active' : ''; ?>"><i class='bx bxs-user-account'></i> Users</a></li>
                <li><a href="<?php echo $adminRel; ?>admin_verify_docs.php" class="fr-nav-link <?php echo ($currentScript === 'admin_verify_docs.php') ? 'active' : ''; ?>"><i class='bx bxs-file-find'></i> Verification</a></li>
                <li><a href="<?php echo $adminRel; ?>admin_sos_logs.php" class="fr-nav-link <?php echo ($currentScript === 'admin_sos_logs.php') ? 'active' : ''; ?>"><i class='bx bxs-alarm-exclamation'></i> SOS</a></li>
            <?php else: ?>
                <li><a href="<?php echo $navRel; ?>index.php" class="fr-nav-link <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $navRel; ?>find_ride.php" class="fr-nav-link <?php echo ($currentScript === 'find_ride.php') ? 'active' : ''; ?>"><i class='bx bx-search'></i> Find Ride</a></li>
                <li><a href="<?php echo $navRel; ?>post_ride.php" class="fr-nav-link <?php echo ($currentScript === 'post_ride.php') ? 'active' : ''; ?>"><i class='bx bx-plus-circle'></i> Post Ride</a></li>
                <li><a href="<?php echo $navRel; ?>about.php" class="fr-nav-link <?php echo ($currentScript === 'about.php') ? 'active' : ''; ?>">About</a></li>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="<?php echo $navRel; ?>notifications.php" class="fr-nav-link" title="Notifications">
                        <i class='bx bxs-bell'></i>
                        <?php if ($navUnreadCount > 0): ?>
                            <span class="nav-unread-dot"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <select id="themeSelector" class="theme-selector" onchange="changeTheme(this.value)" aria-label="Theme Selector">
                        <option value="slate">🌌 Slate</option>
                        <option value="emerald">💚 Emerald</option>
                        <option value="cyberpunk">💜 Cyberpunk</option>
                        <option value="royal">☀️ Light</option>
                    </select>
                </li>
                <li class="profile-dropdown-wrapper">
                    <button type="button" class="profile-avatar-btn" onclick="toggleProfileMenu(event)" aria-label="User Profile Menu">
                        <?php if (!empty($navProfilePhoto)): ?>
                            <img src="<?php echo htmlspecialchars($navProfilePhoto); ?>" class="nav-avatar-img" alt="Profile">
                        <?php else: ?>
                            <div class="nav-avatar-fallback"><i class='bx bxs-user'></i></div>
                        <?php endif; ?>
                    </button>

                    <div class="profile-menu-dropdown" id="profileMenu">
                        <div class="dropdown-header-name">Hi, <?php echo htmlspecialchars($navUserName); ?> 👋</div>
                        <a href="<?php echo $navRel; ?>profile.php" class="dropdown-item-link"><i class='bx bxs-user-circle' style="color:var(--primary);"></i> My Profile</a>
                        <a href="<?php echo $navRel; ?>myrides.php" class="dropdown-item-link"><i class='bx bxs-car' style="color:var(--eco);"></i> Offered Rides</a>
                        <a href="<?php echo $navRel; ?>my_booked_rides.php" class="dropdown-item-link"><i class='bx bxs-receipt' style="color:#818cf8;"></i> Booked Trips</a>
                        <a href="<?php echo $navRel; ?>danger.php" class="dropdown-item-link" style="color:var(--danger) !important;"><i class='bx bxs-alarm-exclamation' style="color:var(--danger);"></i> Emergency SOS</a>
                        <div style="border-top:1px solid var(--border-subtle); margin:6px 0;"></div>
                        <a href="<?php echo $navRel; ?>logout.php" class="dropdown-item-link" style="color:var(--danger) !important;"><i class='bx bx-log-out'></i> Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li>
                    <select id="themeSelector" class="theme-selector" onchange="changeTheme(this.value)" aria-label="Theme Selector">
                        <option value="slate">🌌 Slate</option>
                        <option value="emerald">💚 Emerald</option>
                        <option value="cyberpunk">💜 Cyberpunk</option>
                        <option value="royal">☀️ Light</option>
                    </select>
                </li>
                <li><a href="<?php echo $navRel; ?>login.php" class="fr-btn fr-btn-primary fr-btn-sm">Login / Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<!-- 📱 Fixed Mobile Ergonomic Commute Dock (Visible on Mobile Screens) -->
<nav class="mobile-commute-dock" aria-label="Mobile Navigation Dock">
    <a href="<?php echo $navRel; ?>index.php" class="dock-item <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">
        <i class='bx bxs-home-smile'></i>
        <span>Home</span>
    </a>
    <a href="<?php echo $navRel; ?>find_ride.php" class="dock-item <?php echo ($currentScript === 'find_ride.php') ? 'active' : ''; ?>">
        <i class='bx bx-search-alt-2'></i>
        <span>Find</span>
    </a>
    <a href="<?php echo $navRel; ?>post_ride.php" class="dock-item dock-item-post" title="Post Ride">
        <i class='bx bx-plus'></i>
    </a>
    <a href="<?php echo $navRel; ?><?php echo isset($_SESSION['user_id']) ? 'my_booked_rides.php' : 'login.php'; ?>" class="dock-item <?php echo ($currentScript === 'my_booked_rides.php' || $currentScript === 'myrides.php') ? 'active' : ''; ?>">
        <i class='bx bxs-car'></i>
        <span>Rides</span>
    </a>
    <a href="<?php echo $navRel; ?><?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'; ?>" class="dock-item <?php echo ($currentScript === 'profile.php') ? 'active' : ''; ?>">
        <i class='bx bxs-user-circle'></i>
        <span>Profile</span>
    </a>
</nav>

<script>
    function toggleProfileMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('profileMenu');
        if (menu) menu.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('profileMenu');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    });
</script>
