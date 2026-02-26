<?php
/**
 * Database Connection File
 * Includes config and establishes connection
 * Schema migrations run once and are cached via a version file.
 */

// Database credentials
require_once __DIR__ . '/db_config.php';

// Create connection with custom port
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);

// Check connection
if ($conn->connect_error) {
    error_log('MySQL connection failed: ' . $conn->connect_error);
    if (php_sapi_name() === 'cli') {
        die("MySQL connection failed: " . $conn->connect_error . "\n");
    }
    http_response_code(503);
    die("
    <div style='padding:20px;background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;border-radius:5px;margin:20px'>
        <h3>Service Unavailable</h3>
        <p>We are experiencing a temporary issue. Please try again later.</p>
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

// ── Schema Migrations (run once, cached by version file) ─────────
$_db_schema_version = 2; // Bump this number whenever you add new migrations
$_db_schema_file = __DIR__ . '/.schema_version';
$_db_current_version = file_exists($_db_schema_file) ? (int) file_get_contents($_db_schema_file) : 0;

if ($_db_current_version < $_db_schema_version) {

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

        // Customers table for site users
        "CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `phone` VARCHAR(20),
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Orders table
        "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT NULL,
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
            `persons` VARCHAR(50),
            `location` VARCHAR(100),
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

    // ── Column migrations ────────────────────────────────────────
    $contact_columns = [
        'persons' => "ALTER TABLE `contact_submissions` ADD COLUMN `persons` VARCHAR(50) NULL",
        'location' => "ALTER TABLE `contact_submissions` ADD COLUMN `location` VARCHAR(100) NULL"
    ];

    foreach ($contact_columns as $column => $alter_sql) {
        $column_check = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'contact_submissions' AND COLUMN_NAME = ?");
        if ($column_check) {
            $db_name = DB_NAME;
            $column_check->bind_param('ss', $db_name, $column);
            $column_exists = 0;
            if ($column_check->execute()) {
                $column_check->bind_result($column_exists);
                $column_check->fetch();
            }
            $column_check->close();
            if ((int) $column_exists === 0) {
                $conn->query($alter_sql);
            }
        }
    }

    $order_columns = [
        'customer_id' => "ALTER TABLE `orders` ADD COLUMN `customer_id` INT NULL"
    ];

    foreach ($order_columns as $column => $alter_sql) {
        $column_check = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND COLUMN_NAME = ?");
        if ($column_check) {
            $db_name = DB_NAME;
            $column_check->bind_param('ss', $db_name, $column);
            $column_exists = 0;
            if ($column_check->execute()) {
                $column_check->bind_result($column_exists);
                $column_check->fetch();
            }
            $column_check->close();
            if ((int) $column_exists === 0) {
                $conn->query($alter_sql);
            }
        }
    }

    // ── Performance indexes (v2) ─────────────────────────────────
    $indexes = [
        "CREATE INDEX idx_orders_status ON orders(status)",
        "CREATE INDEX idx_orders_customer_id ON orders(customer_id)",
        "CREATE INDEX idx_orders_created_at ON orders(created_at)",
        "CREATE INDEX idx_orders_status_created ON orders(status, created_at)",
        "CREATE INDEX idx_menu_items_status ON menu_items(status)",
        "CREATE INDEX idx_contact_submissions_status ON contact_submissions(status)",
    ];
    foreach ($indexes as $idx_sql) {
        // Silently skip if index already exists
        @$conn->query($idx_sql);
    }

    // ── RBAC Tables Migration ────────────────────────────────────
    require_once __DIR__ . '/rbac/rbac_tables.php';
    rbac_run_migrations($conn);

    // Write schema version so migrations are skipped on next request
    @file_put_contents($_db_schema_file, (string) $_db_schema_version);

} else {
    // Schema is up-to-date; still need RBAC helper available
    require_once __DIR__ . '/rbac/rbac_tables.php';
}

// Clean up migration variables
unset($_db_schema_version, $_db_schema_file, $_db_current_version);

?>
