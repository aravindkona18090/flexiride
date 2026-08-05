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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $field = $_POST['field'];
    $value = $_POST['value'];
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
           PROFILE PAGE — redesigned below. "Route" concept: each
           section of the profile is a stop along a dashed route line,
           echoing FlexiRide's own ride-route metaphor.
           ============================================================ */

        :root {
            --ink: #14213D;
            --ink-soft: #5B647A;
            --bg: #F5F7FB;
            --card: #FFFFFF;
            --line: #D9DEEA;
            --accent: #FF9800;      /* matches navbar hover accent */
            --accent-soft: #FFE3B8;
            --teal: #1FA394;        /* secondary "on-route" accent */
            --teal-soft: #D8F3EF;
            --danger: #E1553F;
        }

        html {
            background: var(--bg);
        }

        body {
            background: var(--bg);
            color: var(--ink);
        }

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

        /* The route: a dashed vertical line running behind every stop */
        .route {
            position: relative;
            padding-left: 56px;
        }

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

        .stop:last-child {
            margin-bottom: 0;
        }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 11px 0;
            border-bottom: 1px solid #EFF1F7;
        }

        .field-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .field-row:first-of-type {
            padding-top: 0;
        }

        .field-label {
            font-size: 0.85rem;
            color: var(--ink-soft);
            flex-shrink: 0;
            min-width: 140px;
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

        .field-value-wrap input[type="text"],
        .field-value-wrap input[type="date"] {
            font-family: 'JetBrains Mono', monospace;
            padding: 6px 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 0.9rem;
            width: 160px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field-value-wrap input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
            outline: none;
        }

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

        .save-btn:hover {
            background: #e08600;
        }

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

        .avatar-row .field-label {
            min-width: 0;
        }

        .link-out {
            color: var(--teal);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border-bottom: 1px solid var(--teal-soft);
            transition: border-color 0.2s ease;
        }

        .link-out:hover {
            border-color: var(--teal);
        }

        @media (max-width: 600px) {
            .route-page {
                padding: 110px 16px 60px;
            }
            .route {
                padding-left: 44px;
            }
            .route::before {
                left: 17px;
            }
            .stop-marker {
                left: -44px;
                width: 28px;
                height: 28px;
                font-size: 14px;
                top: 24px;
            }
            .field-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .field-value-wrap {
                width: 100%;
                justify-content: space-between;
            }
            .field-value-wrap input[type="text"],
            .field-value-wrap input[type="date"] {
                width: 100%;
            }
        }
    </style>
    <script>
        function editField(field) {
            let fieldSpan = document.getElementById(field);
            let fieldValue = fieldSpan.innerText;
            fieldSpan.innerHTML = `<input type='text' id='${field}_input' value='${fieldValue}'> 
                                   <button class="save-btn" onclick='saveField("${field}")'>Save</button>`;
        }

        function saveField(field) {
            let value = document.getElementById(field + "_input").value;
            let formData = new FormData();
            formData.append("field", field);
            formData.append("value", value);

            fetch("profile.php", {
                method: "POST",
                body: formData
            }).then(response => response.text()).then(data => {
                if (data === "success") {
                    document.getElementById(field).innerText = value;
                } else {
                    alert("Error updating field.");
                }
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
                <span class="field-label">Name</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="name"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('name')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">DOB</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="dob"><?php echo htmlspecialchars($user['dob'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('dob')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Gender</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="gender"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('gender')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Phone</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('phone')">Edit</button>
                </div>
            </div>
            <div class="avatar-row">
                <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" alt="Profile picture">
                <span class="field-label">Profile picture</span>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-phone-call'></i></span>
            <span class="stop-label">Stop 02</span>
            <h2>Emergency Contacts</h2>
            <div class="field-row">
                <span class="field-label">Email 1</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="emergency_email1"><?php echo htmlspecialchars($user['emergency_email1'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('emergency_email1')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Email 2</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="emergency_email2"><?php echo htmlspecialchars($user['emergency_email2'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('emergency_email2')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Emergency Phone</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="emergency_phone"><?php echo htmlspecialchars($user['emergency_phone'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('emergency_phone')">Edit</button>
                </div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-lock-alt'></i></span>
            <span class="stop-label">Stop 03</span>
            <h2>Account Details</h2>
            <div class="field-row">
                <span class="field-label">Email</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="email"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('email')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Password</span>
                <div class="field-value-wrap">
                    <a class="link-out" href="change_password.php">Change Password</a>
                </div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-wallet'></i></span>
            <span class="stop-label">Stop 04</span>
            <h2>Payment Details</h2>
            <div class="field-row">
                <span class="field-label">UPI ID</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="upi_id"><?php echo htmlspecialchars($user['upi_id'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('upi_id')">Edit</button>
                </div>
            </div>
        </section>

        <section class="stop">
            <span class="stop-marker"><i class='bx bxs-map'></i></span>
            <span class="stop-label">Stop 05</span>
            <h2>Address</h2>
            <div class="field-row">
                <span class="field-label">Home Address</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="address"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('address')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">City</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="city"><?php echo htmlspecialchars($user['city'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('city')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">State</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="state"><?php echo htmlspecialchars($user['state'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('state')">Edit</button>
                </div>
            </div>
            <div class="field-row">
                <span class="field-label">Pincode</span>
                <div class="field-value-wrap">
                    <span class="field-value" id="pincode"><?php echo htmlspecialchars($user['pincode'] ?? 'N/A'); ?></span>
                    <button class="edit-btn" onclick="editField('pincode')">Edit</button>
                </div>
            </div>
        </section>

    </div>
</div>

</body>
</html>