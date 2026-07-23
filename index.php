<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();

$locationCount = (int) $pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn();
$routeCount = (int) $pdo->query('SELECT COUNT(*) FROM routes')->fetchColumn();
$busCount = (int) $pdo->query('SELECT COUNT(*) FROM buses')->fetchColumn();
$stopCount = (int) $pdo->query('SELECT COUNT(*) FROM bus_stops')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php" class="active">Dashboard</a>
        <a href="routes.php">Routes</a>
        <a href="buses.php">Buses</a>
        <a href="locations.php">Sources & Destinations</a>
        <a href="stops.php">Bus Stops</a>
        <a href="status.php">Route Status</a>
    </nav>

    <main>
        <h1>Bus Pass Management Dashboard</h1>
        <p>Manage routes, buses, locations, stops, and route status from one place.</p>

        <section class="grid">
            <div class="card stat">
                <h3>Routes</h3>
                <p><?= $routeCount ?></p>
            </div>
            <div class="card stat">
                <h3>Buses</h3>
                <p><?= $busCount ?></p>
            </div>
            <div class="card stat">
                <h3>Locations</h3>
                <p><?= $locationCount ?></p>
            </div>
            <div class="card stat">
                <h3>Stops</h3>
                <p><?= $stopCount ?></p>
            </div>
        </section>

        <section class="card">
            <h2>Quick actions</h2>
            <ul>
                <li><a href="routes.php">Create or manage routes</a></li>
                <li><a href="buses.php">Assign buses to routes</a></li>
                <li><a href="locations.php">Add source and destination locations</a></li>
                <li><a href="stops.php">Manage route bus stops</a></li>
            </ul>
        </section>
    </main>
</body>
</html>
