<?php
/**
 * Global Utility and Security Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Quick escape helper for preventing Cross-Site Scripting (XSS)
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect($path) {
    header("Location: $path");
    exit();
}

// Authentication guards
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to access this page.');
        redirect('/Bus-pass-managemnet/login.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        set_flash_message('error', 'Unauthorized access.');
        redirect('/Bus-pass-managemnet/dashboard.php');
    }
}

// Flash Message Helpers
function set_flash_message($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function display_flash_messages() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        $type = e($msg['type']);
        $text = e($msg['message']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('$text', '$type');
            });
        </script>";
    }
}

// Generate unique pass number
function generate_pass_number() {
    return 'BP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Render dynamic status badge
function get_status_badge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'approved':
            return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>';
        case 'pending':
            return '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>';
        case 'rejected':
            return '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Rejected</span>';
        default:
            return '<span class="badge badge-secondary">' . e(ucfirst($status)) . '</span>';
    }
}
