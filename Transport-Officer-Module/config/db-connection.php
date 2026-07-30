<?php
// db-connection.php - Database connection setup (MySQL version)

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'bus_pass_officer_db';

try {
    // Connect to MySQL server first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    
    // Check if tables already exist
    $tableExists = false;
    try {
        $result = $pdo->query("SELECT 1 FROM users LIMIT 1");
        $tableExists = ($result !== false);
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    // If tables don't exist, import them
    if (!$tableExists) {
        $sql_file = dirname(__DIR__) . '/database_mysql.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
