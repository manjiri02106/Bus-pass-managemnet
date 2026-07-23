<?php
/**
 * Database Connection Helper Class
 * Using PDO for secure, prepared SQL execution.
 */

class Database
{
    private static $host = 'localhost';
    private static $db_name = 'bus_pass_db';
    private static $username = 'root';
    private static $password = '';
    private static $conn = null;

    public static function connect()
    {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Return a clean user-friendly message or throw exception
                throw new Exception("Database Connection Error: " . $e->getMessage());
            }
        }
        return self::$conn;
    }
}
