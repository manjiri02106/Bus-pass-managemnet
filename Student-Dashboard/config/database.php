<?php
/**
 * Database Configuration
 * Bus Pass Management System
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buspass_db');

// Set default time zone to Asia/Kolkata (India)
date_default_timezone_set('Asia/Kolkata');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Set MySQL time zone to Asia/Kolkata
mysqli_query($conn, "SET time_zone = '+05:30'");

// Base URL (adjust if deployed to a subdirectory)
define('BASE_URL', '/test2_final/Student-Dashboard');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/test2_final/Student-Dashboard/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads');

// Application Settings
define('APP_NAME', 'Bus Pass Management System');
define('APP_EMAIL', 'pmpml@pune.gov.in');
define('APP_PHONE', '+91-9876543210');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper function to sanitize input
 */
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Helper function to decode HTML entities from database-stored data
 */
function decode_db_data($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = decode_db_data($value);
        }
        return $data;
    }
    return html_entity_decode(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Helper function to decode HTML entities and prepare for display
 */
function decode_display($data) {
    return htmlspecialchars(decode_db_data($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Helper function to generate application number
 */
function generateApplicationNo($student_id) {
    return 'BP-' . date('Y') . '-' . str_pad($student_id, 6, '0', STR_PAD_LEFT) . '-' . rand(100, 999);
}

/**
 * Look up the single-trip fare from PMPML_Fare_Chart.csv for a given distance (km).
 * Falls back to the $route_fare argument if the CSV cannot be read.
 */
function getDailyFareFromCSV($distance_km, $fallback_fare = 0) {
    $csv_path = $_SERVER['DOCUMENT_ROOT'] . '/Bus-pass-managemnet/data/PMPML_Fare_Chart.csv';
    if (!file_exists($csv_path)) return $fallback_fare ?: 10;

    $handle = fopen($csv_path, 'r');
    $first = true;
    $daily_fare = $fallback_fare ?: 10;

    while (($row = fgetcsv($handle)) !== false) {
        if ($first) { $first = false; continue; } // skip header
        if (count($row) < 3) continue;

        $range = trim($row[1]);
        $fare  = (int) trim($row[2]);
        if (empty($range) || $fare === 0) continue;

        if (preg_match('/to\s*([\d.]+)/i', $range, $m)) {
            $upper = (float) $m[1];
        } else {
            continue;
        }

        if ($distance_km <= $upper) {
            $daily_fare = $fare;
            break;
        }
        $daily_fare = $fare; // last row catches anything beyond the highest stage
    }
    fclose($handle);
    return $daily_fare;
}

/**
 * Calculate bus pass fee from the daily fare (from PMPML_Fare_Chart.csv).
 *
 * Discount schedule (matches frontend JS):
 *   Daily       = fare × 1          (no discount)
 *   Monthly     = fare × 30,  5% off
 *   Quarterly   = fare × 90, 10% off
 *   Half Yearly = fare × 180, 15% off
 *   Yearly      = fare × 365, 20% off
 *
 * @param float  $route_fare  Single-trip daily fare from CSV.
 * @param string $pass_type   Daily | Monthly | Quarterly | Half Yearly | Yearly
 */
function calculatePassFee($route_fare, $pass_type) {
    $config = [
        'Daily'       => ['days' => 1,   'discount' => 0],
        'Monthly'     => ['days' => 30,  'discount' => 5],
        'Quarterly'   => ['days' => 90,  'discount' => 10],
        'Half Yearly' => ['days' => 180, 'discount' => 15],
        'Yearly'      => ['days' => 365, 'discount' => 20],
    ];

    $c     = $config[$pass_type] ?? $config['Monthly'];
    $gross = $route_fare * $c['days'];
    return round($gross * (1 - $c['discount'] / 100), 2);
}


/**
 * Helper function to calculate validity dates
 */
function calculateValidity($pass_type) {
    $periods = [
        'Daily'       => '+1 day',
        'Monthly'     => '+1 month',
        'Quarterly'   => '+3 months',
        'Half Yearly' => '+6 months',
        'Yearly'      => '+12 months'
    ];
    
    $valid_from  = date('Y-m-d');
    $valid_until = date('Y-m-d', strtotime($periods[$pass_type] ?? '+1 month'));
    
    return ['from' => $valid_from, 'until' => $valid_until];
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . BASE_URL . $url);
    exit();
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        $alertClass = [
            'info' => 'alert-info',
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'danger' => 'alert-danger'
        ];
        echo '<div class="alert ' . ($alertClass[$type] ?? 'alert-info') . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($msg);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}

/**
 * Check if student is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'student';
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}
?>

