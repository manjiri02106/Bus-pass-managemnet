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
            
            // Check if route has any buses assigned
            $busStmt = $pdo->prepare('SELECT COUNT(*) FROM buses WHERE route_id = :id');
            $busStmt->execute([':id' => $id]);
            $busCount = (int) $busStmt->fetchColumn();
            if ($busCount > 0) {
                throw new Exception('Cannot delete route: ' . $busCount . ' bus(es) are assigned to this route. Please reassign or delete buses first.');
            }
            
            $pdo->prepare('DELETE FROM routes WHERE id = :id')->execute([':id' => $id]);
            $message = 'Route deleted.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $routeName = trim((string) ($_POST['route_name'] ?? ''));
            $sourceId = (int) ($_POST['source_id'] ?? 0);
            $destinationId = (int) ($_POST['destination_id'] ?? 0);
            $distanceKm = (float) ($_POST['distance_km'] ?? 0);
            $status = trim((string) ($_POST['status'] ?? 'active'));
            $notes = trim((string) ($_POST['notes'] ?? ''));

            if ($routeName === '' || $sourceId <= 0 || $destinationId <= 0) {
                throw new Exception('Route name, source, and destination are required.');
            }

            // Validate that source and destination locations exist
            $sourceStmt = $pdo->prepare('SELECT 1 FROM locations WHERE id = :id');
            $sourceStmt->execute([':id' => $sourceId]);
            $sourceExists = (bool) $sourceStmt->fetchColumn();

            $destStmt = $pdo->prepare('SELECT 1 FROM locations WHERE id = :id');
            $destStmt->execute([':id' => $destinationId]);
            $destExists = (bool) $destStmt->fetchColumn();

            if (!$sourceExists) {
                throw new Exception('Selected source location does not exist.');
            }
            if (!$destExists) {
                throw new Exception('Selected destination location does not exist.');
            }

            if ($id > 0) {
                $pdo->prepare(
                    'UPDATE routes SET route_name = :route_name, source_id = :source_id, destination_id = :destination_id, distance_km = :distance_km, status = :status, notes = :notes WHERE id = :id'
                )->execute([
                    ':route_name' => $routeName,
                    ':source_id' => $sourceId,
                    ':destination_id' => $destinationId,
                    ':distance_km' => $distanceKm,
                    ':status' => $status,
                    ':notes' => $notes,
                    ':id' => $id,
                ]);
                $message = 'Route updated.';
            } else {
                $pdo->prepare(
                    'INSERT INTO routes (route_name, source_id, destination_id, distance_km, status, notes) VALUES (:route_name, :source_id, :destination_id, :distance_km, :status, :notes)'
                )->execute([
                    ':route_name' => $routeName,
                    ':source_id' => $sourceId,
                    ':destination_id' => $destinationId,
                    ':distance_km' => $distanceKm,
                    ':status' => $status,
                    ':notes' => $notes,
                ]);
                $message = 'Route created.';
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
    $editing = $pdo->prepare('SELECT * FROM routes WHERE id = :id');
    $editing->execute([':id' => $editingId]);
    $editing = $editing->fetch();
}

$locations = $pdo->query('SELECT id, name FROM locations ORDER BY name')->fetchAll();

if (empty($locations)) {
    $message = 'No locations found. Please add locations first before creating routes.';
    $messageType = 'warning';
}

$search = trim((string) ($_GET['search'] ?? ''));
$sourceFilter = (int) ($_GET['source_id'] ?? 0);
$destinationFilter = (int) ($_GET['destination_id'] ?? 0);
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(r.route_name LIKE :search OR s.name LIKE :search OR d.name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($sourceFilter > 0) {
    $where[] = 'r.source_id = :source_id';
    $params[':source_id'] = $sourceFilter;
}
if ($destinationFilter > 0) {
    $where[] = 'r.destination_id = :destination_id';
    $params[':destination_id'] = $destinationFilter;
}
if ($statusFilter !== '') {
    $where[] = 'r.status = :status';
    $params[':status'] = $statusFilter;
}

$sql = 'SELECT r.*, s.name AS source_name, d.name AS destination_name FROM routes r JOIN locations s ON r.source_id = s.id JOIN locations d ON r.destination_id = d.id';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY r.route_name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$routes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="routes.php" class="active">Routes</a>
        <a href="buses.php">Buses</a>
        <a href="locations.php">Sources & Destinations</a>
        <a href="stops.php">Bus Stops</a>
        <a href="status.php">Route Status</a>
    </nav>

    <main>
        <h1>Route CRUD and Search</h1>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?= $editing ? 'Edit Route' : 'Add Route' ?></h2>
            <form method="post" <?= empty($locations) ? 'disabled style="opacity: 0.5; pointer-events: none;"' : '' ?>>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
                <div class="form-row">
                    <label>
                        Route name
                        <input type="text" name="route_name" value="<?= htmlspecialchars((string) ($editing['route_name'] ?? '')) ?>" required <?= empty($locations) ? 'disabled' : '' ?>>
                    </label>
                    <label>
                        Source
                        <select name="source_id" required <?= empty($locations) ? 'disabled' : '' ?>>
                            <option value="">Select source</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= (int) $location['id'] ?>" <?= ($editing && (int) $editing['source_id'] === (int) $location['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $location['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Destination
                        <select name="destination_id" required <?= empty($locations) ? 'disabled' : '' ?>>
                            <option value="">Select destination</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= (int) $location['id'] ?>" <?= ($editing && (int) $editing['destination_id'] === (int) $location['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $location['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Distance (km)
                        <input type="number" step="0.1" name="distance_km" value="<?= htmlspecialchars((string) ($editing['distance_km'] ?? '')) ?>" required <?= empty($locations) ? 'disabled' : '' ?>>
                    </label>
                    <label>
                        Status
                        <select name="status" <?= empty($locations) ? 'disabled' : '' ?>>
                            <option value="active" <?= ($editing && (string) $editing['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editing && (string) $editing['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            <option value="maintenance" <?= ($editing && (string) $editing['status'] === 'maintenance') ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </label>
                </div>
                <label style="margin-top: 1rem;">
                    Notes
                    <textarea name="notes" rows="3" <?= empty($locations) ? 'disabled' : '' ?>><?= htmlspecialchars((string) ($editing['notes'] ?? '')) ?></textarea>
                </label>
                <div style="margin-top: 1rem; display: flex; gap: 0.6rem;">
                    <button type="submit" <?= empty($locations) ? 'disabled' : '' ?>>Save</button>
                    <?php if ($editing): ?>
                        <a href="routes.php"><button type="button" class="secondary" <?= empty($locations) ? 'disabled' : '' ?>>Cancel</button></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Search & Filters</h2>
            <form method="get" class="filters">
                <label>
                    Search
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
                </label>
                <label>
                    Source
                    <select name="source_id">
                        <option value="">All</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= (int) $location['id'] ?>" <?= $sourceFilter === (int) $location['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $location['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Destination
                    <select name="destination_id">
                        <option value="">All</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= (int) $location['id'] ?>" <?= $destinationFilter === (int) $location['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $location['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="maintenance" <?= $statusFilter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </label>
                <button type="submit">Apply</button>
            </form>
        </section>

        <section class="card">
            <h2>Route List</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Distance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $route['route_name']) ?></td>
                                <td><?= htmlspecialchars((string) $route['source_name']) ?></td>
                                <td><?= htmlspecialchars((string) $route['destination_name']) ?></td>
                                <td><?= (float) $route['distance_km'] ?> km</td>
                                <td><span class="badge <?= htmlspecialchars((string) $route['status']) ?>"><?= htmlspecialchars((string) $route['status']) ?></span></td>
                                <td>
                                    <a href="routes.php?edit=<?= (int) $route['id'] ?>">Edit</a>
                                    |
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $route['id'] ?>">
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
