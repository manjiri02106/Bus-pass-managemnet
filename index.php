<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Admin Dashboard';
$stats = dashboard_stats();
page_header($pageTitle, 'Dashboard');
?>
<h2>Admin Dashboard</h2>
<p>Central control for users, students, and portal configuration.</p>
<div class="cards">
    <div class="card">
        <h3>Total Users</h3>
        <p class="value"><?php echo $stats['users']; ?></p>
    </div>
    <div class="card">
        <h3>Registered Students</h3>
        <p class="value"><?php echo $stats['students']; ?></p>
    </div>
    <div class="card">
        <h3>Portal Name</h3>
        <p class="value"><?php echo htmlspecialchars($stats['site_name']); ?></p>
    </div>
    <div class="card">
        <h3>Maintenance Mode</h3>
        <p class="value"><?php echo htmlspecialchars($stats['maintenance']); ?></p>
    </div>
</div>
<div class="panel">
    <h3>Quick Actions</h3>
    <ul>
        <li><a href="users.php">Manage system users</a></li>
        <li><a href="students.php">Manage student records</a></li>
        <li><a href="settings.php">Update system and academic settings</a></li>
    </ul>
</div>
<?php page_footer(); ?>
