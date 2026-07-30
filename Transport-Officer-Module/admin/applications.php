<?php
/**
 * All Applications — List, Filter, Search
 * Bus Pass Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$page_title  = 'All Applications';
$active_page = 'applications';

// Build filters from GET params
$filters = [
    'status'    => clean_input($_GET['status']    ?? ''),
    'pass_type' => clean_input($_GET['pass_type'] ?? ''),
    'search'    => clean_input($_GET['search']    ?? ''),
    'date_from' => clean_input($_GET['date_from'] ?? ''),
    'date_to'   => clean_input($_GET['date_to']   ?? ''),
];

$applications = get_all_applications($filters);

$statuses = [
    ''                     => 'All Statuses',
    'pending'              => 'Pending',
    'under_review'         => 'Under Review',
    'docs_verified'        => 'Docs Verified',
    'route_validated'      => 'Route Validated',
    'approved'             => 'Approved',
    'rejected'             => 'Rejected',
    'correction_requested' => 'Correction Requested',
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Filter Bar ────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="applications.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search"
                           value="<?= e($filters['search']) ?>"
                           placeholder="Name, App No, Email…">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Pass Type</label>
                <select class="form-select" name="pass_type">
                    <option value="">All Types</option>
                    <option value="monthly"   <?= $filters['pass_type'] === 'monthly'   ? 'selected' : '' ?>>Monthly</option>
                    <option value="quarterly" <?= $filters['pass_type'] === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                    <option value="annual"    <?= $filters['pass_type'] === 'annual'    ? 'selected' : '' ?>>Annual</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="date_from" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="date_to" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="applications.php" class="btn btn-outline-secondary w-100" title="Clear filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── Results Summary ───────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">
        <?php if (!empty(array_filter($filters))): ?>
            Showing <strong><?= count($applications) ?></strong> filtered results
        <?php else: ?>
            <strong><?= count($applications) ?></strong> total applications
        <?php endif; ?>
    </span>
    <div class="d-flex gap-2">
        <a href="applications.php?status=pending" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-hourglass-split me-1"></i>Pending Only
        </a>
        <a href="applications.php?status=route_validated" class="btn btn-sm btn-outline-success">
            <i class="bi bi-check2-square me-1"></i>Ready to Approve
        </a>
    </div>
</div>

<!-- ── Applications Table ────────────────────────────────── -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0" id="applicationsTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">App No.</th>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Category</th>
                        <th>Route</th>
                        <th>Pass Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td class="ps-3 fw-semibold text-primary small">
                            <?= e($app['application_number']) ?>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= e($app['applicant_name']) ?></div>
                            <div class="text-muted small"><?= e($app['applicant_email']) ?></div>
                        </td>
                        <td class="small text-muted"><?= e($app['applicant_phone']) ?></td>
                        <td><?= category_badge($app['applicant_category']) ?></td>
                        <td>
                            <span class="badge bg-secondary me-1"><?= e($app['route_number']) ?></span>
                            <span class="small text-muted d-block" style="font-size:.7rem;">
                                <?= e($app['boarding_stop']) ?> → <?= e($app['alighting_stop']) ?>
                            </span>
                        </td>
                        <td><?= pass_type_badge($app['pass_type']) ?></td>
                        <td class="fw-semibold">₹<?= number_format($app['amount_paid'], 2) ?></td>
                        <td><?= status_badge($app['status']) ?></td>
                        <td class="text-muted small"><?= format_dt($app['created_at'], 'd M Y') ?></td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="application_detail.php?id=<?= (int)$app['id'] ?>"
                                   class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (in_array($app['status'], ['pending','under_review','docs_verified','route_validated'])): ?>
                                <a href="document_verification.php?id=<?= (int)$app['id'] ?>"
                                   class="btn btn-outline-info" title="Verify Documents">
                                    <i class="bi bi-file-earmark-check"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($app['status'] === 'route_validated'): ?>
                                <a href="approve_reject.php?id=<?= (int)$app['id'] ?>"
                                   class="btn btn-outline-success" title="Approve / Reject">
                                    <i class="bi bi-check2-square"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($applications)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            <span class="text-muted">No applications match your filters.</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
