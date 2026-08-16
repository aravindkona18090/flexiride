<?php
include_once __DIR__ . '/includes/db.php';
include_once __DIR__ . '/includes/trust_score.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

// Handle Photo Upload directly in Profile Popup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['modal_profile_photo'])) {
    if ($_FILES['modal_profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['modal_profile_photo']['tmp_name'];

        // Validate by actual MIME type
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fileTmpPath);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (array_key_exists($mimeType, $allowedMimes)) {
            $ext       = $allowedMimes[$mimeType];
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath    = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $photoPath = 'uploads/' . $newFileName;
                $upStmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $upStmt->bind_param("si", $photoPath, $user_id);
                $upStmt->execute();
                $successMsg = "Profile photo updated successfully! 📸";
            } else {
                $errorMsg = "Failed to save uploaded photo.";
            }
        } else {
            $errorMsg = "Invalid file type. Please upload a JPG, PNG, WEBP, or GIF image.";
        }
    }
}

// Handle Add Vehicle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vehicle'])) {
    $v_cat   = $_POST['vehicle_category'] ?? 'bike';
    $v_model = trim($_POST['vehicle_model'] ?? '');
    $v_plate = strtoupper(trim($_POST['license_plate'] ?? ''));
    $v_seats = ($v_cat === 'bike') ? 2 : (int)($_POST['total_seats'] ?? 5);
    $v_ev    = isset($_POST['is_ev']) ? 1 : 0;
    $v_helm  = isset($_POST['helmet_provided']) ? 1 : 0;

    if (empty($v_model)) {
        $errorMsg = "Please enter your vehicle model.";
    } elseif (empty($v_plate)) {
        $errorMsg = "License Plate Number is mandatory! (e.g. AP39 AB 1234).";
    } else {
        $vStmt = $conn->prepare("INSERT INTO vehicles (user_id, vehicle_category, vehicle_model, license_plate, total_seats, is_ev, helmet_provided) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $vStmt->bind_param("isssiii", $user_id, $v_cat, $v_model, $v_plate, $v_seats, $v_ev, $v_helm);
        if ($vStmt->execute()) {
            $successMsg = "New vehicle added to your garage! 🏍️";
        } else {
            $errorMsg = "Error adding vehicle: " . $conn->error;
        }
    }
}

// Handle Delete Vehicle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vehicle'])) {
    $v_id = (int)$_POST['vehicle_id'];
    $dStmt = $conn->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
    $dStmt->bind_param("ii", $v_id, $user_id);
    $dStmt->execute();
    $successMsg = "Vehicle removed from garage.";
}

// Fetch User Data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: [];

// Fetch Saved Vehicles
$vehiclesStmt = $conn->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY id DESC");
$vehiclesStmt->bind_param("i", $user_id);
$vehiclesStmt->execute();
$vehicles = $vehiclesStmt->get_result();

$trust = calculateRiderAiSafetyScore($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Commute Garage — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/flexiride.css">
    <style>
        .profile-hero-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .modal-photo {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .profile-hero-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<?php 
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    include_once __DIR__ . '/includes/admin_navbar.php';
} else {
    include_once __DIR__ . '/includes/navbar.php';
}
?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container">
        <?php if ($successMsg): ?>
            <div style="background:var(--eco-bg); color:var(--eco); border:1px solid var(--eco-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ✅ <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600;">
                ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- User Profile Header -->
        <div class="profile-hero-card">
            <div style="position:relative; text-align:center;">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" style="width:105px; height:105px; border-radius:50%; object-fit:cover; border:3px solid var(--primary); box-shadow:0 0 20px rgba(56,189,248,0.25);" alt="Avatar">
                <?php else: ?>
                    <div style="width:105px; height:105px; border-radius:50%; background:var(--primary-gradient); color:white; display:flex; align-items:center; justify-content:center; font-size:46px;">
                        <i class='bx bxs-user'></i>
                    </div>
                <?php endif; ?>
                <div>
                    <button type="button" onclick="document.getElementById('photoModal').style.display='flex'" class="fr-btn fr-btn-ghost fr-btn-sm" style="margin-top:8px; font-size:12px;">
                        <i class='bx bxs-camera'></i> Change Photo
                    </button>
                </div>
            </div>

            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                    <h1 style="font-size:26px; font-weight:800; color:var(--text-main);"><?php echo htmlspecialchars($user['name'] ?? 'Commuter'); ?></h1>
                    <span class="trust-shield <?php echo $trust['badge_class']; ?>">
                        <i class='bx bxs-shield-check'></i> <?php echo $trust['score']; ?>% Verified
                    </span>
                </div>
                <p style="font-size:14px; color:var(--text-muted); margin-bottom:14px;">
                    <?php echo htmlspecialchars($user['email'] ?? ''); ?> • <?php echo htmlspecialchars($user['phone'] ?? ''); ?>
                </p>

                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <a href="edit_profile.php" class="fr-btn fr-btn-primary fr-btn-sm">
                        <i class='bx bxs-edit'></i> Edit Profile & ID Verification
                    </a>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="admin/admin_dashboard.php" class="fr-btn fr-btn-danger fr-btn-sm">
                            <i class='bx bxs-dashboard'></i> Admin Console →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Green Commute Impact Metrics -->
        <div class="fr-card" style="margin-bottom: 24px;">
            <h3 style="font-size:17px; font-weight:800; color:var(--eco); margin-bottom:16px; display:flex; align-items:center; gap:6px;">
                <i class='bx bxs-leaf'></i> Green Commute & Petrol Savings Impact
            </h3>
            <div class="fr-grid-3">
                <div style="background:var(--bg-input); border:1px solid var(--border-subtle); padding:18px; border-radius:var(--radius-md); text-align:center;">
                    <div style="font-size:24px; font-weight:800; color:var(--eco);"><i class='bx bxs-leaf'></i> <?php echo number_format((float)($user['total_co2_saved'] ?? 14.5), 1); ?> kg</div>
                    <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px;">CO₂ Emissions Saved</div>
                </div>
                <div style="background:var(--bg-input); border:1px solid var(--border-subtle); padding:18px; border-radius:var(--radius-md); text-align:center;">
                    <div style="font-size:24px; font-weight:800; color:var(--primary);"><i class='bx bx-wallet'></i> ₹<?php echo number_format((float)($user['total_money_saved'] ?? 1250), 0); ?></div>
                    <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px;">Direct Fuel Cost Saved</div>
                </div>
                <div style="background:var(--bg-input); border:1px solid var(--border-subtle); padding:18px; border-radius:var(--radius-md); text-align:center;">
                    <div style="font-size:24px; font-weight:800; color:#f59e0b;"><i class='bx bxs-star' style='color:#eab308;'></i> <?php echo number_format((float)($user['avg_rating'] ?? 5.0), 1); ?> / 5.0</div>
                    <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px;">Community Trust Rating</div>
                </div>
            </div>
        </div>

        <!-- Garage Vehicles Grid -->
        <div class="fr-card" style="margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:18px; font-weight:800; color:var(--text-main); display:flex; align-items:center; gap:8px;">
                    <i class='bx bxs-car-garage'></i> My Commute Garage
                </h3>
                <button type="button" onclick="document.getElementById('vehicleModal').style.display='flex'" class="fr-btn fr-btn-primary fr-btn-sm">
                    <i class='bx bx-plus'></i> Add Vehicle
                </button>
            </div>

            <div class="fr-grid-3">
                <?php if ($vehicles->num_rows > 0): ?>
                    <?php while ($v = $vehicles->fetch_assoc()): ?>
                        <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:16px; position:relative;">
                            <form method="POST" onsubmit="return confirm('Remove this vehicle?');" style="position:absolute; top:10px; right:10px;">
                                <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                                <button type="submit" name="delete_vehicle" class="fr-btn fr-btn-danger fr-btn-sm" style="padding:2px 8px; font-size:11px;">✕</button>
                            </form>
                            <div style="font-size:16px; font-weight:700; color:var(--text-main); margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                <?php echo ($v['vehicle_category'] === 'bike') ? '<i class="bx bx-cycling" style="color:var(--primary);"></i>' : '<i class="bx bxs-car" style="color:var(--eco);"></i>'; ?> <?php echo htmlspecialchars($v['vehicle_model']); ?>
                            </div>
                            <div style="font-size:13px; font-weight:700; color:var(--primary); font-family:monospace;"><?php echo htmlspecialchars($v['license_plate']); ?></div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">Capacity: <?php echo (int)($v['total_seats'] ?? 2); ?> seats</div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-size:14px; grid-column:1/-1;">No vehicles added yet. Add your bike or car to publish rides!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Photo Upload Modal -->
<div id="photoModal" class="modal-photo">
    <div class="fr-card" style="max-width:400px; width:100%; padding:26px;">
        <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:14px;"><i class='bx bxs-camera'></i> Update Profile Photo</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="modal_profile_photo" class="fr-input" accept="image/*" required style="margin-bottom:16px;">
            <div style="display:flex; gap:10px;">
                <button type="submit" class="fr-btn fr-btn-primary" style="flex:1;">Upload Photo</button>
                <button type="button" onclick="document.getElementById('photoModal').style.display='none'" class="fr-btn fr-btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div id="vehicleModal" class="modal-photo">
    <div class="fr-card" style="max-width:440px; width:100%; padding:28px;">
        <h3 style="font-size:18px; font-weight:700; color:var(--text-main); margin-bottom:16px;"><i class='bx bx-cycling'></i> Add Vehicle to Garage</h3>
        <form method="POST">
            <input type="hidden" name="add_vehicle" value="1">
            <div class="fr-form-group">
                <label class="fr-label">Vehicle Category</label>
                <select name="vehicle_category" class="fr-select">
                    <option value="bike">Two-Wheeler / Bike</option>
                    <option value="car">Car Sharing</option>
                </select>
            </div>
            <div class="fr-form-group">
                <label class="fr-label">Make & Model</label>
                <input type="text" name="vehicle_model" class="fr-input" placeholder="e.g. Royal Enfield Classic 350" required>
            </div>
            <div class="fr-form-group">
                <label class="fr-label">License Number Plate</label>
                <input type="text" name="license_plate" class="fr-input" placeholder="e.g. AP39 AB 1234" required>
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="fr-btn fr-btn-primary" style="flex:1;">Save Vehicle</button>
                <button type="button" onclick="document.getElementById('vehicleModal').style.display='none'" class="fr-btn fr-btn-ghost">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>