<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$settings = [
    'site_name' => get_setting('site_name', 'Bus Pass Admin'),
    'maintenance_mode' => get_setting('maintenance_mode', 'Off'),
    'academic_year' => get_setting('academic_year', '2026-2027'),
    'portal_theme' => get_setting('portal_theme', 'Light'),
    'email_notifications' => get_setting('email_notifications', 'On'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => trim($_POST['site_name'] ?? 'Bus Pass Admin'),
        'maintenance_mode' => trim($_POST['maintenance_mode'] ?? 'Off'),
        'academic_year' => trim($_POST['academic_year'] ?? '2026-2027'),
        'portal_theme' => trim($_POST['portal_theme'] ?? 'Light'),
        'email_notifications' => trim($_POST['email_notifications'] ?? 'On'),
    ];
    foreach ($settings as $key => $value) {
        save_setting($key, $value);
    }
    header('Location: settings.php');
    exit;
}

page_header('System Settings', 'System Settings');
?>
<h2>System Settings</h2>
<p>Configure portal identity, maintenance behavior, and academic defaults.</p>
<form method="post" class="form-grid">
    <label>
        Portal Name
        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'Bus Pass Admin'); ?>">
    </label>
    <label>
        Maintenance Mode
        <select name="maintenance_mode">
            <option value="Off" <?php echo (($settings['maintenance_mode'] ?? 'Off') === 'Off') ? 'selected' : ''; ?>>Off</option>
            <option value="On" <?php echo (($settings['maintenance_mode'] ?? 'Off') === 'On') ? 'selected' : ''; ?>>On</option>
        </select>
    </label>
    <label>
        Academic Year
        <input type="text" name="academic_year" value="<?php echo htmlspecialchars($settings['academic_year'] ?? '2026-2027'); ?>">
    </label>
    <label>
        Portal Theme
        <select name="portal_theme">
            <option value="Light" <?php echo (($settings['portal_theme'] ?? 'Light') === 'Light') ? 'selected' : ''; ?>>Light</option>
            <option value="Dark" <?php echo (($settings['portal_theme'] ?? 'Light') === 'Dark') ? 'selected' : ''; ?>>Dark</option>
        </select>
    </label>
    <label>
        Email Notifications
        <select name="email_notifications">
            <option value="On" <?php echo (($settings['email_notifications'] ?? 'On') === 'On') ? 'selected' : ''; ?>>On</option>
            <option value="Off" <?php echo (($settings['email_notifications'] ?? 'On') === 'Off') ? 'selected' : ''; ?>>Off</option>
        </select>
    </label>
    <button type="submit">Save Settings</button>
</form>
<div class="panel">
    <h3>Current Configuration</h3>
    <ul>
        <li><strong>Portal Name:</strong> <?php echo htmlspecialchars($settings['site_name'] ?? 'Bus Pass Admin'); ?></li>
        <li><strong>Maintenance Mode:</strong> <?php echo htmlspecialchars($settings['maintenance_mode'] ?? 'Off'); ?></li>
        <li><strong>Academic Year:</strong> <?php echo htmlspecialchars($settings['academic_year'] ?? '2026-2027'); ?></li>
        <li><strong>Theme:</strong> <?php echo htmlspecialchars($settings['portal_theme'] ?? 'Light'); ?></li>
        <li><strong>Notifications:</strong> <?php echo htmlspecialchars($settings['email_notifications'] ?? 'On'); ?></li>
    </ul>
</div>
<?php page_footer(); ?>
