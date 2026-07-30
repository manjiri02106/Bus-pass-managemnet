<?php
require_once __DIR__ . '/config.php';

echo "Creating database if it doesn't exist...\n";

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbName = DB_NAME;

$mysqli = new mysqli($host, $user, $pass);
if ($mysqli->connect_error) {
    echo "MySQL connection failed: " . $mysqli->connect_error . "\n";
    exit(1);
}

$charsetSql = "CREATE DATABASE IF NOT EXISTS `" . $mysqli->real_escape_string($dbName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($charsetSql) === TRUE) {
    echo "Database '$dbName' created or already exists.\n";
} else {
    echo "Error creating database: " . $mysqli->error . "\n";
    exit(1);
}

$mysqli->close();

// Now include the Database class which will connect to the DB and initialize tables
echo "Initializing tables...\n";
require_once __DIR__ . '/classes/Database.php';
echo "Table initialization complete.\n";

echo "Done. You can now run the test harness or open the app in your browser.\n";

?>
