<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (isset($_GET["error"])) {
    $error = $_GET["error"];
    $error_message = $error; // Assign error message
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_type = $_POST['vehicle_type'];
    $seats_available = $_POST['seats_available'];

    // Validation for seat availability
    if ($vehicle_type === 'bike' && $seats_available != 1) {
        $error_message = "For bikes, seat availability must be exactly 1.";
    } elseif ($vehicle_type === 'car' && ($seats_available < 1 || $seats_available > 3)) {
        $error_message = "For cars, seat availability must be between 1 and 3.";
    } else {
        // Store form data into session
        $_SESSION['origin'] = $_POST['origin'];
        $_SESSION['destination'] = $_POST['destination'];
        $_SESSION['ride_date'] = $_POST['ride_date'];
        $_SESSION['ride_time'] = $_POST['ride_time'];
        $vehicle_type = $_POST['vehicle_type'];
        $_SESSION['seats_available'] = $_POST['seats_available'];
        
        // Redirect to the second page
        header("Location: ride_output.php?vehicle_type=$vehicle_type");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Ride</title>
    <style>
        body {
            background-image: url("images/postal.png");
            background-repeat: no-repeat; 
            background-size: cover; 
            background-position: center;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            font-family: "Josefin Sans", sans-serif;
            text-transform: capitalize;
            font-size: large;
            height: 100vh;
            overflow: scroll; 
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Navigation Bar */
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

        /* Navigation Links */
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

        /* Hover Effects */
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

        /* Responsive Design for smaller screens */
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

        .post {
            font-family: "Josefin Sans", sans-serif;
            max-width: 600px;
            margin: 5% auto;
            padding: 20px;
            background-color: rgba(244, 244, 244, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.9);
        }
        h2{
            text-align: center;
            padding: 5px;
            padding-bottom: 9px;
        }
        .post label {
            display: block;
            margin-bottom: 5px;
            font-size: large;
        }

        .post input, .post select {
            font-family: "Josefin Sans", sans-serif;
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
            font-size: large;
        }

        .post input:hover, .post select:hover {
            border-color: #3b6faa; /* Change border color on hover */
            transform: translateY(-3px); /* Lift effect */
        }

        .post input:focus, .post select:focus {
            border-color: #3b6faa; /* Change border color on focus */
            box-shadow: 0 0 5px rgba(59, 106, 170, 0.5); /* Lift effect */
            outline: none; /* Remove default outline */
        }
        
        .post button[type="submit"] {
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
    width: 95%;
    position: relative;
    overflow: hidden;
}

.post button[type="submit"]:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.5);
}

.post button[type="submit"]:active {
    transform: translateY(1px);
    background: #39396b;
}

/* Button Ripple Effect */
.post button[type="submit"]::after {
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

.post button[type="submit"]:hover::after {
    width: 200%;
    height: 500%;
    opacity: 0;
}

        .icon {
            margin-right: 5px;
        }
        /* Error message styling */
        .error {
            color: red;
            font-size: 0.9em;
            display: none;
        }
        .error.active {
            display: block;
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
        .error1{
            color: #fff;
            margin: 2%;
        }
    </style>
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
</head>
<body style="background-color: white;">
<nav class="navbar">
    <div class="logo">
        <img src="images/logo1.png" alt="logo" height="30%" width="30%;">
    </div>
    <img src="images/name.png" alt="logo" height="20%" width="20%;">
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="rides.php">Find</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="post_ride.php">Post</a></li>
            <li><a href="myrides.php">MyRides</a></li>
            <li><a href="#" id="logout-link">Logout</a></li>
            <li><a href="profile.php"><i class='bx bxs-user bx-sm' ></i></a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="post">
    <h2>Enter Ride Details</h2>
    <form id="rideForm" method="post" action="">
        <input type="text" name="origin" placeholder="Enter origin" required>
        <input type="text" name="destination" placeholder="Enter destination" required>
        <input type="date" id="ride_date" name="ride_date" required>
        <input type="time" id="ride_time" name="ride_time" required>
        <select id="vehicle_type" name="vehicle_type" required>
            <option value="car">Car</option>
            <option value="bike">Bike</option>
        </select>
        <input type="number" id="seats_available" name="seats_available" placeholder="Enter how many seats" required>
        <span id="seat-error" class="error"></span> <!-- Error span for seat validation -->
        <button type="submit">Next</button>
        <div class="error1">
        <?php if(isset($error_message)):?>
           <?php echo $error_message;?>
        <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const today = new Date();
    const year = today.getFullYear();
    const month = (today.getMonth() + 1).toString().padStart(2, '0');
    const day = today.getDate().toString().padStart(2, '0');
    const currentDate = `${year}-${month}-${day}`;

    document.getElementById('ride_date').setAttribute('min', currentDate);

    document.getElementById('ride_date').addEventListener('change', function() {
        const selectedDate = this.value;
        const currentTimeInput = document.getElementById('ride_time');
        
        currentTimeInput.removeAttribute('min');
        if (selectedDate === currentDate) {
            const hours = today.getHours().toString().padStart(2, '0');
            const minutes = today.getMinutes().toString().padStart(2, '0');
            const currentTime = `${hours}:${minutes}`;
            currentTimeInput.setAttribute('min', currentTime);
        }
    });

    // Front-end validation for seat availability
    document.getElementById('rideForm').addEventListener('submit', function(event) {
        const vehicleType = document.getElementById('vehicle_type').value;
        const seatsAvailable = document.getElementById('seats_available').value;
        const seatError = document.getElementById('seat-error');

        seatError.classList.remove('active');

        if (vehicleType === 'bike' && seatsAvailable != 1) {
            seatError.textContent = "For bikes, seat availability must be exactly 1.";
            seatError.classList.add('active');
            event.preventDefault();
        } else if (vehicleType === 'car' && (seatsAvailable < 1 || seatsAvailable > 3)) {
            seatError.textContent = "For cars, seat availability must be greaterthan or equal to 1 and  lessthan or equal to 3.";
            seatError.classList.add('active');
            event.preventDefault();
        }
    });
});
$('#logout-link').on('click', function(e) {
            e.preventDefault();
            var confirmed = confirm('Are you sure you want to logout?');
            if (confirmed) {
                window.location.href = 'logout.php';
            }
        });
</script>
<?php if (isset($_SESSION['user_id'])): ?>
    <button id="danger-link" class="danger-btn-floating" onclick="sendEmergencyAlert()">Emergency</button>
    <?php endif; ?>



<script>
    // Add an event listener to the logout link
    document.getElementById('danger-link').addEventListener('click', function(e) {
        // Prevent the default action
        e.preventDefault();
        
        // Display confirmation alert
        var confirmed = confirm('Do you really want to share your location with your emergency emails');
        
        // If confirmed, redirect to logout.php
        if (confirmed) {
            window.location.href = 'danger.php';
        }
    });
</script>
</body>
</html>
