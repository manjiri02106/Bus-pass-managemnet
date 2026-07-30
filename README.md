# 🚌 Student Bus Pass Management System (PMPML)

<div align="center">

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-XAMPP-D93735?style=for-the-badge&logo=apache&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production_Ready-success?style=for-the-badge)

<p align="center">
  <b>A Smart, Automated, Paperless Transportation & Bus Pass Management Platform</b>
</p>

[Key Features](#-key-features) • [System Architecture](#-system-architecture) • [Database ERD](#-database-entity-relationship-diagram) • [Module Breakdown](#-module-deep-dive) • [Installation Guide](#-installation--setup-guide)

</div>

---

## 📖 System Overview

The **Student Bus Pass Management System** is a full-stack web application designed for educational institutions and municipal transit authorities (e.g., PMPML). It replaces slow, manual paper-based bus pass applications with an instant, secure, online portal. 

Students can calculate fare distances based on GTFS routes, submit verification documents, pay online, and receive digital passes. Transport Officers can verify documents, request corrections, and issue digital passes, while Administrators manage system health, user roles, and global notifications.

---

## 🎨 Visual System Architecture

### 1. High-Level System Architecture

```mermaid
graph TD
    %% Roles
    Student[🎓 Student User]
    Officer[👮 Transport Officer]
    Admin[⚙️ System Admin]

    %% Main Application Routing
    Landing[🏠 Landing Page / Index]
    AuthModule[🔒 Authentication Module /auth]

    %% Dashboards
    StudentDash[📱 Student Dashboard /Student-Dashboard]
    OfficerDash[📑 Officer Portal /Transport-Officer-Module]
    AdminDash[📊 Admin Suite /admin-dashboard]

    %% Database Tier
    DB[(🗄️ MySQL Database: bus_pass_db & buspass_db)]
    Uploads[📁 Upload Storage /Student-Dashboard/uploads]

    %% Connections
    Student -->|Visits| Landing
    Student -->|Log In| AuthModule
    Officer -->|Log In| AuthModule
    Admin -->|Log In| AuthModule

    AuthModule -->|Role: Student| StudentDash
    AuthModule -->|Role: Officer| OfficerDash
    AuthModule -->|Role: Admin| AdminDash

    StudentDash -->|Upload Documents| Uploads
    StudentDash -->|Read/Write Data| DB

    OfficerDash -->|Review Uploads| Uploads
    OfficerDash -->|Approve/Reject| DB

    AdminDash -->|Manage Users & System| DB
```

---

### 2. End-to-End Pass Application & Approval Lifecycle

```mermaid
sequenceDiagram
    autonumber
    actor Student as 🎓 Student
    participant Portal as 🌐 Student Portal
    participant DB as 🗄️ Database
    actor Officer as 👮 Transport Officer
    participant Admin as ⚙️ Administrator

    Student->>Portal: 1. Select Route & Pass Type (Monthly/Quarterly)
    Portal->>Portal: 2. Auto-calculate fee based on GTFS Distance
    Student->>Portal: 3. Upload ID Card, Photo, & Address Proof
    Student->>Portal: 4. Complete Online Payment Simulation
    Portal->>DB: 5. Store Application (Status: Success / Pending Verification)
    
    Officer->>Portal: 6. Open Verification Queue
    Portal->>DB: 7. Fetch Application & Document Links
    Officer->>Portal: 8. Review Uploaded Files in Modal/New Tab
    
    alt Documents Valid
        Officer->>Portal: 9. Click "Approve Application"
        Portal->>DB: 10. Update Status -> Approved & Generate Pass ID
        Portal->>Student: 11. Digital Pass Available for PDF Download
    else Documents Invalid / Blurred
        Officer->>Portal: 9. Submit Correction Request with Instructions
        Portal->>DB: 10. Update Status -> Correction Required
        Portal->>Student: 11. Display Alert & Document Re-upload Form
        Student->>Portal: 12. Re-upload corrected documents
        Portal->>DB: 13. Update Status -> Under Verification
    end

    Admin->>DB: 14. Monitor Analytics, Active Passes, & User Logs
```

---

## 🗄️ Database Entity-Relationship Diagram

```mermaid
erDiagram
    users ||--o{ applications : "submits"
    users ||--o| students : "has details"
    routes ||--o{ applications : "assigned to"
    applications ||--o{ payments : "has transaction"
    applications ||--o{ document_verifications : "has verification checks"
    applications ||--o{ correction_requests : "receives feedback"
    users ||--o{ notifications : "receives"

    users {
        int id PK
        string full_name
        string username UK
        string email UK
        string password
        enum role "student | officer | admin"
        boolean is_active
        datetime created_at
    }

    students {
        int id PK
        int user_id FK
        string prn_number UK
        string roll_number
        string department
        string class
        string mobile
        string address
    }

    routes {
        int id PK
        string route_number
        string source
        string destination
        decimal distance
        decimal fare
    }

    applications {
        int id PK
        string application_no UK
        int student_id FK
        int route_id FK
        string pass_type
        string college_id_doc
        string photograph_doc
        string address_proof_doc
        decimal fee
        date valid_from
        date valid_until
        enum status "pending | under_verification | approved | correction_required | rejected"
        enum payment_status "Pending | Success | Failed"
    }

    payments {
        int id PK
        int application_id FK
        int student_id FK
        decimal amount
        string payment_method
        string transaction_id UK
        string payment_status
        timestamp payment_date
    }

    document_verifications {
        int id PK
        int application_id FK
        string document_type
        enum status "pending | verified | invalid"
        text notes
    }

    correction_requests {
        int id PK
        int application_id FK
        string field_name
        text instruction
        enum status "pending | resolved"
    }

    notifications {
        int id PK
        int user_id FK
        string title
        text message
        boolean is_read
        timestamp created_at
    }
```

---

## 📁 Repository Structure

```
c:\xampp\htdocs\test2_final\
│
├── 📄 index.php                   # Public Landing Page & Portal Entry Point
├── 📄 styles.css                  # Main Landing Page Stylesheet
├── 📄 script.js                   # Public Interactions & Smooth Scroll
├── 🖼️ hero-bus.png                # Hero Banner Illustration
├── 📄 setup_db.php                # Master Automated DB Installer & Seeder
├── 📄 README.md                   # System Documentation
│
├── 📁 auth/                       # Central Authentication Module
│   ├── 📄 login.php               # Multi-Role Unified Login Screen
│   ├── 📄 logout.php              # Session Destruction & Flash Handler
│   ├── 📄 register.php            # Student Self-Registration
│   ├── 📄 forgot_password.php     # Password Reset Request Form
│   ├── 📄 reset_password.php      # Password Reset Handler
│   ├── 📁 config/                 # DB Connection & Session Bootstrapper
│   └── 📁 includes/               # Shared Header/Footer & CSRF Utilities
│
├── 📁 Student-Dashboard/          # Student Self-Service Portal
│   ├── 📄 dashboard.php           # Student Overview & Active Pass Status
│   ├── 📄 apply_pass.php          # New Pass Application & File Upload Form
│   ├── 📄 renew_pass.php          # Pass Renewal & Extension Engine
│   ├── 📄 my_applications.php     # Application Status & Correction Resolution
│   ├── 📄 download_pass.php       # Printable Digital Bus Pass Generation
│   ├── 📄 generate_receipt.php    # Official Payment Receipt HTML/Print
│   ├── 📄 profile.php             # Student Profile & Avatar Management
│   ├── 📄 notifications.php       # Student Notification Center
│   └── 📁 uploads/                # Secured Storage for Uploaded Documents
│
├── 📁 Transport-Officer-Module/   # Officer Application Review & Approvals
│   ├── 📄 officer_dashboard.php   # Verification Queue Summary & Metrics
│   ├── 📄 verify_application.php  # Detailed Document Inspector & Approval Modal
│   ├── 📄 route_management.php    # Add/Update Bus Routes, Distances & Fares
│   ├── 📄 reports.php             # Export Passes & Verification Analytics (CSV/PDF)
│   ├── 📄 notifications.php       # Officer Alert & Task Feed
│   └── 📄 profile.php             # Officer Credentials Management
│
├── 📁 admin-dashboard/            # System Administrator Suite
│   ├── 📄 index.php               # System Health & Master Analytics
│   ├── 📄 users.php               # Manage Admin, Officer, & Student Accounts
│   ├── 📄 students.php            # Master Student Database Directory
│   ├── 📄 route_management.php    # Global Route Overrides & Fare Tables
│   ├── 📄 reports.php             # System Audit & Revenue Export Tools
│   ├── 📄 notifications.php       # Admin Alert Management Center
│   └── 📁 includes/               # Sidebar & Topbar Components
│
└── 📁 database-design/            # SQL Schemas & Initial Seeding Scripts
    └── 📁 database/               # Relational Schema Definitions (.sql)
```

---

## 🔍 Module Deep Dive

### 🎓 1. Student Dashboard Module (`/Student-Dashboard`)
- **Distance-Based Fare Engine**: Automatically calculates pass fees based on route length in km via GTFS integration.
- **Document Management**: File upload system for College ID, Passport Photo, and Address Proof (JPEG, PNG, PDF).
- **Instant Digital Pass**: Upon approval, generates a clean printable pass featuring student details, valid dates, and verification status.
- **Correction Resolution**: If an officer flags a document, students receive a clear notification with instructions to re-upload without starting over.

### 👮 2. Transport Officer Module (`/Transport-Officer-Module`)
- **Document Inspection**: Direct link to view uploaded student credentials in high resolution.
- **Granular Approvals**: Flag specific documents (e.g. blurred photo) or approve the entire application in one click.
- **Route & Stop Controller**: Define origin, destination, standard fares, and distance matrix.
- **Export Engine**: Export daily approved pass lists and route distribution metrics.

### ⚙️ 3. Admin Dashboard Suite (`/admin-dashboard`)
- **Master User Governance**: Create, edit, activate, or suspend Officer and Student accounts.
- **Global System Feed**: Real-time notifications for system alerts, pending queues, and security events.
- **Financial & Activity Reports**: Track total revenue generated, total active passes, and branch-wise pass adoption.

---

## ⚡ Installation & Local Setup Guide

### 📋 Prerequisites
- **XAMPP Server** (PHP 8.0 or higher, MySQL 8.0+, Apache Enabled)
- Web Browser (Chrome, Firefox, Edge)

---

### 🚀 Step-by-Step Installation

1. **Clone or Copy Repository**:
   Place the project folder in your XAMPP `htdocs` directory:
   ```bash
   C:\xampp\htdocs\test2_final
   ```

2. **Start Apache & MySQL**:
   Open XAMPP Control Panel and start both **Apache** and **MySQL** services.

3. **Automated One-Click Database Setup**:
   Navigate to the automated setup script in your browser:
   ```http
   http://localhost/test2_final/setup_db.php
   ```
   Click **"Start Automated Installation"**. This will:
   - Create `bus_pass_db` and `buspass_db`.
   - Run schema migrations and seed initial default accounts.
   - Configure all required relational keys and indices automatically.

4. **Access the Application**:
   Open the home page:
   ```http
   http://localhost/test2_final/
   ```

---

## 🔑 Default Login Credentials

| Role | Username | Email | Password | Access Portal |
| :--- | :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` | `admin@buspass.local` | `Admin@123` | [Admin Portal](http://localhost/test2_final/admin-dashboard/index.php) |
| **Transport Officer** | `officer` | `officer@buspass.local` | `Admin@123` | [Officer Portal](http://localhost/test2_final/Transport-Officer-Module/officer_dashboard.php) |
| **Student** | `student` | `student@buspass.local` | `Admin@123` | [Student Dashboard](http://localhost/test2_final/Student-Dashboard/dashboard.php) |

---

## 🛡️ Security Features

- **Password Hashing**: Uses PHP `password_hash()` with strong `bcrypt` algorithms.
- **Prepared Statements**: All database operations use `PDO` and `mysqli` prepared statements to eliminate SQL Injection risks.
- **CSRF Tokens**: Form submissions validate unique session-based CSRF tokens.
- **File Upload Sanitization**: Strict file extension and MIME-type validation for document uploads.

---

<div align="center">
  <sub>Built for PMPML Student Transit Management • Paperless & Efficient Solution</sub>
</div>
