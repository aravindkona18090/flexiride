<?php
include_once __DIR__ . '/../includes/db.php';  // Include database connection
session_start();

// Ensure the user is an admin
if (!isset($_GET['id'])) {
    header('Location: ../login.php');
    exit();
}


// Check if a user ID is provided
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Prepare the SQL query to delete the user
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);

    // Execute the query and check if the deletion is successful
    if ($stmt->execute()) {
        // Redirect to the users list page with a success message
        header("Location: admin_manage_users.php?message=User deleted successfully!");
        exit();
    } else {
        // Redirect with an error message if the deletion failed
        header("Location: admin_manage_users.php?message=Error deleting user.");
        exit();
    }
} else {
    // Redirect back to the user list if no user ID is provided
    header("Location: admin_manage_users.php");
    exit();
}
?>
