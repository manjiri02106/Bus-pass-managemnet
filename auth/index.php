<?php
/**
 * index.php — Entry point. Routes to the dashboard or login.
 * Bus Pass Management System · Authentication Module
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_functions.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_role()));
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
