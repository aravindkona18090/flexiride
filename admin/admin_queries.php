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
        $successMsg = "Support query marked as resolved.";
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
    <title>Support Queries — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bx-help-circle' style="color:var(--primary);"></i> Commuter Helpdesk Inquiries
                </h1>
                <p style="font-size:14px; color:var(--text-muted);">Manage incoming support requests and rider tickets.</p>
            </div>
            <a href="admin_dashboard.php" class="fr-btn fr-btn-ghost fr-btn-sm"><i class='bx bx-left-arrow-alt'></i> Operations Console</a>
        </div>

        <?php if ($successMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ✅ <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="fr-card" style="padding:14px 20px; margin-bottom:24px;">
            <input type="text" id="querySearch" class="fr-input" placeholder="🔍 Search support tickets by keyword or sender..." onkeyup="filterQueries()">
        </div>

        <div style="display:flex; flex-direction:column; gap:14px;" id="queryList">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="fr-card query-item" style="padding:22px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <div>
                                <h3 style="font-size:16px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <span style="font-size:12.5px; color:var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <a href="mailto:<?php echo urlencode($row['email']); ?>?subject=FlexiRide%20Support%20Response" class="fr-btn fr-btn-primary fr-btn-sm">
                                    <i class='bx bx-reply'></i> Reply
                                </a>
                                <form method="POST" onsubmit="return confirm('Mark this query as resolved?');">
                                    <input type="hidden" name="delete_query_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="fr-btn fr-btn-eco fr-btn-sm"><i class='bx bx-check'></i> Resolve</button>
                                </form>
                            </div>
                        </div>
                        <div style="background:var(--bg-input); border:1px solid var(--border-subtle); padding:14px 18px; border-radius:var(--radius-md); font-size:14px; line-height:1.5; color:var(--text-main);">
                            <?php echo nl2br(htmlspecialchars($row['query'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="fr-card" style="text-align:center; padding:40px; color:var(--text-muted);">
                    No pending support inquiries in the queue.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function filterQueries() {
        const q = document.getElementById('querySearch').value.toLowerCase();
        const items = document.querySelectorAll('.query-item');
        items.forEach(i => {
            const txt = i.textContent.toLowerCase();
            i.style.display = txt.includes(q) ? 'block' : 'none';
        });
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
