<?php
// admin-dashboard/settings.php
require_once __DIR__ . '/../auth/config/session.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../Transport-Officer-Module/db.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $fields = ['site_name', 'maintenance_mode', 'academic_year', 'email_notifications'];
        foreach ($fields as $field) {
            $val = $_POST[$field] ?? '';
            $stmt->execute([$field, $val, $val]);
        }
        $message = "Settings saved successfully.";
    } catch (Exception $e) {
        $message = "Error saving settings: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// Load settings
$settings = [];
$rows = $pdo->query("SELECT * FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">System Settings</h2>
    <p class="page-subtitle">Configure portal preferences, academic year, and maintenance mode.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert" style="margin: 20px;">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="float:right; border:none; background:transparent; font-size:1.2rem;">&times;</button>
    </div>
<?php endif; ?>

<div class="detail-grid" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title">General Configuration</h3>
        </div>
        <div style="padding: 20px;">
            <form method="post" action="settings.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Portal Name</label>
                        <input type="text" name="site_name" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                        <span style="font-size:0.8rem; color:#666;">Displayed on the login and dashboard screens.</span>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Current Academic Year</label>
                        <input type="text" name="academic_year" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($settings['academic_year'] ?? '') ?>">
                        <span style="font-size:0.8rem; color:#666;">Example: 2026-2027</span>
                    </div>
                </div>

                <div class="table-header-row" style="margin-top: 30px; margin-bottom: 20px;">
                    <h3 class="table-title">System Controls</h3>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Maintenance Mode</label>
                        <select name="maintenance_mode" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                            <option value="Off" <?= ($settings['maintenance_mode'] ?? '') === 'Off' ? 'selected' : '' ?>>Off (Live)</option>
                            <option value="On" <?= ($settings['maintenance_mode'] ?? '') === 'On' ? 'selected' : '' ?>>On (Suspended)</option>
                        </select>
                        <span style="font-size:0.8rem; color:#666;">If ON, students will not be able to apply for passes.</span>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Email Notifications</label>
                        <select name="email_notifications" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                            <option value="On" <?= ($settings['email_notifications'] ?? '') === 'On' ? 'selected' : '' ?>>Enabled</option>
                            <option value="Off" <?= ($settings['email_notifications'] ?? '') === 'Off' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                        <span style="font-size:0.8rem; color:#666;">Toggle automatic email dispatch to students.</span>
                    </div>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <button type="submit" class="btn-primary" style="padding: 12px 30px; font-size: 1rem;"><i class="fa-solid fa-save"></i> Save Configurations</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
