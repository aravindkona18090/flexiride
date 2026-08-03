<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);

    if (empty($username) || empty($email) || empty($password) || empty($phone)) {
        $error_message2 = "All fields are required.";
        header("Location: login.php?error2=" . urlencode($error_message2));
        exit();
    }

    // Prepared statement to check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error_message2 = "User with this email already exists.";
        header("Location: login.php?error2=" . urlencode($error_message2));
        exit();
    } else {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Store pending user registration data securely in SESSION (never expose in GET URL)
        $_SESSION['pending_registration'] = [
            'name'     => $username,
            'email'    => $email,
            'password' => $hashed_password,
            'phone'    => $phone
        ];

        header("Location: otp_verify.php");
        exit();
    }
}
?>
