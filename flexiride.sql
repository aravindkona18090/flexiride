-- ============================================================
-- FlexiRide Master 3NF Unified Database Schema & Initial Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS `flexiride` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `flexiride`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `ratings`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `rides`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `user_emergency_contacts`;
DROP TABLE IF EXISTS `user_verifications`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `queries`;
DROP TABLE IF EXISTS `user_photos`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. `users` Table (Core Account Credentials & Ratings)
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  `dob` DATE NULL,
  `city` VARCHAR(100) NULL,
  `phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_phone_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `aadhaar_number` VARCHAR(12) NULL,
  `is_aadhaar_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `dl_number` VARCHAR(20) NULL,
  `is_dl_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `college_email` VARCHAR(150) NULL,
  `is_college_email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `campus_name` VARCHAR(150) NULL,
  `upi_id` VARCHAR(100) NULL,
  `is_upi_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `emergency_email1` VARCHAR(150) NULL,
  `emergency_email2` VARCHAR(150) NULL,
  `emergency_phone` VARCHAR(20) NULL,
  `avg_rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `total_co2_saved` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `total_money_saved` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `profile_photo` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. `user_verifications` Table
-- ------------------------------------------------------------
CREATE TABLE `user_verifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `aadhaar_number` VARCHAR(12) NULL,
  `is_aadhaar_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `dl_number` VARCHAR(20) NULL,
  `is_dl_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `college_email` VARCHAR(150) NULL,
  `is_college_email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `campus_name` VARCHAR(150) NULL,
  `upi_id` VARCHAR(100) NULL,
  `is_upi_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. `user_emergency_contacts` Table
-- ------------------------------------------------------------
CREATE TABLE `user_emergency_contacts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `emergency_email1` VARCHAR(150) NULL,
  `emergency_email2` VARCHAR(150) NULL,
  `emergency_phone` VARCHAR(20) NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. `vehicles` Table
-- ------------------------------------------------------------
CREATE TABLE `vehicles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `vehicle_category` ENUM('bike', 'car') NOT NULL DEFAULT 'bike',
  `vehicle_model` VARCHAR(100) NOT NULL,
  `license_plate` VARCHAR(20) NULL,
  `is_ev` TINYINT(1) NOT NULL DEFAULT 0,
  `helmet_provided` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. `rides` Table
-- ------------------------------------------------------------
CREATE TABLE `rides` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `vehicle_id` INT NULL,
  `vehicle_category` ENUM('bike', 'car') NOT NULL DEFAULT 'bike',
  `vehicle_type` VARCHAR(50) DEFAULT 'bike',
  `vehicle_model` VARCHAR(100) NULL,
  `helmet_provided` TINYINT(1) NOT NULL DEFAULT 1,
  `origin` VARCHAR(255) NOT NULL,
  `destination` VARCHAR(255) NOT NULL,
  `origin_lat` DECIMAL(10,8) NULL,
  `origin_lng` DECIMAL(11,8) NULL,
  `dest_lat` DECIMAL(10,8) NULL,
  `dest_lng` DECIMAL(11,8) NULL,
  `route_distance` DECIMAL(8,2) NOT NULL DEFAULT 25.00,
  `via_route_name` VARCHAR(255) NULL,
  `ride_date` DATE NOT NULL,
  `ride_time` TIME NOT NULL,
  `seats_available` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `gender_preference` ENUM('any', 'female_only') NOT NULL DEFAULT 'any',
  `luggage_limit` VARCHAR(50) DEFAULT 'Backpack only',
  `posted_email` VARCHAR(255) NULL,
  `trip_status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `idx_route` (`origin`, `destination`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. `bookings` Table
-- ------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `seats_booked` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `posted_email` VARCHAR(150) NULL,
  `booked_email` VARCHAR(150) NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Escrow Held',
  `txn_ref` VARCHAR(100) NULL,
  `trip_otp` VARCHAR(10) NULL,
  `trip_status` ENUM('Confirmed', 'OnTheWay', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ride_id` (`ride_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. `payments` Table
-- ------------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL,
  `payer_id` INT NOT NULL,
  `payee_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('UPI', 'Cash', 'Wallet') NOT NULL DEFAULT 'UPI',
  `upi_vpa` VARCHAR(100) NULL,
  `payment_status` ENUM('Pending', 'Completed', 'Failed') NOT NULL DEFAULT 'Completed',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. `notifications` Table
-- ------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. `ratings` Table
-- ------------------------------------------------------------
CREATE TABLE `ratings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `reviewed_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ride_id` (`ride_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `reviewed_id` (`reviewed_id`),
  FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. `messages` Table
-- ------------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ride_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ride_id` (`ride_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 11. `feedback` Table
-- ------------------------------------------------------------
CREATE TABLE `feedback` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `feedback` TEXT NOT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 12. `queries` Table
-- ------------------------------------------------------------
CREATE TABLE `queries` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `query` TEXT NOT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 13. `user_photos` Table
-- ------------------------------------------------------------
CREATE TABLE `user_photos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `photo` LONGBLOB NOT NULL,
  `photo_name` VARCHAR(255) DEFAULT NULL,
  `photo_type` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Admin Account (admin@flexiride.com / Admin@123)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `is_admin`, `is_verified`) 
VALUES (1, 'FlexiRide Admin', 'admin@flexiride.com', '9999999999', '$2y$10$fV3mPz/U.zJ8yR2u/2g2h.93Q6uXN1L7l6iY5aZ1k.fJ8mK1L2m3O', 1, 1)
ON DUPLICATE KEY UPDATE `is_admin` = 1;