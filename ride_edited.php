<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride eidting</title>
    <style>
        /* Reset and General Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Josefin Sans", sans-serif;
            background: #fff;
            color: #333;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #3498db;
            animation: fadeIn 1.5s ease-out;
        }

        /* Loader Styling */
        .loader {
            border: 10px solid rgba(52, 152, 219, 0.2);
            border-top: 10px solid #3498db;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            animation: spin 1.5s linear infinite, pulse 1.5s ease-in-out infinite;
            margin: 20px auto;
        }

        /* Success Message Styling */
        #success-message {
            display: none;
            color: #3498db;
            font-size: 20px;
            margin-top: 20px;
            animation: fadeIn 2s ease-out;
        }

        #success-message p {
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        a button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        a button:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        /* Animations */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.5);
            }
            50% {
                box-shadow: 0 0 20px 10px rgba(52, 152, 219, 0);
            }
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">    
</head>
<body>
    <div class="load">
        <h1>Updating Your Ride</h1>
        <div class="loader"></div>
    </div>
    <div id="success-message">
        <p>Ride Updated Successfully!</p>
        <a href="index.php"><button>Back to Home Page</button></a>
    </div>
    <script>
        // Simulate loading process
        setTimeout(() => {
            // Hide loader and show success message after 2 seconds
            document.querySelector(".load").style.display = "none";
            document.getElementById("success-message").style.display = "block";
        }, 2000); // Simulate a 2-second loading time
    </script>
</body>
</html>
