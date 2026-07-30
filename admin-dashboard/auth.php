<?php
/**
 * admin-dashboard/auth.php
 * Redirects to the centralized authentication module.
 */
require_once __DIR__ . '/../auth/config/session.php';
require_once __DIR__ . '/../auth/config/db.php';

// If already logged in as admin, go to dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: index.php');
    exit;
}

// Everyone else → central login
header('Location: ../auth/login.php');
exit;
