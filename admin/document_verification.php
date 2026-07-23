<?php
/**
 * Document Verification Page
 * Bus Pass Management System
 *
 * - Lists all applications that need document review
 * - For a specific application: shows each document with preview + verify/reject controls
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$active_page = 'document_verification';

// If an application ID is provided, show per-document review UI
$app_id = (int)($_GET['id'] ?? 0);

if ($app_id) {
    // ── Single application document review ───────────────
    $app = get_application($app_id);
    if (!$app) {
        redirect_with_flash('../admin/document_verification.php', 'error', 'Application not found.');
    }

    $documents   = get_application_documents($app_id);
    $doc_total   = count($documents);
    $doc_verified = count(array_filter($documents, fn($d) => $d['status'] === 'verified'));
    $doc_rejected = count(array_filter($documents, fn($d) => $d['status'] === 'rejected'));
    $doc_pending  = count(array_filter($documents, fn($d) => $d['status'] === 'pending'));
    $doc_pct      = $doc_total > 0 ? round(($doc_verified / $doc_total) * 100) : 0;

    $page_title = 'Document Verification — ' . $app['application_number'];
} else {
    // ── List of applications needing doc review ───────────
    $page_title = 'Document Verification';
    $pdo = get_db();
    $apps_needing_review = $pdo->query("
        SELECT a.*, ap.name AS applicant_name, ap.category AS applicant_category,
               r.route_number,
               (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id) AS doc_total,
               (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id AND d.status = 'verified') AS doc_verified,
               (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id AND d.status = 'pending') AS doc_pending,
               (SELECT COUNT(*) FROM documents d WHERE d.application_id = a.id AND d.status = 'rejected') AS doc_rejected
        FROM applications a
        JOIN applicants ap ON a.applicant_id = ap.id
        JOIN bus_routes  r  ON a.route_id     = r.id
        WHERE a.status IN ('pending','under_review','docs_verified','correction_requested','route_validated')
        ORDER BY a.created_at ASC
    ")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';

// ─── Document lightbox modal (shared) ─────────────────────
?>

<!-- Document Lightbox Modal -->
<div class="modal fade" id="docLightbox" tabindex="-1" aria-label="Document Preview">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center bg-dark p-0">
                <!-- content injected by JS -->
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if (!$app_id): ?>
<!-- ══════════════════════════════════════════════════════════
     DOCUMENT VERIFICATION QUEUE (list view)
══════════════════════════════════════════════════════════ -->

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Select an application below to review its uploaded documents.</p>
    <span class="badge bg-primary fs-6"><?= count($apps_needing_review) ?> Application(s) pending review</span>
</div>

<?php if (empty($apps_needing_review)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
        <h5>All documents reviewed!</h5>
        <p class="text-muted">No applications require document verification at this time.</p>
        <a href="applications.php" class="btn btn-outline-primary">View All Applications</a>
    </div>
</div>
<?php else: ?>

<div class="row g-3">
    <?php foreach ($apps_needing_review as $a):
        $pct = $a['doc_total'] > 0 ? round(($a['doc_verified'] / $a['doc_total']) * 100) : 0;
    ?>
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-bold"><?= e($a['applicant_name']) ?></div>
                        <div class="text-muted small"><?= e($a['application_number']) ?></div>
                    </div>
                    <?= status_badge($a['status']) ?>
                </div>

                <div class="d-flex gap-2 flex-wrap mb-3">
                    <?= category_badge($a['applicant_category']) ?>
                    <span class="badge bg-secondary"><?= e($a['route_number']) ?></span>
                    <?= pass_type_badge($a['pass_type']) ?>
                </div>

                <!-- Document progress -->
                <div class="mb-1 d-flex justify-content-between small">
                    <span class="text-muted">Documents Verified</span>
                    <span><?= $a['doc_verified'] ?>/<?= $a['doc_total'] ?></span>
                </div>
                <div class="progress mb-3" style="height:6px;">
                    <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                </div>

                <div class="d-flex gap-2 flex-wrap mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="bi bi-check me-1"></i><?= $a['doc_verified'] ?> Verified
                    </span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="bi bi-x me-1"></i><?= $a['doc_rejected'] ?> Rejected
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                        <i class="bi bi-clock me-1"></i><?= $a['doc_pending'] ?> Pending
                    </span>
                </div>

                <a href="document_verification.php?id=<?= (int)$a['id'] ?>"
                   class="btn btn-primary w-100 btn-sm">
                    <i class="bi bi-file-earmark-check me-2"></i>Review Documents
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════
     SINGLE APPLICATION — DOCUMENT REVIEW
══════════════════════════════════════════════════════════ -->

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="document_verification.php">Document Verification</a></li>
        <li class="breadcrumb-item active"><?= e($app['application_number']) ?></li>
    </ol>
</nav>

<!-- Application header -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-md-4">
                <div class="fw-bold fs-5"><?= e($app['applicant_name']) ?></div>
                <div class="text-muted"><?= e($app['application_number']) ?></div>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-1 text-muted small fw-semibold">DOCUMENT PROGRESS</div>
                <div class="progress mb-1" style="height:10px; border-radius:999px;">
                    <div class="progress-bar bg-success fw-semibold" style="width:<?= $doc_pct ?>%">
                        <?php if ($doc_pct >= 30): ?><?= $doc_pct ?>%<?php endif; ?>
                    </div>
                </div>
                <div class="small text-muted"><?= $doc_verified ?> verified · <?= $doc_rejected ?> rejected · <?= $doc_pending ?> pending</div>
            </div>
            <div class="col-md-4 text-end d-flex gap-2 justify-content-end">
                <?= status_badge($app['status']) ?>
                <a href="application_detail.php?id=<?= $app_id ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i>Full Detail
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Document Cards -->
<div class="row g-4" id="docCardsGrid">
    <?php foreach ($documents as $doc): ?>
    <?php
        $base_url  = '../uploads/documents/';
        $is_image  = str_starts_with($doc['mime_type'], 'image/');
        $is_pdf    = $doc['mime_type'] === 'application/pdf';
        $preview_icon = $is_pdf ? 'bi-file-earmark-pdf-fill' : ($is_image ? 'bi-image' : 'bi-file-earmark');
    ?>
    <div class="col-lg-4 col-md-6" id="doc-card-<?= (int)$doc['id'] ?>">
        <div class="doc-card doc-<?= e($doc['status']) ?>">
            <div class="doc-status-bar <?= e($doc['status']) ?>"></div>

            <!-- Preview area -->
            <div class="doc-preview"
                 onclick="openDocLightbox('<?= e($base_url . $doc['file_name']) ?>', '<?= e($doc['doc_label']) ?>', '<?= e($doc['mime_type']) ?>')">
                <?php if ($is_image): ?>
                    <img src="<?= e($base_url . $doc['file_name']) ?>" class="img-fluid" alt="<?= e($doc['doc_label']) ?>">
                <?php elseif ($is_pdf): ?>
                    <div class="d-flex flex-column align-items-center text-muted">
                        <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                        <span class="small mt-1">Click to preview PDF</span>
                    </div>
                <?php else: ?>
                    <i class="bi <?= $preview_icon ?>"></i>
                <?php endif; ?>
                <div class="doc-preview-overlay">
                    <i class="bi bi-zoom-in fs-3"></i>
                </div>
            </div>

            <div class="p-3">
                <!-- Doc info -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold"><?= e($doc['doc_label']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;">
                            <?= e(strtoupper(pathinfo($doc['file_name'], PATHINFO_EXTENSION))) ?>
                            · <?= format_bytes((int)$doc['file_size']) ?>
                            · <?= format_dt($doc['uploaded_at'], 'd M Y') ?>
                        </div>
                    </div>
                    <?= doc_status_badge($doc['status']) ?>
                </div>

                <?php if ($doc['verified_by_name']): ?>
                <div class="small text-muted mb-2">
                    <i class="bi bi-person-check me-1"></i>
                    By <?= e($doc['verified_by_name']) ?> — <?= format_dt($doc['verified_at']) ?>
                </div>
                <?php endif; ?>

                <!-- Notes textarea -->
                <div class="mb-3">
                    <label class="form-label" for="doc_notes_<?= (int)$doc['id'] ?>">Admin Notes</label>
                    <textarea class="form-control form-control-sm"
                              id="doc_notes_<?= (int)$doc['id'] ?>"
                              rows="2"
                              placeholder="Add verification notes…"
                              maxlength="500"><?= e($doc['admin_notes'] ?? '') ?></textarea>
                </div>

                <!-- Verify / Reject buttons -->
                <div class="d-flex gap-2">
                    <button class="btn btn-verify btn-sm flex-fill"
                            onclick="verifyDocument(<?= (int)$doc['id'] ?>, 'verified', <?= $app_id ?>)"
                            <?= $doc['status'] === 'verified' ? 'disabled' : '' ?>>
                        <i class="bi bi-check-lg me-1"></i>Verify
                    </button>
                    <button class="btn btn-reject btn-sm flex-fill"
                            onclick="verifyDocument(<?= (int)$doc['id'] ?>, 'rejected', <?= $app_id ?>)"
                            <?= $doc['status'] === 'rejected' ? 'disabled' : '' ?>>
                        <i class="bi bi-x-lg me-1"></i>Reject
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($documents)): ?>
    <div class="col-12">
        <div class="card text-center py-5">
            <i class="bi bi-folder2-open fs-1 text-muted d-block mb-2"></i>
            <p class="text-muted">No documents uploaded for this application.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Bottom action bar -->
<?php if ($doc_verified === $doc_total && $doc_total > 0 && $app['status'] === 'under_review'): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mt-4" role="alert">
    <i class="bi bi-check-circle-fill fs-4"></i>
    <div class="flex-fill">
        <strong>All documents verified!</strong>
        This application is ready for route validation.
    </div>
    <a href="route_validation.php?id=<?= $app_id ?>" class="btn btn-success">
        <i class="bi bi-map me-2"></i>Proceed to Route Validation
    </a>
</div>
<?php elseif ($app['status'] === 'docs_verified'): ?>
<div class="alert alert-info d-flex align-items-center gap-3 mt-4" role="alert">
    <i class="bi bi-info-circle-fill fs-4"></i>
    <div class="flex-fill">Documents already verified. Proceed to route validation.</div>
    <a href="route_validation.php?id=<?= $app_id ?>" class="btn btn-primary">
        <i class="bi bi-map me-2"></i>Validate Route
    </a>
</div>
<?php elseif ($app['status'] === 'route_validated'): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mt-4" role="alert">
    <i class="bi bi-patch-check-fill fs-4"></i>
    <div class="flex-fill">Route validated. This application is ready for final approval.</div>
    <a href="approve_reject.php?id=<?= $app_id ?>" class="btn btn-success">
        <i class="bi bi-check2-square me-2"></i>Approve / Reject
    </a>
</div>
<?php endif; ?>

<?php
// Hidden CSRF token for AJAX calls
echo '<input type="hidden" id="global_csrf" name="csrf_token" value="' . e(csrf_token()) . '">';
echo '<meta name="csrf-token" content="' . e(csrf_token()) . '">';
?>

<?php endif; // end if $app_id ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
