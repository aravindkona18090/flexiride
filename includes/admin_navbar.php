<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/db.php';

// Verify Admin Privileges
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$isSubfolder = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin');
$navRel = $isSubfolder ? '../' : '';
$adminRel = $isSubfolder ? '' : 'admin/';

// Unread notifications for admin
$navUnreadCount = 0;
$uId = $_SESSION['user_id'] ?? 0;
$nStmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND (is_read IS NULL OR is_read = 0)");
if ($nStmt) {
    $nStmt->bind_param("i", $uId);
    $nStmt->execute();
    $nRes = $nStmt->get_result()->fetch_assoc();
    $navUnreadCount = (int)($nRes['unread'] ?? 0);
}
?>

<!-- Boxicons Icons CDN with Fallback -->
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href='https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
    .admin-navbar {
        background: var(--card-bg);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--card-border);
        padding: 10px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .admin-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        white-space: nowrap;
    }
    .admin-brand-badge {
        background: var(--primary-gradient);
        color: white;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .admin-brand-text {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-color);
        letter-spacing: -0.5px;
    }
    .admin-brand-text span { color: var(--primary-color); }
    .admin-role-tag {
        font-size: 10px;
        background: rgba(239, 68, 68, 0.15);
        color: var(--danger-color);
        border: 1px solid var(--danger-color);
        padding: 2px 6px;
        border-radius: 6px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .admin-nav-menu {
        display: flex;
        gap: 8px;
        list-style: none;
        align-items: center;
        margin: 0;
        padding: 0;
    }
    .admin-nav-menu li { display: flex; align-items: center; }
    .admin-nav-link {
        color: var(--text-color);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 8px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s ease;
    }
    .admin-nav-link:hover, .admin-nav-link.active {
        background: rgba(56, 189, 248, 0.15);
        color: var(--primary-color);
    }

    .admin-select-theme {
        background: var(--input-bg);
        color: var(--text-color);
        border: 1px solid var(--input-border);
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
    }

    .nav-unread-dot {
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 6px #ef4444;
        animation: pulseRedDot 1.5s infinite;
    }
    @keyframes pulseRedDot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .admin-user-dropdown { position: relative; }
    .admin-avatar-btn {
        background: var(--primary-gradient);
        color: white;
        border: none;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 13px;
        white-space: nowrap;
    }
    .admin-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 42px;
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        border: 1px solid var(--card-border);
        border-radius: 14px;
        padding: 10px 0;
        min-width: 210px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 1001;
    }
    .admin-dropdown-menu.show { display: block; }
    .dropdown-header-name {
        padding: 6px 14px 10px 14px;
        border-bottom: 1px solid var(--card-border);
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
    }
    .dropdown-item-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        color: var(--text-color) !important;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: 0.2s;
    }
    .dropdown-item-link:hover {
        background: rgba(56, 189, 248, 0.15);
        color: var(--primary-color) !important;
    }
</style>

<nav class="admin-navbar">
    <a href="<?php echo $adminRel; ?>admin_dashboard.php" class="admin-brand">
        <span class="admin-brand-badge"><i class='bx bxs-shield-quarter'></i></span>
        <span class="admin-brand-text">Flexi<span>Ride</span></span>
        <span class="admin-role-tag">Admin</span>
    </a>

    <ul class="admin-nav-menu">
        <li><a href="<?php echo $adminRel; ?>admin_dashboard.php" class="admin-nav-link"><i class='bx bxs-dashboard'></i> Dashboard</a></li>
        <li><a href="<?php echo $adminRel; ?>admin_manage_users.php" class="admin-nav-link"><i class='bx bxs-user-account'></i> Users</a></li>
        <li><a href="<?php echo $adminRel; ?>admin_verify_docs.php" class="admin-nav-link"><i class='bx bxs-file-find'></i> Queue</a></li>
        <li><a href="<?php echo $adminRel; ?>admin_broadcast.php" class="admin-nav-link"><i class='bx bxs-megaphone'></i> Broadcast</a></li>
        <li><a href="<?php echo $adminRel; ?>admin_sos_logs.php" class="admin-nav-link"><i class='bx bxs-alarm-exclamation'></i> SOS Logs</a></li>
        <li>
            <a href="<?php echo $navRel; ?>notifications.php" class="admin-nav-link">
                <i class='bx bxs-bell'></i> Alerts
                <?php if ($navUnreadCount > 0): ?>
                    <span class="nav-unread-dot"></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <select id="themeSelector" class="admin-select-theme" onchange="changeTheme(this.value)">
                <option value="slate">🌌 Slate</option>
                <option value="emerald">💚 Emerald</option>
                <option value="cyberpunk">💜 Cyberpunk</option>
                <option value="royal">☀️ Royal</option>
                <option value="amber">🌅 Amber</option>
            </select>
        </li>
        <li class="admin-user-dropdown">
            <button type="button" class="admin-avatar-btn" onclick="toggleAdminMenu(event)">
                <i class='bx bxs-user-badge'></i> Admin <i class='bx bx-chevron-down'></i>
            </button>
            <div class="admin-dropdown-menu" id="adminMenu">
                <div class="dropdown-header-name">System Administrator 🛡️</div>
                <a href="<?php echo $adminRel; ?>admin_dashboard.php" class="dropdown-item-link"><i class='bx bxs-dashboard' style="color:var(--primary-color);"></i> Dashboard Control</a>
                <a href="<?php echo $adminRel; ?>admin_manage_users.php" class="dropdown-item-link"><i class='bx bxs-user-account' style="color:var(--success-color);"></i> Manage Users</a>
                <a href="<?php echo $adminRel; ?>admin_verify_docs.php" class="dropdown-item-link"><i class='bx bxs-file-find' style="color:#38bdf8;"></i> Document Queue</a>
                <a href="<?php echo $adminRel; ?>admin_broadcast.php" class="dropdown-item-link"><i class='bx bxs-megaphone' style="color:#f59e0b;"></i> Broadcast Alerts</a>
                <a href="<?php echo $adminRel; ?>admin_sos_logs.php" class="dropdown-item-link"><i class='bx bxs-alarm-exclamation' style="color:#ef4444;"></i> SOS Incident Logs</a>
                <div style="border-top:1px solid var(--card-border); margin:4px 0;"></div>
                <a href="<?php echo $navRel; ?>logout.php" class="dropdown-item-link" style="color:var(--danger-color) !important;"><i class='bx bx-log-out'></i> Logout</a>
            </div>
        </li>
    </ul>
</nav>

<script>
function toggleAdminMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('adminMenu');
    menu.classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('adminMenu');
    if (menu && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }
});
</script>
