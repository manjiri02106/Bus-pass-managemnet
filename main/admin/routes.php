<?php
/**
 * Route CRUD Management Portal
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/db.php';

require_admin();

$error   = '';
$success = '';
$db      = Database::connect();

// ── Handle Delete ──────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM passes WHERE route_id = :id");
        $stmt->execute(['id' => $delete_id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Cannot delete route — it is associated with existing passes.');
        } else {
            $stmt = $db->prepare("DELETE FROM routes WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            set_flash_message('success', 'Route successfully removed.');
        }
    } catch (Exception $e) {
        set_flash_message('error', 'Error deleting route: ' . $e->getMessage());
    }
    redirect('/Bus-pass-managemnet/admin/routes.php');
}

// ── Handle Add ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_route'])) {
    $route_code     = strtoupper(trim($_POST['route_code'] ?? ''));
    $source         = trim($_POST['source'] ?? '');
    $destination    = trim($_POST['destination'] ?? '');
    $standard_price = floatval($_POST['standard_price'] ?? 0.00);

    if (empty($route_code) || empty($source) || empty($destination) || $standard_price <= 0) {
        $error = 'All fields are required and price must be greater than zero.';
    } else {
        try {
            $stmt = $db->prepare("SELECT id FROM routes WHERE route_code = :code LIMIT 1");
            $stmt->execute(['code' => $route_code]);
            if ($stmt->fetch()) {
                $error = 'Route code already in use — must be unique.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO routes (route_code, source, destination, standard_price)
                    VALUES (:code, :source, :destination, :price)
                ");
                $stmt->execute([
                    'code'        => $route_code,
                    'source'      => $source,
                    'destination' => $destination,
                    'price'       => $standard_price,
                ]);
                set_flash_message('success', 'New route registered successfully.');
                redirect('/Bus-pass-managemnet/admin/routes.php');
            }
        } catch (Exception $e) {
            $error = 'Error saving route: ' . $e->getMessage();
        }
    }
}

// ── Handle Update ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_route'])) {
    $edit_id        = intval($_POST['edit_id'] ?? 0);
    $route_code     = strtoupper(trim($_POST['route_code'] ?? ''));
    $source         = trim($_POST['source'] ?? '');
    $destination    = trim($_POST['destination'] ?? '');
    $standard_price = floatval($_POST['standard_price'] ?? 0.00);

    if (!$edit_id || empty($route_code) || empty($source) || empty($destination) || $standard_price <= 0) {
        $error = 'All fields are required and price must be greater than zero.';
    } else {
        try {
            // Ensure code uniqueness excluding current row
            $stmt = $db->prepare("SELECT id FROM routes WHERE route_code = :code AND id != :id LIMIT 1");
            $stmt->execute(['code' => $route_code, 'id' => $edit_id]);
            if ($stmt->fetch()) {
                $error = 'Route code already in use by another route.';
            } else {
                $stmt = $db->prepare("
                    UPDATE routes
                    SET route_code = :code, source = :source, destination = :destination, standard_price = :price
                    WHERE id = :id
                ");
                $stmt->execute([
                    'code'        => $route_code,
                    'source'      => $source,
                    'destination' => $destination,
                    'price'       => $standard_price,
                    'id'          => $edit_id,
                ]);
                set_flash_message('success', 'Route updated successfully.');
                redirect('/Bus-pass-managemnet/admin/routes.php');
            }
        } catch (Exception $e) {
            $error = 'Error updating route: ' . $e->getMessage();
        }
    }
}

// ── Fetch all routes ───────────────────────────────────────────────────────────
try {
    $stmt   = $db->query("SELECT * FROM routes ORDER BY route_code ASC");
    $routes = $stmt->fetchAll();
} catch (Exception $e) {
    $error  = 'Error listing routes.';
    $routes = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap:wrap; gap:15px;">
    <div>
        <h1 class="text-gradient">Transit Routes Directory</h1>
        <p style="color:var(--color-text-muted);">Manage transport routes, pricing systems, and station lines.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addRouteModal')"><i class="fas fa-plus"></i> Register Route</button>
</div>

<?php if ($error): ?>
    <div style="background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:var(--border-radius-md); padding:12px; color:var(--color-danger); font-size:14px; margin-bottom:20px; text-align:center;">
        <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- Routes Table -->
<div class="glass-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Route Code</th>
                    <th>Source Location</th>
                    <th>Destination Location</th>
                    <th>Standard Price</th>
                    <th style="text-align:right;">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--color-text-muted);">No routes defined in database.</td></tr>
                <?php else: ?>
                    <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><strong><?= e($route['route_code']) ?></strong></td>
                            <td><?= e($route['source']) ?></td>
                            <td><?= e($route['destination']) ?></td>
                            <td><strong>$<?= number_format((float)$route['standard_price'], 2) ?></strong></td>
                            <td style="text-align:right; white-space:nowrap;">
                                <!-- Edit -->
                                <button class="btn btn-secondary" style="padding:5px 10px; font-size:12px; margin-right:6px;"
                                    data-id="<?= (int)$route['id'] ?>"
                                    data-code="<?= e($route['route_code']) ?>"
                                    data-source="<?= e($route['source']) ?>"
                                    data-destination="<?= e($route['destination']) ?>"
                                    data-price="<?= e($route['standard_price']) ?>"
                                    onclick="openEditRoute(this)">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                                <!-- Delete -->
                                <a href="routes.php?delete=<?= $route['id'] ?>" class="btn btn-danger" style="padding:5px 10px; font-size:12px;"
                                   onclick="return confirm('Delete route <?= e($route['route_code']) ?>? This cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Add Route Modal ───────────────────────────────────────────────────────── -->
<div id="addRouteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Register New Transit Route</h3>
            <span class="modal-close" onclick="closeModal('addRouteModal')">&times;</span>
        </div>
        <form action="routes.php" method="POST">
            <input type="hidden" name="add_route" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label for="route_code" class="form-label">Route Code</label>
                    <input type="text" id="route_code" name="route_code" class="form-control" placeholder="e.g. RT-110" required>
                </div>
                <div class="form-group">
                    <label for="source" class="form-label">Source Station</label>
                    <input type="text" id="source" name="source" class="form-control" placeholder="e.g. Grand Terminal" required>
                </div>
                <div class="form-group">
                    <label for="destination" class="form-label">Destination Station</label>
                    <input type="text" id="destination" name="destination" class="form-control" placeholder="e.g. Eastside Plaza" required>
                </div>
                <div class="form-group">
                    <label for="standard_price" class="form-label">Standard Fare Price ($)</label>
                    <input type="number" step="0.01" min="0.01" id="standard_price" name="standard_price" class="form-control" placeholder="e.g. 75.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addRouteModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Register Route</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit Route Modal ──────────────────────────────────────────────────────── -->
<div id="editRouteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Transit Route</h3>
            <span class="modal-close" onclick="closeModal('editRouteModal')">&times;</span>
        </div>
        <form action="routes.php" method="POST">
            <input type="hidden" name="update_route" value="1">
            <input type="hidden" name="edit_id" id="edit_route_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_route_code" class="form-label">Route Code</label>
                    <input type="text" id="edit_route_code" name="route_code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_source" class="form-label">Source Station</label>
                    <input type="text" id="edit_source" name="source" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_destination" class="form-label">Destination Station</label>
                    <input type="text" id="edit_destination" name="destination" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_standard_price" class="form-label">Standard Fare Price ($)</label>
                    <input type="number" step="0.01" min="0.01" id="edit_standard_price" name="standard_price" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editRouteModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditRoute(btn) {
    document.getElementById('edit_route_id').value       = btn.dataset.id;
    document.getElementById('edit_route_code').value     = btn.dataset.code;
    document.getElementById('edit_source').value         = btn.dataset.source;
    document.getElementById('edit_destination').value    = btn.dataset.destination;
    document.getElementById('edit_standard_price').value = btn.dataset.price;
    openModal('editRouteModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
