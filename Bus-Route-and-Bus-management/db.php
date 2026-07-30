<?php

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbName = 'bus_route_management_db';

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS routes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                route_name VARCHAR(255) NOT NULL,
                source_id INT NOT NULL,
                destination_id INT NOT NULL,
                distance_km REAL NOT NULL DEFAULT 0,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(source_id) REFERENCES locations(id) ON DELETE RESTRICT,
                FOREIGN KEY(destination_id) REFERENCES locations(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS buses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                bus_number VARCHAR(255) NOT NULL UNIQUE,
                route_id INT NOT NULL,
                capacity INT NOT NULL DEFAULT 45,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS bus_stops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                route_id INT NOT NULL,
                stop_name VARCHAR(255) NOT NULL,
                stop_order INT NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        return $pdo;
    } catch (Throwable $e) {
        throw new RuntimeException('Database initialization failed: ' . $e->getMessage(), 0, $e);
    }
}

try {
    $pdo = get_db();
} catch (Throwable $e) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        echo '<!DOCTYPE html><html><body><h1>Database Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
        exit;
    }

    throw $e;
}
?>
