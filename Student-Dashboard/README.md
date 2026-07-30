# 🚌 Bus Pass Management System

A production-ready, full-featured Bus Pass Management System designed for educational institutions. Students can apply for bus passes online, track application status, download digital passes, and manage their profiles — all from a modern, responsive web interface.

## ✨ Features

### Student Features
- **📝 Multi-Step Application** — Intuitive 4-step wizard (Select Route → Upload Documents → Review → Payment) for applying bus passes
- **🗺️ Smart Route Selection** — Search stops with autocomplete, auto-matching routes from PMPML CSV, and live PMPML GTFS bus schedules
- **💰 Dynamic Fare Calculation** — Real-time fare calculation based on distance via PMPML Fare Chart CSV, with discounts for longer durations (5%–20% off)
- **💳 UPI QR Code Payment** — Integrated UPI payment with auto-generated QR codes for Google Pay, PhonePe, Paytm, BHIM, and WhatsApp Pay
- **🧾 Digital Receipt Generation** — Download printable PDF receipts via jsPDF with auto-table formatting
- **🎉 Payment Success Page** — Animated confetti celebration, loading spinner, and auto-redirect to dashboard
- **📄 Document Upload** — Upload College ID, Photo, and Address Proof during application
- **📊 Application Tracking** — View all applications with detailed status (Pending/Approved/Rejected/Cancelled/Expired)
- **🔄 Pass Renewal** — Easily renew expired or expiring bus passes (Monthly fixed)
- **📥 Digital Pass Download** — Download and print your approved bus pass with QR code
- **💳 Payment Status** — Track payment history and status with DataTables
- **🔔 Notifications** — Real-time notifications for application updates (info, success, warning, danger types)
- **👤 Profile Management** — View and edit personal information, upload profile picture
- **🔐 Password Recovery** — Forgot password with secure token-based reset

### System Features
- **🔐 Secure Authentication** — Password hashing with bcrypt, session management
- **📱 Responsive Design** — Fully responsive sidebar navigation, works on mobile, tablet, and desktop
- **⚡ Modern UI** — Bootstrap 5 with custom CSS, DataTables, SweetAlert2, Select2
- **🗄️ MySQL Database** — Properly normalized schema with foreign keys and indexes
- **📝 Input Sanitization** — Protection against SQL injection and XSS attacks

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.x |
| **Database** | MySQL 8.x |
| **Frontend** | HTML5, CSS3, Bootstrap 5 |
| **JavaScript** | jQuery, DataTables, SweetAlert2, Select2 |
| **PDF Generation** | jsPDF with autoTable plugin |
| **Icons** | Bootstrap Icons |
| **Transit Data** | PMPML GTFS (real-time bus schedules), PMPML Fare Chart CSV, KML stop/depot maps |

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 7.4+** (PHP 8.x recommended)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache/Nginx Web Server** (or use XAMPP/WAMP/MAMP)
- **Composer** (optional, for GTFS tools)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/Bus-pass-managemnet.git
cd Bus-pass-managemnet
```

### 2. Set Up Database

1. Open phpMyAdmin or MySQL CLI
2. Import the database schema:

```bash
mysql -u root -p < database/bus_pass_db.sql
```

Or via phpMyAdmin: Import `database/bus_pass_db.sql`

### 3. Configure Database Connection

Edit `config/database.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'bus_pass_db');
```

### 4. Configure Base URL

In `config/database.php`, update the `BASE_URL` to match your setup:

```php
define('BASE_URL', '/Bus-pass-managemnet');  // Change to '/' if in root
```

### 5. Set Up Uploads Directory

Ensure the `uploads/` directory is writable:

```bash
chmod -R 755 uploads/
```

### 6. Start the Application

If using XAMPP, place the project in `htdocs/` folder and visit:
```
http://localhost/Bus-pass-managemnet/
```

## 📁 Project Structure

```
Bus-pass-managemnet/
├── assets/
│   ├── css/
│   │   ├── style.css              # Custom styles
│   │   └── success.css            # Payment success page styles
│   └── js/
│       ├── script.js              # Custom JavaScript
│       └── success.js             # Payment success JS (confetti, receipts)
├── config/
│   ├── auth.php                   # Authentication functions
│   └── database.php               # Database connection & helpers
├── database/
│   └── bus_pass_db.sql            # Database schema
├── data/
│   ├── stops.json                 # Bus stops data
│   ├── PMPML_Fare_Chart.csv       # Fare calculation data
│   ├── PMPML 11-Stage Fare Chart Stage Dis.txt  # Alternative fare reference
│   ├── pmpml-routes-list.csv      # PMPML routes
│   ├── pmpml-stops-map.kml        # PMPML stops KML data
│   └── pmpml-depots-map.kml       # PMPML depots KML data
├── includes/
│   ├── header.php                 # HTML head section
│   ├── navbar.php                 # Sidebar navigation
│   └── footer.php                 # Footer with scripts
├── ajax/
│   ├── calculate_fare.php         # AJAX fare & route matching
│   ├── get_live_bus.php           # Live bus schedule API from PMPML
│   └── mark_notification_read.php # Mark notification as read
├── uploads/                       # Document uploads
├── pmpml-gtfs/                    # PMPML GTFS data tools
│
├── index.php                      # Entry point (redirect)
├── login.php                      # Student login
├── register.php                   # Student registration
├── dashboard.php                  # Student dashboard
├── apply_pass.php                 # Apply for bus pass (4-step wizard)
├── my_applications.php            # View all applications
├── renew_pass.php                 # Renew existing pass
├── download_pass.php              # Download digital pass
├── payment.php                    # UPI QR code payment page
├── process_payment.php            # Payment processing handler
├── payment_success.php            # Payment success page (confetti animation)
├── payment_status.php             # Payment history & status
├── generate_receipt.php           # Download PDF receipt
├── notifications.php              # View notifications
├── profile.php                    # View/Edit profile
├── change_password.php            # Change password
├── forgot_password.php            # Password recovery
├── reset_password.php             # Complete password reset
├── logout.php                     # Logout handler
├── generate_json.php              # Generate stops.json from KML
├── process_pmpml_data.php         # Bulk import PMPML routes/stops
├── check_routes_table.php         # Debug: check database routes
├── debug_routes.php               # Debug: route matching tool
├── update_database.php            # Database schema updater
├── test_matching.php              # Test route matching logic
├── test_pass_fees.php             # Test fee calculation logic
│
└── README.md
```

## 📊 Database Schema

### Tables

| Table | Description |
|-------|-------------|
| `students` | Student registration and profile data |
| `routes` | Available bus routes with source, destination, and fare |
| `bus_passes` | Bus pass applications and their status |
| `payments` | Payment transactions |
| `notifications` | Student notifications |

### Key Relationships

- `bus_passes.student_id` → `students.id` (CASCADE DELETE)
- `bus_passes.route_id` → `routes.id` (CASCADE DELETE)
- `payments.pass_id` → `bus_passes.id` (CASCADE DELETE)
- `notifications.student_id` → `students.id` (CASCADE DELETE)

## 💰 Pass Fee Structure

| Pass Type | Duration | Discount |
|-----------|----------|----------|
| Daily | 1 day | 0% |
| Monthly | 30 days | 5% |
| Quarterly | 90 days | 10% |
| Half Yearly | 180 days | 15% |
| Yearly | 365 days | 20% |

**Formula:** `Final Fee = (Daily Fare × Days) × (1 - Discount%)`

## 🔧 Configuration

### Application Settings

In `config/database.php`, you can customize:

```php
define('APP_NAME', 'Bus Pass Management System');
define('APP_EMAIL', 'admin@buspass.edu');
define('APP_PHONE', '+91-9876543210');
```

### File Upload Settings

Supported file types for document uploads:

| Document | Accepted Formats |
|----------|-----------------|
| College ID | JPG, PNG, PDF |
| Photo | JPG, PNG |
| Address Proof | JPG, PNG, PDF |
| Profile Picture | JPG, PNG, GIF |

## 🎨 UI Features

- **Responsive Sidebar Navigation** — Collapsible on mobile with overlay
- **Step Wizard Form** — Visual progress indicator for multi-step applications
- **Live Search** — Select2 powered stop search with autocomplete
- **Real-time Fare Calculator** — Instant price breakdown as you select options
- **DataTables** — Sortable, searchable, paginated application lists
- **SweetAlert2** — Beautiful modal dialogs and confirmations
- **Flash Messages** — Success/error feedback with auto-dismiss
- **Image Preview** — Document upload preview before submission

## 🚍 PMPML GTFS Integration

The system integrates with PMPML (Pune Metropolitan Public Transport) GTFS data for:

- Real-time bus schedule information
- Route matching based on source/destination
- Live departure and arrival times

## 🛡️ Security Features

- Password hashing using PHP's `password_hash()` with bcrypt
- Prepared statements for all SQL queries
- Input sanitization with `mysqli_real_escape_string` and `htmlspecialchars`
- Session-based authentication
- CSRF-safe form handling
- File upload validation

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 👨‍💻 Author

**Your Name** - [Your GitHub](https://github.com/yourusername)

## 🙏 Acknowledgments

- [Bootstrap 5](https://getbootstrap.com/) - Frontend framework
- [PMPML](https://pmpml.com/) - Pune Metropolitan Public Transport
- [DataTables](https://datatables.net/) - jQuery table plugin
- [SweetAlert2](https://sweetalert2.github.io/) - Beautiful alert dialogs
- [Select2](https://select2.org/) - Enhanced select boxes
- [jsPDF](https://github.com/parallax/jsPDF) - PDF receipt generation
- [QRServer API](https://goqr.me/api/) - UPI QR code generation

---

**Built with ❤️ for students by students**
