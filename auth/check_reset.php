<?php
require_once __DIR__ . '/config/db.php';
$email = 'squidgameo67x@gmail.com';
$stmt = $conn->prepare('SELECT id, name, email, reset_token, reset_expiry FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
var_dump($user);
?>
