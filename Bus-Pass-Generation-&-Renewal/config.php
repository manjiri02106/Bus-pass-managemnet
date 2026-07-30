<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_pass_management');

// Application Settings
define('APP_NAME', 'Bus Pass Management System');
define('APP_URL', 'http://localhost/Bus-pass-managemnet');
define('APP_PATH', __DIR__);

// Session Settings
define('SESSION_TIMEOUT', 3600); // 1 hour

// Pass Settings
define('PASS_VALIDITY_DAYS', 365); // 1 year
define('PASS_RENEWAL_DAYS', 30); // Renewal possible 30 days before expiry

// QR Code Settings
define('QR_CODE_SIZE', 200); // pixels
define('QR_CODE_ERROR_CORRECTION', 'M'); // L, M, Q, H

// File Upload Settings
define('UPLOAD_PATH', APP_PATH . '/uploads');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Pagination
define('ITEMS_PER_PAGE', 10);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', APP_PATH . '/logs/error.log');

// Create necessary directories
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!is_dir(APP_PATH . '/logs')) {
    mkdir(APP_PATH . '/logs', 0755, true);
}
?>
