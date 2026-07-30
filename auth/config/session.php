<?php
/**
 * config/session.php — Session bootstrap + timeout + role helpers
 * Bus Pass Management System · Authentication Module
 *
 * Include this file at the TOP of every protected page.
 */
require_once __DIR__ . '/db.php';

// --- Secure session settings (before session_start) -------------------
define('SESSION_LIFETIME', 20 * 60);   // 20 minutes of inactivity → auto logout

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,               // browser session
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,           // set true only if you run HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Session timeout enforcement --------------------------------------
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity'])
        && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        // idle too long → destroy session
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        session_start();
        $_SESSION['flash_error'] = 'Your session expired due to inactivity. Please log in again.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// --- CSRF helpers ------------------------------------------------------
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// --- Auth helpers ------------------------------------------------------
function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

function current_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(array $roles): void {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        die('403 — You do not have permission to access this page.');
    }
}

// --- Flash messages ----------------------------------------------------
function set_flash(string $type, string $msg): void {
    $_SESSION['flash_' . $type] = $msg;
}

function get_flash(string $type): ?string {
    $key = 'flash_' . $type;
    if (!empty($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function render_flash(): string {
    $html = '';
    foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $type => $bs) {
        if ($msg = get_flash($type)) {
            $html .= '<div class="alert alert-' . $bs . ' alert-dismissible fade show" role="alert">'
                   . htmlspecialchars($msg)
                   . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
    return $html;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
