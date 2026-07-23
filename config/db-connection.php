<?php
// db-connection.php - Database connection setup

$db_file = dirname(__DIR__) . '/bus_pass.db';
$db_exists = file_exists($db_file);

try {
    // Create PDO connection to SQLite database
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-create tables if database file did not exist
    if (!$db_exists) {
        $sql_file = dirname(__DIR__) . '/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
