<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = ($result->num_rows > 0) ? $result->fetch_assoc() : [];

/* Whitelist of columns this endpoint is allowed to update.
   Prevents $_POST['field'] from targeting arbitrary DB columns. */
$allowed_fields = [
    'name', 'dob', 'gender', 'phone',
    'emergency_email1', 'emergency_email2', 'emergency_phone',
    'email', 'upi_id',
    'address', 'city', 'state', 'pincode'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';

    if (!in_array($field, $allowed_fields, true)) {
        http_response_code(400);
        echo "error";
        exit();
    }

    $update_query = "UPDATE users SET $field = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $value, $user_id);
    if ($update_stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Profile</title>
    <style>
       /* ============================================================
          NAVBAR — UNCHANGED. Same rules as before, left exactly as-is.
          ============================================================ */
       body {
            margin: 0px;
            padding: 0px;
            font-family: "Josefin Sans", sans-serif;
            overflow: scroll; 
            font-size: large;
            height: 100vh;
            align-items: center;
            background-attachment: fixed;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .navbar {
            background-color: #000; 
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0px 0px;
            position: fixed;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); 
        }
        
        .logo a {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
            letter-spacing: 2px;
            transition: color 0.3s ease;
        }
        
        .logo a:hover {
            color: #ff9800; 
        }
        
        
        .nav-links {
            list-style: none;
            display: flex;
        }
        
        .nav-links li {
            margin-left: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-size: 18px;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 8px 16px;
            border-radius: 4px;
        }
        
        
        .nav-links a:hover {
    color: white; /* Text color on hover */
    background: linear-gradient(135deg, #4a4a8a, #6767b3); /* Gradient matching the button's color */
    border-radius: 30%;
}

        /* Active/Current Page Link */
        .nav-links a.active {
            color: #ff9800;
            background-color:linear-gradient(135deg, #4a4a8a, #6767b3); 
        }
        
        @media screen and (max-width: 768px) {
            .navbar {
                flex-direction: column;
            }
        
            .nav-links {
                flex-direction: column;
                align-items: center;
                margin-top: 10px;
            }
            
            .nav-links li {
                margin-left: 0;
                margin-bottom: 0;
            }
        }

        /* Lock the navbar's font regardless of any page-level font change below */
        .navbar, .navbar * {
            font-family: "Josefin Sans", sans-serif;
        }

        /* ============================================================
           PROFILE PAGE
           ============================================================ */

        :root {
            --ink: #14213D;
            --ink-soft: #5B647A;
            --bg: #F5F7FB;
            --card: #FFFFFF;
            --line: #D9DEEA;
            --accent: #FF9800;
            --accent-soft: #FFE3B8;
            --teal: #1FA394;
            --teal-soft: #D8F3EF;
            --danger: #E1553F;
            --danger-soft: #FBE4E0;
        }

        html { background: var(--bg); }
        body { background: var(--bg); color: var(--ink); }

        .route-page {
            font-family: 'Inter', "Josefin Sans", sans-serif;
            max-width: 760px;
            margin: 0 auto;
            padding: 120px 24px 80px;
            position: relative;
        }

        .page-eyebrow {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--teal);
            text-align: center;
            display: block;
            margin-bottom: 10px;
        }

        h1.page-title {
            font-family: 'Space Grotesk', 'Josefin Sans', sans-serif;
            font-size: 2.4rem;
            font-weight: 700;
            text-align: center;
            color: var(--ink);
            letter-spacing: -0.01em;
            margin: 0 0 8px;
        }

        p.page-subtitle {
            text-align: center;
            color: var(--ink-soft);
            font-size: 1rem;
            margin: 0 0 56px;
        }

        .route { position: relative; padding-left: 56px; }

        .route::before {
            content: "";
            position: absolute;
            top: 14px;
            bottom: 14px;
            left: 23px;
            width: 2px;
            background-image: linear-gradient(var(--line) 60%, transparent 0%);
            background-position: left;
            background-size: 2px 12px;
            background-repeat: repeat-y;
        }

        .stop {
            position: relative;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 26px 28px;
            margin-bottom: 28px;
            box-shadow: 0 1px 2px rgba(20, 33, 61, 0.04);
            transition: box-shadow 0.25s ease, transform 0.25s ease;
        }

        .stop:hover {
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.08);
            transform: translateY(-2px);
        }

        .stop:last-child { margin-bottom: 0; }

        .stop-marker {
            position: absolute;
            left: -56px;
            top: 26px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--card);
            border: 2px solid var(--accent);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .stop-label {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.68rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--teal);
            display: block;
            margin-bottom: 4px;
        }

        .stop h2 {
            font-family: 'Space Grotesk', 'Josefin Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 18px;
        }

        .field-row {
            padding: 11px 0;
            border-bottom: 1px solid #EFF1F7;
        }

        .field-row:last-child { border-bottom: none; padding-bottom: 0; }
        .field-row:first-of-type { padding-top: 0; }

        .field-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .field-label {
            font-size: 0.85rem;
            color: var(--ink-soft);
            flex-shrink: 0;
            min-width: 140px;
        }

        .field-label .req {
            color: var(--danger);
            margin-left: 2px;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.62rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--teal);
            background: var(--teal-soft);
            padding: 2px 7px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .field-value-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-end;
            flex: 1;
            min-width: 0;
        }

        .field-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.92rem;
            color: var(--ink);
            text-align: right;
            word-break: break-word;
        }

        .field-error {
            display: none;
            font-size: 0.78rem;
            color: var(--danger);
            background: var(--danger-soft);
            padding: 6px 10px;
            border-radius: 8px;
            margin-top: 8px;
        }

        .field-error.show { display: block; }

        .edit-btn {
            background: transparent;
            color: var(--ink-soft);
            border: 1px solid var(--line);
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.78rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .edit-btn:hover {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: #8A4B00;
        }

        .field-value-wrap input,
        .field-value-wrap select {
            font-family: 'JetBrains Mono', monospace;
            padding: 6px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 0.9rem;
            width: 180px;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field-value-wrap input:focus,
        .field-value-wrap select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
            outline: none;
        }

        .field-value-wrap input:invalid:not(:placeholder-shown) {
            border-color: var(--danger);
        }

        .edit-actions { display: flex; gap: 6px; flex-shrink: 0; }

        .save-btn {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .save-btn:hover { background: #e08600; }
        .save-btn:disabled { background: var(--line); cursor: not-allowed; }

        .cancel-btn {
            background: transparent;
            color: var(--ink-soft);
            border: 1px solid var(--line);
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-family: 'Inter', sans-serif;
        }

        .cancel-btn:hover { background: #F1F2F6; }

        .avatar-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 14px;
            margin-top: 10px;
            border-top: 1px solid #EFF1F7;
        }

        .avatar-row img {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--teal-soft);
        }

        .avatar-meta { display: flex; flex-direction: column; gap: 6px; }

        .avatar-change-link {
            font-size: 0.78rem;
            color: var(--teal);
            text-decoration: none;
            border-bottom: 1px solid var(--teal-soft);
            width: fit-content;
        }

        .avatar-change-link:hover { border-color: var(--teal); }

        /* ---- Photo change modal ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20, 33, 61, 0.55);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.show { display: flex; }

        .modal-box {
            background: var(--card);
            border-radius: 18px;
            padding: 30px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(20, 33, 61, 0.25);
        }

        .modal-box h3 {
            font-family: 'Space Grotesk', 'Josefin Sans', sans-serif;
            font-size: 1.1rem;
            margin-bottom: 18px;
            color: var(--ink);
        }

        .modal-preview {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--teal-soft);
            margin: 0 auto 20px;
            display: block;
        }

        .modal-filename {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: var(--ink-soft);
            margin-bottom: 16px;
            word-break: break-all;
            min-height: 1.2em;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-btn-primary {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .modal-btn-primary:hover { background: #e08600; }
        .modal-btn-primary:disabled { background: var(--line); cursor: not-allowed; }

        .modal-btn-secondary {
            background: transparent;
            color: var(--ink-soft);
            border: 1px solid var(--line);
            padding: 9px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
        }

        .modal-btn-secondary:hover { background: #F1F2F6; }

        .modal-error {
            display: none;
            font-size: 0.78rem;
            color: var(--danger);
            background: var(--danger-soft);
            padding: 6px 10px;
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .modal-error.show { display: block; }

        .link-out {
            color: var(--teal);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border-bottom: 1px solid var(--teal-soft);
            transition: border-color 0.2s ease;
        }

        .link-out:hover { border-color: var(--teal); }

        @media (max-width: 600px) {
            .route-page { padding: 110px 16px 60px; }
            .route { padding-left: 44px; }
            .route::before { left: 17px; }
            .stop-marker { left: -44px; width: 28px; height: 28px; font-size: 14px; top: 24px; }
            .field-main { flex-direction: column; align-items: flex-start; gap: 6px; }
            .field-value-wrap { width: 100%; justify-content: space-between; }
            .field-value-wrap input,
            .field-value-wrap select { width: 100%; }
        }
    </style>
    <script>
        /* Per-field input configuration: type, validation pattern, options.
           editField() reads this instead of always rendering a plain text box. */
        const INDIAN_STATES = [
            "Andhra Pradesh","Arunachal Pradesh","Assam","Bihar","Chhattisgarh","Goa","Gujarat",
            "Haryana","Himachal Pradesh","Jharkhand","Karnataka","Kerala","Madhya Pradesh",
            "Maharashtra","Manipur","Meghalaya","Mizoram","Nagaland","Odisha","Punjab",
            "Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura","Uttar Pradesh",
            "Uttarakhand","West Bengal","Delhi","Jammu and Kashmir","Ladakh","Puducherry",
            "Chandigarh","Andaman and Nicobar Islands"
        ];

        const FIELD_CONFIG = {
            name:              { type: "text",  required: true,  maxlength: 60 },
            dob:               { type: "date",  required: false },
            gender:            { type: "select", required: false, options: ["Male", "Female", "Other", "Prefer not to say"] },
            phone:             { type: "tel",   required: true,  pattern: "^[6-9]\\d{9}$", maxlength: 10, inputmode: "numeric", hint: "Enter a valid 10-digit mobile number" },
            emergency_email1:  { type: "email", required: false },
            emergency_email2:  { type: "email", required: false },
            emergency_phone:   { type: "tel",   required: false, pattern: "^[6-9]\\d{9}$", maxlength: 10, inputmode: "numeric", hint: "Enter a valid 10-digit mobile number" },
            email:             { type: "email", required: true },
            upi_id:            { type: "text",  required: false, pattern: "^[\\w.\\-]{2,256}@[A-Za-z]{2,64}$", hint: "Format: name@bank, e.g. aravind@okhdfcbank" },
            address:           { type: "text",  required: false, maxlength: 200 },
            city:              { type: "text",  required: false, maxlength: 60 },
            state:             { type: "select", required: false, options: INDIAN_STATES },
            pincode:           { type: "text",  required: false, pattern: "^\\d{6}$", maxlength: 6, inputmode: "numeric", hint: "Enter a valid 6-digit PIN code" }
        };

        function buildInput(field, currentValue) {
            const cfg = FIELD_CONFIG[field] || { type: "text" };
            let el;

            if (cfg.type === "select") {
                el = document.createElement("select");
                el.id = field + "_input";
                (cfg.options || []).forEach(opt => {
                    const o = document.createElement("option");
                    o.value = opt;
                    o.textContent = opt;
                    if (opt === currentValue) o.selected = true;
                    el.appendChild(o);
                });
            } else {
                el = document.createElement("input");
                el.type = cfg.type || "text";
                el.id = field + "_input";
                el.value = currentValue === "N/A" ? "" : currentValue;
                if (cfg.pattern) el.pattern = cfg.pattern;
                if (cfg.maxlength) el.maxLength = cfg.maxlength;
                if (cfg.inputmode) el.inputMode = cfg.inputmode;
                if (cfg.required) el.required = true;
            }
            return el;
        }

        function editField(field) {
            const valueWrap = document.getElementById(field + "_wrap");
            const fieldSpan = document.getElementById(field);
            const currentValue = fieldSpan.innerText;

            valueWrap.dataset.originalValue = currentValue;
            valueWrap.innerHTML = "";

            const input = buildInput(field, currentValue);
            valueWrap.appendChild(input);

            const actions = document.createElement("div");
            actions.className = "edit-actions";

            const saveBtn = document.createElement("button");
            saveBtn.type = "button";
            saveBtn.className = "save-btn";
            saveBtn.textContent = "Save";
            saveBtn.onclick = () => saveField(field);

            const cancelBtn = document.createElement("button");
            cancelBtn.type = "button";
            cancelBtn.className = "cancel-btn";
            cancelBtn.textContent = "Cancel";
            cancelBtn.onclick = () => cancelField(field);

            actions.appendChild(saveBtn);
            actions.appendChild(cancelBtn);
            valueWrap.appendChild(actions);

            input.focus();
        }

        function cancelField(field) {
            const valueWrap = document.getElementById(field + "_wrap");
            const original = valueWrap.dataset.originalValue;
            renderReadonly(field, original);
            hideError(field);
        }

        function showError(field, message) {
            const errorEl = document.getElementById(field + "_error");
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add("show");
            }
        }

        function hideError(field) {
            const errorEl = document.getElementById(field + "_error");
            if (errorEl) errorEl.classList.remove("show");
        }

        function renderReadonly(field, value) {
            const valueWrap = document.getElementById(field + "_wrap");
            valueWrap.innerHTML = `
                <span class="field-value" id="${field}">${value}</span>
                <button class="edit-btn" onclick="editField('${field}')">Edit</button>
            `;
        }

        function saveField(field) {
            const input = document.getElementById(field + "_input");
            const cfg = FIELD_CONFIG[field] || {};

            if (cfg.required && !input.value.trim()) {
                showError(field, "This field can't be empty");
                return;
            }
            if (input.value && !input.checkValidity()) {
                showError(field, cfg.hint || "Please enter a valid value");
                return;
            }
            hideError(field);

            const value = input.value.trim();
            const valueWrap = document.getElementById(field + "_wrap");
            const saveBtn = valueWrap.querySelector(".save-btn");
            saveBtn.disabled = true;
            saveBtn.textContent = "Saving...";

            const formData = new FormData();
            formData.append("field", field);
            formData.append("value", value);

            fetch("profile.php", { method: "POST", body: formData })
                .then(response => response.text())
                .then(data => {
                    if (data === "success") {
                        renderReadonly(field, value || "N/A");
                    } else {
                        showError(field, "Couldn't save that — please try again");
                        saveBtn.disabled = false;
                        saveBtn.textContent = "Save";
                    }
                })
                .catch(() => {
                    showError(field, "Network error — please try again");
                    saveBtn.disabled = false;
                    saveBtn.textContent = "Save";
                });
        }

        /* ---- Photo change modal ---- */
        let selectedPhotoFile = null;

        function openPhotoModal() {
            const currentSrc = document.getElementById('avatar_img').src;
            document.getElementById('modal_preview').src = currentSrc;
            document.getElementById('modal_filename').textContent = '';
            document.getElementById('modal_error').classList.remove('show');
            document.getElementById('photo_file_input').value = '';
            selectedPhotoFile = null;

            // Reset to step 1 (just showing current picture + "Change Picture" button)
            document.getElementById('modal_change_btn').style.display = 'inline-block';
            document.getElementById('modal_choose_btn').style.display = 'none';
            document.getElementById('modal_upload_btn').style.display = 'none';

            document.getElementById('photo_modal').classList.add('show');
        }

        function closePhotoModal() {
            document.getElementById('photo_modal').classList.remove('show');
        }

        function revealFileChooser() {
            // Step 2: swap "Change Picture" for the file chooser button
            document.getElementById('modal_change_btn').style.display = 'none';
            document.getElementById('modal_choose_btn').style.display = 'inline-block';
        }

        function openFileBrowser() {
            document.getElementById('photo_file_input').click();
        }

        function onPhotoFileSelected(input) {
            const file = input.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                document.getElementById('modal_error').textContent = 'Please choose an image file';
                document.getElementById('modal_error').classList.add('show');
                return;
            }
            document.getElementById('modal_error').classList.remove('show');

            selectedPhotoFile = file;
            document.getElementById('modal_filename').textContent = file.name;
            document.getElementById('modal_preview').src = URL.createObjectURL(file);
            document.getElementById('modal_upload_btn').style.display = 'inline-block';
        }

        function uploadPhoto() {
            if (!selectedPhotoFile) return;

            const uploadBtn = document.getElementById('modal_upload_btn');
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Uploading...';

            const formData = new FormData();
            formData.append('profile_picture', selectedPhotoFile);

            fetch('upload_picture.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        document.getElementById('avatar_img').src = URL.createObjectURL(selectedPhotoFile);
                        closePhotoModal();
                    } else {
                        document.getElementById('modal_error').textContent = "Couldn't upload — please try again";
                        document.getElementById('modal_error').classList.add('show');
                    }
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Save Photo';
                })
                .catch(() => {
                    document.getElementById('modal_error').textContent = 'Network error — please try again';
                    document.getElementById('modal_error').classList.add('show');
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Save Photo';
                });
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="a2.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="images/favvi.png" type="image/x-icon">
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <img src="images/logo1.png" alt="logo" height="30%" width="30%">
    </div>
    <img src="images/name.png" alt="logo" height="20%" width="20%">
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="rides.php">Find</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="post_ride.php">Post</a></li>
            <li><a href="myrides.php">MyRides</a></li>
            <li><a href="#" id="logout-link">Logout</a></li>
            <li><a href="profile.php"><i class='bx bxs-user bx-sm'></i></a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
<script>
    document.getElementById('logout-link').addEventListener('click', function(e) {
        e.preventDefault();
        var confirmed = confirm('Are you sure you want to logout?');
        if (confirmed) {
            window.location.href = 'logout.php';
        }
    });
</script>

<div class="route-page">
    <span class="page-eyebrow">Account &middot; FlexiRide</span>
    <h1 class="page-title">Your Profile</h1>
    <p class="page-subtitle">Everything about you, laid out stop by stop</p>

    <div class="route">

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-user'></i></span>
            <span class="stop-label">Stop 01</span>
            <h2>Personal Details</h2>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Name<span class="req">*</span></span>
                    <div class="field-value-wrap" id="name_wrap">
                        <span class="field-value" id="name"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('name')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="name_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">DOB</span>
                    <div class="field-value-wrap" id="dob_wrap">
                        <span class="field-value" id="dob"><?php echo htmlspecialchars($user['dob'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('dob')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="dob_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Gender</span>
                    <div class="field-value-wrap" id="gender_wrap">
                        <span class="field-value" id="gender"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('gender')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="gender_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Phone<span class="req">*</span></span>
                    <div class="field-value-wrap" id="phone_wrap">
                        <span class="field-value" id="phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                        <?php if (!empty($user['phone_verified'])): ?>
                            <span class="verified-badge"><i class='bx bxs-check-shield'></i> Verified</span>
                        <?php endif; ?>
                        <button class="edit-btn" onclick="editField('phone')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="phone_error"></div>
            </div>

            <div class="avatar-row">
                <img id="avatar_img" src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" alt="Profile picture">
                <div class="avatar-meta">
                    <span class="field-label" style="min-width:0;">Profile picture</span>
                    <a class="avatar-change-link" href="#" onclick="event.preventDefault(); openPhotoModal();">Change photo</a>
                </div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-phone-call'></i></span>
            <span class="stop-label">Stop 02</span>
            <h2>Emergency Contacts</h2>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Email 1</span>
                    <div class="field-value-wrap" id="emergency_email1_wrap">
                        <span class="field-value" id="emergency_email1"><?php echo htmlspecialchars($user['emergency_email1'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('emergency_email1')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="emergency_email1_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Email 2</span>
                    <div class="field-value-wrap" id="emergency_email2_wrap">
                        <span class="field-value" id="emergency_email2"><?php echo htmlspecialchars($user['emergency_email2'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('emergency_email2')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="emergency_email2_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Emergency Phone</span>
                    <div class="field-value-wrap" id="emergency_phone_wrap">
                        <span class="field-value" id="emergency_phone"><?php echo htmlspecialchars($user['emergency_phone'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('emergency_phone')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="emergency_phone_error"></div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-lock-alt'></i></span>
            <span class="stop-label">Stop 03</span>
            <h2>Account Details</h2>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Email<span class="req">*</span></span>
                    <div class="field-value-wrap" id="email_wrap">
                        <span class="field-value" id="email"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                        <?php if (!empty($user['email_verified'])): ?>
                            <span class="verified-badge"><i class='bx bxs-check-shield'></i> Verified</span>
                        <?php endif; ?>
                        <button class="edit-btn" onclick="editField('email')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="email_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Password</span>
                    <div class="field-value-wrap">
                        <a class="link-out" href="change_password.php">Change Password</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-wallet'></i></span>
            <span class="stop-label">Stop 04</span>
            <h2>Payment Details</h2>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">UPI ID</span>
                    <div class="field-value-wrap" id="upi_id_wrap">
                        <span class="field-value" id="upi_id"><?php echo htmlspecialchars($user['upi_id'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('upi_id')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="upi_id_error"></div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-map'></i></span>
            <span class="stop-label">Stop 05</span>
            <h2>Address</h2>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Home Address</span>
                    <div class="field-value-wrap" id="address_wrap">
                        <span class="field-value" id="address"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('address')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="address_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">City</span>
                    <div class="field-value-wrap" id="city_wrap">
                        <span class="field-value" id="city"><?php echo htmlspecialchars($user['city'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('city')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="city_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">State</span>
                    <div class="field-value-wrap" id="state_wrap">
                        <span class="field-value" id="state"><?php echo htmlspecialchars($user['state'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('state')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="state_error"></div>
            </div>

            <div class="field-row">
                <div class="field-main">
                    <span class="field-label">Pincode</span>
                    <div class="field-value-wrap" id="pincode_wrap">
                        <span class="field-value" id="pincode"><?php echo htmlspecialchars($user['pincode'] ?? 'N/A'); ?></span>
                        <button class="edit-btn" onclick="editField('pincode')">Edit</button>
                    </div>
                </div>
                <div class="field-error" id="pincode_error"></div>
            </div>
        </section>

    </div>
</div>

<div class="modal-overlay" id="photo_modal">
    <div class="modal-box">
        <h3>Profile Picture</h3>
        <div class="modal-error" id="modal_error"></div>

        <img class="modal-preview" id="modal_preview" src="" alt="Profile picture preview">
        <div class="modal-filename" id="modal_filename"></div>

        <!-- Hidden native file input; opened programmatically -->
        <input type="file" id="photo_file_input" accept="image/*" style="display:none;" onchange="onPhotoFileSelected(this)">

        <div class="modal-actions">
            <button type="button" class="modal-btn-primary" id="modal_change_btn" onclick="revealFileChooser()">Change Picture</button>
            <button type="button" class="modal-btn-primary" id="modal_choose_btn" style="display:none;" onclick="openFileBrowser()">Choose File</button>
            <button type="button" class="modal-btn-primary" id="modal_upload_btn" style="display:none;" onclick="uploadPhoto()">Save Photo</button>
            <button type="button" class="modal-btn-secondary" onclick="closePhotoModal()">Cancel</button>
        </div>
    </div>
</div>

</body>
</html>