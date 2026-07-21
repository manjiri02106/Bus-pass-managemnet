# OmniPass - NextGen Bus Pass Management System

OmniPass is a modern, high-performance digital bus pass management application built in PHP and MySQL. It features a stunning glassmorphic interface, dynamic fare calculations, automated pass verification APIs (suitable for conductor scanning systems), real-time search/filters, and local testing suites.

---

## 🌟 Key Features

1. **Modern Responsive Design**: A high-tech glassmorphic theme designed with Vanilla CSS (Dark Mode by default) focusing on smooth visual feedback and premium UX.
2. **Dynamic Ticket Cards**: Interactive visual pass templates complete with active user photo slots, dynamic QR codes, validity periods, and custom transit codes.
3. **Admin Queue Management**: Rapid-action controls for administrators to instantly approve or reject submitted pass applications.
4. **Real-time AJAX Filters**: An instant-response search, status filter, and pagination system inside the admin passes ledger using decoupled AJAX requests.
5. **RESTful Service APIs**: Exposes endpoints for route lists, pass tiers, and QR-scan verification checks.
6. **Automated Testing Suite**: A custom, lightweight database integration testing runner executed directly from the Command Line interface.

---

## 📂 Project Structure

```
├── assets/
│   ├── css/
│   │   └── style.css            # Base design system, glassmorphism rules, keyframe animations
│   ├── js/
│   │   ├── main.js              # Multi-step steppers, image previews, toast alerts
│   │   └── search.js            # Debounced AJAX search, filter, and pagination
│   └── uploads/                 # Storage for user ID/profile photos
├── database/
│   ├── schema.sql               # Database tables structure and mock seeds
│   └── db.php                   # Secure PDO database connection helper class
├── includes/
│   ├── header.php               # Shared navigation layout (role-dependent items)
│   ├── footer.php               # Shared footer layout & flash toast runner
│   └── functions.php            # Session, security sanitizers, auth guards, badges
├── api/
│   ├── routes.php               # GET JSON transit routes
│   ├── categories.php           # GET JSON pass tiers & discounts
│   └── verify_pass.php          # QR Code validation check endpoint
├── admin/
│   ├── dashboard.php            # Admin Dashboard (KPI Metrics, queue actions)
│   ├── passes.php               # Pass Listing (AJAX Search & Pagination)
│   ├── routes.php               # Route CRUD manager modal dialogs
│   └── categories.php           # Category CRUD manager
└── tests/
    └── run_tests.php            # Custom CLI testing suite (database integrity & pricing assertions)
```

---

## 💾 Database Schema

The database relies on a normalized structural design with foreign keys, indexes, and automated cascading configurations:

- **`users`**: Passenger credentials, profile picture paths, and roles (`admin`, `user`).
- **`categories`**: Discount specifications (e.g. Student 50% Off, Senior Citizen 30% Off).
- **`routes`**: Travel codes, source stations, destination stations, and default base rates.
- **`passes`**: Unique ticket serial numbers (`BP-YYYYMMDD-XXXXXX`), validity durations, calculated prices, status (`pending`, `approved`, `rejected`), and QR signature codes.
- **`payments`**: Transaction records linking tickets, payments received, and invoice statuses.

---

## 🛠️ Local Installation & Deployment

### Prerequites
- **XAMPP** (or any server hosting Apache, PHP 8.0+, and MySQL).
- **Git** (for version control operations).

### Installation Steps
1. Clone the project repository or move the directory directly into XAMPP web root:
   ```bash
   C:\xampp\htdocs\Bus-pass-managemnet
   ```
2. Start the Apache and MySQL modules inside the **XAMPP Control Panel**.
3. Open your browser and navigate to phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
4. Create a new database named `bus_pass_db`.
5. Import the schema script located at `database/schema.sql` (or copy and execute the SQL contents in the SQL console tab).
6. Launch the application: [http://localhost/Bus-pass-managemnet/index.php](http://localhost/Bus-pass-managemnet/index.php)

### Seed Login Credentials
- **System Administrator**:
  - Email: `admin@buspass.com`
  - Password: `admin123`
- **Standard Passenger**:
  - Email: `jane@gmail.com`
  - Password: `user123`

---

## 🌐 REST API Endpoints

### 1. Retrieve Active Routes
- **Endpoint**: `/Bus-pass-managemnet/api/routes.php`
- **Method**: `GET`
- **Response**:
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
        "standard_price": 150.00
      }
    ]
  }
  ```

### 2. Retrieve Pass Tiers & Discount Percentages
- **Endpoint**: `/Bus-pass-managemnet/api/categories.php`
- **Method**: `GET`
- **Response**:
  ```json
  {
    "success": true,
    "count": 4,
    "categories": [
      {
        "id": 2,
        "name": "Student Special",
        "discount_percentage": 50.00,
        "description": "50% discount for school and college students..."
      }
    ]
  }
  ```

### 3. QR Scan Verification Check
- **Endpoint**: `/Bus-pass-managemnet/api/verify_pass.php`
- **Method**: `GET` or `POST`
- **Parameters**: `code` (e.g. `VAL-BP-20260721-0001`)
- **Response (Valid Pass)**:
  ```json
  {
    "success": true,
    "valid": true,
    "message": "Pass Verified: Valid active transit ticket.",
    "details": {
      "pass_number": "BP-20260721-0001",
      "passenger": {
        "name": "Jane Doe",
        "email": "jane@gmail.com",
        "photo": "/Bus-pass-managemnet/assets/uploads/user_2_177114389.png"
      },
      "route": {
        "code": "RT-102",
        "source": "University Campus",
        "destination": "Westside Station"
      },
      "tier": "Student Special",
      "validity": {
        "starts": "2026-07-01",
        "expires": "2026-08-01"
      }
    }
  }
  ```

---

## 🧪 Running Automated Tests

To test database integrity, constraints, math functions, and endpoints locally, execute the test script from the command line:

```bash
C:\xampp\php\php.exe tests\run_tests.php
```

The script will automatically create a temporary test database, run tables setup, execute calculations validations, and perform cleaning assertions.

---

## ⌥ Git Branching & Merging Strategy

To keep the codebase stable and support collaborative integration:
1. All changes are committed to a temporary local feature branch:
   ```bash
   git checkout -b feature/bus-pass-system
   ```
2. Commit specific modular changes incrementally.
3. Switch back to the development branch and pull updates:
   ```bash
   git checkout dev
   git pull origin dev
   ```
4. Merge the feature branch into the integration branch:
   ```bash
   git merge feature/bus-pass-system
   ```
5. Once tested on `dev`, merge to the stable production branch `main`.