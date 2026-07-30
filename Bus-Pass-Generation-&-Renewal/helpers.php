<?php
/**
 * Utility Functions for Bus Pass Management System
 */

/**
 * Format response for API
 */
function formatResponse($success, $data = null, $error = null, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    
    $response = array('success' => $success);
    
    if ($data) {
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['data'] = $data;
        }
    }
    
    if ($error) {
        $response['error'] = $error;
    }
    
    return $response;
}

/**
 * Send JSON response
 */
function sendJSON($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

/**
 * Sanitize string input
 */
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate pass number format
 */
function isValidPassNumber($passNumber) {
    // Check format BP-YYYY-MMDD-XXXXX
    return preg_match('/^BP-\d{4}-\d{4}-\d{5}$/', $passNumber) === 1;
}

/**
 * Calculate days until expiry
 */
function daysUntilExpiry($expiryDate) {
    $today = new DateTime('now');
    $expiry = new DateTime($expiryDate);
    $interval = $today->diff($expiry);
    return $interval->days;
}

/**
 * Check if pass is expiring soon (within 7 days)
 */
function isExpiringsoon($expiryDate) {
    $days = daysUntilExpiry($expiryDate);
    return $days >= 0 && $days <= 7;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

/**
 * Get pass status badge
 */
function getStatusBadge($status) {
    $badges = array(
        'active' => '<span class="badge badge-success">Active</span>',
        'expired' => '<span class="badge badge-danger">Expired</span>',
        'cancelled' => '<span class="badge badge-secondary">Cancelled</span>',
        'suspended' => '<span class="badge badge-warning">Suspended</span>'
    );
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge badge-light">' . ucfirst($status) . '</span>';
}

/**
 * Get request status badge
 */
function getRequestStatusBadge($status) {
    $badges = array(
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
    );
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge badge-light">' . ucfirst($status) . '</span>';
}

/**
 * Generate transaction ID
 */
function generateTransactionId() {
    return 'TXN-' . date('YmdHis') . '-' . substr(md5(uniqid()), 0, 8);
}

/**
 * Log activity
 */
function logActivity($userId, $activity, $details = null) {
    $logFile = dirname(__DIR__) . '/logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $logEntry = "[$timestamp] User: $userId | Activity: $activity | IP: $ip";
    if ($details) {
        $logEntry .= " | Details: " . json_encode($details);
    }
    $logEntry .= "\n";
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Get user type label
 */
function getUserTypeLabel($userType) {
    $types = array(
        'student' => 'Student',
        'employee' => 'Employee',
        'senior_citizen' => 'Senior Citizen',
        'regular' => 'Regular'
    );
    
    return isset($types[$userType]) ? $types[$userType] : ucfirst(str_replace('_', ' ', $userType));
}

/**
 * Get pass type label
 */
function getPassTypeLabel($passType) {
    $types = array(
        'monthly' => 'Monthly Pass',
        'quarterly' => 'Quarterly Pass',
        'annual' => 'Annual Pass',
        'special' => 'Special Pass'
    );
    
    return isset($types[$passType]) ? $types[$passType] : ucfirst($passType);
}

/**
 * Get pass type price (can be customized)
 */
function getPassTypePrice($passType) {
    $prices = array(
        'monthly' => 500,
        'quarterly' => 1200,
        'annual' => 4000,
        'special' => 200
    );
    
    return isset($prices[$passType]) ? $prices[$passType] : 0;
}

/**
 * Apply discount based on user type
 */
function getDiscount($userType) {
    $discounts = array(
        'student' => 10,        // 10% discount
        'employee' => 5,        // 5% discount
        'senior_citizen' => 20, // 20% discount
        'regular' => 0          // No discount
    );
    
    return isset($discounts[$userType]) ? $discounts[$userType] : 0;
}

/**
 * Calculate final price
 */
function calculateFinalPrice($basePrice, $userType) {
    $discount = getDiscount($userType);
    return $basePrice * (100 - $discount) / 100;
}

/**
 * Validate date range
 */
function isValidDateRange($startDate, $endDate) {
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    
    return $start < $end;
}

/**
 * Get pagination data
 */
function getPaginationData($total, $limit, $offset) {
    return array(
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'current_page' => floor($offset / $limit) + 1,
        'total_pages' => ceil($total / $limit),
        'has_next' => ($offset + $limit) < $total,
        'has_prev' => $offset > 0
    );
}

/**
 * Validate API request method
 */
function validateMethod($allowed = array()) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($method, $allowed)) {
        sendJSON(
            array('error' => 'Method not allowed'),
            405
        );
    }
    
    return true;
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $fields) {
    $missing = array();
    
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        sendJSON(
            array('error' => 'Missing required fields: ' . implode(', ', $missing)),
            400
        );
    }
    
    return true;
}

/**
 * Get JSON input
 */
function getJSONInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJSON(
            array('error' => 'Invalid JSON input'),
            400
        );
    }
    
    return $data;
}

/**
 * Generate QR code URL
 */
function generateQRCodeURL($data) {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
}

/**
 * Send email
 */
function sendEmail($to, $subject, $body, $headers = array()) {
    $defaultHeaders = array(
        'From' => 'noreply@buspass.com',
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Mailer' => 'Bus Pass System'
    );
    
    $finalHeaders = array_merge($defaultHeaders, $headers);
    $headerString = '';
    
    foreach ($finalHeaders as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }
    
    return @mail($to, $subject, $body, $headerString);
}

/**
 * Generate email template
 */
function getEmailTemplate($type, $data) {
    $templates = array(
        'renewal_approved' => function($data) {
            return "
                <h2>Renewal Approved</h2>
                <p>Dear {$data['name']},</p>
                <p>Your bus pass renewal request has been approved.</p>
                <p><strong>Pass Number:</strong> {$data['pass_number']}</p>
                <p><strong>New Expiry Date:</strong> {$data['expiry_date']}</p>
                <p>Thank you for using our service.</p>
            ";
        },
        'renewal_rejected' => function($data) {
            return "
                <h2>Renewal Rejected</h2>
                <p>Dear {$data['name']},</p>
                <p>Unfortunately, your bus pass renewal request has been rejected.</p>
                <p><strong>Reason:</strong> {$data['reason']}</p>
                <p>Please contact us for more information.</p>
            ";
        },
        'pass_generated' => function($data) {
            return "
                <h2>Bus Pass Generated</h2>
                <p>Dear {$data['name']},</p>
                <p>Your new bus pass has been generated successfully.</p>
                <p><strong>Pass Number:</strong> {$data['pass_number']}</p>
                <p><strong>Valid From:</strong> {$data['issue_date']}</p>
                <p><strong>Valid Until:</strong> {$data['expiry_date']}</p>
                <p>You can download your pass from your dashboard.</p>
            ";
        }
    );
    
    if (isset($templates[$type])) {
        return call_user_func($templates[$type], $data);
    }
    
    return '';
}

/**
 * Create backup of database
 */
function backupDatabase() {
    global $db;
    $timestamp = date('Y-m-d-H-i-s');
    $backupDir = dirname(__DIR__) . '/backups';
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/bus_pass_backup_' . $timestamp . '.sql';
    
    // This is a simple backup method
    // For production, use mysqldump or other robust methods
    
    return $backupFile;
}

/**
 * Get system statistics
 */
function getSystemStatistics() {
    global $db;
    
    $stats = array();
    
    // Total users
    $result = $db->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Total passes
    $result = $db->query("SELECT COUNT(*) as total FROM bus_passes");
    $stats['total_passes'] = $result->fetch_assoc()['total'];
    
    // Active passes
    $result = $db->query("SELECT COUNT(*) as total FROM bus_passes WHERE status = 'active' AND expiry_date >= CURDATE()");
    $stats['active_passes'] = $result->fetch_assoc()['total'];
    
    // Pending renewals
    $result = $db->query("SELECT COUNT(*) as total FROM renewal_requests WHERE status = 'pending'");
    $stats['pending_renewals'] = $result->fetch_assoc()['total'];
    
    return $stats;
}
?>
