<?php
include 'db.php';
session_start();

$admin_email = getenv('ADMIN_EMAIL') ?: "admin@flexiride.com";
$admin_password = getenv('ADMIN_PASS') ?: "Admin@123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Email and Password are required.";
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }

    // Check if the credentials match Admin
    if ($email === $admin_email && ($password === $admin_password || password_verify($password, $admin_password))) {
        $_SESSION['is_admin'] = true;
        $_SESSION['user_id'] = "admin";
        $_SESSION['name'] = "Admin";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Check database for regular user using prepared statements
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = false;

            header("Location: index.php");
            exit();
        } else {
            $error_message = "Invalid password.";
            header("Location: login.php?error=" . urlencode($error_message));
            exit();
        }
    } else {
        $error_message = "No account found with this email.";
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }
}
?>
