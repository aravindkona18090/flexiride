<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;
$reviewed_id = isset($_GET['reviewed_id']) ? (int)$_GET['reviewed_id'] : (isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0);

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating >= 1 && $rating <= 5 && $reviewed_id > 0) {
        $stmt = $conn->prepare("INSERT INTO ratings (ride_id, reviewer_id, reviewed_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $ride_id, $user_id, $reviewed_id, $rating, $comment);

        if ($stmt->execute()) {
            // Recalculate average rating for reviewed user
            $avgStmt = $conn->prepare("SELECT AVG(rating) as new_avg FROM ratings WHERE reviewed_id = ?");
            $avgStmt->bind_param("i", $reviewed_id);
            $avgStmt->execute();
            $newAvg = $avgStmt->get_result()->fetch_assoc()['new_avg'] ?? 5.0;

            $updateUser = $conn->prepare("UPDATE users SET avg_rating = ? WHERE id = ?");
            $updateUser->bind_param("di", $newAvg, $reviewed_id);
            $updateUser->execute();

            $successMessage = "Thank you! Your rating and review have been recorded.";
        } else {
            $errorMessage = "Rating error: " . $conn->error;
        }
    } else {
        $errorMessage = "Please select a valid 1 to 5 star rating.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Trip & Driver Experience — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .star-box {
            display: flex;
            justify-content: center;
            gap: 12px;
            font-size: 38px;
            color: var(--text-muted);
            margin: 20px 0;
            cursor: pointer;
        }
        .star-box i {
            transition: all 0.2s ease;
        }
        .star-box i.active {
            color: #f59e0b;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 40px 0;">
    <div class="fr-container-sm">
        <div class="fr-card" style="max-width: 500px; margin: 0 auto; text-align: center;">
            <div style="font-size: 40px; margin-bottom: 8px;">⭐</div>
            <h2 style="font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 6px;">
                Rate Your Trip Experience
            </h2>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                Your honest feedback maintains the campus Trust Shield and commuter safety score.
            </p>

            <?php if ($successMessage): ?>
                <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:16px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:700;">
                    ✅ <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <a href="my_booked_rides.php" class="fr-btn fr-btn-primary fr-btn-block">Back to My Bookings</a>
            <?php else: ?>
                <?php if ($errorMessage): ?>
                    <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px; border-radius:var(--radius-md); margin-bottom:18px; font-size:14px;">
                        ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="rating" id="ratingVal" value="5">
                    
                    <div class="star-box" id="stars">
                        <i class='bx bxs-star active' data-val="1"></i>
                        <i class='bx bxs-star active' data-val="2"></i>
                        <i class='bx bxs-star active' data-val="3"></i>
                        <i class='bx bxs-star active' data-val="4"></i>
                        <i class='bx bxs-star active' data-val="5"></i>
                    </div>

                    <div class="fr-form-group" style="text-align:left;">
                        <label class="fr-label">Comments & Commendations (Optional)</label>
                        <textarea name="comment" class="fr-textarea" placeholder="e.g. Prompt departure, smooth driving, spare helmet provided..." rows="3"></textarea>
                    </div>

                    <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                        Submit Review & Rating <i class='bx bx-check'></i>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    const stars = document.querySelectorAll('#stars i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const val = parseInt(this.getAttribute('data-val'));
            document.getElementById('ratingVal').value = val;
            stars.forEach((s, idx) => {
                if (idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });
        });
    });
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
