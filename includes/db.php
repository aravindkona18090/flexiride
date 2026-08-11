<?php
// Master Database Configuration & Helper Utilities
$host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
$user = getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: 'root');
$pass = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
$db   = getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'flexiride');
$port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Helper to safely add column if missing
function safeAddColumn($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

// Ensure 3NF relational tables and missing columns exist
safeAddColumn($conn, 'users', 'profile_photo', "VARCHAR(255) NULL");
safeAddColumn($conn, 'vehicles', 'total_seats', "INT NOT NULL DEFAULT 5");
safeAddColumn($conn, 'rides', 'route_distance', "DECIMAL(8,2) NOT NULL DEFAULT 25.00");
safeAddColumn($conn, 'rides', 'trip_status', "VARCHAR(50) NOT NULL DEFAULT 'active'");
// Dynamic Real-Time Status Engine: Auto-mark passed rides & bookings as 'Completed'
$conn->query("UPDATE bookings b JOIN rides r ON b.ride_id = r.id SET b.trip_status = 'Completed' WHERE b.trip_status IN ('Confirmed', 'OnTheWay') AND CONCAT(r.ride_date, ' ', r.ride_time) < NOW()");
$conn->query("UPDATE rides SET trip_status = 'Completed' WHERE (trip_status IS NULL OR trip_status = '' OR trip_status = 'active') AND CONCAT(ride_date, ' ', ride_time) < NOW()");

// 3NF Relation Sync Helper
function syncUser3NF($conn, $user_id, $aadhaar, $is_aadhaar, $dl, $is_dl, $college, $is_college, $campus, $upi, $is_upi, $is_verified, $em1, $em2, $em_phone) {
    // 1. Sync user_verifications
    $stmt1 = $conn->prepare("INSERT INTO user_verifications (user_id, aadhaar_number, is_aadhaar_verified, dl_number, is_dl_verified, college_email, is_college_email_verified, campus_name, upi_id, is_upi_verified, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        aadhaar_number=VALUES(aadhaar_number), is_aadhaar_verified=VALUES(is_aadhaar_verified),
        dl_number=VALUES(dl_number), is_dl_verified=VALUES(is_dl_verified),
        college_email=VALUES(college_email), is_college_email_verified=VALUES(is_college_email_verified),
        campus_name=VALUES(campus_name), upi_id=VALUES(upi_id), is_upi_verified=VALUES(is_upi_verified), is_verified=VALUES(is_verified)");
    
    if ($stmt1) {
        $stmt1->bind_param("isisisissii", $user_id, $aadhaar, $is_aadhaar, $dl, $is_dl, $college, $is_college, $campus, $upi, $is_upi, $is_verified);
        $stmt1->execute();
    }

    // 2. Sync user_emergency_contacts
    $stmt2 = $conn->prepare("INSERT INTO user_emergency_contacts (user_id, emergency_email1, emergency_email2, emergency_phone) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        emergency_email1=VALUES(emergency_email1), emergency_email2=VALUES(emergency_email2), emergency_phone=VALUES(emergency_phone)");
    
    if ($stmt2) {
        $stmt2->bind_param("isss", $user_id, $em1, $em2, $em_phone);
        $stmt2->execute();
    }
}
?>