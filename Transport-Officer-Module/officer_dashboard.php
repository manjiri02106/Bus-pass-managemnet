<?php
// officer_dashboard.php - Dashboard view for Transport Officers

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce Transport Officer access
check_auth(['officer']);

// Get current date bounds for "Approved Today" query
$today_start = date("Y-m-d 00:00:00");
$today_end = date("Y-m-d 23:59:59");

// Fetch statistics
try {
    // 1. Pending verification count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status IN ('submitted', 'under_verification')");
    $stmt->execute();
    $cnt_pending = $stmt->fetchColumn();

    // 2. Approved today count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'approved' AND last_updated BETWEEN ? AND ?");
    $stmt->execute([$today_start, $today_end]);
    $cnt_approved_today = $stmt->fetchColumn();

    // 3. Rejected count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'rejected'");
    $stmt->execute();
    $cnt_rejected = $stmt->fetchColumn();

    // 4. Correction required count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = 'correction_required'");
    $stmt->execute();
    $cnt_correction = $stmt->fetchColumn();
    
    // Determine view filter
    $view = $_GET['view'] ?? 'all';
    $where_clause = "1=1";
    $params = [];
    
    if ($view === 'pending') {
        $where_clause = "a.status IN ('submitted', 'under_verification')";
    } elseif ($view === 'approved') {
        $where_clause = "a.status = 'approved'";
    } elseif ($view === 'rejected') {
        $where_clause = "a.status = 'rejected'";
    } elseif ($view === 'corrections') {
        $where_clause = "a.status = 'correction_required'";
    }
    
    // Fetch applications matching criteria
    $query = "
        SELECT a.id, a.status, a.submission_date, a.routing_dept,
               s.prn_number, s.roll_number, u.full_name as student_name,
               r.route_number, r.source, r.destination, r.distance
        FROM applications a
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN routes r ON a.route_id = r.id
        WHERE $where_clause
        ORDER BY a.submission_date DESC
    ";
    
    $stmt_apps = $pdo->prepare($query);
    $stmt_apps->execute($params);
    $applications = $stmt_apps->fetchAll();

    // Fetch Route Statistics
    $route_stats_query = "
        SELECT r.route_number, r.source, r.destination, r.distance, COUNT(a.id) as student_count
        FROM routes r
        LEFT JOIN applications a ON r.id = a.route_id AND a.status = 'approved'
        GROUP BY r.id
    ";
    $route_stats = $pdo->query($route_stats_query)->fetchAll();

} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Transport Officer Dashboard</h2>
    <p class="page-subtitle">Verify pass requests, validate routing, check documents, and issue student bus passes.</p>
</div>

<!-- Stat Counters -->
<div class="stats-grid">
    <a href="officer_dashboard.php?view=pending" class="stat-card warning">
        <span class="stat-label">Pending Verification</span>
        <span class="stat-value"><?php echo $cnt_pending; ?></span>
    </a>
    <div class="stat-card success">
        <span class="stat-label">Approved Today</span>
        <span class="stat-value"><?php echo $cnt_approved_today; ?></span>
    </div>
    <a href="officer_dashboard.php?view=rejected" class="stat-card danger">
        <span class="stat-label">Rejected Applications</span>
        <span class="stat-value"><?php echo $cnt_rejected; ?></span>
    </a>
    <a href="officer_dashboard.php?view=corrections" class="stat-card warning">
        <span class="stat-label">Pending Corrections</span>
        <span class="stat-value"><?php echo $cnt_correction; ?></span>
    </a>
</div>

<!-- Main Table of Applications -->
<div class="table-container">
    <div class="table-header-row">
        <h3 class="table-title">
            <?php 
                if ($view === 'pending') echo "Applications Pending Verification";
                elseif ($view === 'approved') echo "Approved Applications";
                elseif ($view === 'rejected') echo "Rejected Applications";
                elseif ($view === 'corrections') echo "Applications Awaiting Student Correction";
                else echo "All Bus Pass Applications";
            ?>
        </h3>
        <div>
            <a href="officer_dashboard.php" class="btn btn-secondary btn-sm <?php echo ($view === 'all') ? 'active' : ''; ?>">All</a>
            <a href="officer_dashboard.php?view=pending" class="btn btn-secondary btn-sm <?php echo ($view === 'pending') ? 'active' : ''; ?>">Pending</a>
            <a href="officer_dashboard.php?view=approved" class="btn btn-secondary btn-sm <?php echo ($view === 'approved') ? 'active' : ''; ?>">Approved</a>
            <a href="officer_dashboard.php?view=corrections" class="btn btn-secondary btn-sm <?php echo ($view === 'corrections') ? 'active' : ''; ?>">Corrections</a>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Student / PRN</th>
                <th>Route Number</th>
                <th>Travel Distance</th>
                <th>Submission Date</th>
                <th>Routing Dept</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                        <i class="fa-regular fa-folder-open" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>
                        No applications found in this category.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($app['prn_number']); ?> (Roll: <?php echo htmlspecialchars($app['roll_number']); ?>)</div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($app['route_number']); ?></strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($app['source']); ?> &rarr; <?php echo htmlspecialchars($app['destination']); ?></div>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($app['distance']); ?> km
                            <?php if ($app['distance'] > 50): ?>
                                <span style="color: var(--danger); font-size: 0.85rem;" title="Requires admin routing validation exception (distance > 50km)">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Heavy Route
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($app['submission_date'])); ?></td>
                        <td>
                            <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary);">
                                <i class="fa-solid fa-code-fork" style="font-size: 0.75rem; margin-right: 4px;"></i>
                                <?php echo htmlspecialchars($app['routing_dept']); ?>
                            </span>
                        </td>
                        <td><?php echo get_status_badge($app['status']); ?></td>
                        <td style="text-align: right;">
                            <a href="verify_application.php?id=<?php echo $app['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-eye-slash"></i> Verify &amp; Decide
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Route Statistics Section -->
<div class="table-container">
    <div class="table-header-row">
        <h3 class="table-title"><i class="fa-solid fa-chart-simple" style="margin-right: 8px; color: var(--accent);"></i> Route Registration Statistics</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Route</th>
                <th>Origin &rarr; Destination</th>
                <th>Distance</th>
                <th>Approved Passes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($route_stats as $stat): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($stat['route_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($stat['source']); ?> &rarr; <?php echo htmlspecialchars($stat['destination']); ?></td>
                    <td><?php echo htmlspecialchars($stat['distance']); ?> km</td>
                    <td>
                        <span class="badge badge-approved">
                            <span class="badge-dot"></span>
                            <?php echo $stat['student_count']; ?> students
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php 
include __DIR__ . '/footer.php';
?>
