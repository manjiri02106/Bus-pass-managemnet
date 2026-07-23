# 🚌 Bus Pass Management System - Technical Documentation

Welcome to the technical documentation for the **Bus Pass Management System**. This comprehensive guide provides detailed information on the system architecture, database schema, RESTful APIs, security mechanisms, installation procedures, and automated testing suites.

---

## 📋 Table of Contents
1. [System Overview](#-system-overview)
2. [Architecture & Technology Stack](#-architecture--technology-stack)
3. [Directory Structure](#-directory-structure)
4. [Database Architecture & Schema](#-database-architecture--schema)
5. [Core Modules & Logic](#-core-modules--logic)
   - [Authentication & Role-Based Access Control](#authentication--role-based-access-control)
   - [Pass Application & Pricing Engine](#pass-application--pricing-engine)
   - [Admin Queue Moderation & AJAX Search](#admin-queue-moderation--ajax-search)
   - [Route & Category CRUD Managers](#route--category-crud-managers)
6. [RESTful API Specifications](#-restful-api-specifications)
7. [Automated CLI Testing Suite](#-automated-cli-testing-suite)
8. [Installation & Deployment Guide](#-installation--deployment-guide)
9. [Security Hardening & Best Practices](#-security-hardening--best-practices)
10. [Troubleshooting & FAQs](#-troubleshooting--faqs)

---

## 🌟 System Overview

The **Bus Pass Management System** is a web application created with PHP and MySQL. It modernizes municipal and private bus transit pass issuance, renewal, verification, and management.

### Key Objectives
- **Digitization**: Replace paper/card transit passes with dynamic, QR-verifiable digital tickets.
- **Automated Verification**: Expose lightweight REST API endpoints for conductors and transit inspectors to verify pass validity in real-time.
- **Fair Fare Calculation**: Apply structured tier discounts (Student, Senior, Special Needs) dynamically across custom transit routes and validity durations.
- **Modern User Experience**: Deliver a responsive dark-mode layout with glassmorphic cards, real-time AJAX pagination/filtering, and image preview uploading.

---

## 🏗️ Architecture & Technology Stack

```
 ┌─────────────────────────────────────────────────────────┐
 │                   Web Browser Client                    │
 │    (Glassmorphic Dark Theme / Vanilla JS / Fetch API)    │
 └────────────────────────────┬────────────────────────────┘
                              │ HTTP / JSON
                              ▼
 ┌─────────────────────────────────────────────────────────┐
 │                    Apache Web Server                    │
 │               (PHP 8.0+ / PDO Middleware)               │
 └──────┬─────────────────────┬────────────────────┬───────┘
        │                     │                    │
        ▼                     ▼                    ▼
 ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
 │ User/Admin   │     │  REST APIs   │     │  CLI Test    │
 │ Pages        │     │  (JSON)      │     │  Runner      │
 └──────┬───────┘     └──────┬───────┘     └──────┬───────┘
        │                    │                    │
        └────────────────────┼────────────────────┘
                             │ SQL (PDO Prepared Statements)
                             ▼
 ┌─────────────────────────────────────────────────────────┐
 │                      MySQL / MariaDB                    │
 │       (bus_pass_db: Users, Passes, Routes, Tiers)        │
 └─────────────────────────────────────────────────────────┘
```

| Layer | Technology | Description |
| :--- | :--- | :--- |
| **Frontend UI** | HTML5, Vanilla CSS3 | Custom design system featuring CSS variables, glassmorphism (`backdrop-filter`), flexbox/grid, and keyframe animations. |
| **Frontend Logic**| Vanilla JavaScript (ES6+) | Dynamic price estimation, file upload previews, debounced AJAX queries, modal dialog handlers. |
| **Backend Engine** | PHP 8.0+ | Procedural and object-oriented scripts utilizing PHP Data Objects (PDO) with strict error modes. |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ | Relational schema with Foreign Keys, `CASCADE`/`RESTRICT` constraints, and index optimizations. |
| **Testing** | PHP CLI Script | Integration test runner for database connectivity, pricing rules, and verification assertions. |

---

## 📂 Directory Structure

```
Bus-pass-managemnet/
├── admin/                     # Administrator Management Portal
│   ├── categories.php         # Discount category manager (Create, Edit, Delete)
│   ├── dashboard.php          # Admin metrics, KPI cards, pending approval queue
│   ├── passes.php             # Pass ledger with AJAX search & status filters
│   └── routes.php             # Transit route manager (Create, Edit, Delete)
├── api/                       # RESTful Public & Verification Endpoints
│   ├── categories.php         # GET JSON list of discount categories
│   ├── routes.php             # GET JSON list of transit routes & pricing
│   └── verify_pass.php        # GET QR Verification Endpoint
├── assets/                    # Static Assets & Storage
│   ├── css/
│   │   └── style.css          # Design system, glassmorphism, responsive utilities
│   ├── js/
│   │   ├── main.js            # Image previews, modal toggles, price calculation
│   │   └── search.js          # AJAX search debounce, filter state, pagination
│   └── uploads/               # Storage directory for uploaded user profile photos
├── database/                  # Database Schema & Connection Helpers
│   ├── db.php                 # Singleton/PDO Connection Class (`Database::getInstance()`)
│   └── schema.sql             # Table creation DDL & seed data
├── includes/                  # Core Helpers & Component Partials
│   ├── footer.php             # Shared HTML footer script & toast notifications
│   ├── functions.php          # Auth guards, security sanitizers, status badge helpers
│   └── header.php             # Navigation bar layout & user session badge
├── tests/                     # Automated Testing Suite
│   └── run_tests.php          # CLI integration & logic test runner
├── apply.php                  # User Pass Application form with live calculator
├── dashboard.php              # User Dashboard displaying active/past passes
├── index.php                  # Hero landing page & route showcase
├── login.php                  # User & Admin authentication form
├── register.php               # New user account registration form
├── README.md                  # Quickstart guide & repository overview
├── SRS.md                     # Software Requirements Specification
└── DOCUMENTATION.md           # Full Technical Documentation (This file)
```

---

## 💾 Database Architecture & Schema

The system database is named `bus_pass_db`. Below is the complete relational specification.

### Entity Relationship Diagram (Conceptual)

```
 [ users ] (1) ─────────── (N) [ passes ] (N) ─────────── (1) [ routes ]
                                  │
                                  │ (N)
                                  │
                                  │ (1)
                           [ categories ]
                                  │
                                  │ (1)
                                  │
                                  │ (N)
                           [ payments ]
```

### Table Specifications

#### 1. `users`
Stores passenger credentials and administrative user records.
- `id` (INT, PK, Auto Increment)
- `name` (VARCHAR 100, NOT NULL)
- `email` (VARCHAR 100, UNIQUE, NOT NULL)
- `password` (VARCHAR 255, NOT NULL) – Hashed using `password_hash()` (Bcrypt).
- `role` (ENUM('admin', 'user'), DEFAULT 'user')
- `profile_pic` (VARCHAR 255, NULL) – Relative path to uploaded file in `assets/uploads/`.
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### 2. `categories`
Defines discount tiers for passenger types.
- `id` (INT, PK, Auto Increment)
- `name` (VARCHAR 50, UNIQUE, NOT NULL) – e.g. "Student Special", "Senior Citizen"
- `discount_percentage` (DECIMAL(5,2), DEFAULT 0.00) – Range: 0.00 to 100.00
- `description` (TEXT, NULL)

#### 3. `routes`
Defines transit routes and standard base rates.
- `id` (INT, PK, Auto Increment)
- `route_code` (VARCHAR 50, UNIQUE, NOT NULL) – e.g. "RT-101"
- `source` (VARCHAR 100, NOT NULL) – Origin location
- `destination` (VARCHAR 100, NOT NULL) – Destination location
- `standard_price` (DECIMAL(10,2), NOT NULL) – Monthly base price in currency units

#### 4. `passes`
Core ticket records linking users, categories, and routes.
- `id` (INT, PK, Auto Increment)
- `pass_number` (VARCHAR 50, UNIQUE, NOT NULL) – Formatted string: `BP-YYYYMMDD-XXXXXX`
- `user_id` (INT, FK -> `users.id` ON DELETE CASCADE)
- `category_id` (INT, FK -> `categories.id` ON DELETE RESTRICT)
- `route_id` (INT, FK -> `routes.id` ON DELETE RESTRICT)
- `start_date` (DATE, NOT NULL)
- `end_date` (DATE, NOT NULL)
- `price` (DECIMAL(10,2), NOT NULL) – Calculated final fare
- `status` (ENUM('pending', 'approved', 'rejected'), DEFAULT 'pending')
- `qr_code_data` (VARCHAR 255, UNIQUE, NOT NULL) – Unique signature token: `VAL-BP-YYYYMMDD-XXXXXX`
- `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### 5. `payments`
Tracks transaction payments tied to generated passes.
- `id` (INT, PK, Auto Increment)
- `pass_id` (INT, FK -> `passes.id` ON DELETE CASCADE)
- `amount` (DECIMAL(10,2), NOT NULL)
- `transaction_id` (VARCHAR 100, UNIQUE, NOT NULL)
- `status` (ENUM('pending', 'completed', 'failed'), DEFAULT 'pending')
- `payment_date` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

---

## ⚙️ Core Modules & Logic

### Authentication & Role-Based Access Control
Security and access policies are maintained centrally via [includes/functions.php](file:///c:/xampp/htdocs/Bus-pass-managemnet/includes/functions.php):
- `require_login()`: Redirects unauthenticated users to `login.php`.
- `require_admin()`: Restricts administrative modules (`admin/*`) strictly to accounts where `$_SESSION['role'] === 'admin'`.
- Passwords are verified securely with `password_verify($input_pass, $stored_hash)` and saved with `password_hash($pass, PASSWORD_BCRYPT)`.

### Pass Application & Pricing Engine
When applying for a pass ([apply.php](file:///c:/xampp/htdocs/Bus-pass-managemnet/apply.php)), users select a route, a tier category, and a duration (1 to 12 months).

#### Fare Calculation Formula
$$\text{Calculated Price} = \text{Base Route Fare} \times \left(1 - \frac{\text{Discount Percentage}}{100}\right) \times \text{Duration (Months)}$$

*Example*:
- Base Route Fare (`RT-101`): **$150.00**
- Category Tier (`Student Special`): **50% Discount**
- Duration: **3 Months**
$$\text{Price} = 150.00 \times (1 - 0.50) \times 3 = 150.00 \times 0.50 \times 3 = \$225.00$$

Client-side calculation updates instantaneously via Vanilla JS in [assets/js/main.js](file:///c:/xampp/htdocs/Bus-pass-managemnet/assets/js/main.js), and server-side recalculation enforces price integrity during record creation.

### Admin Queue Moderation & AJAX Search
The Admin Pass Ledger ([admin/passes.php](file:///c:/xampp/htdocs/Bus-pass-managemnet/admin/passes.php)) uses asynchronous debounced AJAX requests ([assets/js/search.js](file:///c:/xampp/htdocs/Bus-pass-managemnet/assets/js/search.js)):
- **Search Query**: Real-time matching against passenger name, email, or pass number.
- **Filters**: Instant dropdown filtering by Status (`pending`, `approved`, `rejected`), Tier Category, or Route.
- **Action Queue**: Instant Approve / Reject toggle updates pass status without full page reload.

### Route & Category CRUD Managers
Administrators can configure system parameters dynamically:
- Add, Edit, or Remove transit routes with auto-validation for duplicate route codes.
- Add, Edit, or Remove discount categories with validation preventing deletion of categories currently referenced by existing user passes (`ON DELETE RESTRICT`).

---

## 🌐 RESTful API Specifications

The application includes light JSON REST endpoints ideal for mobile app integrations or transit conductor verification scanners.

---

### 1. Verification API Endpoint

Evaluates pass validity from a scanned QR token signature.

- **URL**: `/api/verify_pass.php`
- **Method**: `GET`
- **Parameters**:
  - `code` (string, required): The signature code encoded inside the pass QR code (e.g. `VAL-BP-20260721-0001`).

#### Sample Request
```bash
curl -X GET "http://localhost/Bus-pass-managemnet/api/verify_pass.php?code=VAL-BP-20260721-0001"
```

#### Successful & Valid Response (`200 OK`)
```json
{
  "status": "success",
  "valid": true,
  "message": "Pass is ACTIVE and VALID.",
  "data": {
    "pass_number": "BP-20260721-0001",
    "passenger_name": "Jane Doe",
    "profile_pic": "assets/uploads/user_2.jpg",
    "category": "Student Special",
    "route_code": "RT-102",
    "route": "University Campus -> Westside Station",
    "valid_from": "2026-07-01",
    "valid_until": "2026-08-01",
    "status": "approved"
  }
}
```

#### Expired or Invalid Response (`200 OK`)
```json
{
  "status": "success",
  "valid": false,
  "message": "Pass is EXPIRED (Validity ended on 2026-06-01).",
  "data": {
    "pass_number": "BP-20260501-0004",
    "passenger_name": "John Smith",
    "status": "approved"
  }
}
```

#### Error Response (`404 Not Found` / `400 Bad Request`)
```json
{
  "status": "error",
  "valid": false,
  "message": "Invalid parameter. 'code' is required."
}
```

---

### 2. Public Routes API

Returns active routes and base rates for third-party widgets or mobile clients.

- **URL**: `/api/routes.php`
- **Method**: `GET`

#### Sample Response (`200 OK`)
```json
{
  "success": true,
  "count": 5,
  "routes": [
    {
      "id": 1,
      "route_code": "RT-101",
      "source": "Downtown Hub",
      "destination": "Airport Terminal 2",
      "standard_price": "150.00"
    }
  ]
}
```

---

### 3. Categories & Discounts API

Returns all discount categories and discount rates.

- **URL**: `/api/categories.php`
- **Method**: `GET`

#### Sample Response (`200 OK`)
```json
{
  "success": true,
  "count": 4,
  "categories": [
    {
      "id": 2,
      "name": "Student Special",
      "discount_percentage": "50.00",
      "description": "50% discount for school and college students with valid student ID."
    }
  ]
}
```

---

## 🧪 Automated CLI Testing Suite

The repository includes a dedicated CLI testing script located at [tests/run_tests.php](file:///c:/xampp/htdocs/Bus-pass-managemnet/tests/run_tests.php) to verify database schema integrity, connection helpers, pricing formulas, and verification routines.

### How to Run Tests

Ensure PHP CLI is available in your PATH, then execute:

```bash
php c:\xampp\htdocs\Bus-pass-managemnet\tests\run_tests.php
```

### Test Suite Execution Modules
1. **Database Connectivity Assertion**: Tests PDO connection instantiation and exception throwing.
2. **Schema & Table Integrity Assertion**: Confirms that required tables (`users`, `categories`, `routes`, `passes`, `payments`) exist in `bus_pass_db`.
3. **Pricing Formula Accuracy Test**: Validates calculated prices against expected values for various routes, discount tiers, and months.
4. **Pass Code Generation & QR Format Test**: Asserts prefix formatting and uniqueness logic.
5. **Pass Validity API Logic Test**: Simulates expired, pending, approved, and non-existent QR token checks.

---

## 🛠️ Installation & Deployment Guide

### Prerequisites
- **XAMPP Server** (Apache 2.4+, PHP 8.0+, MySQL 5.7+ / MariaDB 10.4+)
- **Browser**: Google Chrome, Mozilla Firefox, or Microsoft Edge.

### Step-by-Step Installation

1. **Clone or Copy Repository**:
   Place the project folder inside your XAMPP root directory:
   ```text
   C:\xampp\htdocs\Bus-pass-managemnet
   ```

2. **Start Services**:
   Launch the **XAMPP Control Panel** and click **Start** for **Apache** and **MySQL**.

3. **Import Database Schema**:
   - Open your browser and navigate to phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
   - Create a new database named `bus_pass_db`.
   - Click the **Import** tab, select `database/schema.sql`, and click **Go** (or copy/paste the SQL code into the SQL Console tab).

4. **Launch Application**:
   Navigate to [http://localhost/Bus-pass-managemnet/index.php](http://localhost/Bus-pass-managemnet/index.php) in your browser.

### Default Login Credentials

| Account Role | Email Address | Password |
| :--- | :--- | :--- |
| **System Administrator** | `admin@buspass.com` | `admin123` |
| **Passenger User 1** | `jane@gmail.com` | `user123` |
| **Passenger User 2** | `john@gmail.com` | `user123` |

---

## 🛡️ Security Hardening & Best Practices

1. **SQL Injection Prevention**:
   All database queries are executed using PDO prepared statements with strict parameter binding:
   ```php
   $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
   $stmt->execute(['email' => $email]);
   ```

2. **Cross-Site Scripting (XSS) Mitigation**:
   All dynamic user output rendered in HTML templates is escaped using `sanitize_input()` / `htmlspecialchars()`:
   ```php
   function sanitize_input($data) {
       return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
   }
   ```

3. **Session Fixation Defense**:
   Session identifiers are regenerated upon login verification:
   ```php
   session_regenerate_id(true);
   ```

4. **File Upload Security**:
   ID photos uploaded during application undergo extension checking (JPEG/PNG only), file size limits (max 2MB), and random filename renaming before storage in `assets/uploads/`.

---

## ❓ Troubleshooting & FAQs

#### Q1: "Database Connection Failed" Error
- **Cause**: MySQL service in XAMPP is stopped, or database credentials differ.
- **Solution**: Open `database/db.php` and verify host (`localhost`), port (`3306`), user (`root`), and password (default is empty `""` in XAMPP).

#### Q2: Uploaded profile photos are not displaying
- **Cause**: Missing directory permissions or missing `assets/uploads/` directory.
- **Solution**: Ensure `assets/uploads/` directory exists and has write permissions.

#### Q3: How do I test pass verification without physical conductor devices?
- **Solution**: Use the built-in REST API endpoint directly in your browser or cURL client:
  `http://localhost/Bus-pass-managemnet/api/verify_pass.php?code=VAL-BP-20260721-0001`

---

*Documentation compiled and maintained for the Bus Pass Management System repository.*
