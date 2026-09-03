<?php
/**
 * FlexiRide — Database Connection (TiDB Cloud & MySQL Compatible)
 * Starts the session (if not already started) and connects securely to TiDB/MySQL.
 * Business logic helpers are in includes/db_helpers.php (auto-included below).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

// Database Connection Parameters (Configurable via ENV or Default to TiDB Cloud)
$host = getenv('TIDB_HOST') ?: (getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com'));
$user = getenv('TIDB_USER') ?: (getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: '3nzKYeVDwWNWfYG.root'));
$pass = getenv('TIDB_PASS') ?: (getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '4xVy4hOFpTsDz2np'));
$db   = getenv('TIDB_NAME') ?: (getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: 'flexiride'));
$port = (int)(getenv('TIDB_PORT') ?: (getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 4000)));

// Detect if SSL/TLS is needed (TiDB Cloud, port 4000, or remote host)
$isRemoteOrTiDB = ($port === 4000 || strpos($host, 'tidbcloud.com') !== false || getenv('DB_SSL') === 'true');

if ($isRemoteOrTiDB) {
    $conn = mysqli_init();
    if (!$conn) {
        die("Database Initialization Failed (mysqli_init).");
    }

    // Locate system/XAMPP SSL CA certificate bundle
    $caBundlePaths = [
        'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
        'C:\\xampp\\perl\\vendor\\lib\\Mozilla\\CA\\cacert.pem',
        '/etc/ssl/certs/ca-certificates.crt',
        '/etc/pki/tls/certs/ca-bundle.crt'
    ];

    $caFile = null;
    foreach ($caBundlePaths as $path) {
        if (file_exists($path)) {
            $caFile = $path;
            break;
        }
    }

    if ($caFile) {
        $conn->ssl_set(NULL, NULL, $caFile, NULL, NULL);
    }

    // Set connection timeout and SSL verification options
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);

    if (!@$conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
        die("TiDB Cloud Connection Failed: " . mysqli_connect_error());
    }
} else {
    // Local / Standard MySQL connection
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        die("Database Connection Failed: " . $conn->connect_error);
    }
}

$conn->set_charset("utf8mb4");

// Include helpers (backward-compatible — all existing includes continue to work)
require_once __DIR__ . '/db_helpers.php';
?>