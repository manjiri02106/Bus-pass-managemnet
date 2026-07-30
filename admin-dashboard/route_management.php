<?php
// admin-dashboard/route_management.php - Manage Bus Routes for Admin
require_once __DIR__ . '/../auth/config/session.php';

// Enforce access for Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../Transport-Officer-Module/db.php';

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

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Bus Route Management</h2>
    <p class="page-subtitle">Manage routes, source, destinations, and assigned buses.</p>
</div>

<?php if ($message !== ''): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert" style="margin: 20px;">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="float:right; border:none; background:transparent; font-size:1.2rem;">&times;</button>
    </div>
<?php endif; ?>

<div class="detail-grid" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title"><?= $editing ? 'Edit Route' : 'Add New Route' ?></h3>
        </div>
        <div style="padding: 20px;">
            <form method="post" action="route_management.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Route Number <span style="color:red">*</span></label>
                        <input type="text" name="route_number" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['route_number'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Source Stop <span style="color:red">*</span></label>
                        <select name="source" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" required>
                            <option value="">Select source</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($editing && $editing['source'] === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                            <?php if ($editing && !in_array($editing['source'], $locations)): ?>
                                <option value="<?= htmlspecialchars($editing['source']) ?>" selected><?= htmlspecialchars($editing['source']) ?> (Custom)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Destination Stop <span style="color:red">*</span></label>
                        <select name="destination" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" required>
                            <option value="">Select destination</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= ($editing && $editing['destination'] === $loc) ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
                            <?php endforeach; ?>
                            <?php if ($editing && !in_array($editing['destination'], $locations)): ?>
                                <option value="<?= htmlspecialchars($editing['destination']) ?>" selected><?= htmlspecialchars($editing['destination']) ?> (Custom)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Distance (km)</label>
                        <input type="number" step="0.1" name="distance" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['distance'] ?? '') ?>">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Intermediate Stops / Via</label>
                        <input type="text" name="stops" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['stops'] ?? '') ?>" placeholder="e.g. Stop A, Stop B">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Bus Number</label>
                        <input type="text" name="bus_number" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['bus_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Status</label>
                        <select name="status" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                            <option value="active" <?= ($editing && $editing['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editing && $editing['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="padding: 10px 20px;"><?= $editing ? 'Update Route' : 'Add Route' ?></button>
                    <?php if ($editing): ?>
                        <a href="route_management.php" style="margin-left: 10px; color: #555; text-decoration: none;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="detail-grid mt-4" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="table-title">Existing Routes</h3>
            <form method="get" action="route_management.php" style="display: flex; gap: 10px;">
                <input type="text" name="search" style="padding:5px; border:1px solid #ccc; border-radius:4px;" placeholder="Search routes..." value="<?= htmlspecialchars($search) ?>">
                <select name="status" style="padding:5px; border:1px solid #ccc; border-radius:4px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="btn-primary" style="padding: 5px 15px;">Filter</button>
                <?php if ($search || $statusFilter): ?>
                    <a href="route_management.php" style="color: red; text-decoration: none; align-self: center;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Route No.</th>
                    <th>Path</th>
                    <th>Distance</th>
                    <th>Bus No.</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px;">No routes found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($route['route_number']) ?></strong></td>
                            <td>
                                <div><strong><?= htmlspecialchars($route['source']) ?></strong> &rarr; <strong><?= htmlspecialchars($route['destination']) ?></strong></div>
                                <?php if ($route['stops']): ?>
                                    <span style="font-size:0.8rem; color:#666;">Via: <?= htmlspecialchars($route['stops']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($route['distance']) ?> km</td>
                            <td><?= htmlspecialchars($route['bus_number']) ?></td>
                            <td>
                                <?php if ($route['status'] === 'active'): ?>
                                    <span class="badge badge-approved">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="?edit=<?= $route['id'] ?>" style="color: var(--accent); margin-right: 10px; text-decoration: none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                <form method="post" action="route_management.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this route?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $route['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:red; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
