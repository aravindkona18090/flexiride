<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$successMsg = "";

// Handle Resolve/Delete Query
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_query_id'])) {
    $q_id = (int)$_POST['delete_query_id'];
    $dStmt = $conn->prepare("DELETE FROM queries WHERE id = ?");
    if ($dStmt) {
        $dStmt->bind_param("i", $q_id);
        $dStmt->execute();
        $successMsg = "Support query resolved and removed.";
    }
}

$sql = "SELECT * FROM queries ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commuter Support Queries - Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 1000px; margin: 35px auto; padding: 0 20px; width: 100%; }

        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-box h2 { font-size: 26px; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
        .btn-back { background: var(--input-bg); color: var(--text-color); border: 1px solid var(--card-border); padding: 10px 18px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* Search Bar */
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

        .query-grid { display: flex; flex-direction: column; gap: 15px; }
        .query-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 22px 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .user-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .user-meta h3 { font-size: 18px; color: var(--text-color); }

        .query-text { background: var(--input-bg); border: 1px solid var(--input-border); padding: 14px; border-radius: 12px; font-size: 14px; line-height: 1.5; color: var(--text-color); margin-bottom: 15px; }

        .query-actions { display: flex; gap: 10px; }
        .btn-reply { background: var(--primary-gradient); color: white; border: none; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .btn-resolve { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
    <div class="header-box">
        <h2>❓ Commuter Helpdesk Support Queries</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href='../index.php' class="btn-back"><i class='bx bx-home-alt'></i> 🏠 Home</a>
            <a href="admin_dashboard.php" class="btn-back"><i class='bx bx-left-arrow-alt'></i> Admin Dashboard</a>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="search-bar-box">
        <div class="search-input-wrapper">
            <i class='bx bx-search'></i>
            <input type="text" id="querySearch" placeholder="🔍 Search queries by commuter name, email or message text..." onkeyup="filterQueries()">
        </div>
    </div>

    <div class="query-grid" id="queryGrid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="query-card">
                    <div class="user-meta">
                        <div>
                            <h3>👤 <?php echo htmlspecialchars($row['name'] ?? 'Commuter'); ?></h3>
                            <span style="color:var(--primary-color); font-weight:600; font-size:13px;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></span>
                        </div>
                        <span style="font-size:12px; color:var(--text-muted);">
                            📅 <?php echo isset($row['submitted_at']) ? $row['submitted_at'] : (isset($row['created_at']) ? $row['created_at'] : 'Recently'); ?>
                        </span>
                    </div>

                    <div class="query-text">
                        ❓ "<?php echo nl2br(htmlspecialchars($row['query'] ?? $row['message'] ?? 'Need assistance with ride booking.')); ?>"
                    </div>

                    <div class="query-actions">
                        <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>?subject=FlexiRide%20Support%20Reply" class="btn-reply">
                            <i class='bx bxs-envelope'></i> Reply via Email
                        </a>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Mark query as resolved?');">
                            <input type="hidden" name="delete_query_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn-resolve"><i class='bx bxs-check-circle'></i> Mark Resolved</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px; background:var(--card-bg); border:1px solid var(--card-border); border-radius:20px;">
                <h3>No support queries pending!</h3>
                <p style="color:var(--text-muted); margin-top:6px;">Commuter support messages will appear here when submitted.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function filterQueries() {
        const q = document.getElementById('querySearch').value.toLowerCase();
        const cards = document.querySelectorAll('.query-card');
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(q) ? 'block' : 'none';
        });
    }
</script>
</body>
</html>
