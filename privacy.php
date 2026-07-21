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
    <title>Privacy Policy - FlexiRide</title>
    <style>
        /* General styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family:  "Josefin Sans", sans-serif;
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
        <h1>Privacy Policy</h1>
        <p>Your privacy is important to us. This Privacy Policy outlines how FlexiRide collects, uses, and protects your personal information. By using our platform, you agree to the practices described below.</p>

        <h2>Information We Collect</h2>
        <p>We may collect the following types of information:</p>
        <ul>
            <li><strong>Personal Information:</strong> Such as your name, email address, phone number, and payment details when you register or book a ride.</li>
            <li><strong>Location Data:</strong> To match you with rides or provide navigation services.</li>
            <li><strong>Usage Information:</strong> Details about how you interact with our platform, including pages visited and services used.</li>
        </ul>

        <h2>How We Use Your Information</h2>
        <p>Your information is used to:</p>
        <ul>
            <li>Provide and improve our ride-sharing services.</li>
            <li>Facilitate communication between riders and drivers.</li>
            <li>Enhance user experience through analytics and feedback.</li>
        </ul>

        <h2>Data Protection</h2>
        <p>We implement robust security measures to protect your data from unauthorized access, alteration, or disclosure. However, no method of data transmission or storage is 100% secure, and we cannot guarantee absolute security.</p>

        <h2>Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access and update your personal information.</li>
            <li>Request the deletion of your data.</li>
            <li>Withdraw consent for data processing where applicable.</li>
        </ul>

        <h2>Changes to This Policy</h2>
        <p>We may update this Privacy Policy periodically to reflect changes in our practices or for legal reasons. Please review this page regularly for updates.</p>

        <a href="index.php" class="back-home-btn">Back to Home</a>
    </section>

</body>
</html>
