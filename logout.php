<?php
/**
 * Logout Handler
 * Bus Pass Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

require_once __DIR__ . '/includes/auth.php';

logout_admin();

header('Location: login.php');
exit;
