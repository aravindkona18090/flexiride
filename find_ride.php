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
    $sql = "SELECT * FROM rides WHERE origin = '$origin' AND destination = '$destination' AND vehicle_type = '$vehicle_type' AND user_id != '$current_user_id'";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Body Styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background:  linear-gradient(135deg, #74ebd5, #ACB6E5);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: scroll; 
        }

        /* Navbar Styles */
        .navbar {
            background-color: #000;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .logo img {
            height: 50px;
            width: fit-content; /* Adjust as needed */
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
            color: #ff9800;
            background-color: rgba(255, 152, 0, 0.2);
            border-radius: 50%;
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

        /* Animation for floating bubbles */
        @keyframes bubble {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-600px); opacity: 0; }
        }

        .bubble {
            position: absolute;
            bottom: -150px;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
            border-radius: 50%;
            animation: bubble 10s infinite ease-in-out;
            z-index: -10;
            box-shadow: 0px 4px 15px rgba(255, 255, 255, 0.7);
        }

        /* Increased number of bubbles */
        .bubble:nth-child(1) { width: 60px; height: 60px; left: 10%; animation-duration: 7s; }
        .bubble:nth-child(2) { width: 100px; height: 100px; left: 30%; animation-duration: 12s; }
        .bubble:nth-child(3) { width: 40px; height: 40px; left: 50%; animation-duration: 8s; }
        .bubble:nth-child(4) { width: 70px; height: 70px; left: 70%; animation-duration: 15s; }
        .bubble:nth-child(5) { width: 30px; height: 30px; left: 90%; animation-duration: 9s; }
        .bubble:nth-child(6) { width: 80px; height: 80px; left: 20%; animation-duration: 11s; }
        .bubble:nth-child(7) { width: 50px; height: 50px; left: 40%; animation-duration: 14s; }
        .bubble:nth-child(8) { width: 90px; height: 90px; left: 60%; animation-duration: 13s; }
        .bubble:nth-child(9) { width: 30px; height: 30px; left: 80%; animation-duration: 16s; }
        .bubble:nth-child(10) { width: 75px; height: 75px; left: 25%; animation-duration: 10s; }
        .bubble:nth-child(11) { width: 60px; height: 60px; left: 5%; animation-duration: 7s; }
        .bubble:nth-child(12) { width: 100px; height: 100px; left: 15%; animation-duration: 12s; }
        .bubble:nth-child(13) { width: 40px; height: 40px; left: 35%; animation-duration: 8s; }
        .bubble:nth-child(14) { width: 70px; height: 70px; left: 45%; animation-duration: 15s; }
        .bubble:nth-child(15) { width: 30px; height: 30px; left: 55%; animation-duration: 9s; }
        .bubble:nth-child(16) { width: 80px; height: 80px; left: 65%; animation-duration: 11s; }
        .bubble:nth-child(17) { width: 50px; height: 50px; left: 75%; animation-duration: 14s; }
        .bubble:nth-child(18) { width: 90px; height: 90px; left: 2%; animation-duration: 13s; }
        .bubble:nth-child(19) { width: 30px; height: 30px; left: 88%; animation-duration: 16s; }
        .bubble:nth-child(20) { width: 75px; height: 75px; left: 6%; animation-duration: 10s; }

        /* Container for the form */
        .container {
            z-index: 2;
            background: linear-gradient(135deg, #F0FFF0, #E0FFFF);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            transition: transform 0.3s ease;
            margin: auto auto;
        }

        /* Container Hover Effect */
        .container:hover {
            transform: scale(1.05);
        }

        /* Form styling */
        .container h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        .container input, .container select, .container button {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }

        .container input:focus, .container select:focus {
            outline: none;
            box-shadow: 0 0 8px #007BFF;
        }

        .container button {
            background: linear-gradient(135deg, #FFCCCB, #DDA0DD);
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .container button:hover {
            background-color: #0056b3;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            text-align: center;
        }

        .modal-content h3 {
            color: #007BFF;
        }

        .modal-content ul {
            list-style-type: none;
            padding: 0;
        }

        .modal-content li {
            background-color: #f1f1f1;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .modal-content li:hover {
            background-color: #e0e0e0;
        }

        .modal-content .book-now {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .modal-content .close {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <img src="images/logo1.png" alt="logo" height="30%" width="30%;">
        </div>
        <img src="images/name.png" alt="logo" height="20%" width="20%">
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
    </div>

    <!-- Bubbles for animation -->
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>

    <div class="container">
        <h2>Find a Ride</h2>
        <form id="findRideForm">
            <input type="text" id="origin" placeholder="Origin" required>
            <input type="text" id="destination" placeholder="Destination" required>
            <select id="vehicle_type" required>
                <option value="">Select Vehicle Type</option>
                <option value="car">Car</option>
                <option value="bike">Bike</option>
            </select>
            <button type="submit">Find Ride</button>
        </form>
    </div>

    <!-- Modal -->
    <div class="modal" id="rideModal">
        <div class="modal-content">
            <h3>Available Rides</h3>
            <ul id="rideList"></ul>
            <button class="close" onclick="closeModal()">Close</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#findRideForm').on('submit', function(event) {
                event.preventDefault();

                let origin = $('#origin').val();
                let destination = $('#destination').val();
                let vehicle_type = $('#vehicle_type').val();

                $.ajax({
                    type: 'POST',
                    url: 'find_ride.php',
                    data: { origin: origin, destination: destination, vehicle_type: vehicle_type },
                    dataType: 'json',
                    success: function(data) {
                        if (data.rides.length > 0) {
                            let rideList = $('#rideList');
                            rideList.empty();
                            data.rides.forEach(function(ride) {
                                rideList.append('<li>' + ride.origin + ' to ' + ride.destination + ' - ' + ride.vehicle_type + '</li>');
                            });
                            $('#rideModal').fadeIn();
                        } else {
                            alert('No rides found.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while finding rides.');
                    }
                });
            });
        });

        function closeModal() {
            $('#rideModal').fadeOut();
        }
    </script>
</body>
</html>
