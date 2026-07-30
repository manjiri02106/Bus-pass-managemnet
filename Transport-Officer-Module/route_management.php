<?php
// route_management.php - Manage Bus Routes for Transport Officer Module
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce access for Officer or Admin
check_auth(['officer', 'admin']);

$message = '';
$messageType = 'success';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            
            // Check if route is used in applications
            $appStmt = $pdo->prepare('SELECT COUNT(*) FROM applications WHERE route_id = ?');
            $appStmt->execute([$id]);
            $appCount = (int) $appStmt->fetchColumn();
            if ($appCount > 0) {
                throw new Exception("Cannot delete route: $appCount application(s) are linked to this route.");
            }
            
            $pdo->prepare('DELETE FROM routes WHERE id = ?')->execute([$id]);
            $message = 'Route deleted successfully.';
        } elseif ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $routeNumber = trim($_POST['route_number'] ?? '');
            $source = trim($_POST['source'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $stops = trim($_POST['stops'] ?? '');
            $distance = (float) ($_POST['distance'] ?? 0);
            $busNumber = trim($_POST['bus_number'] ?? '');
            $status = trim($_POST['status'] ?? 'active');

            if ($routeNumber === '' || $source === '' || $destination === '') {
                throw new Exception('Route Number, Source, and Destination are required.');
            }

            if ($id > 0) {
                // Update existing route
                $pdo->prepare(
                    'UPDATE routes SET route_number = ?, source = ?, destination = ?, stops = ?, distance = ?, bus_number = ?, status = ? WHERE id = ?'
                )->execute([$routeNumber, $source, $destination, $stops, $distance, $busNumber, $status, $id]);
                $message = 'Route updated successfully.';
            } else {
                // Insert new route
                $pdo->prepare(
                    'INSERT INTO routes (route_number, source, destination, stops, distance, bus_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([$routeNumber, $source, $destination, $stops, $distance, $busNumber, $status]);
                $message = 'Route created successfully.';
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Check if editing
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM routes WHERE id = ?');
    $stmt->execute([$editingId]);
    $editing = $stmt->fetch();
}

// Load stops from Student-Dashboard stops.json
$stopsJsonPath = __DIR__ . '/../Student-Dashboard/data/stops.json';
$locations = [];
if (file_exists($stopsJsonPath)) {
    $stopsData = json_decode(file_get_contents($stopsJsonPath), true);
    if (is_array($stopsData)) {
        foreach ($stopsData as $stop) {
            $locations[] = $stop['name'];
        }
        sort($locations); // Alphabetical sort for dropdown
    }
}

// Handle search and filters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(route_number LIKE ? OR source LIKE ? OR destination LIKE ?)';
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}
if ($statusFilter !== '') {
    $where[] = 'status = ?';
    $params[] = $statusFilter;
}

$sql = 'SELECT * FROM routes WHERE ' . implode(' AND ', $where) . ' ORDER BY route_number';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$routes = $stmt->fetchAll();

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Bus Route Management</h2>
    <p class="page-subtitle">Manage routes, source, destinations, and assigned buses.</p>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= $editing ? 'Edit Route' : 'Add New Route' ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="route_management.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Route Number <span class="text-danger">*</span></label>
                    <input type="text" name="route_number" class="form-control" value="<?= htmlspecialchars($editing['route_number'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Source Stop <span class="text-danger">*</span></label>
                    <select name="source" class="form-select" required>
                        <option value="">Select source</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= ($editing && $editing['source'] === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                        <?php if ($editing && !in_array($editing['source'], $locations)): ?>
                            <option value="<?= htmlspecialchars($editing['source']) ?>" selected><?= htmlspecialchars($editing['source']) ?> (Custom)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Destination Stop <span class="text-danger">*</span></label>
                    <select name="destination" class="form-select" required>
                        <option value="">Select destination</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= ($editing && $editing['destination'] === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                        <?php endforeach; ?>
                        <?php if ($editing && !in_array($editing['destination'], $locations)): ?>
                            <option value="<?= htmlspecialchars($editing['destination']) ?>" selected><?= htmlspecialchars($editing['destination']) ?> (Custom)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Distance (km)</label>
                    <input type="number" step="0.1" name="distance" class="form-control" value="<?= htmlspecialchars($editing['distance'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Intermediate Stops / Via</label>
                    <input type="text" name="stops" class="form-control" value="<?= htmlspecialchars($editing['stops'] ?? '') ?>" placeholder="e.g. Stop A, Stop B">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bus Number</label>
                    <input type="text" name="bus_number" class="form-control" value="<?= htmlspecialchars($editing['bus_number'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($editing && $editing['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editing && $editing['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><?= $editing ? 'Update Route' : 'Add Route' ?></button>
                </div>
            </div>
            <?php if ($editing): ?>
                <div class="mt-3">
                    <a href="route_management.php" class="btn btn-sm btn-outline-secondary">Cancel Edit</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Existing Routes</h5>
        <form method="get" action="route_management.php" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search routes..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            <?php if ($search || $statusFilter): ?>
                <a href="route_management.php" class="btn btn-sm btn-outline-danger">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Route No.</th>
                        <th>Path</th>
                        <th>Distance</th>
                        <th>Bus No.</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($routes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No routes found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($routes as $route): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($route['route_number']) ?></td>
                                <td>
                                    <div><strong><?= htmlspecialchars($route['source']) ?></strong> <i class="bi bi-arrow-right mx-1"></i> <strong><?= htmlspecialchars($route['destination']) ?></strong></div>
                                    <?php if ($route['stops']): ?>
                                        <small class="text-muted">Via: <?= htmlspecialchars($route['stops']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($route['distance']) ?> km</td>
                                <td><?= htmlspecialchars($route['bus_number']) ?></td>
                                <td>
                                    <?php if ($route['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="?edit=<?= $route['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <form method="post" action="route_management.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this route?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $route['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
