<?php
/**
 * Admin Dashboard
 * Bus Pass Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$page_title  = 'Dashboard';
$active_page = 'dashboard';

$stats    = get_dashboard_stats();
$pdo      = get_db();

// Recent 10 applications
$recent = $pdo->query("
    SELECT a.*, ap.name AS applicant_name, r.route_number
    FROM applications a
    JOIN applicants ap ON a.applicant_id = ap.id
    JOIN bus_routes r  ON a.route_id     = r.id
    ORDER BY a.created_at DESC
    LIMIT 10
")->fetchAll();

// Route-wise distribution
$route_dist = $pdo->query("
    SELECT r.route_number, r.route_name, COUNT(a.id) AS total
    FROM bus_routes r
    LEFT JOIN applications a ON r.id = a.route_id
    GROUP BY r.id
    ORDER BY total DESC
    LIMIT 7
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── KPI Cards ────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card total fade-in-up">
            <div class="stat-icon"><i class="bi bi-files"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card pending fade-in-up" style="animation-delay:.05s">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card review fade-in-up" style="animation-delay:.1s">
            <div class="stat-icon"><i class="bi bi-search"></i></div>
            <div>
                <div class="stat-value"><?= ($stats['under_review'] + $stats['docs_verified'] + $stats['route_validated']) ?></div>
                <div class="stat-label">Under Review</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card approved fade-in-up" style="animation-delay:.15s">
            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card rejected fade-in-up" style="animation-delay:.2s">
            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['rejected'] ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card correction fade-in-up" style="animation-delay:.25s">
            <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
            <div>
                <div class="stat-value"><?= $stats['correction_requested'] ?></div>
                <div class="stat-label">Corrections</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts + Quick Stats ─────────────────────────────── -->
<div class="row g-4 mb-4">
    <!-- Status Distribution -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pie-chart me-2 text-primary"></i>Status Distribution</span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center p-4">
                <canvas id="statusChart" width="280" height="280"></canvas>
            </div>
        </div>
    </div>

    <!-- Route distribution table -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-map me-2 text-primary"></i>Applications by Route
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Route</th>
                            <th>Name</th>
                            <th class="text-end pe-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($route_dist as $rd): ?>
                        <tr>
                            <td class="ps-3"><span class="badge bg-primary"><?= e($rd['route_number']) ?></span></td>
                            <td class="text-truncate" style="max-width:130px"><?= e($rd['route_name']) ?></td>
                            <td class="text-end pe-3 fw-semibold"><?= (int)$rd['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick info panel -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2 text-primary"></i>Quick Stats
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Today's Applications</span>
                    <span class="fw-bold fs-5"><?= $stats['today_total'] ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Approved This Month</span>
                    <span class="fw-bold fs-5 text-success"><?= $stats['this_month_approved'] ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Approval Rate</span>
                    <span class="fw-bold fs-5 text-primary">
                        <?= $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0 ?>%
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">Pending Action</span>
                    <span class="fw-bold fs-5 text-warning">
                        <?= ($stats['pending'] + $stats['under_review']) ?>
                    </span>
                </div>

                <hr class="my-3">
                <div class="d-grid gap-2">
                    <a href="applications.php?status=pending" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-inbox me-1"></i>View Pending Applications
                    </a>
                    <a href="document_verification.php" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-file-earmark-check me-1"></i>Verify Documents
                    </a>
                    <a href="approve_reject.php" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-check2-square me-1"></i>Approve / Reject Queue
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Applications ───────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Recent Applications</span>
        <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">App No.</th>
                        <th>Applicant</th>
                        <th>Route</th>
                        <th>Pass Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $app): ?>
                    <tr>
                        <td class="ps-3 fw-semibold"><?= e($app['application_number']) ?></td>
                        <td><?= e($app['applicant_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($app['route_number']) ?></span></td>
                        <td><?= pass_type_badge($app['pass_type']) ?></td>
                        <td><?= status_badge($app['status']) ?></td>
                        <td class="text-muted small"><?= format_dt($app['created_at'], 'd M Y') ?></td>
                        <td class="text-end pe-3">
                            <a href="application_detail.php?id=<?= (int)$app['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No applications yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js render -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    renderStatusChart(
        'statusChart',
        ['Pending', 'Under Review', 'Docs Verified', 'Route Validated', 'Approved', 'Rejected', 'Correction'],
        [
            <?= $stats['pending'] ?>,
            <?= $stats['under_review'] ?>,
            <?= $stats['docs_verified'] ?>,
            <?= $stats['route_validated'] ?>,
            <?= $stats['approved'] ?>,
            <?= $stats['rejected'] ?>,
            <?= $stats['correction_requested'] ?>
        ],
        ['#64748b','#0891b2','#2563eb','#d97706','#16a34a','#dc2626','#ea580c']
    );
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
