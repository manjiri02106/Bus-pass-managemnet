# 🎫 Bus Pass Management System - Complete Documentation

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Architecture](#architecture)
5. [API Reference](#api-reference)
6. [Usage Examples](#usage-examples)
7. [Database Schema](#database-schema)
8. [Configuration](#configuration)
9. [Troubleshooting](#troubleshooting)

---

## Overview

The **Bus Pass Management System** is a comprehensive PHP-based solution for managing digital bus passes. It provides complete lifecycle management from pass generation to renewal workflows with QR code support and downloadable formats.

### Key Capabilities

- ✅ Digital pass generation with unique numbering
- ✅ QR code generation and verification
- ✅ Multiple download formats (PDF, PNG, JPG, HTML)
- ✅ Renewal request workflow with approval system
- ✅ Comprehensive statistics and reporting
- ✅ Email notifications
- ✅ Transaction logging and auditing
- ✅ RESTful API endpoints
- ✅ Bulk operations support

---

## Features

### 1. Digital Bus Pass Generation

Generate unique bus passes with multiple format options:

```
Format 1: BP-2026-0720-12345 (Default)
Format 2: BP-2026-000001 (Sequential)
Format 3: BPM-20260720-1234 (Type-specific)
```

**Features:**
- Automatic uniqueness validation
- Customizable validity periods
- Multiple pass types (monthly, quarterly, annual, special)
- User type categorization (student, employee, senior citizen, regular)

### 2. Pass Number Generation

Three generation strategies implemented:

#### Strategy 1: Date-based Random
```php
$passGen = new PassNumberGenerator();
$number = $passGen->generatePassNumber();
// Returns: BP-2026-0720-12345
```

#### Strategy 2: Sequential
```php
$number = $passGen->generateSequentialPassNumber();
// Returns: BP-2026-000001, BP-2026-000002, etc.
```

#### Strategy 3: Type-specific
```php
$number = $passGen->generateCustomPassNumber('annual');
// Returns: BPA-20260720-1234
```

### 3. QR Code Management

Generate QR codes containing:
- Pass number
- User ID
- Timestamp
- Verification URL

**Supported Methods:**
- Online API (qrserver.com) - Free, no dependencies
- Local library (phpqrcode) - No internet required
- Inline generation (SVG/Base64)

```php
$qrGen = new QRCodeGenerator();
$result = $qrGen->generateQRCode($passNumber, $userId);
// Returns: ['success' => true, 'file_path' => '...', 'file_name' => '...']
```

### 4. Download & Print

Export passes in multiple formats:

#### PDF Format
```
Professional layout with:
- Pass information
- QR code (if available)
- User details
- Validity period
- Official footer
```

#### Image Formats (PNG/JPG)
```
Visual pass card with:
- Header with blue background
- Pass number and user info
- Validity dates
- Status badge
- Embedded QR code
```

#### HTML/Print Format
```
Browser-printable format with:
- Responsive design
- Print-friendly styling
- Direct print button
- Full pass information
```

### 5. Renewal Requests

User-initiated renewal workflow:

```
User Request → System Validation → Pending Status
                                      ↓
                        Admin Review & Decision
                        ↓
                    Approve  OR  Reject
                        ↓              ↓
                    Pass Renewed   Request Denied
                    + Email Sent   + Notification
```

**Features:**
- 30-day renewal window before expiry
- Automatic eligibility checking
- Request cancellation by users
- Status tracking (pending, approved, rejected, cancelled)
- Bulk operations support

### 6. Renewal Approval Workflow

Complete workflow automation for approvers:

```
Pending Requests Dashboard
    ↓
Batch Operations
    ↓
Individual Review
    ↓
Approve / Reject / Request Changes
    ↓
Auto-notification & Transaction Logging
```

**Workflow Actions:**
1. **Approve**: Renew pass with new expiry date
2. **Reject**: Deny renewal with reason
3. **Request Changes**: Ask user for modifications
4. **Bulk Approve**: Process multiple requests
5. **Auto-approve**: Automatic approval for eligible users

### 7. Notification System

Automated email notifications:

```
Renewal Approved → Email to User
Renewal Rejected → Email with Reason
New Pass Issued → Welcome Email
Pass Expiring → Reminder Email
```

**Templates Available:**
- Renewal approved
- Renewal rejected
- Pass generated
- Expiry reminder

---

## Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- XAMPP/Apache
- 50MB disk space

### Quick Start

1. **Copy files to XAMPP**
```bash
Copy Bus-pass-managemnet folder to C:\xampp\htdocs\
```

2. **Create database**
```sql
CREATE DATABASE bus_pass_management;
```

3. **Configure database**
Edit `config.php`:
```php
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

4. **Create directories**
```bash
mkdir uploads\passes uploads\qrcodes logs
```

5. **Verify installation**
```
Visit: http://localhost/Bus-pass-managemnet/test.php
```

6. **Access application**
```
Visit: http://localhost/Bus-pass-managemnet/
```

---

## Architecture

### System Components

```
┌─────────────────────────────────────┐
│        Web Interface                 │
│    (HTML/JavaScript/CSS)             │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│         API Layer                    │
│   (RESTful JSON Endpoints)           │
│  ├─ pass.php                        │
│  └─ renewal.php                     │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│     Business Logic Classes           │
│  ├─ BusPass                         │
│  ├─ PassNumberGenerator             │
│  ├─ QRCodeGenerator                │
│  ├─ PassDownloadManager            │
│  ├─ RenewalRequest                 │
│  └─ RenewalApprovalWorkflow        │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│      Data Access Layer               │
│  ├─ Database                        │
│  └─ Connection Management           │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│        MySQL Database                │
│  ├─ users                           │
│  ├─ bus_passes                      │
│  ├─ renewal_requests                │
│  ├─ pass_transactions               │
│  ├─ pass_downloads                  │
│  └─ admin_users                     │
└──────────────────────────────────────┘
```

### Class Hierarchy

```
Database (Connection & Tables)
    ↓
├── User (User Management)
├── BusPass (Pass Operations)
│   ├── PassNumberGenerator
│   ├── QRCodeGenerator
│   ├── PassDownloadManager
│   └── RenewalRequest
└── RenewalApprovalWorkflow (Workflow Engine)
```

---

## API Reference

### Pass Management API (`/api/pass.php`)

#### Generate Pass
```
POST /api/pass.php?action=generate_pass
Content-Type: application/json

{
  "user_id": 1,
  "pass_type": "monthly",
  "validity_days": 30
}

Response:
{
  "success": true,
  "pass_id": 1,
  "pass_number": "BP-2026-0720-12345",
  "issue_date": "2026-07-20",
  "expiry_date": "2026-08-20"
}
```

#### Get Pass Details
```
GET /api/pass.php?action=get_pass&id=1

Response:
{
  "success": true,
  "pass": {
    "id": 1,
    "user_id": 1,
    "pass_number": "BP-2026-0720-12345",
    "pass_type": "monthly",
    "issue_date": "2026-07-20",
    "expiry_date": "2026-08-20",
    "status": "active",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com"
  }
}
```

#### Download Pass
```
GET /api/pass.php?action=download_pass&id=1&format=pdf

Formats: pdf, png, jpg, html
Response: File download or HTML output
```

#### Generate QR Code
```
POST /api/pass.php?action=generate_qrcode
Content-Type: application/json

{
  "pass_number": "BP-2026-0720-12345",
  "user_id": 1
}

Response:
{
  "success": true,
  "file_path": "/uploads/qrcodes/qr_BP-2026-0720-12345_1234567890.png",
  "file_name": "qr_BP-2026-0720-12345_1234567890.png",
  "url": "/uploads/qrcodes/..."
}
```

#### Verify Pass
```
POST /api/pass.php?action=verify_pass
Content-Type: application/json

{
  "qr_data": "{\"pass_number\":\"BP-2026-0720-12345\",...}"
}

Response:
{
  "valid": true,
  "pass_number": "BP-2026-0720-12345",
  "user_id": 1,
  "status": "active"
}
```

#### Get Statistics
```
GET /api/pass.php?action=get_pass_statistics

Response:
{
  "success": true,
  "statistics": {
    "total_passes": 150,
    "active_passes": 120,
    "expired_passes": 25,
    "expiring_soon": 5
  }
}
```

### Renewal Management API (`/api/renewal.php`)

#### Create Renewal Request
```
POST /api/renewal.php?action=create_renewal_request
Content-Type: application/json

{
  "pass_id": 1,
  "user_id": 1
}

Response:
{
  "success": true,
  "request_id": 5,
  "current_expiry": "2026-08-20",
  "requested_expiry": "2027-08-20",
  "message": "Renewal request submitted successfully"
}
```

#### Get Pending Requests
```
GET /api/renewal.php?action=get_pending_requests?limit=20&offset=0

Response:
{
  "success": true,
  "requests": [
    {
      "id": 5,
      "bus_pass_id": 1,
      "user_id": 1,
      "pass_number": "BP-2026-0720-12345",
      "status": "pending",
      "request_date": "2026-07-25 10:30:00",
      "first_name": "John",
      "last_name": "Doe"
    }
  ],
  "total": 15,
  "limit": 20,
  "offset": 0
}
```

#### Approve Renewal
```
POST /api/renewal.php?action=approve_renewal_request
Content-Type: application/json

{
  "request_id": 5,
  "approver_id": 2,
  "comments": "Approved"
}

Response:
{
  "success": true,
  "request_id": 5,
  "new_expiry_date": "2027-08-20",
  "message": "Renewal request approved and pass renewed successfully",
  "notification": {
    "type": "approval",
    "title": "Renewal Request Approved",
    "message": "Your bus pass renewal request has been approved.",
    "pass_number": "BP-2026-0720-12345",
    "new_expiry_date": "2027-08-20",
    "recipient_email": "john@example.com"
  }
}
```

#### Reject Renewal
```
POST /api/renewal.php?action=reject_renewal_request
Content-Type: application/json

{
  "request_id": 5,
  "approver_id": 2,
  "rejection_reason": "Documents not verified"
}

Response:
{
  "success": true,
  "request_id": 5,
  "message": "Renewal request rejected",
  "notification": {
    "type": "rejection",
    "title": "Renewal Request Rejected",
    "reason": "Documents not verified"
  }
}
```

#### Bulk Approve
```
POST /api/renewal.php?action=bulk_approve_requests
Content-Type: application/json

{
  "request_ids": [5, 6, 7, 8],
  "approver_id": 2
}

Response:
{
  "success": true,
  "result": {
    "approved": 4,
    "failed": 0,
    "errors": []
  }
}
```

#### Get Renewal Statistics
```
GET /api/renewal.php?action=get_renewal_statistics

Response:
{
  "success": true,
  "statistics": {
    "total_requests": 45,
    "pending_requests": 12,
    "approved_requests": 30,
    "rejected_requests": 3
  }
}
```

---

## Usage Examples

### Example 1: Complete Pass Generation Flow

```php
<?php
require_once 'config.php';
require_once 'classes/User.php';
require_once 'classes/BusPass.php';
require_once 'classes/PassNumberGenerator.php';
require_once 'classes/QRCodeGenerator.php';

// Step 1: Create User
$user = new User();
$userResult = $user->createUser([
    'first_name' => 'Alice',
    'last_name' => 'Johnson',
    'email' => 'alice@example.com',
    'phone' => '9876543210',
    'address' => '456 Oak Ave',
    'city' => 'Metropolis',
    'state' => 'State',
    'postal_code' => '54321',
    'user_type' => 'employee'
]);

if (!$userResult['success']) {
    die("User creation failed: " . $userResult['error']);
}

$userId = $userResult['user_id'];
echo "✓ User created (ID: $userId)\n";

// Step 2: Generate Pass Number
$passGen = new PassNumberGenerator();
$passNumber = $passGen->generatePassNumber();
echo "✓ Pass number generated: $passNumber\n";

// Step 3: Create Pass
$busPass = new BusPass();
$passResult = $busPass->createPass($userId, $passNumber, 'annual', 365);

if (!$passResult['success']) {
    die("Pass creation failed: " . $passResult['error']);
}

$passId = $passResult['pass_id'];
echo "✓ Pass created (ID: $passId)\n";

// Step 4: Generate QR Code
$qrGen = new QRCodeGenerator();
$qrResult = $qrGen->generateQRCode($passNumber, $userId);

if ($qrResult['success']) {
    $busPass->updatePassQRCode($passId, $qrResult['file_path']);
    echo "✓ QR code generated\n";
}

echo "\n✓ Complete! Pass ready for download.\n";
?>
```

### Example 2: Renewal Approval Workflow

```php
<?php
require_once 'config.php';
require_once 'classes/RenewalRequest.php';
require_once 'classes/RenewalApprovalWorkflow.php';

// Step 1: Get pending requests
$renewal = new RenewalRequest();
$pending = $renewal->getPendingRequests(10, 0);
echo "Pending requests: " . count($pending) . "\n";

// Step 2: Review and approve
foreach ($pending as $request) {
    if ($request['user_id'] != 5) continue; // Example filter
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->approveRenewalRequest($request['id'], 2);
    
    if ($result['success']) {
        echo "✓ Approved request #" . $request['id'] . "\n";
        
        // Step 3: Send notification
        $workflow->sendNotification($result['notification']);
        echo "✓ Notification sent to user\n";
    }
}
?>
```

### Example 3: Download Pass in Multiple Formats

```php
<?php
require_once 'config.php';
require_once 'classes/PassDownloadManager.php';

$downloadMgr = new PassDownloadManager();

// PDF
$pdf = $downloadMgr->generatePassPDF(1);
if ($pdf['success']) {
    echo "✓ PDF generated: " . $pdf['file_name'] . "\n";
}

// PNG
$png = $downloadMgr->generatePassImage(1, 'png');
if ($png['success']) {
    echo "✓ PNG generated: " . $png['file_name'] . "\n";
}

// HTML for printing
$html = $downloadMgr->generatePrintablePass(1);
if ($html['success']) {
    echo "✓ HTML generated\n";
    echo $html['html']; // Display in browser
}
?>
```

---

## Database Schema

### users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(15) UNIQUE,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    postal_code VARCHAR(10),
    id_proof VARCHAR(255),
    profile_photo VARCHAR(255),
    user_type ENUM('student', 'employee', 'senior_citizen', 'regular'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### bus_passes Table
```sql
CREATE TABLE bus_passes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    pass_number VARCHAR(20) UNIQUE,
    qr_code VARCHAR(255),
    pass_type ENUM('monthly', 'quarterly', 'annual', 'special'),
    issue_date DATE,
    expiry_date DATE,
    status ENUM('active', 'expired', 'cancelled', 'suspended'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### renewal_requests Table
```sql
CREATE TABLE renewal_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_pass_id INT,
    user_id INT,
    request_date DATETIME,
    current_expiry_date DATE,
    requested_expiry_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'cancelled'),
    rejection_reason TEXT,
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

### pass_transactions Table
```sql
CREATE TABLE pass_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_pass_id INT,
    user_id INT,
    transaction_type ENUM('issue', 'renewal', 'reprint', 'cancellation'),
    amount DECIMAL(10, 2),
    payment_status ENUM('pending', 'completed', 'failed'),
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### pass_downloads Table
```sql
CREATE TABLE pass_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_pass_id INT,
    download_date DATETIME,
    download_format ENUM('pdf', 'png', 'jpg'),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (bus_pass_id) REFERENCES bus_passes(id)
);
```

---

## Configuration

Edit `config.php` to customize:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_pass_management');

// Pass Settings
define('PASS_VALIDITY_DAYS', 365);
define('PASS_RENEWAL_DAYS', 30);

// QR Code
define('QR_CODE_SIZE', 200);
define('QR_CODE_ERROR_CORRECTION', 'M'); // L, M, Q, H

// File Upload
define('UPLOAD_PATH', APP_PATH . '/uploads');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Pagination
define('ITEMS_PER_PAGE', 10);
```

---

## Troubleshooting

### Common Issues

**Q: Database connection failed**
A: 
- Check MySQL is running
- Verify credentials in config.php
- Ensure database exists
- Check PHP MySQL extension

**Q: Cannot upload files**
A:
- Check directory permissions (755)
- Verify directory exists
- Check Apache user permissions
- Check disk space available

**Q: QR code not generating**
A:
- Check internet connection
- Verify firewall settings
- Check cURL is enabled
- Verify qrserver.com is accessible

**Q: API returns 404**
A:
- Check file paths
- Verify Apache mod_rewrite
- Check .htaccess configuration
- Verify URL structure

---

## Support & Updates

For help:
1. Check logs in `/logs/` directory
2. Run `test.php` to diagnose
3. Review error messages
4. Consult documentation
5. Check system requirements

---

**Last Updated**: July 20, 2026
**Version**: 1.0.0
**Status**: Production Ready ✅
