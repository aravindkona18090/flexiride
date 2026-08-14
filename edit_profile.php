<?php
include_once __DIR__ . '/includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: [];

$errorMsg = "";

// Verhoeff Checksum Algorithm helper for Indian Aadhaar Numbers
function isValidAadhaar($number) {
    if (!preg_match('/^[2-9][0-9]{11}$/', $number)) return false;
    $d = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6], [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8], [5, 6, 7, 8, 9, 0, 1, 2, 3, 4],
        [6, 7, 8, 9, 5, 1, 2, 3, 4, 0], [7, 8, 9, 5, 6, 2, 3, 4, 0, 1],
        [8, 9, 5, 6, 7, 3, 4, 0, 1, 2], [9, 5, 6, 7, 8, 4, 0, 1, 2, 3]
    ];
    $p = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 5, 7, 6, 2, 8, 3, 9, 4, 0],
        [2, 3, 9, 0, 5, 6, 4, 1, 8, 7], [3, 6, 1, 5, 8, 2, 7, 4, 0, 9],
        [4, 7, 0, 6, 9, 1, 8, 3, 5, 2], [5, 8, 6, 9, 7, 0, 2, 1, 3, 4],
        [6, 9, 8, 4, 0, 7, 1, 2, 5, 3], [7, 0, 4, 2, 1, 9, 5, 8, 6, 3]
    ];
    $c = 0;
    $invertedArray = array_reverse(str_split($number));
    for ($i = 0; $i < count($invertedArray); $i++) {
        $c = $d[$c][$p[$i % 8][$invertedArray[$i]]];
    }
    return ($c === 0);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name              = trim($_POST['name']);
    $phone             = trim($_POST['phone']);
    $dob               = $_POST['dob'];
    $gender            = $_POST['gender'];
    $upi_id            = trim($_POST['upi_id']);
    $aadhaar_number    = trim($_POST['aadhaar_number']);
    $dl_number         = strtoupper(trim($_POST['dl_number']));
    $college_email     = strtolower(trim($_POST['college_email']));
    $campus_name       = trim($_POST['campus_name']);
    $emergency_email1  = strtolower(trim($_POST['emergency_email1']));
    $emergency_email2  = strtolower(trim($_POST['emergency_email2']));
    $emergency_phone   = trim($_POST['emergency_phone']);
    $city              = trim($_POST['city']);

    $user_primary_email = strtolower(trim($user['email'] ?? ''));

    // Emergency Contact Validation
    if (!empty($emergency_email1) && ($emergency_email1 === $user_primary_email || $emergency_email1 === $college_email)) {
        $errorMsg = "Emergency Contact Email 1 cannot be the same as your personal or college email address!";
    } elseif (!empty($emergency_email2) && ($emergency_email2 === $user_primary_email || $emergency_email2 === $college_email)) {
        $errorMsg = "Emergency Contact Email 2 cannot be the same as your personal or college email address!";
    } elseif (!empty($emergency_email1) && !empty($emergency_email2) && $emergency_email1 === $emergency_email2) {
        $errorMsg = "Emergency Contact Email 1 and Email 2 cannot be identical!";
    }

    // UPI Validation
    $is_upi_valid = 0;
    if (empty($errorMsg) && !empty($upi_id)) {
        if (preg_match('/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', $upi_id)) {
            $is_upi_valid = 1;
        } else {
            $errorMsg = "Invalid UPI ID format! Must be like username@okicici or phone@ybl.";
        }
    }

    // Aadhaar Validation
    $is_aadhaar_valid = 0;
    if (empty($errorMsg) && !empty($aadhaar_number)) {
        if (isValidAadhaar($aadhaar_number)) {
            $is_aadhaar_valid = 1;
        } else {
            $errorMsg = "Invalid 12-digit Aadhaar number! Please enter a valid UIDAI Aadhaar.";
        }
    }

    // Driving License Validation
    $is_dl_valid = 0;
    if (empty($errorMsg) && !empty($dl_number)) {
        $cleanDl = str_replace([' ', '-'], '', $dl_number);
        if (preg_match('/^[A-Z]{2}[0-9]{2}[0-9]{11}$/', $cleanDl)) {
            $is_dl_valid = 1;
        } else {
            $errorMsg = "Invalid Indian Driving License format! Must be e.g. AP3920210012345 or KA0120200001234.";
        }
    }

    // College Email Validation
    $is_college_valid = 0;
    if (empty($errorMsg) && !empty($college_email)) {
        if (filter_var($college_email, FILTER_VALIDATE_EMAIL)) {
            $is_college_valid = 1;
        } else {
            $errorMsg = "Invalid College/Corporate email address!";
        }
    }

    if (empty($errorMsg)) {
        $oldPhone = trim($user['phone'] ?? '');
        $is_phone_ver = ($phone === $oldPhone) ? (int)($user['is_phone_verified'] ?? 0) : 0;

        safeAddColumn($conn, 'users', 'is_phone_verified', "TINYINT(1) NOT NULL DEFAULT 0");
        // Note: safeAddColumn kept here intentionally until next schema migration run
        $update_stmt = $conn->prepare("UPDATE users SET name=?, phone=?, is_phone_verified=?, dob=?, gender=?, upi_id=?, is_upi_verified=?, aadhaar_number=?, is_aadhaar_verified=?, is_verified=?, dl_number=?, is_dl_verified=?, college_email=?, is_college_email_verified=?, campus_name=?, emergency_email1=?, emergency_email2=?, emergency_phone=?, city=? WHERE id=?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("ssisssisiisisssssssi", $name, $phone, $is_phone_ver, $dob, $gender, $upi_id, $is_upi_valid, $aadhaar_number, $is_aadhaar_valid, $is_aadhaar_valid, $dl_number, $is_dl_valid, $college_email, $is_college_valid, $campus_name, $emergency_email1, $emergency_email2, $emergency_phone, $city, $user_id);
            $update_stmt->execute();
        }

        // Sync 3NF Normalized Tables
        syncUser3NF($conn, $user_id, $aadhaar_number, $is_aadhaar_valid, $dl_number, $is_dl_valid, $college_email, $is_college_valid, $campus_name, $upi_id, $is_upi_valid, $is_aadhaar_valid, $emergency_email1, $emergency_email2, $emergency_phone);

        $_SESSION['name'] = $name;
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile & Identity Verification - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-color) !important; color: var(--text-color) !important; min-height: 100vh; display: flex; flex-direction: column; }

        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; width: 100%; }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--input-bg); color: var(--primary-color); border: 1px solid var(--primary-color);
            padding: 10px 18px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s;
        }
        .btn-back:hover { background: var(--primary-color); color: white; }

        .form-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        h2 { font-size: 26px; text-align: center; margin-bottom: 25px; color: var(--text-color); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%; padding: 14px; border-radius: 10px; border: 1px solid var(--input-border);
            background: var(--input-bg); color: var(--text-color); font-size: 15px; outline: none;
        }

        .flex-box { display: flex; gap: 10px; }
        .btn-verify {
            padding: 14px 20px; background: var(--success-color); color: white; border: none;
            border-radius: 10px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: 0.3s;
        }
        .btn-verify:hover { opacity: 0.9; }
        .btn-unlock-edit {
            padding: 14px 20px; background: var(--input-bg); color: var(--primary-color); border: 1px solid var(--primary-color);
            border-radius: 10px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: 0.3s;
        }
        .btn-unlock-edit:hover { background: rgba(56, 189, 248, 0.2); }

        .status-badge {
            display: inline-block; margin-top: 8px; font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 8px;
        }
        .badge-valid { background: var(--success-bg); color: var(--success-color); border: 1px solid var(--success-color); }
        .badge-invalid { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); }

        .btn-save {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-gradient);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 15px;
        }
        .alert-error { background: var(--danger-bg); color: var(--danger-color); border: 1px solid var(--danger-color); padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">
    <div class="header-bar">
        <a href="profile.php" class="btn-back"><i class='bx bx-left-arrow-alt' style="font-size:20px;"></i> Back to Profile</a>
    </div>

    <div class="form-card">
        <h2>✏️ Edit Profile & Identity Verification</h2>

        <?php if ($errorMsg): ?>
            <div class="alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
            </div>

            <!-- Aadhaar Number Verification -->
            <div class="form-group">
                <label>🛡️ 12-Digit UIDAI Aadhaar Number</label>
                <div class="flex-box">
                    <input type="text" name="aadhaar_number" id="aadhaar_number" placeholder="Enter 12-digit Aadhaar number" maxlength="12" value="<?php echo htmlspecialchars($user['aadhaar_number'] ?? ''); ?>" <?php if (!empty($user['aadhaar_number']) && ($user['is_aadhaar_verified']??0)) echo 'readonly'; ?> required>
                    
                    <?php if (!empty($user['aadhaar_number']) && ($user['is_aadhaar_verified']??0)): ?>
                        <button type="button" class="btn-unlock-edit" id="btn-edit-aadhaar" onclick="unlockField('aadhaar_number', 'btn-verify-aadhaar', 'btn-edit-aadhaar')">✏️ Edit</button>
                        <button type="button" class="btn-verify" id="btn-verify-aadhaar" style="display:none;" onclick="verifyAadhaar()">Verify Math</button>
                    <?php else: ?>
                        <button type="button" class="btn-verify" id="btn-verify-aadhaar" onclick="verifyAadhaar()">Verify Math</button>
                    <?php endif; ?>
                </div>
                <div class="status-badge" id="aadhaarStatusBadge" style="<?php echo ($user['is_aadhaar_verified']??0) ? 'display:inline-block;' : 'display:none;'; ?>">
                    <?php if ($user['is_aadhaar_verified']??0): ?>
                        <span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Verified UIDAI Aadhaar</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Driving License Verification -->
            <div class="form-group">
                <label>🏍️ Indian Driving License (DL) Number</label>
                <div class="flex-box">
                    <input type="text" name="dl_number" id="dl_number" placeholder="e.g. AP39 20210012345" value="<?php echo htmlspecialchars($user['dl_number'] ?? ''); ?>" <?php if (!empty($user['dl_number']) && ($user['is_dl_verified']??0)) echo 'readonly'; ?>>
                    
                    <?php if (!empty($user['dl_number']) && ($user['is_dl_verified']??0)): ?>
                        <button type="button" class="btn-unlock-edit" id="btn-edit-dl" onclick="unlockField('dl_number', 'btn-verify-dl', 'btn-edit-dl')">✏️ Edit</button>
                        <button type="button" class="btn-verify" id="btn-verify-dl" style="display:none;" onclick="verifyDl()">Verify DL</button>
                    <?php else: ?>
                        <button type="button" class="btn-verify" id="btn-verify-dl" onclick="verifyDl()">Verify DL</button>
                    <?php endif; ?>
                </div>
                <div class="status-badge" id="dlStatusBadge" style="<?php echo ($user['is_dl_verified']??0) ? 'display:inline-block;' : 'display:none;'; ?>">
                    <?php if ($user['is_dl_verified']??0): ?>
                        <span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Verified DL</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- UPI Verification Box -->
            <div class="form-group">
                <label>💳 UPI ID (Google Pay / PhonePe / Paytm)</label>
                <div class="flex-box">
                    <input type="text" name="upi_id" id="upi_id" placeholder="username@okicici or phone@ybl" value="<?php echo htmlspecialchars($user['upi_id'] ?? ''); ?>" <?php if (!empty($user['upi_id']) && ($user['is_upi_verified']??0)) echo 'readonly'; ?> required>
                    
                    <?php if (!empty($user['upi_id']) && ($user['is_upi_verified']??0)): ?>
                        <button type="button" class="btn-unlock-edit" id="btn-edit-upi" onclick="unlockField('upi_id', 'btn-verify-upi', 'btn-edit-upi')">✏️ Edit</button>
                        <button type="button" class="btn-verify" id="btn-verify-upi" style="display:none;" onclick="verifyUpi()">Verify UPI</button>
                    <?php else: ?>
                        <button type="button" class="btn-verify" id="btn-verify-upi" onclick="verifyUpi()">Verify UPI</button>
                    <?php endif; ?>
                </div>
                <div class="status-badge" id="upiStatusBadge" style="<?php echo ($user['is_upi_verified']??0) ? 'display:inline-block;' : 'display:none;'; ?>">
                    <?php if ($user['is_upi_verified']??0): ?>
                        <span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Verified VPA</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- College / Corporate Verification -->
            <div class="grid-2">
                <div class="form-group">
                    <label>🎓 Campus / University Name</label>
                    <input type="text" name="campus_name" placeholder="e.g. MBU Campus / VIT" value="<?php echo htmlspecialchars($user['campus_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>📧 College / Corporate Email</label>
                    <input type="email" name="college_email" placeholder="student@mbu.asia" value="<?php echo htmlspecialchars($user['college_email'] ?? ''); ?>">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male" <?php if(($user['gender']??'')==='Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if(($user['gender']??'')==='Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if(($user['gender']??'')==='Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
            </div>

            <h3 style="font-size:18px; margin:15px 0; color:var(--primary-color);">🚨 Emergency Contacts (Must be different from your account email)</h3>

            <div class="grid-2">
                <div class="form-group">
                    <label>Emergency Email 1 (Parent / Guardian)</label>
                    <input type="email" name="emergency_email1" value="<?php echo htmlspecialchars($user['emergency_email1'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Emergency Email 2 (Friend / Relative)</label>
                    <input type="email" name="emergency_email2" value="<?php echo htmlspecialchars($user['emergency_email2'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Emergency Phone</label>
                <input type="tel" name="emergency_phone" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn-save">Save Profile Changes</button>
        </form>
    </div>
</div>

<script>
    function unlockField(inputId, verifyBtnId, editBtnId) {
        const inp = document.getElementById(inputId);
        inp.readOnly = false;
        inp.focus();
        document.getElementById(verifyBtnId).style.display = 'inline-block';
        document.getElementById(editBtnId).style.display = 'none';
    }

    function validateAadhaarVerhoeff(num) {
        if (!/^[2-9][0-9]{11}$/.test(num)) return false;
        const d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6], [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8], [5, 6, 7, 8, 9, 0, 1, 2, 3, 4],
            [6, 7, 8, 9, 5, 1, 2, 3, 4, 0], [7, 8, 9, 5, 6, 2, 3, 4, 0, 1],
            [8, 9, 5, 6, 7, 3, 4, 0, 1, 2], [9, 5, 6, 7, 8, 4, 0, 1, 2, 3]
        ];
        const p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 5, 7, 6, 2, 8, 3, 9, 4, 0],
            [2, 3, 9, 0, 5, 6, 4, 1, 8, 7], [3, 6, 1, 5, 8, 2, 7, 4, 0, 9],
            [4, 7, 0, 6, 9, 1, 8, 3, 5, 2], [5, 8, 6, 9, 7, 0, 2, 1, 3, 4],
            [6, 9, 8, 4, 0, 7, 1, 2, 5, 3], [7, 0, 4, 2, 1, 9, 5, 8, 6, 3]
        ];
        let c = 0;
        const inv = num.split('').reverse().map(Number);
        for (let i = 0; i < inv.length; i++) {
            c = d[c][p[i % 8][inv[i]]];
        }
        return (c === 0);
    }

    function verifyAadhaar() {
        const num = document.getElementById('aadhaar_number').value.trim();
        const badge = document.getElementById('aadhaarStatusBadge');

        if (validateAadhaarVerhoeff(num)) {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Valid UIDAI Aadhaar Verified!</span>`;
        } else {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-invalid"><i class='bx bxs-x-circle'></i> Invalid 12-digit Aadhaar Number!</span>`;
        }
    }

    function verifyDl() {
        const dl = document.getElementById('dl_number').value.trim().replace(/[\s-]/g, '').toUpperCase();
        const badge = document.getElementById('dlStatusBadge');

        if (/^[A-Z]{2}[0-9]{2}[0-9]{11}$/.test(dl)) {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Valid DL Verified!</span>`;
        } else {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-invalid"><i class='bx bxs-x-circle'></i> Invalid DL Format!</span>`;
        }
    }

    function verifyUpi() {
        const upi = document.getElementById('upi_id').value.trim();
        const badge = document.getElementById('upiStatusBadge');

        if (/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/.test(upi)) {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-valid"><i class='bx bxs-check-shield'></i> ✅ Valid VPA Verified!</span>`;
        } else {
            badge.style.display = 'inline-block';
            badge.innerHTML = `<span class="badge-invalid"><i class='bx bxs-x-circle'></i> Invalid UPI format!</span>`;
        }
    }
</script>
</body>
</html>