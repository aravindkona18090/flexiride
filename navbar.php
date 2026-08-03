<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'db.php';

// Fetch user profile photo and unread notifications if logged in
$navProfilePhoto = '';
$navUserName = 'User';
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
        }
    }
}
?>

<script src="theme.js"></script>

<style>
    /* ============================================================
       Master Dynamic CSS Variable Design System
       ============================================================ */
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
        --success-color: #6ee7b7;
        --success-bg: rgba(16, 185, 129, 0.2);
        --danger-color: #f87171;
        --danger-bg: rgba(239, 68, 68, 0.2);
        --text-color: #ecfdf5;
        --text-muted: #a7f3d0;
        --input-bg: #042116;
        --input-border: #065f46;
    }

    [data-theme="cyberpunk"] {
        --bg-color: #120326;
        --card-bg: rgba(34, 9, 66, 0.88);
        --card-border: rgba(217, 70, 239, 0.25);
        --primary-color: #e879f9;
        --primary-gradient: linear-gradient(135deg, #c026d3 0%, #9333ea 100%);
        --success-color: #22d3ee;
        --success-bg: rgba(6, 182, 212, 0.2);
        --danger-color: #f43f5e;
        --danger-bg: rgba(244, 63, 94, 0.2);
        --text-color: #fae8ff;
        --text-muted: #d8b4fe;
        --input-bg: #1c053a;
        --input-border: #7e22ce;
    }

    [data-theme="royal"] {
        --bg-color: #f0f9ff;
        --card-bg: #ffffff;
        --card-border: #bae6fd;
        --primary-color: #0284c7;
        --primary-gradient: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
        --success-color: #16a34a;
        --success-bg: #dcfce7;
        --danger-color: #dc2626;
        --danger-bg: #fee2e2;
        --text-color: #0f172a;
        --text-muted: #475569;
        --input-bg: #f8fafc;
        --input-border: #cbd5e1;
    }

    [data-theme="amber"] {
        --bg-color: #fffbeb;
        --card-bg: #ffffff;
        --card-border: #fde68a;
        --primary-color: #d97706;
        --primary-gradient: linear-gradient(135deg, #d97706 0%, #ea580c 100%);
        --success-color: #15803d;
        --success-bg: #dcfce7;
        --danger-color: #b91c1c;
        --danger-bg: #fee2e2;
        --text-color: #1e1b4b;
        --text-muted: #6b7280;
        --input-bg: #fffbf0;
        --input-border: #fcd34d;
    }

    body {
        background-color: var(--bg-color) !important;
        color: var(--text-color) !important;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .navbar {
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--card-border);
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: background 0.3s ease, border-color 0.3s ease;
    }

    .logo {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-color);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: color 0.3s ease;
    }
    .logo-badge {
        background: var(--primary-gradient);
        color: white;
        padding: 6px 10px;
        border-radius: 10px;
        font-size: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    .logo:hover .logo-badge {
        transform: rotate(15deg) scale(1.08);
    }
    .logo-text {
        color: var(--text-color);
        font-weight: 800;
        letter-spacing: -0.5px;
        transition: color 0.3s ease;
    }
    .logo-text span {
        color: var(--primary-color);
        transition: color 0.3s ease;
    }
    
    .nav-links {
        display: flex;
        align-items: center;
        gap: 15px;
        list-style: none;
    }
    .nav-links a {
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 15px;
        transition: 0.3s;
    }
    .nav-links a:hover { color: var(--primary-color); }
    
    .theme-selector {
        background: var(--input-bg);
        color: var(--text-color);
        border: 1px solid var(--input-border);
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-login {
        background: var(--primary-gradient);
        color: white !important;
        padding: 8px 16px;
        border-radius: 10px;
        font-weight: 600;
    }

    /* Profile Avatar & Dropdown Container */
    .profile-dropdown-wrapper {
        position: relative;
        display: inline-block;
    }
    .profile-avatar-btn {
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 2px;
        border-radius: 50%;
        outline: none;
    }
    .nav-avatar-img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
        font-size: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .profile-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 48px;
        background: var(--card-bg);
        backdrop-filter: blur(16px);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        padding: 10px 0;
        width: 210px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        z-index: 2000;
    }
    .profile-menu-dropdown.show {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }
    .dropdown-header-name {
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-color);
        border-bottom: 1px solid var(--card-border);
        margin-bottom: 5px;
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

    @keyframes fadeIn { 0% { opacity: 0; transform: translateY(-8px); } 100% { opacity: 1; transform: translateY(0); } }

    .mobile-btn { display: none; background: none; border: none; color: var(--text-color); font-size: 24px; cursor: pointer; }

    @media (max-width: 768px) {
        .nav-links { display: none; flex-direction: column; position: absolute; top: 100%; left: 0; right: 0; background: var(--card-bg); padding: 20px; border-bottom: 1px solid var(--card-border); }
        .nav-links.active { display: flex; }
        .mobile-btn { display: block; }
    }
</style>

<nav class="navbar">
    <a href="index.php" class="logo">
        <span class="logo-badge"><i class='bx bxs-bolt'></i></span>
        <span class="logo-text">Flexi<span>Ride</span></span>
    </a>

    <button class="mobile-btn" onclick="toggleMobileMenu()"><i class='bx bx-menu'></i></button>

    <ul class="nav-links" id="navLinks">
        <li><a href="index.php">🏠 Home</a></li>
        <li><a href="find_ride.php">Find Ride</a></li>
        <li><a href="post_ride.php">Offer Ride</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <li>
                <select id="themeSelector" class="theme-selector" onchange="changeTheme(this.value)">
                    <option value="slate">🌌 Slate Dark</option>
                    <option value="emerald">💚 Emerald Eco</option>
                    <option value="cyberpunk">💜 Cyberpunk</option>
                    <option value="royal">☀️ Royal Light</option>
                    <option value="amber">🌅 Sunset Amber</option>
                </select>
            </li>

            <!-- Dynamic Profile Photo / Icon Dropdown -->
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
                    <a href="profile.php" class="dropdown-item-link"><i class='bx bxs-user-detail' style="color:var(--primary-color);"></i> My Profile</a>
                    <a href="myrides.php" class="dropdown-item-link"><i class='bx bxs-notepad' style="color:var(--success-color);"></i> My Offered Rides</a>
                    <a href="my_booked_rides.php" class="dropdown-item-link"><i class='bx bxs-receipt' style="color:#818cf8;"></i> My Booked Trips</a>
                    <a href="notifications.php" class="dropdown-item-link"><i class='bx bxs-bell' style="color:#f59e0b;"></i> Activity & Alerts</a>
                    <div style="border-top:1px solid var(--card-border); margin:5px 0;"></div>
                    <a href="logout.php" class="dropdown-item-link" style="color:var(--danger-color) !important;"><i class='bx bx-log-out'></i> Logout</a>
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
            <li><a href="login.php" class="btn-login">Login / Register</a></li>
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
