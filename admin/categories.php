<?php
/**
 * Discount Category CRUD Management Portal
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/db.php';

require_admin();

$error = '';
$success = '';
$db = Database::connect();

// Handle Delete Request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        // Prevent deletion if categories are currently associated with passes
        $stmt = $db->prepare("SELECT COUNT(*) FROM passes WHERE category_id = :id");
        $stmt->execute(['id' => $delete_id]);
        if ($stmt->fetchColumn() > 0) {
            set_flash_message('error', 'Cannot delete category. It is currently associated with active passenger passes.');
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            set_flash_message('success', 'Category successfully removed.');
        }
    } catch (Exception $e) {
        set_flash_message('error', 'Error deleting category: ' . $e->getMessage());
    }
    redirect('/Bus-pass-managemnet/admin/categories.php');
}

// Handle Add/Insert Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $discount_percentage = floatval($_POST['discount_percentage'] ?? 0.00);
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || $discount_percentage < 0 || $discount_percentage > 100) {
        $error = 'Category name is required. Discount must be a percentage between 0 and 100.';
    } else {
        try {
            // Verify unique name
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
            $stmt->execute(['name' => $name]);
            if ($stmt->fetch()) {
                $error = 'A category with this name already exists.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO categories (name, discount_percentage, description) 
                    VALUES (:name, :discount, :desc)
                ");
                $stmt->execute([
                    'name' => $name,
                    'discount' => $discount_percentage,
                    'desc' => $description
                ]);
                set_flash_message('success', 'New tier registered successfully.');
                redirect('/Bus-pass-managemnet/admin/categories.php');
            }
        } catch (Exception $e) {
            $error = 'Error saving category details: ' . $e->getMessage();
        }
    }
}

// Fetch all categories
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY discount_percentage DESC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Error listing categories.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1 class="text-gradient">Pass Tiers & Discounts</h1>
        <p style="color: var(--color-text-muted);">Manage ticket tiers, specific passenger classifications, and discount rules.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addCatModal')"><i class="fas fa-plus"></i> Register Tier</button>
</div>

<?php if ($error): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--border-radius-md); padding: 12px; color: var(--color-danger); font-size: 14px; margin-bottom: 20px; text-align: center;">
        <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- Categories Grid Table -->
<div class="glass-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Discount Percentage</th>
                    <th>Description</th>
                    <th style="text-align: right;">Operations</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--color-text-muted);">No categories defined.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?= e($cat['name']) ?></strong></td>
                            <td>
                                <?php if ($cat['discount_percentage'] > 0): ?>
                                    <span class="badge badge-success"><?= e(number_format($cat['discount_percentage'], 0)) ?>% Off</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Full Fare (0%)</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--color-text-muted); font-size: 13px; max-width: 400px;"><?= e($cat['description']) ?></td>
                            <td style="text-align: right;">
                                <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Are you sure you want to delete category <?= e($cat['name']) ?>?');">
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

<!-- Add Category Modal Dialog -->
<div id="addCatModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Register New Pass Tier</h3>
            <span class="modal-close" onclick="closeModal('addCatModal')">&times;</span>
        </div>
        <form action="categories.php" method="POST">
            <input type="hidden" name="add_category" value="1">
            <div class="modal-body">
                <div class="form-group">
                    <label for="name" class="form-label">Category Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Employee Special" required>
                </div>
                
                <div class="form-group">
                    <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                    <input type="number" step="0.1" min="0" max="100" id="discount_percentage" name="discount_percentage" class="form-control" placeholder="e.g. 25.0" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description / Eligibility Details</label>
                    <textarea id="description" name="description" rows="3" class="form-control" placeholder="Define who qualifies and proof required..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCatModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Register Tier</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
