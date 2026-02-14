<?php
/**
 * Admin Authentication Configuration - Example
 * 
 * IMPORTANT: Copy this file to auth_config.php and update with your actual credentials
 * The auth_config.php file is gitignored to protect sensitive information
 */

// Admin Credentials
define('ADMIN_USERNAME', 'your_admin_username');
define('ADMIN_PASSWORD', 'your_secure_password');

/**
 * SETUP INSTRUCTIONS:
 * 
 * 1. Copy this file:
 *    cp admin/includes/auth_config.example.php admin/includes/auth_config.php
 * 
 * 2. Edit auth_config.php with your desired admin credentials
 * 
 * 3. Use a strong password for production environments
 * 
 * SECURITY NOTE:
 * - Never commit auth_config.php to version control
 * - Use strong passwords in production
 * - Consider implementing password hashing for better security
 */
?>
