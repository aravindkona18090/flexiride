<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ride_id = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : 0;

// Fix: Support both reviewed_id and driver_id parameters
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

            $successMessage = "Rating submitted successfully!";
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
    <title>Rate Trip & Rider - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }
        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        h2 { font-size: 24px; margin-bottom: 10px; color: var(--text-color); }
        .star-rating { display: flex; justify-content: center; gap: 10px; font-size: 32px; color: var(--text-muted); margin: 20px 0; cursor: pointer; }
        .star-rating i.active { color: #f59e0b; }
        textarea { width: 100%; height: 100px; padding: 12px; border-radius: 10px; border: 1px solid var(--input-border); background: var(--input-bg); color: var(--text-color); outline: none; margin-bottom: 20px; }
        .btn-submit { width: 100%; padding: 15px; border: none; border-radius: 12px; background: var(--primary-gradient); color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 15px; }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="card">
        <h2>⭐ Rate Your Trip Experience</h2>
        <p style="color:var(--text-muted); font-size:14px;">Help build trust in the FlexiRide community!</p>

        <?php if ($successMessage): ?>
            <div class="alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
            <a href="my_booked_rides.php" class="btn-submit" style="display:block; text-decoration:none;">Back to Booked Trips</a>
        <?php else: ?>
            <?php if ($errorMessage): ?>
                <div class="alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="rating" id="ratingVal" value="5">
                <div class="star-rating" id="stars">
                    <i class='bx bxs-star active' data-val="1"></i>
                    <i class='bx bxs-star active' data-val="2"></i>
                    <i class='bx bxs-star active' data-val="3"></i>
                    <i class='bx bxs-star active' data-val="4"></i>
                    <i class='bx bxs-star active' data-val="5"></i>
                </div>

                <textarea name="comment" placeholder="Write a short review (e.g. Safe driving, punctual, helmet provided)..."></textarea>
                <button type="submit" class="btn-submit">Submit Rating & Review</button>
            </form>
        <?php endif; ?>
    </div>
</div>

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
</body>
</html>
