<?php
/**
 * Authentication Check
 * Bus Pass Management System
 */

require_once 'database.php';

// Check if student is logged in, if not redirect to login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php', 'Please login to access this page.', 'warning');
    }
}

// Check if admin is logged in
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('/admin/login.php', 'Please login as admin to access this page.', 'warning');
    }
}

// Get current student data
function getCurrentStudent() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $student_id = (int)$_SESSION['student_id'];
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Get student by ID
function getStudentById($id) {
    global $conn;
    
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}
?>

