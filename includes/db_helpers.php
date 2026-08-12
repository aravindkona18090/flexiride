<?php
/**
 * FlexiRide — Shared Database Helpers & Business Logic Utilities
 *
 * Separated from db.php to keep the connection file single-responsibility.
 * Include this file in pages that need these helpers.
 */

/**
 * Cleans long Nominatim/OpenStreetMap address strings into concise city names.
 */
if (!function_exists('cleanShortAddress')) {
    function cleanShortAddress($str) {
        if (empty($str)) return '';
        $viaStr = '';
        if (preg_match('/\((Via [^\)]+)\)/i', $str, $m)) {
            $viaStr = ' (' . $m[1] . ')';
        }
        $clean = preg_replace('/\([^\)]+\)/', '', $str);
        $parts = array_map('trim', explode(',', $clean));
        return ($parts[0] ?? $str) . $viaStr;
    }
}

/**
 * Safely adds a column to a table if it does not already exist.
 * Use this only for legacy compatibility — prefer schema migrations for new columns.
 */
if (!function_exists('safeAddColumn')) {
    function safeAddColumn($conn, $table, $column, $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check && $check->num_rows == 0) {
            @$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}

/**
 * Syncs user verification data across 3NF-normalised tables
 * (user_verifications and user_emergency_contacts).
 */
if (!function_exists('syncUser3NF')) {
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
}
?>
