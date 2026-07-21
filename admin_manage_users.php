<?php
include "db.php";
$sql = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
            margin-bottom: 30px; /* Increased gap from content */
        }

        .user-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px; /* Increased padding for a bit more space */
            margin-bottom: 20px; /* Increased margin between cards */
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .user-card h3 {
            margin: 0;
            color: #333;
            margin-bottom: 10px; /* Add space below the name */
        }

        .user-card p {
            margin: 10px 0; /* Added more spacing between paragraphs */
            color: #555;
        }

        .user-card .timestamp {
            font-size: 14px;
            color: #888;
            text-align: right;
            margin-top: 15px; /* Added extra gap before the timestamp */
        }

        .actions a {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 3px;
            color: white;
            margin-right: 10px; /* Added space between buttons */
        }

        .actions {
            margin-top: 15px; /* Added gap above the action buttons */
        }

        .no-users {
            text-align: center;
            color: #666;
            margin: 30px 0; /* Increased gap for no users message */
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
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
            <li><a href="admin_manage_users.php" class="active">Manage Users</a></li>
            <li><a href="admin_feedback.php">Feedback</a></li>
            <li><a href="admin_queries.php">Queries</a></li>
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
        document.getElementById('delete').addEventListener('click', function(e) {
            e.preventDefault();
            var confirmed = confirm('Are you sure you want to delete this user?');
            if (confirmed) {
                window.location.href = 'admin_delete_user.php?id=<?php echo $row['id']; ?>';
            }
        });
    </script>
    <div class="container">
        <h1>Manage Users</h1>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="user-card">
                    <h3><?php echo htmlspecialchars($row['name'] ?? ''); ?></h3>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email'] ?? ''); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['phone'] ?? ''); ?></p>
                    <p><strong>Emergency Email 1:</strong> <?php echo htmlspecialchars($row['emergency_email1'] ?? ''); ?></p>
                    <p><strong>Emergency Email 2:</strong> <?php echo htmlspecialchars($row['emergency_email2'] ?? ''); ?></p>
                    <div class="actions">
                        <a href="admin_edit_user.php?id=<?php echo $row['id']; ?>" style="background-color: #4CAF50;">Edit</a>
                        <a id = "delete" href="admin_delete_user.php?id=<?php echo $row['id']; ?>" style="background-color: #f44336;">Delete</a>
                    </div>
                    <!-- Check if 'joined_at' exists -->
                    <?php if (isset($row['joined_at'])): ?>
                        <p class="timestamp">Joined at: <?php echo $row['joined_at']; ?></p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-users">No users available.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
