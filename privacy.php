<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }
        .navbar {
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 24px; font-weight: 700; color: #38bdf8; text-decoration: none; }
        .nav-links { display: flex; gap: 20px; list-style: none; }
        .nav-links a { color: #94a3b8; text-decoration: none; font-size: 16px; font-weight: 500; }
        .nav-links a:hover { color: #38bdf8; }

        .container { max-width: 900px; margin: 50px auto; padding: 0 20px; width: 100%; }
        .card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            line-height: 1.7;
        }
        h1 { font-size: 32px; color: #38bdf8; margin-bottom: 20px; text-align: center; }
        p { color: #cbd5e1; font-size: 16px; margin-bottom: 15px; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo"><i class='bx bxs-navigation'></i> FlexiRide</a>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="find_ride.php">Find Ride</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <h1>Privacy Policy</h1>
        <p>Your privacy and safety are paramount at <strong>FlexiRide</strong>. We collect necessary user details (name, phone number, email address) solely to facilitate carpooling & bike pooling matching and emergency safety tracking.</p>
        <p>Location data retrieved during emergency SOS alerts (`danger.php`) is strictly used to send immediate GPS coordinates to your specified emergency contacts and is never sold or shared with third parties.</p>
    </div>
</div>

</body>
</html>
