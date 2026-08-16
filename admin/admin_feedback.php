<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$successMsg = "";

// Handle Delete Feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_feedback_id'])) {
    $f_id = (int)$_POST['delete_feedback_id'];
    $dStmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    if ($dStmt) {
        $dStmt->bind_param("i", $f_id);
        $dStmt->execute();
        $successMsg = "Feedback record dismissed successfully.";
    }
}

$sql = "SELECT * FROM feedback ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Stream — Admin FlexiRide</title>
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
                    <i class='bx bxs-star' style="color:#eab308;"></i> Platform Feedback & Commendations
                </h1>
                <p style="font-size:14px; color:var(--text-muted);">Review ideas, suggestions, and rider praise from campus commuters.</p>
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
            <input type="text" id="feedbackSearch" class="fr-input" placeholder="🔍 Search feedback entries by keyword or user..." onkeyup="filterFeedback()">
        </div>

        <div style="display:flex; flex-direction:column; gap:14px;" id="feedbackList">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="fr-card feedback-item" style="padding:22px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <div>
                                <h3 style="font-size:16px; font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></h3>
                                <span style="font-size:12.5px; color:var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></span>
                            </div>
                            <form method="POST" onsubmit="return confirm('Dismiss this feedback record?');">
                                <input type="hidden" name="delete_feedback_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="fr-btn fr-btn-danger fr-btn-sm"><i class='bx bx-trash'></i> Dismiss</button>
                            </form>
                        </div>
                        <div style="background:var(--bg-input); border:1px solid var(--border-subtle); padding:14px 18px; border-radius:var(--radius-md); font-size:14px; line-height:1.5; color:var(--text-main);">
                            <?php echo nl2br(htmlspecialchars($row['feedback'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="fr-card" style="text-align:center; padding:40px; color:var(--text-muted);">
                    No feedback records received yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function filterFeedback() {
        const q = document.getElementById('feedbackSearch').value.toLowerCase();
        const items = document.querySelectorAll('.feedback-item');
        items.forEach(i => {
            const txt = i.textContent.toLowerCase();
            i.style.display = txt.includes(q) ? 'block' : 'none';
        });
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
