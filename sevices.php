<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <title>Our Services - FlexiRide</title>
    <style>
        /* General styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Josefin Sans", sans-serif;
            line-height: 1.6;
            background: linear-gradient(to right, #f3f4f6, #eef2f3);
            color: #333;
            padding: 20px;
        }

        /* Section styling */
        section {
            background: #fff;
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        h1::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: #3498db;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        h2 {
            font-size: 1.8rem;
            color: #34495e;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        p {
            font-size: 1rem;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.8;
        }

        /* Back to Home button */
        .back-home-btn {
            display: inline-block;
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: bold;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .back-home-btn:hover {
            background-color: #2c3e50;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.5rem;
            }

            p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

    <section>
        <h1>Our Services</h1>
        <p>At FlexiRide, we offer a variety of services tailored to meet the needs of our community members. Whether you're looking to share a car or bike ride, we have options to make your journey smoother and more affordable.</p>

        <h2>Car Ride Sharing</h2>
        <p>Find available seats in cars going your way. Our platform connects you with trusted drivers who are heading to the same destination as you. Whether it’s a daily commute or a long-distance trip, car-sharing is an eco-friendly and budget-conscious option.</p>

        <h2>Bike Ride Sharing</h2>
        <p>Our unique bike ride-sharing service is perfect for those who prefer a two-wheeled adventure. You can post or find bike rides, making it convenient to travel short distances quickly and cost-effectively.</p>

        <h2>Post a Ride</h2>
        <p>If you have spare seats in your car or bike, why not post a ride? You can help others while also reducing your travel costs. Just provide a few details about your route and schedule, and we'll help match you with passengers going the same way.</p>

        <h2>Safety and Trust</h2>
        <p>Your safety is our priority. We encourage users to provide feedback on their ride experiences, ensuring a trustworthy and safe community for everyone.</p>

        <a href="index.php" class="back-home-btn">Back to Home</a>
    </section>

</body>
</html>
