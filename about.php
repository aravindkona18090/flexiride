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
    <title>About Us - FlexiRide</title>
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

        ul {
            margin-left: 20px;
            list-style-type: square;
            color: #444;
        }

        li {
            margin-bottom: 10px;
            font-size: 1rem;
        }

        li strong {
            color: #3498db;
        }

        /* Hover effects */
        ul li:hover {
            color: #2c3e50;
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

            ul {
                margin-left: 15px;
            }
        }
    </style>
</head>
<body>

    <section>
        <h1>About FlexiRide</h1>
        <p>Welcome to FlexiRide, your trusted platform for sharing rides, both cars and bikes, with ease and comfort. Established in 2024, our mission is to make travel more affordable, sustainable, and community-oriented.</p>
        
        <h2>Our Story</h2>
        <p>FlexiRide started with the simple idea of connecting people who were traveling to the same destination and had spare seats. Over time, we realized that many people also needed a reliable platform to offer and find bike rides. Today, FlexiRide serves thousands of people who choose to share their rides for convenience and eco-friendliness.</p>
        
        <h2>Our Values</h2>
        <ul>
            <li><strong>Community:</strong> We believe in building a strong and trustworthy community of riders and drivers.</li>
            <li><strong>Sustainability:</strong> Reducing carbon footprints by encouraging ride-sharing for both cars and bikes.</li>
            <li><strong>Affordability:</strong> Helping people save money on travel by offering budget-friendly alternatives to traditional transportation.</li>
        </ul>
        
        <a href="index.php" class="back-home-btn">Back to Home</a>
    </section>
    
</body>
</html>
