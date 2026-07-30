# Bus Pass Management System - Setup Guide

## Prerequisites

- XAMPP or similar PHP/MySQL stack
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Basic command line knowledge

## Installation Steps

### Step 1: Prepare Database

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `bus_pass_management`
3. Note the database connection details

### Step 2: Configure Application

1. Navigate to `c:\xampp\htdocs\Bus-pass-managemnet\`
2. Open `config.php` in a text editor
3. Update the following if needed:

```php
define('DB_HOST', 'localhost');      // Usually localhost
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', '');               // Your MySQL password
define('DB_NAME', 'bus_pass_management');
```

4. Save the file

### Step 3: Create Required Directories

Open Command Prompt and run:

```bash
cd c:\xampp\htdocs\Bus-pass-managemnet
mkdir uploads\passes
mkdir uploads\qrcodes
mkdir logs
mkdir backups
```

Or on PowerShell:

```powershell
cd c:\xampp\htdocs\Bus-pass-managemnet
New-Item -ItemType Directory -Path .\uploads\passes -Force
New-Item -ItemType Directory -Path .\uploads\qrcodes -Force
New-Item -ItemType Directory -Path .\logs -Force
New-Item -ItemType Directory -Path .\backups -Force
```

### Step 4: Verify Installation

1. Start XAMPP (Apache and MySQL)
2. Open browser and go to: `http://localhost/Bus-pass-managemnet/test.php`
3. Run all tests - they should all pass ✓
4. Check for any errors in the output

### Step 5: Access the Application

1. Open browser
2. Navigate to: `http://localhost/Bus-pass-managemnet/`
3. You should see the dashboard with 6 feature cards

## Configuration Options

Edit `config.php` to customize:

```php
// Pass Settings
define('PASS_VALIDITY_DAYS', 365);           // Default 1 year
define('PASS_RENEWAL_DAYS', 30);             // Renewal window before expiry

// QR Code Settings
define('QR_CODE_SIZE', 200);                 // Size in pixels
define('QR_CODE_ERROR_CORRECTION', 'M');     // Error correction level

// File Settings
define('MAX_FILE_SIZE', 5242880);            // 5MB
define('UPLOAD_PATH', APP_PATH . '/uploads');

// Pagination
define('ITEMS_PER_PAGE', 10);
```

## File Permissions

On Linux/Mac, set proper permissions:

```bash
chmod 755 uploads
chmod 755 uploads/passes
chmod 755 uploads/qrcodes
chmod 755 logs
chmod 755 backups
chmod 644 config.php
```

## First Time Usage

### 1. Create a Test User

Using API:
```bash
curl -X POST http://localhost/Bus-pass-managemnet/api/pass.php?action=generate_pass \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "pass_type": "monthly",
    "validity_days": 30
  }'
```

Or via PHP:
```php
<?php
require_once 'config.php';
require_once 'classes/User.php';

$user = new User();
$result = $user->createUser([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '9876543210',
    'address' => '123 Main St',
    'city' => 'Springfield',
    'state' => 'State',
    'postal_code' => '12345',
    'user_type' => 'student'
]);

var_dump($result);
?>
```

### 2. Generate a Pass

```php
<?php
require_once 'config.php';
require_once 'classes/BusPass.php';
require_once 'classes/PassNumberGenerator.php';

$passGen = new PassNumberGenerator();
$passNum = $passGen->generatePassNumber();

$busPass = new BusPass();
$result = $busPass->createPass(1, $passNum, 'monthly', 30);

var_dump($result);
?>
```

### 3. Generate QR Code

```php
<?php
require_once 'config.php';
require_once 'classes/QRCodeGenerator.php';

$qrGen = new QRCodeGenerator();
$result = $qrGen->generateQRCode($passNumber, $userId);

var_dump($result);
?>
```

### 4. Download Pass

Visit: `http://localhost/Bus-pass-managemnet/api/pass.php?action=download_pass&id=1&format=pdf`

### 5. Create Renewal Request

```php
<?php
require_once 'config.php';
require_once 'classes/RenewalRequest.php';

$renewal = new RenewalRequest();
$result = $renewal->createRenewalRequest($passId, $userId);

var_dump($result);
?>
```

### 6. Approve Renewal

```php
<?php
require_once 'config.php';
require_once 'classes/RenewalApprovalWorkflow.php';

$workflow = new RenewalApprovalWorkflow();
$result = $workflow->approveRenewalRequest($requestId, $approverId);

if ($result['success']) {
    $workflow->sendNotification($result['notification']);
}

var_dump($result);
?>
```

## Troubleshooting

### Issue: "Database connection failed"
**Solution:**
- Check if MySQL is running
- Verify database credentials in config.php
- Ensure database exists
- Check PHP MySQL extension is enabled

### Issue: "Cannot create files in upload directory"
**Solution:**
- Check directory permissions (should be 755)
- Ensure Apache user has write permissions
- Create directories manually with proper permissions

### Issue: "QR code generation fails"
**Solution:**
- Check internet connection (uses online API)
- Check firewall/proxy settings
- Verify cURL is enabled in PHP
- Check if qrserver.com is accessible

### Issue: "404 Not Found" on API endpoints
**Solution:**
- Verify file paths in API calls
- Check Apache mod_rewrite is enabled
- Ensure .htaccess is properly configured
- Check URL structure

### Issue: "Tables not created in database"
**Solution:**
- Run test.php to initialize tables
- Check database privileges
- Ensure database is selected
- Check MySQL error log

## Email Configuration (Optional)

For email notifications, configure in your PHP:

```php
// In production, use a mail service like:
// PHPMailer, Swift Mailer, or AWS SES

// Edit helpers.php sendEmail() function to use your provider
```

## Backup & Recovery

### Create Backup

```bash
# MySQL dump
mysqldump -u root -p bus_pass_management > backup_$(date +%Y%m%d_%H%M%S).sql

# Or use the provided backup function
php backup.php
```

### Restore from Backup

```bash
mysql -u root -p bus_pass_management < backup_file.sql
```

## Security Checklist

- [ ] Change default MySQL password
- [ ] Update database credentials
- [ ] Set proper file permissions
- [ ] Enable HTTPS in production
- [ ] Configure firewall rules
- [ ] Regular backups scheduled
- [ ] Monitor error logs
- [ ] Update PHP and MySQL
- [ ] Use strong session secrets
- [ ] Implement rate limiting

## Performance Tips

1. **Database Indexing**: Already configured on key columns
2. **Pagination**: Use limit/offset for large datasets
3. **Caching**: Implement Redis for frequently accessed data
4. **CDN**: Use for static assets in production
5. **Database Optimization**: Regular ANALYZE TABLE commands

## Monitoring

### Check Logs

```bash
tail -f logs/error.log
tail -f logs/activity.log
```

### Database Health

```sql
SELECT TABLE_NAME, ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Size (MB)'
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'bus_pass_management';
```

## Scaling Considerations

- Load balancing for multiple servers
- Database replication for high availability
- Separate read/write databases
- Caching layer (Redis/Memcached)
- Message queues for email notifications
- CDN for file delivery

## Support

For issues:
1. Check logs in `/logs/` directory
2. Run `test.php` to identify issues
3. Review error messages carefully
4. Check system requirements
5. Consult README.md for more details

## Next Steps

1. ✅ Complete installation
2. ✅ Configure database
3. ✅ Test system
4. ✅ Create test data
5. Create admin users
6. Set up email notifications
7. Configure access controls
8. Deploy to production
9. Schedule regular backups
10. Monitor system health

---

**Installation Complete!** 🎉

Your Bus Pass Management System is ready to use.
