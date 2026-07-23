<?php
/**
 * Database Connection — PDO
 * Bus Pass Management System
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'bus_pass_system');
define('DB_USER',    'root');        // Change to your MySQL username
define('DB_PASS',    '');            // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance.
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error — never expose it to the browser
            error_log('DB Connection failed: ' . $e->getMessage());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection error. Please contact the administrator.'
            ]));
        }
    }

    return $pdo;
}
