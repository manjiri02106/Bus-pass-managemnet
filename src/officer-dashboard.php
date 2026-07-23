<?php
// src/officer-dashboard.php — Transport Officer Dashboard
// Uses: VerifyApplicationsService, RouteValidationService
require_once dirname(__DIR__) . '/config/db-connection.php';
require_once __DIR__ . '/auth-helper.php';

check_auth(['officer', 'admin']);

$today_start = date("Y-m-d 00:00:00");
$today_end   = date("Y-m-d 23:59:59");

try {
    $cnt_pending       = $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted','under_verification')")->fetchColumn();
    $cnt_approved_today= $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status='approved' AND last_updated BETWEEN ? AND ?");
    $cnt_approved_today->execute([$today_start, $today_end]);
    $cnt_approved_today= $cnt_approved_today->fetchColumn();
    $cnt_rejected      = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();
    $cnt_correction    = $pdo->query("SELECT COUNT(*) FROM applications WHERE status='correction_required'")->fetchColumn();

    $view = $_GET['view'] ?? 'all';
    $where = match($view) {
        'pending'    => "a.status IN ('submitted','under_verification')",
        'approved'   => "a.status='approved'",
        'corrections'=> "a.status='correction_required'",
        default      => "1=1"
    };

    $applications = $pdo->query("
        SELECT a.id, a.status, a.submission_date, a.routing_dept,
               s.prn_number, s.roll_number, u.full_name AS student_name,
               r.route_number, r.source, r.destination, r.distance
        FROM applications a
        JOIN students s ON a.student_id = s.id
        JOIN users   u ON s.user_id    = u.id
        JOIN routes  r ON a.route_id   = r.id
        WHERE $where
        ORDER BY a.submission_date DESC
    ")->fetchAll();

    $route_stats = $pdo->query("
        SELECT r.route_number, r.source, r.destination, r.distance,
               COUNT(a.id) AS student_count
        FROM routes r
        LEFT JOIN applications a ON r.id=a.route_id AND a.status='approved'
        GROUP BY r.id
    ")->fetchAll();

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard — Bus Pass Portal</title>
    <meta name="description" content="Transport Officer dashboard for managing bus pass applications, document verification and routing.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<header>
    <div class="header-brand">
        <div class="logo-icon">BP</div>
        <div class="header-title"><h1>Bus Pass Portal</h1><span>Student Transportation Services</span></div>
    </div>
    <div class="header-user">
        <div class="user-badge"><i class="fa-regular fa-user"></i> <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span></div>
        <form action="logout.php" method="POST" style="margin:0;"><button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button></form>
    </div>
</header>

<div class="app-container">
<aside class="sidebar">
    <a href="officer-dashboard.php" class="sidebar-link <?php echo (!isset($_GET['view'])) ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="officer-dashboard.php?view=pending" class="sidebar-link <?php echo ($view==='pending') ? 'active' : ''; ?>"><i class="fa-solid fa-user-check"></i> Verify Applications</a>
    <a href="officer-dashboard.php?view=approved" class="sidebar-link <?php echo ($view==='approved') ? 'active' : ''; ?>"><i class="fa-solid fa-signature"></i> Approved Passes</a>
    <a href="officer-dashboard.php?view=corrections" class="sidebar-link <?php echo ($view==='corrections') ? 'active' : ''; ?>"><i class="fa-solid fa-circle-question"></i> Pending Corrections</a>
    <hr style="border:0; border-top:1px solid var(--border-color); margin:1rem 0;">
    <form action="logout.php" method="POST" style="width:100%;">
        <button type="submit" class="sidebar-link" style="background:none; border:none; width:100%; text-align:left; cursor:pointer; font-family:inherit;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
    </form>
</aside>
<main class="main-content">

<div class="page-title-section">
    <h2 class="page-title">Officer Dashboard</h2>
    <p class="page-subtitle">Verify applications, validate documents, route exceptions, and issue bus passes.</p>
</div>

<div class="stats-grid">
    <a href="officer-dashboard.php?view=pending" class="stat-card warning"><span class="stat-label">Pending Verification</span><span class="stat-value"><?php echo $cnt_pending; ?></span></a>
    <div class="stat-card success"><span class="stat-label">Approved Today</span><span class="stat-value"><?php echo $cnt_approved_today; ?></span></div>
    <a href="officer-dashboard.php" class="stat-card danger"><span class="stat-label">Rejected</span><span class="stat-value"><?php echo $cnt_rejected; ?></span></a>
    <a href="officer-dashboard.php?view=corrections" class="stat-card warning"><span class="stat-label">Awaiting Corrections</span><span class="stat-value"><?php echo $cnt_correction; ?></span></a>
</div>

<div class="table-container">
    <div class="table-header-row">
        <h3 class="table-title">
            <?php echo match($view) {
                'pending'     => 'Applications Pending Verification',
                'approved'    => 'Approved Applications',
                'corrections' => 'Awaiting Student Corrections',
                default       => 'All Bus Pass Applications'
            }; ?>
        </h3>
        <div style="display:flex; gap:8px;">
            <a href="officer-dashboard.php"                    class="btn btn-secondary btn-sm">All</a>
            <a href="officer-dashboard.php?view=pending"       class="btn btn-secondary btn-sm">Pending</a>
            <a href="officer-dashboard.php?view=approved"      class="btn btn-secondary btn-sm">Approved</a>
            <a href="officer-dashboard.php?view=corrections"   class="btn btn-secondary btn-sm">Corrections</a>
        </div>
    </div>
    <table>
        <thead><tr>
            <th>Student / PRN</th><th>Route</th><th>Distance</th>
            <th>Submitted</th><th>Dept Queue</th><th>Status</th>
            <th style="text-align:right;">Action</th>
        </tr></thead>
        <tbody>
        <?php if (empty($applications)): ?>
            <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:3rem;">
                <i class="fa-regular fa-folder-open" style="font-size:2.5rem; display:block; margin-bottom:1rem;"></i>
                No applications found.
            </td></tr>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($app['prn_number']); ?></div>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($app['route_number']); ?></strong>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($app['source']); ?> &rarr; <?php echo htmlspecialchars($app['destination']); ?></div>
                </td>
                <td>
                    <?php echo htmlspecialchars($app['distance']); ?> km
                    <?php if ($app['distance'] > 50): ?>
                        <span style="color:var(--danger); font-size:0.8rem;" title="Long haul — Admin Exception required">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d M Y', strtotime($app['submission_date'])); ?></td>
                <td><span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-secondary);"><?php echo htmlspecialchars($app['routing_dept']); ?></span></td>
                <td><?php echo get_status_badge($app['status']); ?></td>
                <td style="text-align:right;">
                    <a href="verify-application.php?id=<?php echo $app['id']; ?>" class="btn btn-primary btn-sm" id="btn-verify-<?php echo $app['id']; ?>">
                        <i class="fa-solid fa-magnifying-glass"></i> Verify &amp; Decide
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <div class="table-header-row"><h3 class="table-title"><i class="fa-solid fa-route" style="color:var(--accent); margin-right:8px;"></i> Route Statistics</h3></div>
    <table>
        <thead><tr><th>Route</th><th>Origin &rarr; Destination</th><th>Distance</th><th>Approved Passes</th></tr></thead>
        <tbody>
        <?php foreach ($route_stats as $rs): ?>
        <tr>
            <td><strong><?php echo htmlspecialchars($rs['route_number']); ?></strong></td>
            <td><?php echo htmlspecialchars($rs['source']); ?> &rarr; <?php echo htmlspecialchars($rs['destination']); ?></td>
            <td><?php echo htmlspecialchars($rs['distance']); ?> km</td>
            <td><span class="badge badge-approved"><span class="badge-dot"></span><?php echo $rs['student_count']; ?> students</span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</main>
</div>
<footer>
    <div>&copy; <?php echo date("Y"); ?> Bus Pass Management System. All Rights Reserved.</div>
    <div class="footer-links"><span>Transport Operations</span><span>v1.1-feature-only</span></div>
</footer>
</body>
</html>
