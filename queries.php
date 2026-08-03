<?php
session_start();

// Initialize variables for form data and success message
$name = $email = $query = "";
$successMessage = "";

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate form data
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $query = isset($_POST['query']) ? htmlspecialchars(trim($_POST['query'])) : '';

    // Simple validation
    if (!empty($name) && !empty($email) && !empty($query)) {
        // Connect to the database (ensure the database connection file is correct)
        include "db.php"; // Replace with your database connection file

        // Insert query into the database
        $stmt = $conn->prepare("INSERT INTO queries (name, email, query) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $query);

        if ($stmt->execute()) {
            $successMessage = "Thank you, $name! Your query has been submitted successfully.";
        } else {
            $successMessage = "An error occurred. Please try again.";
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();
    } else {
        $successMessage = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
    <title>Queries - FlexiRide</title>
    <style>
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

        body {
            font-family: "Josefin Sans", sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        .contact-section {
            background-color: #f4f4f4;
            padding: 40px;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .contact-section h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 20px;
        }

        .contact-section form {
            display: flex;
            flex-direction: column;
        }

        .contact-section label {
            text-align: left;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        .contact-section input[type="text"],
        .contact-section input[type="email"],
        .contact-section textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .contact-section button {
            padding: 10px;
            background-color: #000000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .contact-section button:hover {
            background-color: #444444;
        }

        .success-message {
            margin-top: 20px;
            color: green;
        }

        .error-message {
            margin-top: 20px;
            color: red;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home-btn">Back to Home</a>
    <section class="contact-section">
        <h1>Queries</h1>
        <form action="" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="query">Query:</label>
            <textarea id="query" name="query" rows="5" required></textarea>

            <button type="submit" style="font-family: 'Josefin Sans', sans-serif;">Send Queries</button>
        </form>

        <?php if (!empty($successMessage)): ?>
            <div class="success-message"><?php echo $successMessage; ?></div>
        <?php endif; ?>
    </section>
</body>
</html>
