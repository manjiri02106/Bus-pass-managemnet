<?php
/**
 * config/db.php — Database connection (MySQLi)
 * Bus Pass Management System · Authentication Module
 *
 * XAMPP defaults: host = localhost, user = root, password = (empty).
 * Adjust only if your XAMPP MySQL uses a password.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'buspass_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site-wide settings
define('APP_NAME', 'BusPass');
define('APP_TAGLINE', 'Bus Pass Management System');
// Base URL — dynamically computed so it works seamlessly regardless of XAMPP folder path
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Resolve relative path from htdocs document root
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $appDir  = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    if ($docRoot && str_starts_with($appDir, $docRoot)) {
        $relPath = substr($appDir, strlen($docRoot));
    } else {
        // Fallback relative path for this workspace
        $relPath = '/demo/Bus-pass-managemnet/buspass-auth';
    }
    define('BASE_URL', rtrim($protocol . $host . $relPath, '/'));
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('Database connection failed. Have you imported database/buspass_auth.sql? ' .
        htmlspecialchars($e->getMessage()));
}
