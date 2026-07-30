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
    
    $student_id = (int)($_SESSION['student_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    
    if ($student_id === 0 && $user_id > 0 && ($_SESSION['user_role'] ?? '') === 'student') {
        // Auto-fix missing student record
        $stmt_student = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt_student->bind_param('i', $user_id);
        $stmt_student->execute();
        $res = $stmt_student->get_result()->fetch_assoc();
        if ($res) {
            $student_id = (int)$res['id'];
        } else {
            $temp_prn = 'PRN' . strtoupper(substr(uniqid(), -6));
            $stmt_insert = $conn->prepare("INSERT INTO students (user_id, prn_number, roll_number, department, class, mobile, address) VALUES (?, ?, '', '', '', '', '')");
            $stmt_insert->bind_param('is', $user_id, $temp_prn);
            $stmt_insert->execute();
            $student_id = (int)$stmt_insert->insert_id;
            $stmt_insert->close();
        }
        $stmt_student->close();
        $_SESSION['student_id'] = $student_id;
    }
    
    $query = "SELECT s.*, u.full_name, u.email 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// Get student by ID
function getStudentById($id) {
    global $conn;
    
    $query = "SELECT s.*, u.full_name, u.email 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}
?>

