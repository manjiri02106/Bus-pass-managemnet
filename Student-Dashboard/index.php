<?php
/**
 * Index - Redirect to login
 * Bus Pass Management System
 */
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>

