<?php
/**
 * logout.php — Securely destroys the session.
 * Bus Pass Management System · Authentication Module
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();
session_start();
$_SESSION['flash_success'] = 'You have been logged out successfully.';

header('Location: ' . BASE_URL . '/../index.php');
exit;
