<?php
// auth-helper.php - Authentication & Session Helper

require_once dirname(__DIR__) . '/config/app-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Timeout implementation using config constants
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Enforces login and checks authorized roles.
 */
function check_auth(array $allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        die("Unauthorized access. Access restricted to: " . implode(', ', $allowed_roles));
    }
}

/**
 * Returns user role description label.
 */
function get_role_label($role) {
    switch ($role) {
        case 'admin': return 'Administrator';
        case 'officer': return 'Transport Officer';
        case 'student': return 'Student';
        default: return 'User';
    }
}

/**
 * Returns styled HTML status badge.
 */
function get_status_badge($status) {
    switch ($status) {
        case 'submitted':
            return '<span class="badge badge-submitted"><span class="badge-dot"></span>Submitted</span>';
        case 'under_verification':
            return '<span class="badge badge-verification"><span class="badge-dot"></span>Under Verification</span>';
        case 'approved':
            return '<span class="badge badge-approved"><span class="badge-dot"></span>Approved</span>';
        case 'rejected':
            return '<span class="badge badge-rejected"><span class="badge-dot"></span>Rejected</span>';
        case 'correction_required':
            return '<span class="badge badge-correction"><span class="badge-dot"></span>Correction Required</span>';
        default:
            return '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>
