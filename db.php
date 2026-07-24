<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'bus_pass_management';

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$conn->select_db($db);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','transport_officer','student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$defaultUsers = [
    ['Admin User', 'admin@example.com', 'admin123', 'admin'],
    ['Officer User', 'officer@example.com', 'officer123', 'transport_officer'],
    ['Student User', 'student@example.com', 'student123', 'student']
];

foreach ($defaultUsers as $user) {
    $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $check->bind_param('s', $user[1]);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $hash = password_hash($user[2], PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $user[0], $user[1], $hash, $user[3]);
        $stmt->execute();
    }
}
?>
