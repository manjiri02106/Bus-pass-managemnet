<?php
// admin-dashboard/reports.php - Generate Reports for Admin
require_once __DIR__ . '/../auth/config/session.php';

// Enforce access for Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../Transport-Officer-Module/db.php';

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

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-title-section no-print">
    <h2 class="page-title">Bus Pass Management Reports</h2>
    <p class="page-subtitle">Generate and view detailed reports based on status, routes, and applications.</p>
</div>

<div class="detail-grid no-print" style="grid-template-columns: 1fr;">
    <div class="table-container p-4">
        <form method="get" id="filterForm" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Report Type</label>
                <select name="report_type" style="padding:8px; border:1px solid #ccc; border-radius:4px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?= $report_type === 'all' ? 'selected' : '' ?>>All Applications</option>
                    <option value="pending" <?= $report_type === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                    <option value="approved" <?= $report_type === 'approved' ? 'selected' : '' ?>>Approved Passes</option>
                    <option value="rejected" <?= $report_type === 'rejected' ? 'selected' : '' ?>>Rejected Applications</option>
                    <option value="renewal" <?= $report_type === 'renewal' ? 'selected' : '' ?>>Renewal Requests</option>
                    <option value="route-wise" <?= $report_type === 'route-wise' ? 'selected' : '' ?>>Route-wise Summary</option>
                </select>
            </div>
            <?php if ($report_type !== 'route-wise'): ?>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Filter by Route</label>
                <select name="route" style="padding:8px; border:1px solid #ccc; border-radius:4px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Routes</option>
                    <?php foreach ($all_routes as $r): ?>
                        <option value="<?= htmlspecialchars($r['route_number']) ?>" <?= $route_filter === $r['route_number'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['route_number']) ?> (<?= htmlspecialchars($r['source']) ?> - <?= htmlspecialchars($r['destination']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Filter by Status</label>
                <select name="status" style="padding:8px; border:1px solid #ccc; border-radius:4px;" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?= $status_filter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <?php endif; ?>
            <div style="margin-left: auto;">
                <button type="button" class="btn-primary" onclick="window.print()" style="padding: 8px 20px;">
                    <i class="fa-solid fa-print"></i> Print / Save as PDF
                </button>
            </div>
        </form>
    </div>
</div>

<div class="detail-grid mt-4 print-area" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row text-center" style="justify-content: center; background: #f8f9fa;">
            <h3 class="table-title"><?= $title ?></h3>
        </div>
        <table>
            <thead>
                <?php if ($report_type === 'route-wise'): ?>
                    <tr>
                        <th>Route Number</th>
                        <th>Path (Source - Destination)</th>
                        <th style="text-align:center;">Total Applications</th>
                        <th style="text-align:center; color:green;">Approved</th>
                        <th style="text-align:center; color:orange;">Pending</th>
                        <th style="text-align:center; color:red;">Rejected</th>
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
                        <td colspan="7" style="text-align:center; padding:20px; color:#666;">No records found matching the current filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportRows as $row): ?>
                        <?php if ($report_type === 'route-wise'): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['route_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['source']) ?> &rarr; <?= htmlspecialchars($row['destination']) ?></td>
                                <td style="text-align:center; font-weight:bold;"><?= $row['total'] ?></td>
                                <td style="text-align:center; color:green; font-weight:bold;"><?= $row['approved'] ?></td>
                                <td style="text-align:center; color:orange; font-weight:bold;"><?= $row['pending'] ?></td>
                                <td style="text-align:center; color:red; font-weight:bold;"><?= $row['rejected'] ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['application_no']) ?></strong><br>
                                    <span style="font-size:0.8rem; color:#666;"><?= htmlspecialchars($row['prn_number']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td>
                                    <span class="badge" style="background:#17a2b8; color:white;"><?= htmlspecialchars($row['route_number']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['pass_type']) ?></td>
                                <td>
                                    <?php if ($row['valid_from'] && $row['valid_until']): ?>
                                        <span style="font-size:0.85rem;"><?= date('d M Y', strtotime($row['valid_from'])) ?> to <?= date('d M Y', strtotime($row['valid_until'])) ?></span>
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $s = $row['status'];
                                        if ($s === 'approved') echo '<span class="badge badge-approved">Approved</span>';
                                        elseif ($s === 'rejected') echo '<span class="badge badge-rejected">Rejected</span>';
                                        elseif (in_array($s, ['submitted', 'under_verification'])) echo '<span class="badge badge-pending">Pending</span>';
                                        else echo '<span class="badge" style="background:#6c757d; color:white;">'.htmlspecialchars($s).'</span>';
                                    ?>
                                </td>
                                <td><span style="font-size:0.85rem;"><?= date('d M Y h:i A', strtotime($row['submission_date'])) ?></span></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
    header {
        display: none !important;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
