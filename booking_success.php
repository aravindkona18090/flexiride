<?php

session_start();
include 'db.php';


if (!isset($_GET['ride_id'])) {
    header("Location: rides.php");
    exit();
}

$ride_id = $_GET['ride_id'];

// Get the ride details and the user who posted it
$sql = "SELECT r.origin, r.destination, r.ride_date, r.price,u.name AS posted_user_name, u.phone AS posted_user_phone ,r.price
        FROM rides r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ride_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $ride = $result->fetch_assoc();
   
} else {
    echo "Ride not found!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success</title>
    <style>
        /* General Body Styling */
body {
    font-family: "Josefin Sans", sans-serif;
    margin: 0;
    padding: 0;
    background-image: url("images/eee.jpg");
            background-repeat: no-repeat; 
            background-size: cover; 
            background-position: center;
            background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

/* Loader */
.loader {
    position: relative;
    width: 80px;
    height: 80px;
    border: 4px solid transparent;
    border-top-color: #88c9d0;
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
    margin-bottom: 20px;
}

.loader:before,
.loader:after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 4px solid transparent;
    border-radius: 50%;
}

.loader:before {
    border-top-color: #f2a5a5;
    animation: spin 1.5s linear infinite reverse;
}

.loader:after {
    border-top-color: #f7d59e;
    animation: spin 1.5s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success Message */
.success {
    text-align: center;
    padding: 20px;
    animation: fadeIn 1.5s ease-out;
    display: none;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    color:  #3498db;
    font-size: 2rem;
    margin-bottom: 20px;
    animation: bounceIn 1s ease-in-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 1; }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); }
}

/* Ride Details Table */
table {
    margin: 0 auto;
    border-collapse: collapse;
    width: 80%;
    background: #f9f9f9; /* Light background for contrast */
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    animation: slideUp 1.5s ease-out;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    font-weight: bold;
    color: #4a88a2; /* Light blue for headers */
}

td {
    color: #4a4a4a;
}

/* Button Styling */
.btn-home {
    display: inline-block;
    margin: 20px auto;
    padding: 10px 20px;
    background: linear-gradient(135deg, #e6eff7, #d8e6f2);
    color: #4a4a4a;
    text-decoration: none;
    text-align: center;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    width: 180px;
}

.btn-home:hover {
    background: linear-gradient(135deg, #d0ebff, #c2d9ee);
    transform: scale(1.05);
}

.btn-home:active {
    transform: scale(0.95);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    table {
        width: 90%;
    }

    th, td {
        font-size: 0.9rem;
    }

    h2 {
        font-size: 1.5rem;
    }

    .btn-home {
        width: 150px;
        font-size: 0.9rem;
    }
}


    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader" id="loader"></div>

    <!-- Success Message -->
    <div class="success" id="successMessage">
        <h2>Your ride has been booked successfully!</h2>
        <h3>Ride Details:</h3>
        <table>
            <tr>
                <th>Origin</th>
                <td><?php echo $ride['origin']; ?></td>
            </tr>
            <tr>
                <th>Destination</th>
                <td><?php echo $ride['destination']; ?></td>
            </tr>
            <tr>
                <th>Ride Date</th>
                <td><?php echo $ride['ride_date']; ?></td>
            </tr>
            <tr>
                <th>Price</th>
                <td>₹<?php echo $ride['price']; ?></td>
            </tr>
            <tr>
                <th>Posted By</th>
                <td><?php echo $ride['posted_user_name']; ?></td>
            </tr>
            <tr>
                <th>Contact</th>
                <td><?php echo $ride['posted_user_phone']; ?></td>
            </tr>
        </table>

        <!-- Button to go back to home page -->
        <a href="index.php" class="btn-home">Go to Home Page</a>
    </div>

    <script>
        // Simulate loader for 3 seconds, then show success message
        setTimeout(function() {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
        }, 3000);
    </script>
</body>
</html>
