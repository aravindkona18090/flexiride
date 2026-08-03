<?php
include 'db.php';  // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if the user already exists
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
       
        $error_message2 = "User with this email already exists.";
        header("Location: login.php?error2=$error_message2");
    } else {
        header("Location: otp_verify.php?email=$email&name=$username&password=$hashed_password&phone=$phone");
        // Insert the new user into the database
        
    }
}
?>
