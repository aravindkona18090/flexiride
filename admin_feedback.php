<?php
include "db.php";
$sql = "SELECT * FROM feedback ORDER BY submitted_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Josefin Sans", sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            font-size: large;
            height: 100vh;
            background-attachment: fixed;
            overflow: scroll;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            background-color: #000;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
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

        .nav-links {
            list-style: none;
            display: flex;
        }

        .nav-links li {
            margin-left: 30px;
            display: flex;
            align-items: center;
            width: 150%;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-size: 18px;
            transition: color 0.3s ease, background-color 0.3s ease;
            padding: 8px 8px;
            border-radius: 4px;
        }

        .nav-links li:hover {
            color: white;
            background: linear-gradient(135deg, #4a4a8a, #6767b3);
            border-radius: 30%;
        }

        .nav-links a.active {
            background-color: linear-gradient(135deg, #4a4a8a, #6767b3);
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

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .feedback-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .feedback-card:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #e0e0e0, #ffffff);
        }

        .feedback-card h3 {
            margin: 0;
            color: #333;
        }

        .feedback-card p {
            margin: 5px 0;
            color: #555;
        }

        .feedback-card .timestamp {
            font-size: 14px;
            color: #888;
            text-align: right;
            margin-top: 10px;
        }

        .no-feedback {
            text-align: center;
            color: #666;
            margin: 20px 0;
        }
    </style>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/logo1.png" alt="logo" height="30%" width="30%">
        </div>
        <img src="images/name.png" alt="logo" height="30%" width="20%">
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Home</a></li>
            <li><a href="admin_view_all_rides.html">Rides</a></li>
            <li><a href="admin_view_all_bookings.html">Bookings</a></li>
            <li><a href="admin_manage_users.php">Manage Users</a></li>
            <li><a href="admin_feedback.php" class="active">Feedback</a></li>
            <li><a href="admin_queries.php" class="active">Queries</a></li>
            <li><a id="logout-link" href="logout.php" class="logout">Logout</a></li>
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
    <div class="container">
        <h1>Feedbacks</h1>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="feedback-card">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($row['feedback'])); ?></p>
                    <p class="timestamp">Submitted at: <?php echo $row['submitted_at']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-feedback">No feedback available.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
