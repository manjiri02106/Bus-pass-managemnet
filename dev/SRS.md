# Software Requirements Specification (SRS)
## Bus Pass Management System

---

### 1. Introduction

#### 1.1 Purpose
This document provides a detailed Software Requirements Specification (SRS) for the **Bus Pass Management System**. It describes the functional and non-functional requirements, system architecture, database design, user interfaces, security protocols, and verification methods.

#### 1.2 Scope
The Bus Pass Management System is a web-based portal designed to digitize municipal and private bus transit pass issuance, renewal, verification, and administration. Key capabilities include:
- **Passenger Portal**: Account registration, pass application with dynamic fare and discount calculation, ID photo upload, pass status tracking, and digital ticket display with QR verification code.
- **Admin Portal**: Pass application review (approval/rejection queue), transit route CRUD management, discount category management, and real-time pass search with pagination.
- **Verification API**: RESTful API endpoints for transit inspectors and conductors to scan and instantly verify pass validity.

#### 1.3 Definitions, Acronyms, and Abbreviations
- **SRS**: Software Requirements Specification
- **CRUD**: Create, Read, Update, Delete
- **PDO**: PHP Data Objects
- **QR Code**: Quick Response Code used for ticket verification
- **XSS**: Cross-Site Scripting
- **KPI**: Key Performance Indicator

---

### 2. Overall Description

#### 2.1 Product Perspective
The system operates on an Apache/PHP backend connected to a MySQL relational database. The frontend is built using standard HTML5, CSS3 (Glassmorphic dark design system), JavaScript (Vanilla JS with fetch API for real-time operations), and FontAwesome icons.

#### 2.2 Product Functions
- **User Management**: Authentication (login/register), session management, role-based authorization (`user` vs `admin`).
- **Pass Application**: Multi-tier pass creation based on selected route, category discount, and validity duration.
- **Pass Verification Ledger**: Dynamic real-time pass search (by name, email, pass number), category/route/status filtering, and AJAX pagination.
- **Route & Tier Management**: Admin CRUD operations for managing transit routes (source, destination, fare) and passenger categories (Student, Senior, General, etc.).
- **Pass Verification API**: Public/semi-public endpoint returning structured JSON validity status for scanned QR tokens.

#### 2.3 User Classes and Characteristics
1. **Passenger / General Public**: End-users who apply for bus passes, check pass validity, and present digital tickets during transit.
2. **System Administrator**: Transit authority staff who manage routes, approve pass applications, define discount rates, and inspect system statistics.
3. **Conductor / Inspector**: Field staff using mobile devices or scanner terminals that query the pass verification API.

#### 2.4 Operating Environment
- **Web Server**: Apache 2.4+ (XAMPP / Linux / Windows Server)
- **Programming Language**: PHP 8.0+
- **Database Engine**: MySQL 5.7+ / MariaDB 10.4+
- **Browser Compatibility**: Chrome, Firefox, Edge, Safari (Modern ES6+ support required)

---

### 3. System Features & Functional Requirements

#### 3.1 Authentication & Authorization Module
- **FR-1.1**: Passengers can register an account providing name, email, and password.
- **FR-1.2**: Users can log in using email and password. Passwords are securely hashed using PHP `password_hash()` (Bcrypt).
- **FR-1.3**: System enforces role-based redirecting (`admin` -> Admin Portal, `user` -> User Dashboard).

#### 3.2 Pass Application & Dynamic Pricing Module
- **FR-2.1**: User can select a route, category tier (e.g., General, Student 50%, Senior Citizen 30%), and validity start date.
- **FR-2.2**: The system dynamically calculates price: `Total Price = Base Route Fare * (1 - Category Discount %) * Validity Months`.
- **FR-2.3**: User can upload an identity photo (JPEG/PNG) which is sanitized and stored in `assets/uploads/`.
- **FR-2.4**: Upon submission, a unique pass serial number (`BP-YYYYMMDD-XXXXXX`) and QR signature code (`VAL-BP-YYYYMMDD-XXXXXX`) are generated.

#### 3.3 Admin Application Queue & Ledger
- **FR-3.1**: Admin can view all pass applications with real-time AJAX search (debounced text input for passenger name, email, or pass number).
- **FR-3.2**: Admin can filter pass records by Status (Pending, Active, Rejected), Tier, and Route.
- **FR-3.3**: Admin can approve or reject pending pass applications with a single click.

#### 3.4 Transit Routes & Categories CRUD
- **FR-4.1**: Admin can create, edit, and delete transit routes (Route Code, Source Station, Destination Station, Standard Price).
- **FR-4.2**: Admin can create, edit, and delete discount categories (Category Name, Discount %, Description).
- **FR-4.3**: System prevents deletion of routes or categories currently assigned to existing passes.

#### 3.5 Pass Verification REST API
- **FR-5.1**: Endpoint `GET /api/verify_pass.php?code={QR_CODE}` returns JSON pass status.
- **FR-5.2**: Endpoint verifies if pass status is `approved`, if current date is between `start_date` and `end_date`, and returns passenger and route metadata.

---

### 4. Non-Functional Requirements

#### 4.1 Performance
- Page load time must be under 1.5 seconds under normal operation.
- Search and filter queries on the admin pass ledger must respond within 300ms via AJAX.

#### 4.2 Security
- All database operations use PDO prepared statements with parameter binding to prevent SQL Injection.
- User input is sanitized using `htmlspecialchars()` to prevent XSS attacks.
- Session regeneration (`session_regenerate_id(true)`) is performed upon successful login to prevent session fixation.

#### 4.3 UI / Aesthetics
- Modern dark-mode aesthetic with CSS glassmorphic cards, smooth gradient accents, and responsive layout.

---

### 5. Database Architecture

#### Schema Tables
1. `users`: (`id`, `name`, `email`, `password`, `role`, `profile_pic`, `created_at`)
2. `categories`: (`id`, `name`, `discount_percentage`, `description`)
3. `routes`: (`id`, `route_code`, `source`, `destination`, `standard_price`)
4. `passes`: (`id`, `pass_number`, `user_id`, `category_id`, `route_id`, `start_date`, `end_date`, `price`, `status`, `qr_code_data`, `created_at`)
5. `payments`: (`id`, `pass_id`, `amount`, `transaction_id`, `status`, `payment_date`)

---

### 6. Verification and Testing

The project includes an automated command-line test runner located at `tests/run_tests.php`.

#### Execution Command:
```bash
php tests/run_tests.php
```

#### Test Coverage:
- Database connection & table creation assertions
- User registration & password verification checks
- Fare calculation and discount formula verification
- Pass generation & status transitions
- Verification API response testing
