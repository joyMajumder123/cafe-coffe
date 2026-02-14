# Cafe Coffee ☕

A web-based cafe management system for ordering coffee and food items online.

## Features

- 🛒 Online menu and ordering system
- 👤 Customer registration and authentication
- 📦 Order tracking and history
- 👨‍🍳 Chef profiles
- 📊 Admin dashboard for managing orders, menu items, and reservations
- 💳 Multiple payment options
- 🎨 Responsive design with Bootstrap

## Quick Start

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/joyMajumder123/cafe-coffe.git
   cd cafe-coffe
   ```

2. **Configure the application**
   
   See [SETUP.md](SETUP.md) for detailed configuration instructions.
   
   Quick setup:
   ```bash
   # Copy example configuration files
   cp admin/includes/db_config.example.php admin/includes/db_config.php
   cp admin/includes/auth_config.example.php admin/includes/auth_config.php
   
   # Edit the configuration files with your credentials
   ```

3. **Start your web server**
   
   Point your web server document root to the project directory, or if using XAMPP:
   - Copy project to `htdocs/cafe-coffe`
   - Start Apache and MySQL services

4. **Access the application**
   
   - Main site: `http://localhost/cafe-coffe/`
   - Admin panel: `http://localhost/cafe-coffe/admin/login.php`

## Project Structure

```
cafe-coffe/
├── admin/              # Admin panel
│   ├── includes/       # Admin configuration and includes
│   ├── dashboard.php   # Admin dashboard
│   ├── menu.php        # Menu management
│   ├── orders.php      # Order management
│   └── ...
├── includes/           # Shared includes
├── assets/             # CSS, JS, images
├── index.php           # Homepage
├── menulist.php        # Menu listing
├── checkout.php        # Checkout page
└── ...
```

## Configuration Files

⚠️ **Important:** Never commit sensitive configuration files to version control!

The following files contain sensitive information and must be created locally:
- `admin/includes/db_config.php` - Database credentials
- `admin/includes/auth_config.php` - Admin authentication

Template files are provided:
- `admin/includes/db_config.example.php`
- `admin/includes/auth_config.example.php`

## Security

- All sensitive configuration files are excluded via `.gitignore`
- Database credentials are stored separately from code
- Admin credentials use configuration-based authentication
- Use strong passwords in production environments

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For setup instructions, see [SETUP.md](SETUP.md)

For issues or questions, please create an issue in the GitHub repository.
