<?php
session_start();

// Check if the user is the admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // If not admin, redirect to login page
    header("Location: login.php");
    exit();
}

// Admin Page Content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url("images/home.jpg");
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
            padding-top: 10px ;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            height: 15%;
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
            display: flex;
            align-items: center;
            width:150%;
            
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-size: 18px;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 8px 8px;
            border-radius: 4px;
        }

        /* Hover Effects */
        .nav-links li:hover {
    color: white; /* Text color on hover */
    background: linear-gradient(135deg, #4a4a8a, #6767b3); /* Gradient matching the button's color */
    border-radius: 30%;
}
      .nav-links a{
        
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
    </style>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<nav class="navbar">
    <div class="logo">
      <img src="images/logo1.png" alt="logo"  height="30%"; width="30%;">

    </div>
    <img src="images\name.png" alt="logo"  height="30%"; width="20%;">
    <ul class="nav-links">
      <li><a href="admin_dashboard.php">Home</a></li>
      <?php if (isset($_SESSION['is_admin'])): ?>
        <li><a href="admin_view_all_rides.html">Rides</a></li>
        <li><a href="admin_view_all_bookings.html">Bookings</a></li>
        <li><a href="admin_manage_users.php">Manage Users</a></li>
        <li><a href="admin_feedback.php">Feedback</a></li>
        <li><a href="admin_queries.php">Queries</a></li>
        <li><a id="logout-link" href="logout.php" class="logout">Logout</a></li>
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
</script><!-- Navbar -->
 
    <!-- Main Content -->
    <div class="content">
        
        <!-- Other content here -->
    </div>
</body>
</html>

