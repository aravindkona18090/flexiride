<?php
session_start();
include_once __DIR__ . '/includes/db.php';

$name = "";
$email = "";
$successMsg = "";
$errorMsg = "";

// Auto-fill logged in user info
if (isset($_SESSION['user_id'])) {
    $u_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $u_stmt->bind_param("i", $_SESSION['user_id']);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result()->fetch_assoc();
    if ($u_res) {
        $name = $u_res['name'];
        $email = $u_res['email'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $feedback = trim($_POST["Feedback"]);

    if (!empty($name) && !empty($email) && !empty($feedback)) {
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, feedback) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $feedback);
        if ($stmt->execute()) {
            $successMsg = "Thank you for your valuable feedback! Your thoughts help us make FlexiRide better for everyone. ⭐";
        } else {
            $errorMsg = "Database error: " . $conn->error;
        }
    } else {
        $errorMsg = "Please complete all fields before submitting.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Platform Feedback - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 650px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .card h2 { font-size: 24px; color: var(--text-color); margin-bottom: 8px; text-align: center; }
        .card p { font-size: 14px; color: var(--text-muted); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; color: var(--text-color); margin-bottom: 8px; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); outline: none; font-size: 15px;
        }

        .btn-submit {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient); color: white; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
        }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="index.php" style="background:var(--input-bg); color:var(--text-color); border:1px solid var(--card-border); padding:10px 18px; border-radius:12px; text-decoration:none; font-weight:600; font-size:14px; display:inline-flex; align-items:center; gap:6px;">
            <i class='bx bx-left-arrow-alt'></i> 🏠 Back to Home
        </a>
    </div>

    <div class="card">
        <h2>⭐ Share Your FlexiRide Experience</h2>
        <p>Your feedback helps us continuously improve safety, features, and comfort for all commuters!</p>

        <?php if ($successMsg): ?>
            <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Your Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label>Your Email Address *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label>Your Feedback & Suggestions *</label>
                <textarea name="Feedback" rows="5" placeholder="Share your experience or feature suggestions..." required></textarea>
            </div>

            <button type="submit" class="btn-submit"><i class='bx bxs-star'></i> Submit Feedback →</button>
        </form>
    </div>
</div>
</body>
</html>
