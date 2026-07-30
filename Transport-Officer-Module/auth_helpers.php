<?php
// auth_helpers.php - Authentication and Session Management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session Timeout implementation (15 minutes = 900 seconds)
$timeout_duration = 900; 

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
        // Session has expired
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?msg=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time(); // Update activity tracker
}

/**
 * Ensures user is logged in and possesses one of the allowed roles.
 * @param array $allowed_roles Roles allowed to access the page (e.g., ['officer', 'admin'])
 */
function check_auth(array $allowed_roles) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        die("Unauthorized access. You do not have permissions to view this page.");
    }
}

/**
 * Helper to check current role
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
 * Returns a styled status badge
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
