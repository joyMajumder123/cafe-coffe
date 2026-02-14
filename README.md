

# ☕ Brew & Bite Cafe Management System

A lightweight, responsive web application designed to streamline cafe operations. From browsing the menu to managing orders, this app provides a seamless experience for both customers and staff.

##  Features

* **🛒 Digital Menu:** Categorized food and beverage listings with real-time pricing.
* **📱 Responsive UI:** Built with **Bootstrap**, ensuring the app looks great on mobile, tablet, and desktop.
* **🔐 User Authentication:** Secure login and registration for customers and administrators.
* **📋 Order Management:** (Admin) Track, update, and manage incoming customer orders.
* **📂 Inventory Dashboard:** (Admin) Add, edit, or remove menu items and update it.
* **💌 Contact & Feedback:** Integrated form for customer inquiries and reviews.

---

## 🛠️ Tech Stack

| Layer | Technology |
| --- | --- |
| **Frontend** | HTML5, CSS3, **Bootstrap 5**, JavaScript |
| **Backend** | **PHP** (Procedural or OOP) |
| **Database** | **MySQL** / MariaDB |
| **Server** | Apache (XAMPP / WAMP / MAMP) |

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

* **PHP** (v7.4 or higher recommended)
* **MySQL**
* A local server environment like **XAMPP** or **Laragon**.

---

## ⚙️ Installation & Setup

1. **Clone the Repository**
```bash
git clone https://github.com/joyMajumder123/cafe-coffe.git

```


2. **Database Configuration**
* Create a configuration file at `admin/includes/db_config.php` with your database credentials:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cafe_db');
define('DB_PORT', 3306);
?>
```

* **Note:** The database `cafe_db` will be created automatically when you first run the application.


3. **Run the Application**
* Move the project folder to your server's root directory (e.g., `htdocs`).
* Open your browser and navigate to `http://localhost/cafe-coffe`.



---

## 📸 Screenshots
> * *Home Page* <img width="1850" height="889" alt="image" src="https://github.com/user-attachments/assets/5661c7d1-cbde-481d-a10c-011a2dbbb46c" />
>

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. 

1. Fork the Project.
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`).
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the Branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

