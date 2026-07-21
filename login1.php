<?php
include 'db.php';
session_start();

// Hardcoded admin credentials
$admin_email = "admin@flexiride.com";
$admin_password = "Admin@123"; // You can choose a secure password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);

    // Check if the credentials are for the admin
    if ($email === $admin_email && $password === $admin_password) {
        // Set admin session
        $_SESSION['is_admin'] = true;
        $_SESSION['user_id'] = "admin";
        $_SESSION['name'] = "Admin"; // Optional: Admin name
        header("Location: admin_dashboard.php"); // Redirect to Admin Dashboard
        exit();
    }

    // For normal users, check the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = false; // Regular user

            header("Location: index.php"); // Redirect to User Homepage
            exit();
        } else {
            $error_message = "Invalid password.";
            header("Location: login.php?error=$error_message");
            exit();
        }
    } else {
        $error_message = "No user found.";
        header("Location: login.php?error=$error_message");
        exit();
    }
}
?>
