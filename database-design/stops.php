<?php
require_once __DIR__ . '/db.php';

$pdo = get_db();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM bus_stops WHERE id = :id')->execute([':id' => $id]);
            $message = 'Stop deleted.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $routeId = (int) ($_POST['route_id'] ?? 0);
            $stopName = trim((string) ($_POST['stop_name'] ?? ''));
            $stopOrder = (int) ($_POST['stop_order'] ?? 1);

            if ($routeId <= 0 || $stopName === '') {
                throw new Exception('Route and stop name are required.');
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE bus_stops SET route_id = :route_id, stop_name = :stop_name, stop_order = :stop_order WHERE id = :id')->execute([
                    ':route_id' => $routeId,
                    ':stop_name' => $stopName,
                    ':stop_order' => $stopOrder,
                    ':id' => $id,
                ]);
                $message = 'Stop updated.';
            } else {
                $pdo->prepare('INSERT INTO bus_stops (route_id, stop_name, stop_order) VALUES (:route_id, :stop_name, :stop_order)')->execute([
                    ':route_id' => $routeId,
                    ':stop_name' => $stopName,
                    ':stop_order' => $stopOrder,
                ]);
                $message = 'Stop created.';
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
if ($editingId > 0) {
    $editing = $pdo->prepare('SELECT * FROM bus_stops WHERE id = :id');
    $editing->execute([':id' => $editingId]);
    $editing = $editing->fetch();
}

$routes = $pdo->query('SELECT id, route_name FROM routes ORDER BY route_name')->fetchAll();
$stops = $pdo->query('SELECT bs.*, r.route_name FROM bus_stops bs JOIN routes r ON bs.route_id = r.id ORDER BY r.route_name, bs.stop_order')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Stops</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="routes.php">Routes</a>
        <a href="buses.php">Buses</a>
        <a href="locations.php">Sources & Destinations</a>
        <a href="stops.php" class="active">Bus Stops</a>
        <a href="status.php">Route Status</a>
    </nav>

    <main>
        <h1>Bus Stops Management</h1>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?= $editing ? 'Edit Stop' : 'Add Stop' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
                <div class="form-row">
                    <label>
                        Route
                        <select name="route_id" required>
                            <option value="">Select route</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= (int) $route['id'] ?>" <?= ($editing && (int) $editing['route_id'] === (int) $route['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $route['route_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Stop name
                        <input type="text" name="stop_name" value="<?= htmlspecialchars((string) ($editing['stop_name'] ?? '')) ?>" required>
                    </label>
                    <label>
                        Stop order
                        <input type="number" name="stop_order" value="<?= htmlspecialchars((string) ($editing['stop_order'] ?? '1')) ?>" required>
                    </label>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.6rem;">
                    <button type="submit">Save</button>
                    <?php if ($editing): ?>
                        <a href="stops.php"><button type="button" class="secondary">Cancel</button></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Route Stops</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Stop name</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stops as $stop): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $stop['route_name']) ?></td>
                                <td><?= htmlspecialchars((string) $stop['stop_name']) ?></td>
                                <td><?= (int) $stop['stop_order'] ?></td>
                                <td>
                                    <a href="stops.php?edit=<?= (int) $stop['id'] ?>">Edit</a>
                                    |
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $stop['id'] ?>">
                                        <button class="danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
