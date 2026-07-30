# Digital Bus Pass Management System

A comprehensive PHP-based system for managing digital bus passes with features including pass generation, QR code functionality, download/print capabilities, renewal requests, and approval workflows.

## Features

### 1. **Digital Bus Pass Generation**
- Generate unique bus pass numbers with multiple formats
- Support for different pass types (monthly, quarterly, annual, special)
- Automatic pass number validation and uniqueness checking
- User-friendly pass creation interface

### 2. **Pass Number Generation**
- Multiple generation strategies:
  - **Default Format**: `BP-YYYY-MMDD-XXXXX` (e.g., BP-2026-0720-12345)
  - **Sequential Format**: `BP-YYYY-XXXXXX` (e.g., BP-2026-000001)
  - **Custom Formats**: Type-specific prefixes (BPM, BPQ, BPA, BPS)
- Built-in uniqueness validation
- Real-time pass number verification

### 3. **QR Code Generation (Optional)**
- Generate QR codes for each bus pass using free API (qrserver.com)
- Embedded QR code data includes:
  - Pass number
  - User ID
  - Timestamp
  - Verification URL
- Support for local QR code generation with phpqrcode library
- QR code verification for authenticity checking

### 4. **Download & Print Functionality**
- **Multiple Export Formats**:
  - PDF format with professional layout
  - PNG/JPG image formats
  - Printable HTML format
- Download history tracking
- IP address and user agent logging
- Direct browser download with proper headers
- Print-friendly styling included

### 5. **Renewal Requests**
- Users can request pass renewal 30 days before expiry
- Request status tracking:
  - Pending
  - Approved
  - Rejected
  - Cancelled
- Automatic eligibility checking
- Request cancellation by users
- Expiry date extension calculation

### 6. **Renewal Approval Workflow**
- Admin/Approver dashboard for managing requests
- **Workflow States**:
  - Submitted → Approval → Renewed
  - Submitted → Rejection with reason
  - Submitted → Change Request
- Bulk approval capabilities
- Auto-approve functionality for eligible requests
- Workflow status tracking with timeline
- Transaction logging for all actions

### 7. **Notification System**
- Email notifications for:
  - Renewal approval
  - Renewal rejection with reason
  - Change requests
- SMS notification support (optional)
- Customizable notification templates

## Quick Start

### Installation
1. Clone or extract files to `htdocs/Bus-pass-managemnet/`
2. Create database: `CREATE DATABASE bus_pass_management;`
3. Configure `config.php` with your database credentials
4. Create directories: `mkdir -p uploads/passes uploads/qrcodes logs`
5. Access: `http://localhost/Bus-pass-managemnet/`

### API Usage

```bash
# Generate Pass
curl -X POST http://localhost/Bus-pass-managemnet/api/pass.php?action=generate_pass \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"pass_type":"monthly"}'

# Create Renewal Request
curl -X POST http://localhost/Bus-pass-managemnet/api/renewal.php?action=create_renewal_request \
  -H "Content-Type: application/json" \
  -d '{"pass_id":1,"user_id":1}'

# Approve Renewal
curl -X POST http://localhost/Bus-pass-managemnet/api/renewal.php?action=approve_renewal_request \
  -H "Content-Type: application/json" \
  -d '{"request_id":1,"approver_id":1}'
```

## Documentation

See [Complete Documentation](docs/DOCUMENTATION.md) for detailed information on:
- System architecture
- Database schema
- API endpoints
- Configuration options
- Usage examples
- Troubleshooting

## Support

For issues and feature requests, please check the documentation or contact support.