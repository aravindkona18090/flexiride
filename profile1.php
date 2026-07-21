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

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    $user = []; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['emergency_email1']) && isset($_POST['emergency_email2'])) {
    $emergency_email1 = $_POST['emergency_email1'];
    $emergency_email2 = $_POST['emergency_email2'];

    $update_query = "UPDATE users SET emergency_email1 = ?, emergency_email2 = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi",  $emergency_email1, $emergency_email2, $user_id);

    if ($update_stmt->execute()) {
        
        $user['emergency_email1'] = $emergency_email1;
        $user['emergency_email2'] = $emergency_email2;
        $message = "emergency contacts updated successfully!";
    } else {
        $message = "Error updating  emergency contacts.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="a2.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="images/favvi.png" type="image/x-icon">
    <style>
        body {
            background-image: url("images/boooook.jpg");
            background-repeat: no-repeat; 
            background-size: cover;
            background-position: center;
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
            position: sticky;
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

        section {
            background-color: rgba(255, 255, 255, 0.5);
            padding: 40px;
            margin: 2% auto;
            max-width: 1200px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            text-align: center;
            font-family: "Josefin Sans", sans-serif;
        
        }

        section h1 {
            font-family: "Josefin Sans", sans-serif;
            font-size: 36px;
            color: #333333;
            margin-bottom: 20px;
            font-family: 'Lora', serif;
        }

        section p {
            font-family: "Josefin Sans", sans-serif;
            font-size: 18px;
            color: #555555;
            line-height: 1.6;
            font-family: 'Lora', serif;
        }

        .login:hover {
            color: #fddb3a;
        }
        .danger-btn-floating {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: red;
            color: white;
            border: none;
            padding: 30px;
            font-size: 18px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .edit-btn {
            position: absolute;
            top: 19.5%;
            right: 13%;
            background: linear-gradient(135deg, #4a4a8a, #6767b3);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            width:8%;
           text-align: center;
            border-radius: 5px;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .edit-btn:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.5);
}

.edit-btn:active {
    transform: translateY(1px);
    background: #39396b;
}

/* Ripple Effect */
.edit-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transition: width 0.5s ease, height 0.5s ease, opacity 0.3s ease;
    z-index: 1;
}

.edit-btn:hover::after {
    width: 200%;
    height: 500%;
    opacity: 0;
}
label {
            display: block;
            margin-bottom: 5px;
            font-size: large;
        }

        input, select {
            font-family: "Josefin Sans", sans-serif;
            width: 350px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
            font-size: large;
        }

         input:hover, select:hover {
            border-color: #3b6faa; /* Change border color on hover */
            transform: translateY(-3px); /* Lift effect */
        }

        input:focus, select:focus {
            border-color: #3b6faa; /* Change border color on focus */
            box-shadow: 0 0 5px rgba(59, 106, 170, 0.5); /* Lift effect */
            outline: none; /* Remove default outline */
        }
        
        button[type="submit"] {
            font-family: "Josefin Sans", sans-serif;
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 350px;
    position: relative;
    overflow: hidden;
}

button[type="submit"]:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.5);
}
 button[type="submit"]:active {
    transform: translateY(1px);
    background: #39396b;
}

/* Button Ripple Effect */
button[type="submit"]::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transition: width 0.5s ease, height 0.5s ease, opacity 0.3s ease;
    z-index: 1;
}

button[type="submit"]:hover::after {
    width: 200%;
    height: 500%;
    opacity: 0;
}

    </style>
</head>
<body>
<a href="edit_profile.php" class="edit-btn">Edit</a>
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
<section class="section1">
    <div>
        <h1 style="font-family:'Josefin Sans', sans-serif;">Your Profile</h1>
        <b>
            <p style="font-family:'Josefin Sans', sans-serif;">
                <strong>Name:</strong> <?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'N/A'; ?>
            </p>
            <p style="font-family:'Josefin Sans', sans-serif;">
                <strong>Email:</strong> <?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'N/A'; ?>
            </p>
            <p style="font-family:'Josefin Sans', sans-serif;"><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <?php if (empty($user['emergency_email1']) || empty($user['emergency_email2'])): ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            
            <label for="emergency_email1">Enter Emergency Email 1:</label>
            <input type="email" id="emergency_email1" name="emergency_email1" required><br><br>
            <label for="emergency_email2">Enter Emergency Email 2:</label>
            <input type="email" id="emergency_email2" name="emergency_email2" required><br><br>
            <button type="submit">Submit</button>
        </form>
    <?php else: ?>
        
        <p style="font-family:'Josefin Sans', sans-serif;"><strong>Emergency Email 1:</strong> <?php echo htmlspecialchars($user['emergency_email1']); ?></p>
        <p style="font-family:'Josefin Sans', sans-serif;"><strong>Emergency Email 2:</strong> <?php echo htmlspecialchars($user['emergency_email2']); ?></p>
    <?php endif; ?>
        </b>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
    <button id="danger-link" class="danger-btn-floating" onclick="sendEmergencyAlert()">Emergency</button>
    <?php endif; ?>
    


<script>
    document.getElementById('danger-link').addEventListener('click', function(e) {
        e.preventDefault();
        var confirmed = confirm('Do you really want to share your location with your emergency emails');
        
        if (confirmed) {
            window.location.href = 'danger.php';
        }
    });
</script>

</section>
</body>
</html>
