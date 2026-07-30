<?php
// reports.php - Generate Reports for Transport Officer Module
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce access for Officer or Admin
check_auth(['officer', 'admin']);

$report_type = $_GET['report_type'] ?? 'all';
$route_filter = $_GET['route'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build the SQL query for reports based on filters
$where = ['1=1'];
$params = [];

if ($route_filter !== '') {
    $where[] = 'r.route_number = ?';
    $params[] = $route_filter;
}

if ($status_filter !== '') {
    $where[] = 'a.status = ?';
    $params[] = $status_filter;
}

if (in_array($report_type, ['pending', 'approved', 'rejected'])) {
    if ($report_type === 'pending') {
        $where[] = "a.status IN ('submitted', 'under_verification')";
    } else {
        $where[] = 'a.status = ?';
        $params[] = $report_type;
    }
} elseif ($report_type === 'renewal') {
    $where[] = 'a.original_pass_id IS NOT NULL';
}

$where_clause = implode(' AND ', $where);

$query = "
    SELECT 
        a.id as application_id, 
        a.application_no, 
        a.status, 
        a.pass_type, 
        a.valid_from, 
        a.valid_until, 
        a.submission_date,
        s.prn_number, 
        u.full_name as student_name,
        r.route_number, 
        r.source, 
        r.destination
    FROM applications a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN routes r ON a.route_id = r.id
    WHERE $where_clause
    ORDER BY a.submission_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Fetch distinct routes for the filter dropdown
$route_stmt = $pdo->query("SELECT route_number, source, destination FROM routes ORDER BY route_number");
$all_routes = $route_stmt->fetchAll();

// Route-wise grouping if required
$reportRows = [];
if ($report_type === 'route-wise') {
    $grouped = [];
    foreach ($records as $record) {
        $route = $record['route_number'];
        if (!isset($grouped[$route])) {
            $grouped[$route] = [
                'route_number' => $route,
                'source' => $record['source'],
                'destination' => $record['destination'],
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'rejected' => 0
            ];
        }
        $grouped[$route]['total']++;
        if ($record['status'] === 'approved') {
            $grouped[$route]['approved']++;
        } elseif (in_array($record['status'], ['submitted', 'under_verification'])) {
            $grouped[$route]['pending']++;
        } elseif ($record['status'] === 'rejected') {
            $grouped[$route]['rejected']++;
        }
    }
    $reportRows = array_values($grouped);
} else {
    $reportRows = $records;
}

$title = "Student Pass Reports";
if ($report_type === 'route-wise') $title = "Route-wise Summary Report";
if ($report_type === 'renewal') $title = "Renewal Passes Report";
if (in_array($report_type, ['pending', 'approved', 'rejected'])) $title = ucfirst($report_type) . " Passes Report";

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section no-print">
    <h2 class="page-title">Bus Pass Management Reports</h2>
    <p class="page-subtitle">Generate and view detailed reports based on status, routes, and applications.</p>
</div>

<div class="card shadow-sm mb-4 no-print">
    <div class="card-body bg-light">
        <form method="get" id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold text-primary">Report Type</label>
                <select name="report_type" class="form-select border-primary" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?= $report_type === 'all' ? 'selected' : '' ?>>All Applications</option>
                    <option value="pending" <?= $report_type === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="approved" <?= $report_type === 'approved' ? 'selected' : '' ?>>Approved Passes</option>
                    <option value="rejected" <?= $report_type === 'rejected' ? 'selected' : '' ?>>Rejected Applications</option>
                    <option value="renewal" <?= $report_type === 'renewal' ? 'selected' : '' ?>>Renewal Requests</option>
                    <option value="route-wise" <?= $report_type === 'route-wise' ? 'selected' : '' ?>>Route-wise Summary</option>
                </select>
            </div>
            <?php if ($report_type !== 'route-wise'): ?>
            <div class="col-md-3">
                <label class="form-label fw-bold text-primary">Filter by Route</label>
                <select name="route" class="form-select border-primary" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Routes</option>
                    <?php foreach ($all_routes as $r): ?>
                        <option value="<?= htmlspecialchars($r['route_number']) ?>" <?= $route_filter === $r['route_number'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['route_number']) ?> (<?= htmlspecialchars($r['source']) ?> - <?= htmlspecialchars($r['destination']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold text-primary">Filter by Status</label>
                <select name="status" class="form-select border-primary" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3 ms-auto text-end">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm print-area">
    <div class="card-header bg-white border-bottom">
        <h4 class="mb-0 text-center py-2"><?= $title ?></h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <?php if ($report_type === 'route-wise'): ?>
                        <tr>
                            <th>Route Number</th>
                            <th>Path (Source - Destination)</th>
                            <th class="text-center">Total Applications</th>
                            <th class="text-center text-success">Approved</th>
                            <th class="text-center text-warning">Pending</th>
                            <th class="text-center text-danger">Rejected</th>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>App No. / PRN</th>
                            <th>Student Name</th>
                            <th>Route</th>
                            <th>Pass Type</th>
                            <th>Valid Dates</th>
                            <th>Status</th>
                            <th>Applied On</th>
                        </tr>
                    <?php endif; ?>
                </thead>
                <tbody>
                    <?php if (empty($reportRows)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No records found matching the current filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportRows as $row): ?>
                            <?php if ($report_type === 'route-wise'): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($row['route_number']) ?></td>
                                    <td><?= htmlspecialchars($row['source']) ?> &rarr; <?= htmlspecialchars($row['destination']) ?></td>
                                    <td class="text-center fw-bold"><?= $row['total'] ?></td>
                                    <td class="text-center text-success fw-bold"><?= $row['approved'] ?></td>
                                    <td class="text-center text-warning fw-bold"><?= $row['pending'] ?></td>
                                    <td class="text-center text-danger fw-bold"><?= $row['rejected'] ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['application_no']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['prn_number']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?= htmlspecialchars($row['route_number']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['pass_type']) ?></td>
                                    <td>
                                        <?php if ($row['valid_from'] && $row['valid_until']): ?>
                                            <small><?= date('d M Y', strtotime($row['valid_from'])) ?> to <?= date('d M Y', strtotime($row['valid_until'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $s = $row['status'];
                                            if ($s === 'approved') echo '<span class="badge bg-success">Approved</span>';
                                            elseif ($s === 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
                                            elseif (in_array($s, ['submitted', 'under_verification'])) echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            else echo '<span class="badge bg-secondary">'.htmlspecialchars($s).'</span>';
                                        ?>
                                    </td>
                                    <td><small><?= date('d M Y h:i A', strtotime($row['submission_date'])) ?></small></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .sidebar {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    table {
        border: 1px solid #dee2e6 !important;
    }
    th, td {
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
