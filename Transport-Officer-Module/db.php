<?php
// db.php - Database connection and initialization (MySQL version)

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'buspass_db';

try {
    // Connect to MySQL server first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    
    // Removed automatic schema execution to prevent parsing bugs. 
    // Schema must be initialized manually via seed or setup script.
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
