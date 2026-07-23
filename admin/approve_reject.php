<?php
/**
 * Approve / Reject Applications Page
 * Bus Pass Management System
 *
 * Two views:
 *  1. ?id=X   — Approve/reject a specific application
 *  2. default — List of applications ready for final decision
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$active_page = 'approve_reject';
$app_id      = (int)($_GET['id'] ?? 0);

if ($app_id) {
    // ── Single application approve/reject ─────────────────
    $app = get_application($app_id);
    if (!$app) {
        redirect_with_flash('../admin/approve_reject.php', 'error', 'Application not found.');
    }

    $documents = get_application_documents($app_id);
    $logs      = get_application_logs($app_id);

    $page_title  = 'Approve/Reject — ' . $app['application_number'];

    // Count document stats
    $doc_total    = count($documents);
    $doc_verified = count(array_filter($documents, fn($d) => $d['status'] === 'verified'));
    $doc_rejected = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));

    // Pre-flight checks
    $checks = [
        'docs_all_verified' => $doc_verified === $doc_total && $doc_total > 0,
        'no_docs_rejected'  => $doc_rejected === 0,
        'route_validated'   => in_array($app['status'], ['route_validated','approved']),
        'route_active'      => (bool)$app['route_is_active'],
        'payment_valid'     => !empty($app['payment_ref']),
    ];
    $all_checks_pass = !in_array(false, $checks, true);

    require_once __DIR__ . '/../includes/header.php';
    ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="approve_reject.php">Approve/Reject Queue</a></li>
            <li class="breadcrumb-item active"><?= e($app['application_number']) ?></li>
        </ol>
    </nav>

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <div class="row g-4">
        <!-- Left: Applicant summary + Pre-flight checks -->
        <div class="col-lg-5">
            <!-- Applicant card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person-check me-2 text-primary"></i>Applicant Summary
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-circle mx-auto mb-2" style="width:56px;height:56px;font-size:1.4rem;">
                            <?= strtoupper(substr($app['applicant_name'], 0, 1)) ?>
                        </div>
                        <h5 class="mb-0 fw-bold"><?= e($app['applicant_name']) ?></h5>
                        <p class="text-muted small mb-1"><?= e($app['applicant_email']) ?></p>
                        <?= category_badge($app['applicant_category']) ?>
                    </div>
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted">Application No.</td><td class="fw-semibold"><?= e($app['application_number']) ?></td></tr>
                        <tr><td class="text-muted">Route</td><td><span class="badge bg-secondary"><?= e($app['route_number']) ?></span> <?= e($app['route_name']) ?></td></tr>
                        <tr><td class="text-muted">Boarding</td><td><?= e($app['boarding_stop']) ?></td></tr>
                        <tr><td class="text-muted">Alighting</td><td><?= e($app['alighting_stop']) ?></td></tr>
                        <tr><td class="text-muted">Pass Type</td><td><?= pass_type_badge($app['pass_type']) ?></td></tr>
                        <tr><td class="text-muted">Amount Paid</td><td class="fw-semibold text-success">₹<?= number_format($app['amount_paid'], 2) ?></td></tr>
                        <tr><td class="text-muted">Payment Ref</td><td><?= e($app['payment_ref'] ?? '—') ?></td></tr>
                        <tr><td class="text-muted">Submitted</td><td><?= format_dt($app['created_at']) ?></td></tr>
                        <tr><td class="text-muted">Current Status</td><td><?= status_badge($app['status']) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Document summary -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-paperclip me-2 text-primary"></i>Documents (<?= $doc_verified ?>/<?= $doc_total ?> verified)
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($documents as $doc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <div>
                                <div class="small fw-semibold"><?= e($doc['doc_label']) ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?= e($doc['doc_type']) ?></div>
                            </div>
                            <?= doc_status_badge($doc['status']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right: Pre-flight checks + Action -->
        <div class="col-lg-7">
            <?php if ($app['status'] !== 'approved' && $app['status'] !== 'rejected'): ?>
            <!-- Pre-flight checks -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-clipboard2-check me-2 text-primary"></i>Pre-Approval Checklist
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-file-earmark-check me-2 text-primary"></i>
                                All Documents Verified
                                <div class="text-muted small ms-4"><?= $doc_verified ?>/<?= $doc_total ?> documents verified</div>
                            </div>
                            <span class="badge <?= $checks['docs_all_verified'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $checks['docs_all_verified'] ? '✓ Pass' : '✗ Fail' ?>
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-x-circle me-2 text-primary"></i>
                                No Documents Rejected
                                <div class="text-muted small ms-4"><?= $doc_rejected ?> rejected document(s)</div>
                            </div>
                            <span class="badge <?= $checks['no_docs_rejected'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $checks['no_docs_rejected'] ? '✓ Pass' : '✗ Fail' ?>
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-map me-2 text-primary"></i>
                                Route Validated
                                <div class="text-muted small ms-4">Status: <?= e($app['status']) ?></div>
                            </div>
                            <span class="badge <?= $checks['route_validated'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $checks['route_validated'] ? '✓ Pass' : '✗ Fail' ?>
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-toggle-on me-2 text-primary"></i>
                                Route is Active
                                <div class="text-muted small ms-4">Route <?= e($app['route_number']) ?> operational status</div>
                            </div>
                            <span class="badge <?= $checks['route_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $checks['route_active'] ? '✓ Pass' : '✗ Fail' ?>
                            </span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <div>
                                <i class="bi bi-receipt me-2 text-primary"></i>
                                Payment Reference Available
                                <div class="text-muted small ms-4">Ref: <?= e($app['payment_ref'] ?? '—') ?></div>
                            </div>
                            <span class="badge <?= $checks['payment_valid'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $checks['payment_valid'] ? '✓ Pass' : '⚠ Check' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Overall result bar -->
                    <div class="mt-3 pt-3 border-top">
                        <?php
                        $pass_count = count(array_filter($checks));
                        $total_checks = count($checks);
                        $check_pct = round(($pass_count / $total_checks) * 100);
                        ?>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Readiness Score</span>
                            <span class="fw-semibold"><?= $pass_count ?>/<?= $total_checks ?> checks passed</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar <?= $all_checks_pass ? 'bg-success' : 'bg-warning' ?>"
                                 style="width:<?= $check_pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision Panel -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-gavel me-2 text-primary"></i>Final Decision
                </div>
                <div class="card-body">
                    <?php if (!$all_checks_pass): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Not all pre-approval checks have passed.
                        Approval is still possible but not recommended.
                    </div>
                    <?php endif; ?>

                    <p class="text-muted small mb-3">
                        Only authorised admins can make final approval decisions.
                        This action will be logged with your credentials and timestamp.
                    </p>

                    <div class="d-grid gap-3">
                        <!-- Approve -->
                        <button class="btn btn-success py-2 fw-semibold"
                                <?= (!admin_has_role('admin')) ? 'disabled title="Requires Admin role"' : '' ?>
                                onclick="updateApplicationStatus(<?= $app_id ?>, 'approved')">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            Approve Application &amp; Issue Bus Pass
                        </button>

                        <!-- Reject -->
                        <button class="btn btn-danger py-2 fw-semibold"
                                onclick="updateApplicationStatus(<?= $app_id ?>, 'rejected')">
                            <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                            Reject Application
                        </button>

                        <!-- Request correction instead -->
                        <a href="request_corrections.php?id=<?= $app_id ?>"
                           class="btn btn-outline-warning">
                            <i class="bi bi-pencil-square me-2"></i>Request Corrections Instead
                        </a>
                    </div>

                    <?php if (!admin_has_role('admin')): ?>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        You have <strong>Verifier</strong> access. Approval requires an <strong>Admin</strong> or higher.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Clean summary card instead -->
            <div class="card border-<?= $app['status'] === 'approved' ? 'success' : 'danger' ?> border-start border-3 mb-4">
                <div class="card-body text-center py-4">
                    <?php if ($app['status'] === 'approved'): ?>
                        <i class="bi bi-patch-check-fill text-success fs-1 d-block mb-2"></i>
                        <h5 class="fw-bold mb-1 text-success">Application Approved</h5>
                        <p class="mb-3 text-muted">A bus pass has been successfully generated and issued.</p>
                        <div class="bg-light rounded p-3 mb-3 d-inline-block w-100 text-center">
                            <div class="text-muted small">PASS NUMBER</div>
                            <div class="fw-bold fs-5 text-primary mb-2"><?= e($app['pass_number']) ?></div>
                            <div class="text-muted small">Valid: <?= format_dt($app['pass_start_date'], 'd M Y') ?> to <?= format_dt($app['pass_end_date'], 'd M Y') ?></div>
                        </div>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger fs-1 d-block mb-2"></i>
                        <h5 class="fw-bold mb-1 text-danger">Application Rejected</h5>
                        <p class="mb-3 text-muted">This application was rejected by the reviewer.</p>
                        <div class="bg-light rounded p-3 mb-3 d-inline-block w-100 text-center">
                            <div class="text-muted small">REJECTION REASON</div>
                            <div class="fw-semibold text-danger"><?= e($app['rejection_reason']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="approve_reject.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Back to Queue
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit log -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-clock-history me-2 text-primary"></i>History
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($logs as $log):
                    $cls = match($log['new_status'] ?? '') {
                        'approved' => 'tl-success',
                        'rejected' => 'tl-danger',
                        'correction_requested' => 'tl-warning',
                        default    => ''
                    };
                ?>
                <div class="timeline-item <?= $cls ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-time"><?= format_dt($log['created_at']) ?></div>
                    <div class="timeline-title"><?= e($log['action']) ?>
                        <?php if ($log['performed_by_name']): ?>
                            <span class="text-muted fw-normal small">— by <?= e($log['performed_by_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($log['notes']): ?>
                    <div class="timeline-body"><?= e($log['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Default: Approve/Reject queue ─────────────────────────
$page_title = 'Approve / Reject Applications';
$pdo        = get_db();

$ready_apps = $pdo->query("
    SELECT a.*, ap.name AS applicant_name, ap.email AS applicant_email,
           ap.category AS applicant_category,
           r.route_number, r.route_name,
           (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id) AS doc_total,
           (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id AND d.status = 'verified') AS doc_verified
    FROM applications a
    JOIN applicants ap ON a.applicant_id = ap.id
    JOIN bus_routes  r  ON a.route_id     = r.id
    WHERE a.status = 'route_validated'
    ORDER BY a.created_at ASC
")->fetchAll();

$history_apps = $pdo->query("
    SELECT a.*, ap.name AS applicant_name, r.route_number, au.name AS reviewed_by_name
    FROM applications a
    JOIN applicants ap ON a.applicant_id = ap.id
    JOIN bus_routes  r  ON a.route_id     = r.id
    LEFT JOIN admin_users au ON a.reviewed_by = au.id
    WHERE a.status IN ('approved','rejected')
    ORDER BY a.reviewed_at DESC
    LIMIT 20
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="approvalTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#readyTab">
            <i class="bi bi-hourglass-split me-1"></i>
            Ready for Decision
            <span class="badge bg-primary ms-1"><?= count($ready_apps) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#historyTab">
            <i class="bi bi-clock-history me-1"></i>
            Recent Decisions
            <span class="badge bg-secondary ms-1"><?= count($history_apps) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Ready for decision -->
    <div class="tab-pane fade show active" id="readyTab">
        <?php if (empty($ready_apps)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
                <h5>No applications pending decision</h5>
                <p class="text-muted">All route-validated applications have been processed.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($ready_apps as $a): ?>
            <div class="col-xl-6">
                <div class="card border-start border-success border-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-bold"><?= e($a['applicant_name']) ?></div>
                                <div class="text-muted small"><?= e($a['application_number']) ?> · <?= e($a['applicant_email']) ?></div>
                            </div>
                            <span class="badge bg-warning text-dark">Ready to Approve</span>
                        </div>

                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <?= category_badge($a['applicant_category']) ?>
                            <span class="badge bg-secondary"><?= e($a['route_number']) ?></span>
                            <?= pass_type_badge($a['pass_type']) ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <?= $a['doc_verified'] ?>/<?= $a['doc_total'] ?> docs ✓
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold text-success fs-5">₹<?= number_format($a['amount_paid'], 2) ?></span>
                                <span class="text-muted small ms-2"><?= format_dt($a['created_at'], 'd M Y') ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="approve_reject.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Review
                                </a>
                                <?php if (admin_has_role('admin')): ?>
                                <button class="btn btn-success btn-sm"
                                        onclick="updateApplicationStatus(<?= (int)$a['id'] ?>, 'approved')">
                                    <i class="bi bi-check-lg me-1"></i>Approve
                                </button>
                                <button class="btn btn-danger btn-sm"
                                        onclick="updateApplicationStatus(<?= (int)$a['id'] ?>, 'rejected')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- History tab -->
    <div class="tab-pane fade" id="historyTab">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">App No.</th>
                                <th>Applicant</th>
                                <th>Route</th>
                                <th>Pass Type</th>
                                <th>Decision</th>
                                <th>Pass No.</th>
                                <th>Reviewed By</th>
                                <th class="pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_apps as $a): ?>
                            <tr>
                                <td class="ps-3 fw-semibold small text-primary"><?= e($a['application_number']) ?></td>
                                <td><?= e($a['applicant_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= e($a['route_number']) ?></span></td>
                                <td><?= pass_type_badge($a['pass_type']) ?></td>
                                <td><?= status_badge($a['status']) ?></td>
                                <td class="fw-semibold small"><?= e($a['pass_number'] ?? '—') ?></td>
                                <td class="small"><?= e($a['reviewed_by_name'] ?? '—') ?></td>
                                <td class="small text-muted pe-3"><?= format_dt($a['reviewed_at'], 'd M Y') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
