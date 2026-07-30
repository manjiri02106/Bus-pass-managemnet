<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $routeId = (int) ($_POST['route_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'active'));

        if ($routeId <= 0) {
            throw new Exception('Please select a route.');
        }

        $pdo->prepare('UPDATE routes SET status = :status WHERE id = :id')->execute([
            ':status' => $status,
            ':id' => $routeId,
        ]);
        $message = 'Route status updated.';
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

$routes = $pdo->query('SELECT id, route_name, status FROM routes ORDER BY route_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Status</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="routes.php">Routes</a>
        <a href="buses.php">Buses</a>
        <a href="locations.php">Sources & Destinations</a>
        <a href="stops.php">Bus Stops</a>
        <a href="status.php" class="active">Route Status</a>
    </nav>

    <main>
        <h1>Route Status Management</h1>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2>Update route status</h2>
            <form method="post" class="form-row">
                <label>
                    Route
                    <select name="route_id" required>
                        <option value="">Select route</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?= (int) $route['id'] ?>"><?= htmlspecialchars((string) $route['route_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </label>
                <label>
                    Action
                    <button type="submit">Update Status</button>
                </label>
            </form>
        </section>

        <section class="card">
            <h2>Current route status</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $route['route_name']) ?></td>
                                <td><span class="badge <?= htmlspecialchars((string) $route['status']) ?>"><?= htmlspecialchars((string) $route['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
