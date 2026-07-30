<?php

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!class_exists('PDO') || !extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('PDO SQLite support is not available. Enable pdo_sqlite in php.ini and restart the server.');
    }

    $dbDir = __DIR__ . '/data';
    if (!is_dir($dbDir) && !mkdir($dbDir, 0777, true) && !is_dir($dbDir)) {
        throw new RuntimeException('Unable to create the database directory: ' . $dbDir);
    }

    $dbPath = $dbDir . '/bus_pass.db';

    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS locations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS routes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                route_name TEXT NOT NULL,
                source_id INTEGER NOT NULL,
                destination_id INTEGER NOT NULL,
                distance_km REAL NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(source_id) REFERENCES locations(id) ON DELETE RESTRICT,
                FOREIGN KEY(destination_id) REFERENCES locations(id) ON DELETE RESTRICT
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS buses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bus_number TEXT NOT NULL UNIQUE,
                route_id INTEGER NOT NULL,
                capacity INTEGER NOT NULL DEFAULT 45,
                status TEXT NOT NULL DEFAULT 'active',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE RESTRICT
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS bus_stops (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                route_id INTEGER NOT NULL,
                stop_name TEXT NOT NULL,
                stop_order INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE
            )"
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
