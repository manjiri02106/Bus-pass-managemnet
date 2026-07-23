<?php
/**
 * Student Logout
 * Bus Pass Management System
 */
require_once 'config/database.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php?msg=You have been logged out successfully.');
exit();
?>

