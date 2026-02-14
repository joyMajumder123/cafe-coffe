<?php
/**
 * Database Configuration File - Example
 * 
 * IMPORTANT: Copy this file to db_config.php and update with your actual credentials
 * The db_config.php file is gitignored to protect sensitive information
 */

// Database Configuration
define('DB_HOST', 'localhost');        // Database host (usually localhost)
define('DB_USER', 'your_db_username'); // Database username
define('DB_PASS', 'your_db_password'); // Database password
define('DB_NAME', 'cafe_db');          // Database name
define('DB_PORT', 3306);               // MySQL port (default is 3306)

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Copy this file:
 *    cp admin/includes/db_config.example.php admin/includes/db_config.php
 * 
 * 2. Edit db_config.php with your actual database credentials
 * 
 * 3. Make sure your MySQL server is running
 * 
 * 4. The application will automatically create the database and tables on first run
 */
?>
