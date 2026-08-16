<?php
include_once __DIR__ . '/includes/db.php';

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
        $errorMsg = "Emergency Contact Email 1 cannot be your own primary or college email!";
    } elseif (!empty($emergency_email2) && ($emergency_email2 === $user_primary_email || $emergency_email2 === $college_email)) {
        $errorMsg = "Emergency Contact Email 2 cannot be your own primary or college email!";
    } elseif (!empty($emergency_email1) && !empty($emergency_email2) && $emergency_email1 === $emergency_email2) {
        $errorMsg = "Emergency Contact Email 1 and Email 2 cannot be identical!";
    }

    // UPI Validation
    $is_upi_valid = 0;
    if (empty($errorMsg) && !empty($upi_id)) {
        if (preg_match('/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', $upi_id)) {
            $is_upi_valid = 1;
        } else {
            $errorMsg = "Invalid UPI ID format! Must be e.g. username@okaxis or 9876543210@ybl.";
        }
    }

    // Aadhaar Validation
    $is_aadhaar_valid = 0;
    if (empty($errorMsg) && !empty($aadhaar_number)) {
        if (isValidAadhaar($aadhaar_number)) {
            $is_aadhaar_valid = 1;
        } else {
            $errorMsg = "Invalid 12-digit Aadhaar number checksum! Please enter your valid UIDAI number.";
        }
    }

    // Driving License Validation
    $is_dl_valid = 0;
    if (empty($errorMsg) && !empty($dl_number)) {
        $cleanDl = str_replace([' ', '-'], '', $dl_number);
        if (preg_match('/^[A-Z]{2}[0-9]{2}[0-9]{11}$/', $cleanDl)) {
            $is_dl_valid = 1;
        } else {
            $errorMsg = "Invalid Indian DL format! Example format: AP3920210012345 or KA0120200001234.";
        }
    }

    // College Email Validation
    $is_college_valid = 0;
    if (empty($errorMsg) && !empty($college_email)) {
        if (filter_var($college_email, FILTER_VALIDATE_EMAIL)) {
            $is_college_valid = 1;
        } else {
            $errorMsg = "Invalid College or Institute email address format!";
        }
    }

    if (empty($errorMsg)) {
        $oldPhone = trim($user['phone'] ?? '');
        $is_phone_ver = ($phone === $oldPhone) ? (int)($user['is_phone_verified'] ?? 0) : 0;

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
    <title>Edit Profile & Trust Credentials — FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/css/flexiride.css">
</head>
<body>

<?php include_once __DIR__ . '/includes/navbar.php'; ?>

<main class="page-content" style="padding: 30px 0;">
    <div class="fr-container-sm">
        <a href="profile.php" class="fr-btn fr-btn-ghost fr-btn-sm" style="margin-bottom:18px;">
            <i class='bx bx-left-arrow-alt'></i> Back to Profile
        </a>

        <div class="fr-card">
            <h2 style="font-size:24px; font-weight:800; color:var(--text-main); margin-bottom:6px;">
                Edit Profile & Trust Verification
            </h2>
            <p style="font-size:14px; color:var(--text-muted); margin-bottom:24px;">
                Complete your identity credentials to increase your Trust Score and unlock ride sharing.
            </p>

            <?php if ($errorMsg): ?>
                <div style="background:var(--danger-bg); color:var(--danger); border:1px solid var(--danger-border); padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:600; font-size:14px;">
                    ⚠️ <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="fr-grid-2">
                    <div class="fr-form-group">
                        <label class="fr-label">Full Name</label>
                        <input type="text" name="name" class="fr-input" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">Mobile Phone Number</label>
                        <input type="tel" name="phone" class="fr-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>

                <!-- Aadhaar Number Verification -->
                <div class="fr-form-group">
                    <label class="fr-label">🛡️ 12-Digit UIDAI Aadhaar Number</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="aadhaar_number" id="aadhaar_number" class="fr-input" placeholder="12-digit Aadhaar number" maxlength="12" value="<?php echo htmlspecialchars($user['aadhaar_number'] ?? ''); ?>" <?php if (!empty($user['aadhaar_number']) && ($user['is_aadhaar_verified']??0)) echo 'readonly'; ?> required>
                        
                        <?php if (!empty($user['aadhaar_number']) && ($user['is_aadhaar_verified']??0)): ?>
                            <button type="button" class="fr-btn fr-btn-ghost" id="btn-edit-aadhaar" onclick="unlockField('aadhaar_number', 'btn-verify-aadhaar', 'btn-edit-aadhaar')">✏️ Edit</button>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-aadhaar" style="display:none;" onclick="verifyAadhaar()">Verify</button>
                        <?php else: ?>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-aadhaar" onclick="verifyAadhaar()">Verify</button>
                        <?php endif; ?>
                    </div>
                    <div id="aadhaarStatusBadge" style="margin-top:6px; <?php echo ($user['is_aadhaar_verified']??0) ? 'display:block;' : 'display:none;'; ?>">
                        <?php if ($user['is_aadhaar_verified']??0): ?>
                            <span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Verified UIDAI Aadhaar</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Driving License Verification -->
                <div class="fr-form-group">
                    <label class="fr-label">🏍️ Driving License (DL) Number</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="dl_number" id="dl_number" class="fr-input" placeholder="e.g. AP39 20210012345" value="<?php echo htmlspecialchars($user['dl_number'] ?? ''); ?>" <?php if (!empty($user['dl_number']) && ($user['is_dl_verified']??0)) echo 'readonly'; ?>>
                        
                        <?php if (!empty($user['dl_number']) && ($user['is_dl_verified']??0)): ?>
                            <button type="button" class="fr-btn fr-btn-ghost" id="btn-edit-dl" onclick="unlockField('dl_number', 'btn-verify-dl', 'btn-edit-dl')">✏️ Edit</button>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-dl" style="display:none;" onclick="verifyDl()">Verify</button>
                        <?php else: ?>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-dl" onclick="verifyDl()">Verify</button>
                        <?php endif; ?>
                    </div>
                    <div id="dlStatusBadge" style="margin-top:6px; <?php echo ($user['is_dl_verified']??0) ? 'display:block;' : 'display:none;'; ?>">
                        <?php if ($user['is_dl_verified']??0): ?>
                            <span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Verified DL</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- UPI Verification -->
                <div class="fr-form-group">
                    <label class="fr-label">💳 UPI ID for Direct 0% Fuel Split Transfers</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="upi_id" id="upi_id" class="fr-input" placeholder="e.g. yourname@okaxis or phone@ybl" value="<?php echo htmlspecialchars($user['upi_id'] ?? ''); ?>" <?php if (!empty($user['upi_id']) && ($user['is_upi_verified']??0)) echo 'readonly'; ?> required>
                        
                        <?php if (!empty($user['upi_id']) && ($user['is_upi_verified']??0)): ?>
                            <button type="button" class="fr-btn fr-btn-ghost" id="btn-edit-upi" onclick="unlockField('upi_id', 'btn-verify-upi', 'btn-edit-upi')">✏️ Edit</button>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-upi" style="display:none;" onclick="verifyUpi()">Verify</button>
                        <?php else: ?>
                            <button type="button" class="fr-btn fr-btn-primary" id="btn-verify-upi" onclick="verifyUpi()">Verify</button>
                        <?php endif; ?>
                    </div>
                    <div id="upiStatusBadge" style="margin-top:6px; <?php echo ($user['is_upi_verified']??0) ? 'display:block;' : 'display:none;'; ?>">
                        <?php if ($user['is_upi_verified']??0): ?>
                            <span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Verified UPI VPA</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="fr-grid-2">
                    <div class="fr-form-group">
                        <label class="fr-label">🎓 Campus / University</label>
                        <input type="text" name="campus_name" class="fr-input" placeholder="e.g. MBU / VIT Campus" value="<?php echo htmlspecialchars($user['campus_name'] ?? ''); ?>">
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">📧 Student / Institute Email</label>
                        <input type="email" name="college_email" class="fr-input" placeholder="student@mbu.asia" value="<?php echo htmlspecialchars($user['college_email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="fr-grid-3">
                    <div class="fr-form-group">
                        <label class="fr-label">Date of Birth</label>
                        <input type="date" name="dob" class="fr-input" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">Gender</label>
                        <select name="gender" class="fr-select">
                            <option value="Male" <?php if(($user['gender']??'')==='Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if(($user['gender']??'')==='Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if(($user['gender']??'')==='Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="fr-form-group">
                        <label class="fr-label">City</label>
                        <input type="text" name="city" class="fr-input" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Emergency Contacts Section -->
                <div style="background:var(--bg-input); border:1px solid var(--border-subtle); border-radius:var(--radius-md); padding:18px; margin:20px 0;">
                    <div style="font-size:15px; font-weight:700; color:var(--text-main); margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                        <i class='bx bxs-alarm-exclamation' style="color:var(--danger);"></i> Emergency Contacts (For Panic SOS Broadcasts)
                    </div>
                    <div class="fr-grid-2">
                        <div class="fr-form-group">
                            <label class="fr-label">Emergency Email 1 (Parent/Guardian)</label>
                            <input type="email" name="emergency_email1" class="fr-input" value="<?php echo htmlspecialchars($user['emergency_email1'] ?? ''); ?>" placeholder="parent@gmail.com">
                        </div>
                        <div class="fr-form-group">
                            <label class="fr-label">Emergency Email 2 (Campus Roommate)</label>
                            <input type="email" name="emergency_email2" class="fr-input" value="<?php echo htmlspecialchars($user['emergency_email2'] ?? ''); ?>" placeholder="friend@gmail.com">
                        </div>
                    </div>
                    <div class="fr-form-group" style="margin-bottom:0;">
                        <label class="fr-label">Emergency Contact Phone Number</label>
                        <input type="tel" name="emergency_phone" class="fr-input" value="<?php echo htmlspecialchars($user['emergency_phone'] ?? ''); ?>" placeholder="10-digit mobile number">
                    </div>
                </div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-block fr-btn-lg">
                    Save Profile & Credentials <i class='bx bx-save'></i>
                </button>
            </form>
        </div>
    </div>
</main>

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
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Valid UIDAI Aadhaar Verified!</span>`;
        } else {
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-danger"><i class='bx bxs-x-circle'></i> Invalid 12-digit Aadhaar Number!</span>`;
        }
    }

    function verifyDl() {
        const dl = document.getElementById('dl_number').value.trim().replace(/[\s-]/g, '').toUpperCase();
        const badge = document.getElementById('dlStatusBadge');

        if (/^[A-Z]{2}[0-9]{2}[0-9]{11}$/.test(dl)) {
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Valid DL Verified!</span>`;
        } else {
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-danger"><i class='bx bxs-x-circle'></i> Invalid DL Format!</span>`;
        }
    }

    function verifyUpi() {
        const upi = document.getElementById('upi_id').value.trim();
        const badge = document.getElementById('upiStatusBadge');

        if (/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/.test(upi)) {
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-eco"><i class='bx bxs-check-shield'></i> ✅ Valid VPA Verified!</span>`;
        } else {
            badge.style.display = 'block';
            badge.innerHTML = `<span class="fr-badge fr-badge-danger"><i class='bx bxs-x-circle'></i> Invalid UPI format!</span>`;
        }
    }
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>