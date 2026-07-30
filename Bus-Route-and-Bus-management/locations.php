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
            
            // Check if location is used in any routes
            $routeStmt = $pdo->prepare('SELECT COUNT(*) FROM routes WHERE source_id = :id OR destination_id = :id');
            $routeStmt->execute([':id' => $id]);
            $routeCount = (int) $routeStmt->fetchColumn();
            if ($routeCount > 0) {
                throw new Exception('Cannot delete location: ' . $routeCount . ' route(s) use this location. Please update or delete those routes first.');
            }
            
            $pdo->prepare('DELETE FROM locations WHERE id = :id')->execute([':id' => $id]);
            $message = 'Location deleted.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if ($name === '') {
                throw new Exception('Location name is required.');
            }

            if ($id > 0) {
                $pdo->prepare('UPDATE locations SET name = :name, description = :description WHERE id = :id')->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':id' => $id,
                ]);
                $message = 'Location updated.';
            } else {
                $pdo->prepare('INSERT INTO locations (name, description) VALUES (:name, :description)')->execute([
                    ':name' => $name,
                    ':description' => $description,
                ]);
                $message = 'Location created.';
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
    $editing = $pdo->prepare('SELECT * FROM locations WHERE id = :id');
    $editing->execute([':id' => $editingId]);
    $editing = $editing->fetch();
}

$locations = $pdo->query('SELECT * FROM locations ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sources & Destinations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="routes.php">Routes</a>
        <a href="buses.php">Buses</a>
        <a href="locations.php" class="active">Sources & Destinations</a>
        <a href="stops.php">Bus Stops</a>
        <a href="status.php">Route Status</a>
    </nav>

    <main>
        <h1>Source and Destination Management</h1>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="card">
            <h2><?= $editing ? 'Edit Location' : 'Add Location' ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
                <div class="form-row">
                    <label>
                        Name
                        <input type="text" name="name" value="<?= htmlspecialchars((string) ($editing['name'] ?? '')) ?>" required>
                    </label>
                    <label>
                        Description
                        <input type="text" name="description" value="<?= htmlspecialchars((string) ($editing['description'] ?? '')) ?>">
                    </label>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.6rem;">
                    <button type="submit">Save</button>
                    <?php if ($editing): ?>
                        <a href="locations.php"><button type="button" class="secondary">Cancel</button></a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Existing Locations</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $location): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $location['name']) ?></td>
                                <td><?= htmlspecialchars((string) $location['description']) ?></td>
                                <td>
                                    <a href="locations.php?edit=<?= (int) $location['id'] ?>">Edit</a>
                                    |
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $location['id'] ?>">
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
