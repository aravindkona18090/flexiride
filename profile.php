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

h1, h2 {
    text-align: center;
    margin-top: 30px;
    color: #222;
}

section {
    background: linear-gradient(145deg, #f0f0f0, #ffffff);
    margin: 30px auto;
    width: 90%;
    max-width: 800px;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

section:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
}

p {
    font-size: 1.1rem;
    margin: 15px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

strong {
    color: #333;
}

button {
    background: linear-gradient(135deg, #007bff, #00bfff);
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    transition: all 0.25s ease;
}

button:hover {
    background: linear-gradient(135deg, #00bfff, #007bff);
    transform: scale(1.05);
}

input[type="text"], input[type="date"] {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 65%;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="text"]:focus, input[type="date"]:focus {
    border-color: #007bff;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
    outline: none;
}

img {
    border-radius: 10px;
    margin-top: 10px;
    transition: transform 0.3s ease;
}

img:hover {
    transform: scale(1.05);
}

a {
    text-decoration: none;
    color: #007bff;
    transition: color 0.3s ease;
}

a:hover {
    color: #00bfff;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    section {
        padding: 20px;
    }
    p {
        flex-direction: column;
        align-items: flex-start;
    }
    input[type="text"], input[type="date"] {
        width: 100%;
        margin-top: 8px;
    }
}

    </style>
    <script>
        function editField(field) {
            let fieldSpan = document.getElementById(field);
            let fieldValue = fieldSpan.innerText;
            fieldSpan.innerHTML = `<input type='text' id='${field}_input' value='${fieldValue}'> 
                                   <button onclick='saveField("${field}")'>Save</button>`;
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
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
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
    <h1>Profile</h1>

    <section>
        <h2>Personal Details</h2>
        <p><strong>Name:</strong> <span id="name"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></span> <button onclick="editField('name')">Edit</button></p>
        <p><strong>DOB:</strong> <span id="dob"><?php echo htmlspecialchars($user['dob'] ?? 'N/A'); ?></span> <button onclick="editField('dob')">Edit</button></p>
        <p><strong>Gender:</strong> <span id="gender"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></span> <button onclick="editField('gender')">Edit</button></p>
        <p><strong>Phone:</strong> <span id="phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span> <button onclick="editField('phone')">Edit</button></p>
        <p><strong>Profile Picture:</strong> <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" width="100"></p>
    </section>

    <section>
        <h2>Emergency Contacts</h2>
        <p><strong>Email 1:</strong> <span id="emergency_email1"><?php echo htmlspecialchars($user['emergency_email1'] ?? 'N/A'); ?></span> <button onclick="editField('emergency_email1')">Edit</button></p>
        <p><strong>Email 2:</strong> <span id="emergency_email2"><?php echo htmlspecialchars($user['emergency_email2'] ?? 'N/A'); ?></span> <button onclick="editField('emergency_email2')">Edit</button></p>
        <p><strong>Emergency Phone:</strong> <span id="emergency_phone"><?php echo htmlspecialchars($user['emergency_phone'] ?? 'N/A'); ?></span> <button onclick="editField('emergency_phone')">Edit</button></p>
    </section>

    <section>
        <h2>Account Details</h2>
        <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span> <button onclick="editField('email')">Edit</button></p>
        <p><strong>Change Password:</strong> <a href="change_password.php">Change Password</a></p>
    </section>

    <section>
        <h2>Payment Details</h2>
        <p><strong>UPI ID:</strong> <span id="upi_id"><?php echo htmlspecialchars($user['upi_id'] ?? 'N/A'); ?></span> <button onclick="editField('upi_id')">Edit</button></p>
    </section>

    <section>
        <h2>Address</h2>
        <p><strong>Home Address:</strong> <span id="address"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></span> <button onclick="editField('address')">Edit</button></p>
        <p><strong>City:</strong> <span id="city"><?php echo htmlspecialchars($user['city'] ?? 'N/A'); ?></span> <button onclick="editField('city')">Edit</button></p>
        <p><strong>State:</strong> <span id="state"><?php echo htmlspecialchars($user['state'] ?? 'N/A'); ?></span> <button onclick="editField('state')">Edit</button></p>
        <p><strong>Pincode:</strong> <span id="pincode"><?php echo htmlspecialchars($user['pincode'] ?? 'N/A'); ?></span> <button onclick="editField('pincode')">Edit</button></p>
    </section>

</body>
</html>
