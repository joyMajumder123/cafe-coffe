<?php
/**
 * Admin Authentication Configuration - Example
 * 
 * IMPORTANT: Copy this file to auth_config.php and update with your actual credentials
 * The auth_config.php file is gitignored to protect sensitive information
 */

// Admin Credentials
define('ADMIN_USERNAME', 'your_admin_username');
// Store the hashed password here. Generate using: php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', 'your_password_hash_here');

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Copy this file:
 *    cp admin/includes/auth_config.example.php admin/includes/auth_config.php
 * 
 * 2. Generate a password hash for your desired password:
 *    php -r "echo password_hash('YourSecurePassword', PASSWORD_DEFAULT);"
 * 
 * 3. Edit auth_config.php:
 *    - Set ADMIN_USERNAME to your desired username
 *    - Set ADMIN_PASSWORD_HASH to the generated hash from step 2
 * 
 * SECURITY NOTES:
 * - Never commit auth_config.php to version control
 * - Use strong passwords in production
 * - Passwords are hashed for security - plain text passwords are never stored
 * - The hash should look like: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
 */
?>
