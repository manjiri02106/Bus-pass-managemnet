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
            $pdo->prepare('DELETE FROM buses WHERE id = :id')->execute([':id' => $id]);
            $message = 'Bus deleted.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $busNumber = trim((string) ($_POST['bus_number'] ?? ''));
            $routeId = (int) ($_POST['route_id'] ?? 0);
            $capacity = (int) ($_POST['capacity'] ?? 45);
            $status = trim((string) ($_POST['status'] ?? 'active'));
            $notes = trim((string) ($_POST['notes'] ?? ''));

            if ($busNumber === '' || $routeId <= 0) {
                throw new Exception('Bus number and route are required.');
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE buses SET bus_number = :bus_number, route_id = :route_id, capacity = :capacity, status = :status, notes = :notes WHERE id = :id')->execute([
                    ':bus_number' => $busNumber,
                    ':route_id' => $routeId,
                    ':capacity' => $capacity,
                    ':status' => $status,
                    ':notes' => $notes,
                    ':id' => $id,
                ]);
                $message = 'Bus updated.';
            } else {
                $pdo->prepare('INSERT INTO buses (bus_number, route_id, capacity, status, notes) VALUES (:bus_number, :route_id, :capacity, :status, :notes)')->execute([
                    ':bus_number' => $busNumber,
                    ':route_id' => $routeId,
                    ':capacity' => $capacity,
                    ':status' => $status,
                    ':notes' => $notes,
                ]);
                $message = 'Bus created.';
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
    $editing = $pdo->prepare('SELECT * FROM buses WHERE id = :id');
    $editing->execute([':id' => $editingId]);
    $editing = $editing->fetch();
}

$routes = $pdo->query('SELECT id, route_name FROM routes ORDER BY route_name')->fetchAll();
$buses = $pdo->query('SELECT b.*, r.route_name FROM buses b JOIN routes r ON b.route_id = r.id ORDER BY b.bus_number')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="routes.php">Routes</a>
        <a href="buses.php" class="active">Buses</a>
        <a href="locations.php">Sources & Destinations</a>
        <a href="stops.php">Bus Stops</a>
        <a href="status.php">Route Status</a>
    </nav>

    <main>
        <h1>Bus CRUD</h1>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?= $editing ? 'Edit Bus' : 'Add Bus' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
                <div class="form-row">
                    <label>
                        Bus number
                        <input type="text" name="bus_number" value="<?= htmlspecialchars((string) ($editing['bus_number'] ?? '')) ?>" required>
                    </label>
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
                        Capacity
                        <input type="number" name="capacity" value="<?= htmlspecialchars((string) ($editing['capacity'] ?? '45')) ?>" required>
                    </label>
                    <label>
                        Status
                        <select name="status">
                            <option value="active" <?= ($editing && (string) $editing['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editing && (string) $editing['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="maintenance" <?= ($editing && (string) $editing['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </label>
                </div>
                <label style="margin-top: 1rem;">
                    Notes
                    <textarea name="notes" rows="3"><?= htmlspecialchars((string) ($editing['notes'] ?? '')) ?></textarea>
                </label>
                <div style="margin-top: 1rem; display: flex; gap: 0.6rem;">
                    <button type="submit">Save</button>
                    <?php if ($editing): ?>
                        <a href="buses.php"><button type="button" class="secondary">Cancel</button></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Bus List</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Bus number</th>
                            <th>Route</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $bus): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $bus['bus_number']) ?></td>
                                <td><?= htmlspecialchars((string) $bus['route_name']) ?></td>
                                <td><?= (int) $bus['capacity'] ?></td>
                                <td><span class="badge <?= htmlspecialchars((string) $bus['status']) ?>"><?= htmlspecialchars((string) $bus['status']) ?></span></td>
                                <td><?= htmlspecialchars((string) $bus['notes']) ?></td>
                                <td>
                                    <a href="buses.php?edit=<?= (int) $bus['id'] ?>">Edit</a>
                                    |
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $bus['id'] ?>">
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
