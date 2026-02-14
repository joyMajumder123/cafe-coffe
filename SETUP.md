# Cafe Coffee - Setup Guide

This guide will help you set up the Cafe Coffee application with the required configuration files.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB)
- XAMPP, WAMP, or similar local development environment

## Configuration Setup

The application requires two configuration files that contain sensitive information. These files are not included in the repository for security reasons.

### 1. Database Configuration

Create the database configuration file:

```bash
cp admin/includes/db_config.example.php admin/includes/db_config.php
```

Then edit `admin/includes/db_config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');        // Your database host
define('DB_USER', 'your_db_username'); // Your database username
define('DB_PASS', 'your_db_password'); // Your database password
define('DB_NAME', 'cafe_db');          // Database name
define('DB_PORT', 3306);               // MySQL port
```

### 2. Admin Authentication Configuration

Create the admin authentication configuration file:

```bash
cp admin/includes/auth_config.example.php admin/includes/auth_config.php
```

**Generate a password hash** for your admin password:

```bash
php -r "echo password_hash('YourSecurePassword', PASSWORD_DEFAULT);"
```

Then edit `admin/includes/auth_config.php` with your admin credentials:

```php
define('ADMIN_USERNAME', 'your_admin_username');
define('ADMIN_PASSWORD_HASH', 'generated_hash_from_above');
```

**IMPORTANT:** 
- Use a strong password, especially in production environments!
- The password hash should look like: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
- Never store plain text passwords

## First Run

1. Ensure your MySQL server is running
2. Access the application through your web server (e.g., `http://localhost/cafe-coffe/`)
3. The application will automatically create the database and all required tables on first run

## Admin Panel Access

- URL: `http://localhost/cafe-coffe/admin/login.php`
- Use the credentials you set in `auth_config.php`

## Security Notes

⚠️ **NEVER commit the following files to version control:**
- `admin/includes/db_config.php`
- `admin/includes/auth_config.php`
- Any other files containing credentials or sensitive information

These files are already listed in `.gitignore` to prevent accidental commits.

### Git History Warning

If you're working with a fork or clone of this repository, be aware that older commits may contain example or test credentials in the git history. For production deployments:

1. Generate new, strong credentials that have never been committed
2. Consider creating a fresh repository without history for production
3. Never reuse any credentials that may have been in the repository history

## Troubleshooting

### Database Connection Issues

If you see a MySQL connection error:
1. Verify your database credentials in `db_config.php`
2. Ensure MySQL is running
3. Check that the port number is correct (default: 3306)
4. Verify no firewall is blocking the connection

### Admin Login Issues

If you cannot login to the admin panel:
1. Verify your credentials in `auth_config.php`
2. Check that the file exists and has the correct PHP syntax
3. Clear your browser cache and cookies

## Support

For issues or questions, please create an issue in the GitHub repository.
