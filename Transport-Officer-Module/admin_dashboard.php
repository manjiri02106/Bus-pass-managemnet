<?php
// admin_dashboard.php - Admin Portal Dashboard

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce admin role access
check_auth(['admin']);

try {
    // Fetch stats
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_routes = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    $total_passes = $pdo->query("SELECT COUNT(*) FROM passes")->fetchColumn();
    $pending_apps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted', 'under_verification')")->fetchColumn();
    
    // Fetch routes list
    $routes = $pdo->query("SELECT * FROM routes")->fetchAll();
    
    // Fetch users list
    $users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Admin Dashboard</h2>
    <p class="page-subtitle">Portal wide configuration, student list management, and active bus routes.</p>
</div>

<!-- Stat Counters -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Total Students</span>
        <span class="stat-value"><?php echo $total_students; ?></span>
    </div>
    <div class="stat-card success">
        <span class="stat-label">Active Routes</span>
        <span class="stat-value"><?php echo $total_routes; ?></span>
    </div>
    <div class="stat-card warning">
        <span class="stat-label">Pending Reviews</span>
        <span class="stat-value"><?php echo $pending_apps; ?></span>
    </div>
    <div class="stat-card success">
        <span class="stat-label">Total Issued Passes</span>
        <span class="stat-value"><?php echo $total_passes; ?></span>
    </div>
</div>

<div class="detail-grid" style="grid-template-columns: 1fr 1fr;">
    <!-- Manage Routes -->
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title"><i class="fa-solid fa-route" style="color: var(--accent); margin-right: 8px;"></i> Manage Bus Routes</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Route No</th>
                    <th>Stops</th>
                    <th>Distance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($route['route_number']); ?></strong></td>
                        <td><span style="font-size:0.8rem;"><?php echo htmlspecialchars($route['stops']); ?></span></td>
                        <td><?php echo htmlspecialchars($route['distance']); ?> km</td>
                        <td>
                            <span class="badge <?php echo ($route['status'] == 'active') ? 'badge-approved' : 'badge-rejected'; ?>">
                                <?php echo htmlspecialchars($route['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Manage Users -->
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title"><i class="fa-solid fa-users" style="color: var(--accent); margin-right: 8px;"></i> System Users</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="user-role" style="background: <?php 
                                if ($user['role'] == 'admin') echo 'var(--danger)'; 
                                elseif ($user['role'] == 'officer') echo 'var(--accent)';
                                else echo 'var(--bg-tertiary)';
                            ?>;">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
include __DIR__ . '/footer.php';
?>
