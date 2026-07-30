<?php
require_once __DIR__ . '/database.php';

function page_header($title, $active) {
    $menu = [
        ['Dashboard', 'index.php'],
        ['User Management', 'users.php'],
        ['Student Management', 'students.php'],
        ['System Settings', 'settings.php'],
    ];
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>'; 
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>'; 
    echo '<link rel="stylesheet" href="assets/style.css">';
    echo '</head>';
    echo '<body>';
    echo '<div class="layout">';
    echo '<aside class="sidebar">';
    echo '<h1>Admin Portal</h1>';
    echo '<p>Bus Pass Management</p>';
    echo '<nav>';
    foreach ($menu as $item) {
        $label = $item[0];
        $link = $item[1];
        $class = ($active === $label) ? 'active' : '';
        echo '<a class="' . $class . '" href="' . $link . '">' . $label . '</a>';
    }
    echo '</nav>';
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        echo '<a href="logout.php">Logout</a>';
    }
    echo '</aside>';
    echo '<main class="content">';
}

function page_footer() {
    echo '</main>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

function dashboard_stats() {
    seed_default_settings();
    $users = get_all_users();
    $students = get_all_students();
    $siteName = get_setting('site_name', 'Bus Pass Admin');
    $maintenance = get_setting('maintenance_mode', 'Off');

    return [
        'users' => count($users),
        'students' => count($students),
        'site_name' => $siteName,
        'maintenance' => $maintenance,
    ];
/**
 * Global Utility and Security Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Quick escape helper for preventing Cross-Site Scripting (XSS)
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect($path)
{
    header("Location: $path");
    exit();
}

// Authentication guards
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_admin()
{
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

function require_login()
{
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to access this page.');
        redirect('/Bus-pass-managemnet/login.php');
    }
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        set_flash_message('error', 'Unauthorized access.');
        redirect('/Bus-pass-managemnet/dashboard.php');
    }
}

// Flash Message Helpers
function set_flash_message($type, $message)
{
    $_SESSION['flash_msg'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function display_flash_messages()
{
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
function generate_pass_number()
{
    return 'BP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Render dynamic status badge
function get_status_badge($status)
{
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
