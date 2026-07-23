<?php
/**
 * Application Detail View
 * Bus Pass Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$id  = (int)($_GET['id'] ?? 0);
$app = $id ? get_application($id) : null;

if (!$app) {
    redirect_with_flash('../admin/applications.php', 'error', 'Application not found.');
}

$documents    = get_application_documents($id);
$logs         = get_application_logs($id);
$corrections  = get_correction_requests($id);

// Step workflow mapping
$steps = [
    'pending'              => 1,
    'under_review'         => 2,
    'docs_verified'        => 3,
    'route_validated'      => 4,
    'approved'             => 5,
    'rejected'             => 5,
    'correction_requested' => 2,
];
$current_step = $steps[$app['status']] ?? 1;

$page_title  = 'Application: ' . $app['application_number'];
$active_page = 'applications';

// Start review (pending → under_review)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_review'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        redirect_with_flash("application_detail.php?id={$id}", 'error', 'Invalid CSRF token.');
    }
    if ($app['status'] === 'pending') {
        update_application_status($id, 'under_review', (int)$_SESSION['admin_id'], 'Admin started review process');
    }
    redirect_with_flash("application_detail.php?id={$id}", 'success', 'Application marked as Under Review.');
}

require_once __DIR__ . '/../includes/header.php';

// Doc stats
$doc_total    = count($documents);
$doc_verified = count(array_filter($documents, fn($d) => $d['status'] === 'verified'));
$doc_rejected = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));
$doc_pending  = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
$doc_pct      = $doc_total > 0 ? round(($doc_verified / $doc_total) * 100) : 0;

// Route stops
$stops = json_decode($app['route_stops'] ?? '[]', true) ?: [];
?>

<!-- ── Breadcrumb ────────────────────────────────────────── -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="applications.php">Applications</a></li>
        <li class="breadcrumb-item active"><?= e($app['application_number']) ?></li>
    </ol>
</nav>

<!-- ── Status Workflow Step Indicator ───────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="step-indicator">
            <?php
            $step_defs = [
                ['Submitted',       'bi-send-check'],
                ['Under Review',    'bi-search'],
                ['Docs Verified',   'bi-file-earmark-check'],
                ['Route Validated', 'bi-map'],
                [$app['status'] === 'rejected' ? 'Rejected' : 'Approved', $app['status'] === 'rejected' ? 'bi-x-circle' : 'bi-check-circle'],
            ];
            foreach ($step_defs as $i => $step):
                $step_num  = $i + 1;
                $is_done   = $current_step > $step_num;
                $is_active = $current_step === $step_num;
                $is_reject = $app['status'] === 'rejected' && $step_num === 5;
                $cls       = $is_reject ? 'rejected' : ($is_done ? 'completed' : ($is_active ? 'active' : ''));
            ?>
            <div class="step <?= $cls ?>">
                <div class="step-circle">
                    <?php if ($is_done): ?>
                        <i class="bi bi-check-lg"></i>
                    <?php elseif ($is_reject): ?>
                        <i class="bi bi-x-lg"></i>
                    <?php else: ?>
                        <?= $step_num ?>
                    <?php endif; ?>
                </div>
                <div class="step-label"><?= e($step[0]) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Header Row: Info + Quick Actions ─────────────────── -->
<div class="row g-4 mb-4">
    <!-- Applicant Info -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-lines-fill me-2 text-primary"></i>Applicant Information</span>
                <?= status_badge($app['status']) ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:45%">Application No.</td>
                                <td class="fw-semibold"><?= e($app['application_number']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Full Name</td>
                                <td class="fw-semibold"><?= e($app['applicant_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email</td>
                                <td><?= e($app['applicant_email']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Phone</td>
                                <td><?= e($app['applicant_phone']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date of Birth</td>
                                <td><?= format_dt($app['applicant_dob'], 'd M Y') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Gender</td>
                                <td class="text-capitalize"><?= e($app['applicant_gender']) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:45%">Category</td>
                                <td><?= category_badge($app['applicant_category']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">ID Proof Type</td>
                                <td><?= e($app['applicant_id_proof_type']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">ID Number</td>
                                <td class="fw-semibold"><?= e($app['applicant_id_proof_number']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Address</td>
                                <td><?= e($app['applicant_address']) ?>, <?= e($app['applicant_city']) ?> – <?= e($app['applicant_pincode']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submitted On</td>
                                <td><?= format_dt($app['created_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-lightning-charge me-2 text-primary"></i>Quick Actions
            </div>
            <div class="card-body d-grid gap-2">
                <?php if ($app['status'] === 'pending'): ?>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" name="start_review" class="btn btn-info w-100">
                            <i class="bi bi-play-circle me-2"></i>Start Review
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (in_array($app['status'], ['pending','under_review','docs_verified','route_validated','correction_requested'])): ?>
                    <a href="document_verification.php?id=<?= $id ?>" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-check me-2"></i>Verify Documents
                    </a>
                <?php endif; ?>

                <?php if (in_array($app['status'], ['docs_verified','under_review'])): ?>
                    <a href="route_validation.php?id=<?= $id ?>" class="btn btn-outline-warning">
                        <i class="bi bi-map me-2"></i>Validate Route
                    </a>
                <?php endif; ?>

                <?php if ($app['status'] === 'route_validated'): ?>
                    <a href="approve_reject.php?id=<?= $id ?>" class="btn btn-success">
                        <i class="bi bi-check2-square me-2"></i>Approve / Reject
                    </a>
                <?php endif; ?>

                <?php if (in_array($app['status'], ['under_review','docs_verified','route_validated'])): ?>
                    <a href="request_corrections.php?id=<?= $id ?>" class="btn btn-outline-warning">
                        <i class="bi bi-pencil-square me-2"></i>Request Corrections
                    </a>
                <?php endif; ?>

                <?php if ($app['status'] === 'approved'): ?>
                    <div class="alert alert-success py-2 mb-0">
                        <i class="bi bi-patch-check-fill me-2"></i>
                        <strong>Pass Issued:</strong> <?= e($app['pass_number']) ?>
                    </div>
                <?php endif; ?>

                <a href="applications.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Applications
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Pass & Route Info ─────────────────────────────────── -->
<div class="row g-4 mb-4">
    <!-- Pass Details -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-ticket-perforated me-2 text-primary"></i>Pass Details
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width:50%">Pass Type</td>
                        <td><?= pass_type_badge($app['pass_type']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Amount Paid</td>
                        <td class="fw-semibold text-success">₹<?= number_format($app['amount_paid'], 2) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Payment Ref</td>
                        <td><?= e($app['payment_ref'] ?? '—') ?></td>
                    </tr>
                    <?php if ($app['pass_number']): ?>
                    <tr>
                        <td class="text-muted">Pass Number</td>
                        <td class="fw-bold text-primary"><?= e($app['pass_number']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Valid From</td>
                        <td><?= format_dt($app['pass_start_date'], 'd M Y') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Valid Until</td>
                        <td><?= format_dt($app['pass_end_date'], 'd M Y') ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($app['admin_remarks']): ?>
                    <tr>
                        <td class="text-muted">Admin Remarks</td>
                        <td class="small"><?= e($app['admin_remarks']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($app['rejection_reason']): ?>
                    <tr>
                        <td class="text-muted">Rejection Reason</td>
                        <td class="small text-danger"><?= e($app['rejection_reason']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Route Details -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-signpost-2 me-2 text-primary"></i>Route Details
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="text-muted small">Route</div>
                        <div class="fw-semibold"><?= e($app['route_number']) ?> — <?= e($app['route_name']) ?></div>
                    </div>
                    <span class="badge <?= $app['route_is_active'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $app['route_is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="text-muted small">Boarding Stop</div>
                            <div class="fw-semibold text-primary"><?= e($app['boarding_stop']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded p-2 text-center">
                            <div class="text-muted small">Alighting Stop</div>
                            <div class="fw-semibold text-success"><?= e($app['alighting_stop']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Route stops visual -->
                <?php if ($stops): ?>
                <div class="route-stops">
                    <?php foreach ($stops as $si => $stop): ?>
                        <div class="text-center" style="flex:1; min-width:60px;">
                            <div class="route-stop-dot mx-auto mb-1
                                <?= $stop === $app['boarding_stop']  ? '' : ($stop === $app['alighting_stop'] ? '' : '') ?>"
                                 style="<?= $stop === $app['boarding_stop'] ? 'background:var(--primary)' : ($stop === $app['alighting_stop'] ? 'background:var(--success)' : 'background:var(--text-muted)') ?>">
                            </div>
                            <div class="route-stop-label <?= ($stop === $app['boarding_stop'] || $stop === $app['alighting_stop']) ? 'fw-bold text-primary' : '' ?>">
                                <?= e($stop) ?>
                            </div>
                        </div>
                        <?php if ($si < count($stops) - 1): ?>
                        <div class="route-stop-line"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <div class="text-center">
                        <div class="text-muted small">Monthly</div>
                        <div class="fw-semibold">₹<?= number_format($app['fare_monthly'], 0) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted small">Quarterly</div>
                        <div class="fw-semibold">₹<?= number_format($app['fare_quarterly'], 0) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted small">Annual</div>
                        <div class="fw-semibold">₹<?= number_format($app['fare_annual'], 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Documents Summary ─────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-paperclip me-2 text-primary"></i>Documents (<?= $doc_verified ?>/<?= $doc_total ?> verified)</span>
        <div class="d-flex align-items-center gap-3">
            <div class="progress" style="width:150px; height:8px;">
                <div class="progress-bar bg-success" style="width:<?= $doc_pct ?>%"></div>
            </div>
            <span class="small text-muted"><?= $doc_pct ?>%</span>
            <a href="document_verification.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Review Docs
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($documents as $doc): ?>
            <div class="col-md-3 col-sm-6">
                <div class="doc-card doc-<?= e($doc['status']) ?>">
                    <div class="doc-status-bar <?= e($doc['status']) ?>"></div>
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-semibold small"><?= e($doc['doc_label']) ?></span>
                            <?= doc_status_badge($doc['status']) ?>
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <?= e(strtoupper(pathinfo($doc['file_name'], PATHINFO_EXTENSION))) ?>
                            · <?= format_bytes((int)$doc['file_size']) ?>
                        </div>
                        <?php if ($doc['admin_notes']): ?>
                        <div class="mt-1 small text-<?= $doc['status'] === 'verified' ? 'success' : 'danger' ?>">
                            <i class="bi bi-chat-left-text me-1"></i><?= e($doc['admin_notes']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Correction Requests ───────────────────────────────── -->
<?php if (!empty($corrections)): ?>
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-pencil-square me-2 text-warning"></i>Correction Requests
    </div>
    <div class="card-body">
        <?php foreach ($corrections as $cr):
            $fields = json_decode($cr['fields_to_fix'] ?? '[]', true) ?: [];
        ?>
        <div class="border rounded p-3 mb-3 border-warning">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold small">Request by <?= e($cr['requested_by_name']) ?></span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?= $cr['status'] === 'resolved' ? 'success' : 'warning text-dark' ?>">
                        <?= e(ucfirst($cr['status'])) ?>
                    </span>
                    <span class="text-muted small"><?= format_dt($cr['created_at']) ?></span>
                </div>
            </div>
            <?php if ($fields): ?>
            <div class="mb-2">
                <?php foreach ($fields as $f): ?>
                <span class="badge bg-light text-dark border me-1">
                    <i class="bi bi-flag me-1 text-warning"></i><?= e(ucwords(str_replace('_',' ',$f))) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="mb-0 small text-muted"><?= nl2br(e($cr['message'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Audit Timeline ────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-2 text-primary"></i>Audit Trail
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
                <div class="timeline-time"><?= format_dt($log['created_at']) ?> — <?= e($log['ip_address'] ?? '') ?></div>
                <div class="timeline-title"><?= e($log['action']) ?></div>
                <?php if ($log['old_status'] && $log['new_status'] && $log['old_status'] !== $log['new_status']): ?>
                <div class="timeline-body">
                    <?= status_badge($log['old_status']) ?>
                    <i class="bi bi-arrow-right mx-1 text-muted"></i>
                    <?= status_badge($log['new_status']) ?>
                    <?php if ($log['performed_by_name']): ?>
                    · by <strong><?= e($log['performed_by_name']) ?></strong>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($log['notes']): ?>
                <div class="timeline-body"><?= e($log['notes']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
