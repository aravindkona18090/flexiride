<?php
include_once __DIR__ . '/includes/db.php'; // Ensure this file has $conn connection

// Handle upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['photo'])) {
    $fileData = file_get_contents($_FILES['photo']['tmp_name']);
    $fileName = $_FILES['photo']['name'];
    $fileType = $_FILES['photo']['type'];

    $stmt = $conn->prepare("INSERT INTO user_photos (photo, photo_name, photo_type) VALUES (?, ?, ?)");
    $stmt->bind_param("bss", $null, $fileName, $fileType);
    $stmt->send_long_data(0, $fileData);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Photo uploaded successfully!</p>";
    } else {
        echo "<p style='color: red;'>Upload failed: " . $conn->error . "</p>";
    }
}

// Handle download request
if (isset($_GET['download_id'])) {
    $id = $_GET['download_id'];
    $stmt = $conn->prepare("SELECT photo, photo_name, photo_type FROM user_photos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    header("Content-Type: " . $result['photo_type']);
    header("Content-Disposition: attachment; filename=\"" . $result['photo_name'] . "\"");
    echo $result['photo'];
    exit();
}

// Handle view large request
if (isset($_GET['view_id'])) {
    $id = $_GET['view_id'];
    $stmt = $conn->prepare("SELECT photo, photo_type FROM user_photos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    header("Content-Type: " . $result['photo_type']);
    echo $result['photo'];
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload, View Large & Download Photos</title>
    <style>
        img { border: 1px solid #333; margin-bottom: 5px; }
        .photo-card { display: inline-block; text-align: center; margin: 10px; border: 1px solid #aaa; padding: 10px; border-radius: 8px; }
        button { margin-top: 5px; display: block; width: 100%; }
    </style>
</head>
<body>
    <h2>Upload Photo</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="photo" required>
        <button type="submit">Upload</button>
    </form>

    <h2>All Photos</h2>
    <?php
    $result = $conn->query("SELECT id, photo_name, photo_type FROM user_photos ORDER BY id DESC");
    while ($row = $result->fetch_assoc()) {
        $thumb = "view_id=" . $row['id'];
        echo "<div class='photo-card'>";
        echo '<img src="?view_id=' . $row['id'] . '" width="200" height="200" /><br>';
        echo "<small>" . htmlspecialchars($row['photo_name']) . "</small><br>";
        echo '<a href="?view_id=' . $row['id'] . '" target="_blank"><button>View Large</button></a>';
        echo '<a href="?download_id=' . $row['id'] . '"><button>Download</button></a>';
        echo "</div>";
    }
    ?>
</body>
</html>
