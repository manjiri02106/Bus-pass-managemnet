<?php
// db.php - Database connection and initialization

$db_file = __DIR__ . '/bus_pass.db';
$db_exists = file_exists($db_file);

try {
    // Create PDO connection to SQLite
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // If database didn't exist, create tables
    if (!$db_exists) {
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
