<?php
$conn = new mysqli('localhost', 'root', '', 'flexiride');
if ($conn->connect_error) { die("DB Connect Error"); }

function forceAddCol($conn, $table, $col, $def) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        echo "ADDED_$col\n";
    } else {
        echo "EXISTS_$col\n";
    }
}

forceAddCol($conn, 'rides', 'via_route_name', "VARCHAR(255) NULL AFTER destination");
forceAddCol($conn, 'rides', 'route_distance', "DECIMAL(8,2) NOT NULL DEFAULT 25.00");
forceAddCol($conn, 'rides', 'trip_status', "VARCHAR(50) NOT NULL DEFAULT 'active'");
forceAddCol($conn, 'users', 'profile_photo', "VARCHAR(255) NULL");
forceAddCol($conn, 'vehicles', 'total_seats', "INT NOT NULL DEFAULT 5");
?>