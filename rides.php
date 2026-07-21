<?php
include 'db.php';
session_start();

$rides = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $vehicle_type = $_POST['vehicle_type'];

    // Get the current user ID
    $current_user_id = $_SESSION['user_id'];

    // Modify the query to exclude rides posted by the current user
    $sql = "SELECT * FROM rides WHERE origin = '$origin' AND destination = '$destination' AND vehicle_type = '$vehicle_type' AND user_id != '$current_user_id' AND seats_available > 0 " ;
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rides[] = $row;
        }
    }
    
    // Return the rides as a JSON response
    header('Content-Type: application/json');
    echo json_encode(['rides' => $rides]);
    exit; // Stop further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Ride</title>
    <link rel="stylesheet" href="">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
    <style>
        body {
            background-image: url("images/niki.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            margin: 0;
            padding: 0;
            font-family: "Josefin Sans", sans-serif;
            text-transform: capitalize;
            font-size: large;
            height: 100vh;
            align-items: center;
            background-attachment: fixed;
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

        .form {
            font-family: "Josefin Sans", sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(244, 244, 244, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(7px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.9);
        }
        .form:hover{
            backdrop-filter: blur(10px);
            animation: bounce 0.6s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .form h2 {
            text-align: center;
            color: #333;
        }

        .form label {
            font-weight: bold;
            display: block;
            margin: 10px 0 5px;
        }

        .form input[type="text"],
        .form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: large;
            font-family: "Josefin Sans", sans-serif;
        }

        .form button[type="submit"] {
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
    width: 100%;
    position: relative;
    overflow: hidden;
}

.form button[type="submit"]:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.5);
}

.form button[type="submit"]:active {
    transform: translateY(1px);
    background: #39396b;
}

/* Button Ripple Effect */
.form button[type="submit"]::after {
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

.form button[type="submit"]:hover::after {
    width: 200%;
    height: 500%;
    opacity: 0;
}


        /* Modal Styles */
        .modal {
            font-family: "Josefin Sans", sans-serif;
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.7); /* Black w/ opacity */
            padding-top: 60px; /* Location of the box */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            width: 80%; /* Could be more or less, depending on screen size */
            animation: fadeIn 0.4s; /* Add fade-in effect */
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .avail {
            font-family: "Josefin Sans", sans-serif;
            margin-top: 20px;
        }

        .avail .rides-title {
            color: #4CAF50;
            text-align: center;
        }

        .avail .rides-list {
            list-style-type: none;
            padding: 0;
        }

        .avail .ride-item {
            border: 1px solid #ccc;
            border-radius: 5px;
            margin: 10px 0;
            padding: 15px;
            background-color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.3s ease;
        }

        .avail .ride-item:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .avail .ride-details {
            flex-grow: 1;
        }

        .avail .book-now {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .avail .book-now:hover {
            background-color: #45a049;
        }

        .avail .no-rides {
            text-align: center;
            padding: 20px;
            background-color: #ffe3e3;
            border: 1px solid #f00;
            border-radius: 5px;
            color: #f00;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
</head>
<body>
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

    <div class="form">
        <h2 style="text-align: center;">Find a Ride</h2>
        <form id="ride-form" method="post" action="">
            <label for="origin">Origin:</label>
            <input type="text" name="origin" required>
            <label for="destination">Destination:</label>
            <input type="text" name="destination" required>
            <label for="vehicle_type">Vehicle Type:</label>
            <select name="vehicle_type" required>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" id="close-modal">&times;</span>
            <div id="modal-body" class="avail">
                <!-- Results will be injected here -->
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#ride-form').on('submit', function (e) {
                e.preventDefault(); // Prevent default form submission
                const formData = $(this).serialize(); // Serialize form data

                $.post('', formData, function (response) {
                    const rides = response.rides; // Assuming response structure is { rides: [...] }
                    let modalContent = '';

                    if (rides && rides.length > 0) {
                        modalContent += '<h3 class="rides-title">Available Rides:</h3><ul class="rides-list">';
                        rides.forEach(ride => {
                            modalContent += `<li class="ride-item">
                                <div class="ride-details">
                                    Ride from ${ride.origin} to ${ride.destination} on ${ride.ride_date} at ${ride.ride_time}. 
                                    Price: ₹${ride.price}
                                </div>
                                <a href="book_ride.php?ride_id=${ride.id}" class="book-now">Book Now</a>
                            </li>`;
                        });
                        modalContent += '</ul>';
                    } else {
                        modalContent = '<div class="no-rides">No rides found.</div>';
                    }

                    $('#modal-body').html(modalContent); // Inject modal content
                    $('#modal').fadeIn(); // Show modal
                }, 'json');
            });

            // Close modal
            $('#close-modal').on('click', function () {
                $('#modal').fadeOut();
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
