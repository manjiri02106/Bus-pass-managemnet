<?php
/**
 * Request Corrections Page
 * Bus Pass Management System
 *
 * Two views:
 *  1. ?id=X   — Send correction request for a specific application
 *  2. default — List all open correction requests
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$active_page = 'request_corrections';
$app_id      = (int)($_GET['id'] ?? 0);

if ($app_id) {
    // ── Single application correction form ────────────────
    $app = get_application($app_id);
    if (!$app) {
        redirect_with_flash('../admin/request_corrections.php', 'error', 'Application not found.');
    }

    $documents        = get_application_documents($app_id);
    $past_corrections = get_correction_requests($app_id);
    $page_title       = 'Request Corrections — ' . $app['application_number'];

    // Correctable fields definition
    $correctable_fields = [
        'applicant_name'        => ['label' => 'Applicant Full Name',     'icon' => 'bi-person',              'group' => 'Personal Details'],
        'dob'                   => ['label' => 'Date of Birth',           'icon' => 'bi-calendar',            'group' => 'Personal Details'],
        'phone'                 => ['label' => 'Phone Number',            'icon' => 'bi-telephone',           'group' => 'Personal Details'],
        'address'               => ['label' => 'Residential Address',     'icon' => 'bi-house',               'group' => 'Personal Details'],
        'id_proof'              => ['label' => 'ID Proof Document',       'icon' => 'bi-card-text',           'group' => 'Documents'],
        'address_proof'         => ['label' => 'Address Proof Document',  'icon' => 'bi-file-earmark',        'group' => 'Documents'],
        'applicant_photo'       => ['label' => 'Applicant Photograph',    'icon' => 'bi-camera',              'group' => 'Documents'],
        'fee_receipt'           => ['label' => 'Fee Payment Receipt',     'icon' => 'bi-receipt',             'group' => 'Documents'],
        'category_certificate'  => ['label' => 'Category Certificate',   'icon' => 'bi-patch-check',         'group' => 'Documents'],
        'boarding_stop'         => ['label' => 'Boarding Stop',           'icon' => 'bi-geo-alt',             'group' => 'Route Details'],
        'alighting_stop'        => ['label' => 'Alighting Stop',          'icon' => 'bi-geo-alt-fill',        'group' => 'Route Details'],
        'route_id'              => ['label' => 'Selected Route',          'icon' => 'bi-signpost-2',          'group' => 'Route Details'],
        'pass_type'             => ['label' => 'Pass Type / Duration',    'icon' => 'bi-ticket-perforated',   'group' => 'Pass Details'],
        'payment_ref'           => ['label' => 'Payment Reference',       'icon' => 'bi-bank',                'group' => 'Pass Details'],
    ];

    // Group fields
    $grouped = [];
    foreach ($correctable_fields as $key => $def) {
        $grouped[$def['group']][$key] = $def;
    }

    require_once __DIR__ . '/../includes/header.php';
    ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="request_corrections.php">Correction Requests</a></li>
            <li class="breadcrumb-item active"><?= e($app['application_number']) ?></li>
        </ol>
    </nav>

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <div class="row g-4">
        <!-- Left: Correction form -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square me-2 text-warning"></i>
                    Select Fields Requiring Correction
                </div>
                <div class="card-body">
                    <form id="correctionForm"
                          onsubmit="return submitCorrectionRequest(this)">

                        <?= csrf_field() ?>
                        <input type="hidden" name="application_id" value="<?= $app_id ?>">

                        <!-- Field checkboxes grouped by section -->
                        <?php foreach ($grouped as $group_name => $fields): ?>
                        <h6 class="fw-bold text-uppercase text-muted mb-2 mt-3"
                            style="font-size:.72rem; letter-spacing:.08em;">
                            <i class="bi bi-folder2-open me-1"></i><?= e($group_name) ?>
                        </h6>
                        <?php foreach ($fields as $key => $def): ?>
                        <div class="correction-field-item mb-2" id="cfi-<?= e($key) ?>">
                            <input class="form-check-input mt-0 flex-shrink-0"
                                   type="checkbox"
                                   name="fields_to_fix[]"
                                   id="field_<?= e($key) ?>"
                                   value="<?= e($key) ?>">
                            <label class="form-check-label w-100 cursor-pointer d-flex align-items-center gap-2"
                                   for="field_<?= e($key) ?>">
                                <i class="bi <?= e($def['icon']) ?> text-primary"></i>
                                <span class="fw-semibold"><?= e($def['label']) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>

                        <!-- Select all / Clear -->
                        <div class="d-flex gap-2 mt-3 mb-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.querySelectorAll('#correctionForm input[type=checkbox]').forEach(cb => { cb.checked=true; cb.closest('.correction-field-item').classList.add('selected'); })">
                                Select All
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="document.querySelectorAll('#correctionForm input[type=checkbox]').forEach(cb => { cb.checked=false; cb.closest('.correction-field-item').classList.remove('selected'); })">
                                Clear All
                            </button>
                        </div>

                        <hr>

                        <!-- Message to applicant -->
                        <div class="mb-4">
                            <label for="correction_message" class="form-label fw-semibold">
                                Message to Applicant <span class="text-danger">*</span>
                            </label>
                            <p class="text-muted small mb-2">
                                This message will be displayed to the applicant when they log in.
                                Be specific about what needs to be corrected and why.
                            </p>
                            <textarea class="form-control" id="correction_message"
                                      name="correction_message" rows="6" maxlength="2000"
                                      placeholder="Example: Dear Applicant, your address proof document appears to be expired (issued before 3 months). Please upload a recent document dated within the last 3 months. Additionally, please verify your boarding stop — the stop you entered does not match the stops on the selected route."
                                      required></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <div class="text-muted small">Be clear and specific to help the applicant resolve the issue quickly.</div>
                                <div class="text-muted small" id="charCount">0/2000</div>
                            </div>
                        </div>

                        <!-- Quick message templates -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Quick Templates</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php
                                $templates = [
                                    'Blurry Document'  => 'The uploaded document is blurry or unreadable. Please upload a clear, high-resolution scan or photograph of the document.',
                                    'Expired Document' => 'The document you uploaded appears to be expired. Please submit a valid, unexpired document.',
                                    'Wrong Document'   => 'The document uploaded does not match the required document type. Please re-read the requirements and upload the correct document.',
                                    'Invalid Stop'     => 'The boarding/alighting stop you entered is not available on the selected route. Please select a valid stop from the route\'s official stop list.',
                                ];
                                foreach ($templates as $tname => $tmsg): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="document.getElementById('correction_message').value = <?= json_encode($tmsg) ?>; updateCharCount();">
                                    <?= e($tname) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-warning fw-semibold px-4">
                                <i class="bi bi-send me-2"></i>Send Correction Request
                            </button>
                            <a href="application_detail.php?id=<?= $app_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Application summary + past corrections -->
        <div class="col-lg-4">
            <!-- Application summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person me-2 text-primary"></i>Application Summary
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted">Name</td>
                            <td class="fw-semibold"><?= e($app['applicant_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">App No.</td>
                            <td class="fw-semibold text-primary small"><?= e($app['application_number']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td class="small"><?= e($app['applicant_email']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Category</td>
                            <td><?= category_badge($app['applicant_category']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Route</td>
                            <td>
                                <span class="badge bg-secondary"><?= e($app['route_number']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Pass Type</td>
                            <td><?= pass_type_badge($app['pass_type']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td><?= status_badge($app['status']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Documents status -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-paperclip me-2 text-primary"></i>Document Status
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($documents as $doc): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="small"><?= e($doc['doc_label']) ?></span>
                            <?= doc_status_badge($doc['status']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Past correction requests -->
            <?php if (!empty($past_corrections)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2 text-warning"></i>
                    Past Correction Requests (<?= count($past_corrections) ?>)
                </div>
                <div class="card-body">
                    <?php foreach ($past_corrections as $cr):
                        $fields = json_decode($cr['fields_to_fix'] ?? '[]', true) ?: [];
                    ?>
                    <div class="border rounded p-2 mb-2 border-warning-subtle">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-semibold text-muted"><?= format_dt($cr['created_at'], 'd M Y') ?></span>
                            <span class="badge bg-<?= $cr['status'] === 'resolved' ? 'success' : 'warning text-dark' ?> small">
                                <?= e(ucfirst($cr['status'])) ?>
                            </span>
                        </div>
                        <?php foreach ($fields as $f): ?>
                        <span class="badge bg-light text-dark border me-1" style="font-size:.65rem;">
                            <?= e(ucwords(str_replace('_', ' ', $f))) ?>
                        </span>
                        <?php endforeach; ?>
                        <p class="mb-0 small text-muted mt-1" style="line-height:1.4;">
                            <?= e(mb_substr($cr['message'], 0, 100)) ?>…
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateCharCount() {
        const ta  = document.getElementById('correction_message');
        const cnt = document.getElementById('charCount');
        if (ta && cnt) cnt.textContent = ta.value.length + '/2000';
    }
    document.getElementById('correction_message')?.addEventListener('input', updateCharCount);
    </script>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Default: All correction requests ─────────────────────
$page_title = 'Request Corrections';
$pdo        = get_db();

$all_corrections = $pdo->query("
    SELECT cr.*, a.application_number, a.status AS app_status,
           ap.name AS applicant_name, ap.email AS applicant_email,
           au.name AS requested_by_name
    FROM correction_requests cr
    JOIN applications a  ON cr.application_id = a.id
    JOIN applicants   ap ON a.applicant_id     = ap.id
    LEFT JOIN admin_users au ON cr.requested_by = au.id
    ORDER BY cr.created_at DESC
")->fetchAll();

// Applications eligible for correction request
$eligible_apps = $pdo->query("
    SELECT a.id, a.application_number, ap.name AS applicant_name, a.status,
           r.route_number
    FROM applications a
    JOIN applicants ap ON a.applicant_id = ap.id
    JOIN bus_routes  r  ON a.route_id     = r.id
    WHERE a.status IN ('under_review','docs_verified','route_validated','correction_requested')
    ORDER BY a.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4 mb-4">
    <!-- Eligible applications card -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2 text-primary"></i>Eligible Applications</span>
                <span class="badge bg-primary"><?= count($eligible_apps) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($eligible_apps)): ?>
                <div class="text-center py-4 text-muted small">No applications eligible right now.</div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($eligible_apps as $ea): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                        <div>
                            <div class="fw-semibold small"><?= e($ea['applicant_name']) ?></div>
                            <div class="text-muted" style="font-size:.72rem;">
                                <?= e($ea['application_number']) ?> ·
                                <span class="badge bg-secondary" style="font-size:.6rem;"><?= e($ea['route_number']) ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?= status_badge($ea['status']) ?>
                            <a href="request_corrections.php?id=<?= (int)$ea['id'] ?>"
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Correction request stats -->
    <div class="col-lg-7">
        <?php
        $open_count     = count(array_filter($all_corrections, fn($c) => $c['status'] === 'open'));
        $progress_count = count(array_filter($all_corrections, fn($c) => $c['status'] === 'in_progress'));
        $resolved_count = count(array_filter($all_corrections, fn($c) => $c['status'] === 'resolved'));
        ?>
        <div class="row g-3 mb-3">
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fs-2 fw-bold text-warning"><?= $open_count ?></div>
                    <div class="text-muted small">Open</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fs-2 fw-bold text-info"><?= $progress_count ?></div>
                    <div class="text-muted small">In Progress</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center py-3">
                    <div class="fs-2 fw-bold text-success"><?= $resolved_count ?></div>
                    <div class="text-muted small">Resolved</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2 text-primary"></i>How Correction Requests Work
            </div>
            <div class="card-body">
                <ol class="mb-0 ps-3" style="font-size:.875rem; line-height:2;">
                    <li>Select an application from the list on the left.</li>
                    <li>Choose which fields need to be corrected.</li>
                    <li>Write a clear message explaining what to fix.</li>
                    <li>Submit — the applicant is notified and the status changes to <strong>Correction Requested</strong>.</li>
                    <li>When the applicant resubmits, the application returns for review.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- All correction requests table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>All Correction Requests</span>
        <span class="badge bg-secondary"><?= count($all_corrections) ?> total</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($all_corrections)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
            <p class="text-muted">No correction requests have been made yet.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">App No.</th>
                        <th>Applicant</th>
                        <th>Fields</th>
                        <th>Requested By</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_corrections as $cr):
                        $fields = json_decode($cr['fields_to_fix'] ?? '[]', true) ?: [];
                    ?>
                    <tr>
                        <td class="ps-3 fw-semibold small text-primary"><?= e($cr['application_number']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($cr['applicant_name']) ?></div>
                            <div class="text-muted small"><?= e($cr['applicant_email']) ?></div>
                        </td>
                        <td>
                            <?php foreach (array_slice($fields, 0, 2) as $f): ?>
                            <span class="badge bg-light text-dark border me-1 small">
                                <?= e(ucwords(str_replace('_', ' ', $f))) ?>
                            </span>
                            <?php endforeach; ?>
                            <?php if (count($fields) > 2): ?>
                            <span class="text-muted small">+<?= count($fields) - 2 ?> more</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= e($cr['requested_by_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?= $cr['status'] === 'resolved' ? 'success' : ($cr['status'] === 'in_progress' ? 'info' : 'warning text-dark') ?>">
                                <?= e(ucwords(str_replace('_', ' ', $cr['status']))) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= format_dt($cr['created_at'], 'd M Y') ?></td>
                        <td class="text-end pe-3">
                            <a href="application_detail.php?id=<?= (int)$cr['application_id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
