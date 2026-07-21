<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor\autoload.php';
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $name=$_POST["name"];
    $email=$_POST["email"];
    $feedback=$_POST["Feedback"];
    $sql = "INSERT INTO feedback(name, email, feedback) VALUES('$name', '$email', '$feedback')";

    if ($conn->query($sql) === TRUE) 
    {
        try{
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';              
            $mail->SMTPAuth = true;                        
            $mail->Username = 'flexiride247@gmail.com';   
            $mail->Password = 'lhyzlfabuyopgkqo';         
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port = 587;                            

            $mail->setFrom('flexiride247@gmail.com', 'FlexiRide'); 
        $mail->addAddress("feedbackflexiride@gmail.com");
        $mail->isHTML(true);
        $mail->Subject = "Feedback";
        $mail->Body = "
                <h3>New Feedback Received</h3>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Feedback:</strong> $feedback</p>
            ";
        $mail->send();
        }
        catch(Exception $e){
            echo "<script>alert('Feed back saved but error ouured in sending email:$e');</script>";
            echo "<script> alert('Feed back sent succcessfully');</script>";
        }
        } 
    else 
    {
        echo "<script>alert('Feedback not sent');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">    
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <style>
        /* Global styles */
        body {
            font-family: "Josefin Sans", sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
            
        }

        

        /* Form container */
        form {
            
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px 35px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            animation: pop-in 1s ease forwards;
            backdrop-filter: blur(10px);
            background-color: rgba(244, 244, 244, 0.7);
            
        }

        @keyframes pop-in {
            0% { transform: scale(0.7); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Labels */
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
            color: #333333;
            font-family: "Josefin Sans", sans-serif;
            font-size: large;
            opacity: 0;
            animation: fade-in 1.5s forwards;
        }

        @keyframes fade-in {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Input fields */
        input[type="text"], 
        input[type="email"], 
        textarea {
            width: 95%;
            padding: 12px;
            margin-top: 5px;
            font-size: large;
            margin-bottom: 15px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            color: #333333;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, 
        input[type="email"]:focus, 
        textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
            transform: scale(1.05);
        }

        /* Textarea */
        textarea {
            height: 120px;
            resize: vertical;
        }

        /* Submit button */
        button {
            width: 100%;
            padding: 12px 15px;
            background:#3498db;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            animation: slide-in 1.5s ease forwards;
            animation-delay: 1s;
            font-family: "Josefin Sans", sans-serif;
        }

        button:hover {
            background:linear-gradient(135deg, #6767b3, #4a4a8a);
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        @keyframes slide-in {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Floating effect for the form */
        form:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }
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
    </style>
</head>
<body>
    <a class="back-home-btn" href="index.php">Back to home</a>
    <form action="feedback.php" method="post">
        <label for="name">Enter your Name:</label>
        <input type="text" name="name" id="name" placeholder="John Doe" />
        
        <label for="email">Enter your Email ID:</label>
        <input type="email" name="email" id="email" placeholder="example@mail.com" />
        
        <label for="Feedback">Feedback:</label>
        <textarea name="Feedback" id="Feedback" placeholder="Write your feedback here..."></textarea>
        
        <button type="submit">Submit</button>
    </form>
    
</body>
</html>
