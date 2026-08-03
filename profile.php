<?php
include 'db.php';
session_start();

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
        $fileName = $_FILES['modal_profile_photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

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
            $errorMsg = "Invalid photo format! Please select a JPG, PNG, or WEBP image.";
        }
    }
}

// Handle Add Vehicle (Mandatory Number Plate Check & Capacity Algorithm)
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
        $errorMsg = "License Plate Number is mandatory! Please enter a valid number plate (e.g. AP39 AB 1234).";
    } else {
        safeAddColumn($conn, 'vehicles', 'total_seats', "INT NOT NULL DEFAULT 5");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile & Garage - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; width: 100%; }

        .profile-header-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 35px;
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            position: relative;
        }

        .avatar-container { position: relative; text-align: center; }

        .avatar-img {
            width: 110px; height: 110px; border-radius: 50%; object-fit: cover;
            border: 3px solid var(--primary-color); box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .avatar-box {
            width: 110px; height: 110px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 52px; color: white;
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-photo-edit {
            display: inline-flex; align-items: center; gap: 4px;
            background: var(--input-bg); color: var(--primary-color); border: 1px solid var(--primary-color);
            padding: 5px 12px; border-radius: 14px; font-size: 12px; font-weight: 600; cursor: pointer;
            margin-top: 8px; transition: 0.3s;
        }
        .btn-photo-edit:hover { background: var(--primary-color); color: white; }

        .user-details h2 { font-size: 28px; font-weight: 700; margin-bottom: 6px; color: var(--text-color); }
        .badge-verified { display: inline-flex; align-items: center; gap: 4px; background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-top: 6px; margin-right: 6px; }

        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
        }
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--primary-color); display: flex; justify-content: space-between; align-items: center; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 4px; font-weight: 500; }
        .info-item span { font-size: 16px; color: var(--text-color); font-weight: 600; }

        /* Vehicle Card List */
        .vehicles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
        .vehicle-card {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 14px;
            padding: 18px;
            position: relative;
        }
        .vehicle-card h4 { font-size: 16px; margin-bottom: 6px; color: var(--text-color); display: flex; align-items: center; gap: 6px; }
        .btn-del-v { position: absolute; top: 12px; right: 12px; background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); border-radius: 6px; padding: 2px 8px; font-size: 11px; cursor: pointer; }

        .btn-add-v { background: var(--primary-gradient); color: white; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; }
        .btn-edit-profile {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--primary-gradient);
            color: white; padding: 12px 24px; border-radius: 12px;
            text-decoration: none; font-weight: 600; transition: 0.3s; margin-top: 15px;
        }

        /* Modal Popup Styling */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(10px);
            z-index: 9999; justify-content: center; align-items: center; padding: 20px;
        }
        .modal-card {
            background: var(--card-bg); border: 1px solid var(--primary-color);
            border-radius: 24px; padding: 35px; max-width: 460px; width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .modal-card input, .modal-card select {
            width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); margin-bottom: 12px; outline: none;
        }

        .alert-success { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 14px; }

        @media (max-width: 768px) {
            .profile-header-card { flex-direction: column; text-align: center; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <?php if ($successMsg): ?>
        <div class="alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <div class="profile-header-card">
        <div class="avatar-container">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" class="avatar-img" alt="User Profile Avatar">
            <?php else: ?>
                <div class="avatar-box"><i class='bx bxs-user'></i></div>
            <?php endif; ?>
            <div>
                <button type="button" class="btn-photo-edit" onclick="openPhotoModal()">
                    <i class='bx bxs-camera'></i> Change Photo
                </button>
            </div>
        </div>

        <div class="user-details">
            <h2><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
            <p style="color:var(--text-muted); font-size:15px;"><?php echo htmlspecialchars($user['email'] ?? ''); ?> | <?php echo htmlspecialchars($user['phone'] ?? ''); ?></p>
            <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;">
                <?php if ($user['is_aadhaar_verified'] ?? 0): ?>
                    <span class="badge-verified"><i class='bx bxs-shield-quarter'></i> 🛡️ Aadhaar Verified Rider</span>
                <?php endif; ?>
                <?php if ($user['is_dl_verified'] ?? 0): ?>
                    <span class="badge-verified" style="background:rgba(129,140,248,0.2); color:#818cf8; border-color:#818cf8;">
                        <i class='bx bxs-id-card'></i> 🏍️ Licensed Driver
                    </span>
                <?php endif; ?>
                <?php if ($user['is_college_email_verified'] ?? 0): ?>
                    <span class="badge-verified" style="background:rgba(245,158,11,0.2); color:#fbbf24; border-color:#f59e0b;">
                        <i class='bx bxs-graduation'></i> 🎓 Campus Student
                    </span>
                <?php endif; ?>
                <?php if ($user['is_upi_verified'] ?? 0): ?>
                    <span class="badge-verified" style="background:rgba(56,189,248,0.2); color:var(--primary-color); border-color:var(--primary-color);">
                        <i class='bx bx-qr-scan'></i> Verified UPI VPA
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <a href="edit_profile.php" class="btn-edit-profile"><i class='bx bxs-edit'></i> Edit Profile Details</a>
            </div>
        </div>
    </div>

    <!-- My Saved Garage Vehicles Card -->
    <div class="section-card">
        <div class="section-title">
            <span><i class='bx bxs-car'></i> 🏍️ My Garage Vehicles</span>
            <button type="button" class="btn-add-v" onclick="openVehicleModal()">➕ Add New Vehicle</button>
        </div>

        <div class="vehicles-grid">
            <?php if ($vehicles->num_rows > 0): ?>
                <?php while ($v = $vehicles->fetch_assoc()): ?>
                    <?php 
                        $totalSeating = (int)($v['total_seats'] ?? ($v['vehicle_category'] === 'bike' ? 2 : 5));
                        $maxOffered = ($v['vehicle_category'] === 'bike') ? 1 : max(1, $totalSeating - 1);
                    ?>
                    <div class="vehicle-card">
                        <form method="POST" onsubmit="return confirm('Remove vehicle from garage?');">
                            <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                            <button type="submit" name="delete_vehicle" class="btn-del-v">✕ Remove</button>
                        </form>
                        <h4>
                            <?php echo ($v['vehicle_category'] === 'bike') ? '🏍️' : '🚗'; ?> 
                            <?php echo htmlspecialchars($v['vehicle_model']); ?>
                        </h4>
                        <p style="font-size:13px; color:var(--primary-color); font-weight:700; margin-top:4px;">
                            License Plate: <?php echo htmlspecialchars($v['license_plate']); ?>
                        </p>
                        <p style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            Total Capacity: <?php echo $totalSeating; ?> Seater (Max <?php echo $maxOffered; ?> Offered Seats)
                        </p>
                        <div style="margin-top:6px; font-size:12px;">
                            <?php if ($v['is_ev']): ?>
                                <span style="background:rgba(34,197,94,0.2); color:var(--success-color); padding:2px 6px; border-radius:4px;">⚡ EV Electric</span>
                            <?php endif; ?>
                            <?php if ($v['vehicle_category'] === 'bike' && $v['helmet_provided']): ?>
                                <span style="background:rgba(56,189,248,0.2); color:var(--primary-color); padding:2px 6px; border-radius:4px;">🪖 Spare Helmet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--text-muted); font-size:14px; grid-column:1/-1;">No vehicles added to garage yet. Click <strong>"Add New Vehicle"</strong> to save your bike or car details!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Identity & Authentication Details -->
    <div class="section-card">
        <div class="section-title"><span><i class='bx bxs-user-detail'></i> Multi-Factor Identity Authentication</span></div>
        <div class="info-grid">
            <div class="info-item">
                <label>Aadhaar Verification</label>
                <span>
                    <?php if (!empty($user['aadhaar_number']) && ($user['is_aadhaar_verified']??0)): ?>
                        XXXX-XXXX-<?php echo substr($user['aadhaar_number'], -4); ?> <i class='bx bxs-check-circle' style="color:var(--success-color);"></i> Verified
                    <?php else: ?>
                        Not verified
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <label>Driving License (DL)</label>
                <span>
                    <?php if (!empty($user['dl_number']) && ($user['is_dl_verified']??0)): ?>
                        <?php echo htmlspecialchars($user['dl_number']); ?> <i class='bx bxs-check-circle' style="color:var(--success-color);"></i> Verified
                    <?php else: ?>
                        Not verified
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <label>Campus / College Email</label>
                <span>
                    <?php echo htmlspecialchars($user['college_email'] ?? 'Not set'); ?>
                    <?php if (!empty($user['college_email'])): ?>
                        <i class='bx bxs-check-circle' style="color:var(--success-color);"></i>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-item">
                <label>UPI Payment VPA</label>
                <span>
                    <?php echo htmlspecialchars($user['upi_id'] ?? 'Not set'); ?>
                    <?php if (!empty($user['upi_id'])): ?>
                        <i class='bx bxs-check-circle' style="color:var(--success-color);"></i>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title"><span><i class='bx bxs-error-circle' style="color:var(--danger-color);"></i> Emergency SOS Contacts</span></div>
        <div class="info-grid">
            <div class="info-item"><label>Emergency Contact 1</label><span><?php echo htmlspecialchars($user['emergency_email1'] ?? 'None'); ?></span></div>
            <div class="info-item"><label>Emergency Contact 2</label><span><?php echo htmlspecialchars($user['emergency_email2'] ?? 'None'); ?></span></div>
            <div class="info-item"><label>Emergency Phone</label><span><?php echo htmlspecialchars($user['emergency_phone'] ?? 'None'); ?></span></div>
        </div>
    </div>
</div>

<!-- Photo Upload Modal Popup -->
<div class="modal-overlay" id="photoModal">
    <div class="modal-card">
        <h3 style="font-size:20px; color:var(--text-color); margin-bottom:15px; text-align:center;">📸 Upload Profile Photo</h3>
        
        <?php if (!empty($user['profile_photo'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" style="width:90px; height:90px; border-radius:50%; object-fit:cover; margin:0 auto 15px; display:block;" alt="Avatar Preview">
        <?php else: ?>
            <div class="avatar-box" style="margin:0 auto 15px; width:80px; height:80px; font-size:40px;"><i class='bx bxs-user'></i></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="background:var(--input-bg); padding:15px; border-radius:12px; border:1px solid var(--input-border); margin-bottom:15px;">
                <input type="file" name="modal_profile_photo" accept="image/*" required style="font-size:13px; color:var(--text-color); width:100%;">
            </div>
            <button type="submit" class="btn-add-v" style="width:100%; padding:14px; font-size:16px;">Upload & Save Photo</button>
            <button type="button" style="width:100%; padding:12px; border:none; background:transparent; color:var(--text-muted); cursor:pointer; margin-top:8px;" onclick="closePhotoModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- Add Vehicle Modal Popup (Dynamic Category Seating Adaptation) -->
<div class="modal-overlay" id="vehicleModal">
    <div class="modal-card">
        <h3 style="font-size:20px; color:var(--text-color); margin-bottom:15px; text-align:center;">🏍️ Add Vehicle to Garage</h3>

        <form method="POST">
            <input type="hidden" name="add_vehicle" value="1">
            
            <label style="font-size:13px; color:var(--text-muted);">Vehicle Category *</label>
            <select name="vehicle_category" id="modalVCat" onchange="updateModalCapacityOptions()">
                <option value="bike">🏍️ Two-Wheeler / Bike</option>
                <option value="car">🚗 Car / Four-Wheeler</option>
            </select>

            <label style="font-size:13px; color:var(--text-muted);">Vehicle Model Name *</label>
            <input type="text" name="vehicle_model" placeholder="e.g. Yamaha FZ-S / TVS Jupiter / Honda City" required>

            <label style="font-size:13px; color:var(--text-muted);">License Plate Number *</label>
            <input type="text" name="license_plate" placeholder="e.g. AP39 AB 1234" required>

            <div id="capacitySelectGroup">
                <label style="font-size:13px; color:var(--text-muted);">Total Vehicle Seating Capacity (Driver Included) *</label>
                <select name="total_seats" id="modalVSeats">
                    <!-- Dynamic Options Populated via JS -->
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:var(--text-color); cursor:pointer; margin-bottom:8px;">
                    <input type="checkbox" name="is_ev" value="1" style="width:auto; margin:0;"> ⚡ Is Electric Vehicle (EV)
                </label>
                <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:var(--text-color); cursor:pointer;">
                    <input type="checkbox" name="helmet_provided" value="1" checked style="width:auto; margin:0;"> 🪖 Spare Helmet Provided
                </label>
            </div>

            <button type="submit" class="btn-add-v" style="width:100%; padding:14px; font-size:16px;">Save Vehicle to Profile</button>
            <button type="button" style="width:100%; padding:12px; border:none; background:transparent; color:var(--text-muted); cursor:pointer; margin-top:8px;" onclick="closeVehicleModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
    function updateModalCapacityOptions() {
        const cat = document.getElementById('modalVCat').value;
        const seatsSel = document.getElementById('modalVSeats');
        seatsSel.innerHTML = '';

        if (cat === 'bike') {
            const opt = document.createElement('option');
            opt.value = "2";
            opt.textContent = "2 Seater (Bike / Scooter - Max 1 Pillion Seat)";
            seatsSel.appendChild(opt);
        } else {
            const options = [
                { val: "4", txt: "4 Seater (Hatchback Car - Max 3 Offered Seats)" },
                { val: "5", txt: "5 Seater (Sedan / Compact SUV - Max 4 Offered Seats)" },
                { val: "7", txt: "7 Seater (SUV / MUV - Max 6 Offered Seats)" },
                { val: "8", txt: "8 Seater (Large Van - Max 7 Offered Seats)" }
            ];
            options.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.val;
                opt.textContent = o.txt;
                if (o.val === "5") opt.selected = true;
                seatsSel.appendChild(opt);
            });
        }
    }

    function openPhotoModal() { document.getElementById('photoModal').style.display = 'flex'; }
    function closePhotoModal() { document.getElementById('photoModal').style.display = 'none'; }

    function openVehicleModal() {
        document.getElementById('vehicleModal').style.display = 'flex';
        updateModalCapacityOptions();
    }
    function closeVehicleModal() { document.getElementById('vehicleModal').style.display = 'none'; }
</script>
</body>
</html>