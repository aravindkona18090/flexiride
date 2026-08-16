<?php
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/mailer.php';
session_start();

// Ensure user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $user = [];
}

$errorMessage = "";

// Handle form submission to update profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $emergency_email1 = trim($_POST['emergency_email1']);
    $emergency_email2 = trim($_POST['emergency_email2']);

    $update_query = "UPDATE users SET name = ?, email = ?, phone = ?, emergency_email1 = ?, emergency_email2 = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssssi", $name, $email, $phone, $emergency_email1, $emergency_email2, $user_id);

    if ($update_stmt->execute()) {
        $emailBody = "
            <h2>FlexiRide Account Updated</h2>
            <p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your FlexiRide commuter profile was updated by system operations.</p>
        ";
        try {
            sendResendMail($email, $name, "FlexiRide Profile Updated", $emailBody);
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
        }

        header("Location: admin_manage_users.php?message=User profile updated successfully!");
        exit();
    } else {
        $errorMessage = "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Commuter Profile — Admin FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/../includes/admin_navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container-sm">
        <a href="admin_manage_users.php" class="fr-btn fr-btn-ghost fr-btn-sm" style="margin-bottom:18px;">
            <i class='bx bx-left-arrow-alt'></i> Back to Users Directory
        </a>

        <div class="fr-card" style="max-width: 560px; margin: 0 auto;">
            <h2 style="font-size:22px; font-weight:800; color:var(--text-main); margin-bottom:6px;">
                Edit Commuter Account
            </h2>
            <p style="font-size:13.5px; color:var(--text-muted); margin-bottom:20px;">
                Modify contact details and emergency routing for user #<?php echo $user_id; ?>
            </p>

            <?php if ($errorMessage): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 16px; border-radius:var(--radius-md); margin-bottom:20px; font-size:14px;">
                    ⚠️ <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="fr-form-group">
                    <label class="fr-label">Full Name</label>
                    <input type="text" name="name" class="fr-input" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="fr-form-group">
                    <label class="fr-label">Email Address</label>
                    <input type="email" name="email" class="fr-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>

                <div class="fr-form-group">
                    <label class="fr-label">Mobile Phone</label>
                    <input type="tel" name="phone" class="fr-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>

                <div class="fr-grid-2">
                    <div class="fr-form-group">
                        <label class="fr-label">Emergency Email 1</label>
                        <input type="email" name="emergency_email1" class="fr-input" value="<?php echo htmlspecialchars($user['emergency_email1'] ?? ''); ?>">
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">Emergency Email 2</label>
                        <input type="email" name="emergency_email2" class="fr-input" value="<?php echo htmlspecialchars($user['emergency_email2'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Save Changes <i class='bx bx-save'></i>
                </button>
            </form>
        </div>
    </div>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
