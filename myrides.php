<?php
include 'db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the user ID
$user_id = $_SESSION['user_id'];

// Fetch rides posted by the logged-in user along with the bookings made for those rides
$sql = "SELECT r.*, 
            b.seats_booked, 
            u.name AS booked_user_name, 
            u.phone AS booked_user_phone
        FROM rides r
        LEFT JOIN bookings b ON r.id = b.ride_id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE r.user_id = '$user_id'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posted Rides</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url("images/op.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            margin: 0px;
            padding: 0px;
            font-family: "Josefin Sans", sans-serif;
            color:  #000;
            font-size: large;
            height: 100vh;
            align-items: center;
            overflow: scroll; 
        }
        * {
            font-family: "Josefin Sans", sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Navigation Bar */
        .navbar {
            font-family: "Josefin Sans", sans-serif;
            background-color: #000; /* Black theme */
            color:white; /* White text */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0px 0px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); /* Slight shadow for depth */
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
            color: #ff9800; /* Logo hover color */
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
            color: #fff; /* Default link color */
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
                margin-bottom:0;
            }
        }

        /* Table Styles */
        .table {
            font-family: "Josefin Sans", sans-serif;
            margin-top: 5px;
        }

        .table table {
            width: 80%;
            margin: 0 auto;
            border-collapse: collapse;
            backdrop-filter: blur(10px); /* Apply blur to the entire table */
        }

        .table th, 
        .table td {
            padding: 15px;
            text-align: center;
            color: #000; /* Black text in cells */
            backdrop-filter: blur(10px); /* Apply blur to table cells */
            background-color: rgba(255, 255, 255, 0.5); /* Semi-transparent white background for cells */
            border: 1px solid rgba(255, 255, 255, 0.5); /* Semi-transparent borders */
        }

        .table th {
            background-color: rgba(0, 0, 0, 0.5); /* Darker semi-transparent background for header */
            color: #fff; /* White text for headers */
            font-size: 18px;
        }

        .table tr:nth-child(even) td {
            background-color: rgba(255, 255, 255, 0.3); /* Very light grey background for even rows */
        }

        /* Hover effect for table rows */
        .table tr:hover td {
            background-color: rgba(255, 255, 255, 0.2);
            color: black; 
        }

        /* Styling for the "No rides posted" message */
        .table p {
            text-align: center;
            font-size: 1.2em;
            color: black;
        }

        /* Button Styling */
        .table  button {
            font-family: "Josefin Sans", sans-serif;
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: black;
            background-color: #000000; /* Vibrant button color */
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Button shadow */
            transition: background-color 0.3s ease;
        }
        button {
            font-family: "Josefin Sans", sans-serif;
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: white;
            background-color: #000000; /* Vibrant button color */
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Button shadow */
            transition: background-color 0.3s ease;
        }
        .table a {
            display: inline-block;
            padding: 10px 10px;
            margin: 20px auto;
            color: white;
            background-color: #000000; /* Vibrant button color */
            border: none;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Button shadow */
            transition: background-color 0.3s ease;
        }

        .table a:hover, button:hover {
            background: linear-gradient(135deg, #4a4a8a, #6767b3); /* Darker shade on hover */
            color: white;
        }
        .book{
            width: fit-content;
            justify-content: center;
            margin: 0 auto;
            padding: 0px 15px;
            margin-top: 3px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 15px black;
            
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
</head>
<body>
<nav class="navbar">
    <div class="logo">
      <img src="images/logo1.png" al
      
      t="logo" height="30%"; width="30%;">
    </div>
    <img src="images/name.png" alt="logo" height="20%"; width="20%;">
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
    // Add an event listener to the logout link
    document.getElementById('logout-link').addEventListener('click', function(e) {
        // Prevent the default action
        e.preventDefault();
        
        // Display confirmation alert
        var confirmed = confirm('Are you sure you want to logout?');
        
        // If confirmed, redirect to logout.php
        if (confirmed) {
            window.location.href = 'logout.php';
        }
    });
</script>
<br>
<div class="book" >
<h2 style="text-align:center;font-family: 'Josefin Sans', sans-serif;">My Posted Rides 
    <a href="my_booked_rides.php">
        <button style="text-align:center;font-family: font-family: 'Josefin Sans', sans-serif;;font-size:large;">My Booked rides</button>
    </a>
</h2>
</div>
<div class="table">
<?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>Origin</th>
            <th>Destination</th>
            <th>RideDate</th>
            <th>RideTime</th>
            <th>Price</th>
            <th>VehicleType</th>
            <th>Seats Available</th>
            <th>Seats Booked</th>
            <th>Booked User Name</th>
            <th>Booked User Phone</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['origin']); ?></td>
                <td><?php echo htmlspecialchars($row['destination']); ?></td>
                <td><?php echo htmlspecialchars($row['ride_date']); ?></td>
                <td><?php echo htmlspecialchars($row['ride_time']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                <td><?php echo htmlspecialchars($row['seats_available']); ?></td>
                <td><?php echo htmlspecialchars($row['seats_booked'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars($row['booked_user_name'] ?? 'No bookings'); ?></td>
                <td><?php echo htmlspecialchars($row['booked_user_phone'] ?? 'N/A'); ?></td>
                <td>
                    <a href="edit_ride.php?id=<?php echo $row['id']; ?>">Edit</a> |
                    <a href="delete_ride.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this ride?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>You have not posted any rides yet.</p>
<?php endif; ?>
</div>
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
