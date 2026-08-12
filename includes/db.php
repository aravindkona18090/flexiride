<?php
/**
 * FlexiRide — Database Connection
 * Single responsibility: connect to MySQL and set charset.
 * Business logic helpers are in includes/db_helpers.php (auto-included below).
 */
date_default_timezone_set('Asia/Kolkata');

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

// Include helpers (backward-compatible — all existing includes continue to work)
require_once __DIR__ . '/db_helpers.php';
?>