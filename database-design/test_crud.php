<?php
declare(strict_types=1);

function run()
{
    $dbDir = __DIR__ . '/data';
    if (!is_dir($dbDir) && !mkdir($dbDir, 0777, true) && !is_dir($dbDir)) {
        throw new RuntimeException('Unable to create the database directory: ' . $dbDir);
    }

    $dbPath = $dbDir . '/test_bus_pass.db';

    if (file_exists($dbPath)) {
        unlink($dbPath);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Create schema (mirrors main schema but in a test DB)
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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS test_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            operation TEXT NOT NULL,
            success INTEGER NOT NULL DEFAULT 0,
            payload TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $results = [];

    try {
        // Insert locations
        $stmt = $pdo->prepare('INSERT INTO locations (name, description) VALUES (:n, :d)');
        $stmt->execute([':n' => 'Alpha', ':d' => 'Source city']);
        $stmt->execute([':n' => 'Beta', ':d' => 'Destination city']);
        $results[] = ['op' => 'insert_locations', 'ok' => true, 'data' => ['Alpha', 'Beta']];

        // Create route
        $stmt = $pdo->prepare('INSERT INTO routes (route_name, source_id, destination_id, distance_km) VALUES (:r, :s, :d, :dist)');
        $stmt->execute([':r' => 'R1', ':s' => 1, ':d' => 2, ':dist' => 12.5]);
        $results[] = ['op' => 'create_route', 'ok' => true, 'data' => ['route_id' => (int)$pdo->lastInsertId()]];

        // Add bus
        $stmt = $pdo->prepare('INSERT INTO buses (bus_number, route_id, capacity) VALUES (:bn, :rid, :cap)');
        $stmt->execute([':bn' => 'BUS-100', ':rid' => 1, ':cap' => 50]);
        $results[] = ['op' => 'create_bus', 'ok' => true, 'data' => ['bus_id' => (int)$pdo->lastInsertId()]];

        // Add bus stops
        $stmt = $pdo->prepare('INSERT INTO bus_stops (route_id, stop_name, stop_order) VALUES (:rid, :sname, :sorder)');
        $stmt->execute([':rid' => 1, ':sname' => 'Stop A', ':sorder' => 1]);
        $stmt->execute([':rid' => 1, ':sname' => 'Stop B', ':sorder' => 2]);
        $results[] = ['op' => 'create_stops', 'ok' => true, 'data' => ['stops' => ['Stop A', 'Stop B']]];

        // Update route status
        $stmt = $pdo->prepare('UPDATE routes SET status = :st WHERE id = :id');
        $stmt->execute([':st' => 'inactive', ':id' => 1]);
        $results[] = ['op' => 'update_route_status', 'ok' => true, 'data' => ['route_id' => 1, 'status' => 'inactive']];

        // Query with filter
        $stmt = $pdo->prepare('SELECT r.*, ls.name as source_name, ld.name as dest_name FROM routes r JOIN locations ls ON ls.id = r.source_id JOIN locations ld ON ld.id = r.destination_id WHERE r.status = :st');
        $stmt->execute([':st' => 'inactive']);
        $routes = $stmt->fetchAll();
        $results[] = ['op' => 'query_routes_filtered', 'ok' => true, 'data' => $routes];

        // Delete bus
        $stmt = $pdo->prepare('DELETE FROM buses WHERE id = :id');
        $stmt->execute([':id' => 1]);
        $results[] = ['op' => 'delete_bus', 'ok' => true, 'data' => ['deleted_bus_id' => 1]];

    } catch (Throwable $e) {
        $results[] = ['op' => 'exception', 'ok' => false, 'data' => $e->getMessage()];
    }

    // Persist results into test_results table
    $ins = $pdo->prepare('INSERT INTO test_results (operation, success, payload) VALUES (:op, :s, :p)');
    foreach ($results as $r) {
        $ins->execute([':op' => $r['op'], ':s' => $r['ok'] ? 1 : 0, ':p' => json_encode($r['data'])]);
    }

    // Output summary
    $rows = $pdo->query('SELECT * FROM test_results ORDER BY id')->fetchAll();

    echo "Test DB path: " . $dbPath . PHP_EOL . PHP_EOL;
    echo "Results:\n";
    foreach ($rows as $row) {
        echo sprintf("%d | %s | %s | %s\n", $row['id'], $row['operation'], $row['success'] ? 'OK' : 'FAIL', $row['payload']);
    }

    return 0;
}

try {
    exit(run());
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
