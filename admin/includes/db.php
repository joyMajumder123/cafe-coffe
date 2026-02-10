<?php
/**
 * Database Connection File
 * Includes config and establishes connection
 * Auto-creates database and tables
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cafeapp');
define('DB_PORT', 3308);

// Create connection with custom port
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

// Check connection
if ($conn->connect_error) {
    die("
    <div style='padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 5px; margin: 20px;'>
        <h3>❌ MySQL Connection Failed</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
        <p><strong>Please ensure:</strong></p>
        <ul>
            <li>XAMPP MySQL service is running on port " . DB_PORT . "</li>
            <li>No firewall is blocking the connection</li>
        </ul>
    </div>
    ");
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db(DB_NAME);

// Set charset
$conn->set_charset("utf8mb4");

// Create tables if they don't exist
$tables = array(
    // Users table for contact form submissions
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20),
        `persons` VARCHAR(50),
        `location` VARCHAR(100),
        `message` TEXT,
        `status` ENUM('new', 'processing', 'resolved') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Orders table
    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `phone` VARCHAR(20),
        `address` VARCHAR(255),
        `city` VARCHAR(100),
        `items` JSON,
        `subtotal` DECIMAL(10,2) DEFAULT 0,
        `delivery_charge` DECIMAL(10,2) DEFAULT 0,
        `tax` DECIMAL(10,2) DEFAULT 0,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `payment_method` VARCHAR(50) DEFAULT 'cash_on_delivery',
        `status` VARCHAR(50) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Menu items table
    "CREATE TABLE IF NOT EXISTS `menu_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `category` VARCHAR(50),
        `price` DECIMAL(10,2) NOT NULL,
        `image` VARCHAR(255),
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Reservations table
    "CREATE TABLE IF NOT EXISTS `reservations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `phone` VARCHAR(20),
        `reservation_date` DATE NOT NULL,
        `reservation_time` TIME NOT NULL,
        `guests` INT DEFAULT 1,
        `special_requests` TEXT,
        `status` ENUM('pending', 'confirmed', 'rejected', 'completed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Chefs table
    "CREATE TABLE IF NOT EXISTS `chefs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `position` VARCHAR(100),
        `speciality` VARCHAR(150),
        `image` VARCHAR(255),
        `bio` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Staff table
    "CREATE TABLE IF NOT EXISTS `staff` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `position` VARCHAR(100),
        `email` VARCHAR(100),
        `phone` VARCHAR(20),
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `hire_date` DATE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Categories table
    "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `description` TEXT,
        `image` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Payments table
    "CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `amount` DECIMAL(10,2) DEFAULT 0,
        `payment_method` VARCHAR(50) DEFAULT 'cash_on_delivery',
        `status` VARCHAR(50) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Contact form submissions
    "CREATE TABLE IF NOT EXISTS `contact_submissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `phone` VARCHAR(20),
        `message` TEXT,
        `status` ENUM('new', 'processing', 'resolved') DEFAULT 'new',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

foreach ($tables as $table) {
    if (!$conn->query($table)) {
        die("Error creating table: " . $conn->error);
    }
}

echo "Database and tables created successfully!";
?>
