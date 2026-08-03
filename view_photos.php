<?php
include 'db.php';
session_start();

// If user_id from session (or set manually for demo)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Demo user
}
$user_id = $_SESSION['user_id'];

// Fetch photos
$stmt = $conn->prepare("SELECT photo_path FROM user_photos WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$photos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Uploaded Photos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            padding: 40px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .gallery img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 2px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .gallery img:hover {
            transform: scale(1.05);
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <h2>Your Uploaded Photos</h2>
    <div class="gallery">
        <?php 
        if (count($photos) > 0) {
            foreach ($photos as $photo) {
                echo '<img src="'.$photo['photo_path'].'" alt="User Photo">';
            }
        } else {
            echo "<p style='text-align:center;'>No photos uploaded yet.</p>";
        }
        ?>
    </div>
</body>
</html>
