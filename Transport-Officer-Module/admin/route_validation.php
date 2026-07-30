<?php
/**
 * Route Validation Page
 * Bus Pass Management System
 *
 * Two views:
 *  1. ?view=routes  — Manage master route list
 *  2. ?id=X         — Validate route for a specific application
 *  3. (default)     — List applications needing route validation
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$active_page = 'route_validation';
$view        = clean_input($_GET['view'] ?? '');
$app_id      = (int)($_GET['id'] ?? 0);

// ── Handle master routes view ─────────────────────────────
if ($view === 'routes') {
    $page_title = 'Manage Bus Routes';
    $routes     = get_all_routes(false);
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0">All configured bus routes in the system.</p>
        <a href="route_validation.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Validation Queue
        </a>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover datatable mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Route No.</th>
                            <th>Name</th>
                            <th>Source → Destination</th>
                            <th>Stops</th>
                            <th>Dist.</th>
                            <th>Monthly</th>
                            <th>Quarterly</th>
                            <th>Annual</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($routes as $r):
                            $stops = json_decode($r['stops'] ?? '[]', true) ?: [];
                        ?>
                        <tr>
                            <td class="ps-3"><span class="badge bg-primary"><?= e($r['route_number']) ?></span></td>
                            <td class="fw-semibold"><?= e($r['route_name']) ?></td>
                            <td><?= e($r['source']) ?> → <?= e($r['destination']) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border" data-bs-toggle="tooltip"
                                      title="<?= e(implode(', ', $stops)) ?>">
                                    <?= count($stops) ?> stops
                                </span>
                            </td>
                            <td><?= e($r['distance_km']) ?> km</td>
                            <td>₹<?= number_format($r['fare_monthly'], 0) ?></td>
                            <td>₹<?= number_format($r['fare_quarterly'], 0) ?></td>
                            <td>₹<?= number_format($r['fare_annual'], 0) ?></td>
                            <td>
                                <span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Single application route validation ──────────────────
if ($app_id) {
    $app = get_application($app_id);
    if (!$app) {
        redirect_with_flash('../admin/route_validation.php', 'error', 'Application not found.');
    }

    $page_title = 'Route Validation — ' . $app['application_number'];
    $stops      = json_decode($app['route_stops'] ?? '[]', true) ?: [];
    $all_routes = get_all_routes(true);

    // Check if boarding/alighting stops are on the route
    $boarding_valid  = in_array($app['boarding_stop'],  $stops);
    $alighting_valid = in_array($app['alighting_stop'], $stops);
    $route_active    = (bool)$app['route_is_active'];
    $all_valid       = $boarding_valid && $alighting_valid && $route_active;

    require_once __DIR__ . '/../includes/header.php';
    ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="route_validation.php">Route Validation</a></li>
            <li class="breadcrumb-item active"><?= e($app['application_number']) ?></li>
        </ol>
    </nav>

    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <div class="row g-4">
        <!-- Validation panel -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-map me-2 text-primary"></i>Route Validation Checks
                </div>
                <div class="card-body">
                    <!-- Validation checklist -->
                    <div class="list-group list-group-flush mb-4">

                        <!-- Check 1: Route exists -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-signpost-2 me-2 text-primary"></i>
                                <strong>Route Exists in System</strong>
                                <div class="text-muted small ms-4">Route <?= e($app['route_number']) ?> — <?= e($app['route_name']) ?></div>
                            </div>
                            <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Valid</span>
                        </div>

                        <!-- Check 2: Route active -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-toggle-on me-2 text-primary"></i>
                                <strong>Route is Active</strong>
                                <div class="text-muted small ms-4">Current operational status</div>
                            </div>
                            <span class="badge <?= $route_active ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $route_active ? 'check-lg' : 'x-lg' ?> me-1"></i>
                                <?= $route_active ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <!-- Check 3: Boarding stop -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-geo-alt me-2 text-primary"></i>
                                <strong>Boarding Stop Valid</strong>
                                <div class="text-muted small ms-4">
                                    Requested: <em>"<?= e($app['boarding_stop']) ?>"</em>
                                    <?php if (!$boarding_valid): ?>
                                    <span class="text-danger ms-1">— not found on this route</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?= $boarding_valid ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $boarding_valid ? 'check-lg' : 'x-lg' ?> me-1"></i>
                                <?= $boarding_valid ? 'Valid' : 'Invalid' ?>
                            </span>
                        </div>

                        <!-- Check 4: Alighting stop -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-geo-alt-fill me-2 text-success"></i>
                                <strong>Alighting Stop Valid</strong>
                                <div class="text-muted small ms-4">
                                    Requested: <em>"<?= e($app['alighting_stop']) ?>"</em>
                                    <?php if (!$alighting_valid): ?>
                                    <span class="text-danger ms-1">— not found on this route</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?= $alighting_valid ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $alighting_valid ? 'check-lg' : 'x-lg' ?> me-1"></i>
                                <?= $alighting_valid ? 'Valid' : 'Invalid' ?>
                            </span>
                        </div>

                        <!-- Check 5: Boarding ≠ Alighting -->
                        <?php $diff = $app['boarding_stop'] !== $app['alighting_stop']; ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <i class="bi bi-arrows-expand me-2 text-primary"></i>
                                <strong>Boarding ≠ Alighting Stop</strong>
                                <div class="text-muted small ms-4">Stops must be different</div>
                            </div>
                            <span class="badge <?= $diff ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-<?= $diff ? 'check-lg' : 'x-lg' ?> me-1"></i>
                                <?= $diff ? 'Valid' : 'Same Stop!' ?>
                            </span>
                        </div>

                        <!-- Check 6: Fare matches -->
                        <?php
                        $fare_key  = 'fare_' . $app['pass_type'];
                        $fare_expected = $app[$fare_key] ?? 0;
                        $fare_match    = abs($app['amount_paid'] - $fare_expected) < 0.01;
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                            <div>
                                <i class="bi bi-currency-rupee me-2 text-primary"></i>
                                <strong>Fare Amount Correct</strong>
                                <div class="text-muted small ms-4">
                                    Paid ₹<?= number_format($app['amount_paid'], 2) ?> ·
                                    Expected ₹<?= number_format($fare_expected, 2) ?>
                                </div>
                            </div>
                            <span class="badge <?= $fare_match ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <i class="bi bi-<?= $fare_match ? 'check-lg' : 'exclamation-triangle' ?> me-1"></i>
                                <?= $fare_match ? 'Correct' : 'Mismatch' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Overall result -->
                    <?php if ($all_valid && $diff): ?>
                    <div class="alert alert-success d-flex align-items-center gap-3">
                        <i class="bi bi-shield-check fs-4"></i>
                        <div class="flex-fill">
                            <strong>All route checks passed!</strong>
                            You can now mark the route as validated.
                        </div>
                        <?php if ($app['status'] !== 'route_validated' && $app['status'] !== 'approved'): ?>
                        <button class="btn btn-success" onclick="validateRoute(<?= $app_id ?>)">
                            <i class="bi bi-check2-all me-1"></i>Mark Validated
                        </button>
                        <?php else: ?>
                        <span class="badge bg-success fs-6">Already Validated</span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                        <div>
                            <strong>Route validation failed!</strong>
                            <div class="small">Please request corrections or reject the application.</div>
                        </div>
                        <a href="request_corrections.php?id=<?= $app_id ?>" class="btn btn-warning btn-sm ms-auto">
                            <i class="bi bi-pencil-square me-1"></i>Request Correction
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($app['status'] === 'route_validated'): ?>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="approve_reject.php?id=<?= $app_id ?>" class="btn btn-success">
                            <i class="bi bi-check2-square me-2"></i>Proceed to Approve / Reject
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Route visual panel -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-signpost-2 me-2 text-primary"></i>Route Map: <?= e($app['route_number']) ?>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small">Full Route</span>
                        <div class="fw-semibold"><?= e($app['route_source']) ?> → <?= e($app['route_destination']) ?></div>
                    </div>

                    <!-- Visual stop list -->
                    <div class="position-relative ps-4" style="border-left: 2px solid var(--primary);">
                        <?php foreach ($stops as $i => $stop):
                            $is_boarding  = $stop === $app['boarding_stop'];
                            $is_alighting = $stop === $app['alighting_stop'];
                            $is_first     = $i === 0;
                            $is_last      = $i === count($stops) - 1;
                            $dot_class    = $is_boarding ? 'bg-primary' : ($is_alighting ? 'bg-success' : 'bg-light border border-secondary');
                        ?>
                        <div class="d-flex align-items-center gap-2 py-1 position-relative">
                            <div class="position-absolute rounded-circle <?= $dot_class ?>"
                                 style="width:12px; height:12px; left:-7px; border:2px solid white; box-shadow:0 0 0 2px var(--primary);">
                            </div>
                            <span class="ms-1 <?= ($is_boarding || $is_alighting) ? 'fw-semibold' : '' ?>">
                                <?= e($stop) ?>
                                <?php if ($is_boarding):  ?><span class="badge bg-primary ms-1 small">Boarding</span><?php endif; ?>
                                <?php if ($is_alighting): ?><span class="badge bg-success ms-1 small">Alighting</span><?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <div class="row text-center g-2">
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="text-muted" style="font-size:.7rem;">Distance</div>
                                    <div class="fw-semibold small"><?= e($app['distance_km'] ?? '—') ?> km</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="text-muted" style="font-size:.7rem;">Stops</div>
                                    <div class="fw-semibold small"><?= count($stops) ?></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light rounded p-2">
                                    <div class="text-muted" style="font-size:.7rem;">Fare</div>
                                    <div class="fw-semibold small">₹<?= number_format($fare_expected, 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application summary -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person me-2 text-primary"></i>Applicant Summary
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e($app['applicant_name']) ?></td></tr>
                        <tr><td class="text-muted">Category</td><td><?= category_badge($app['applicant_category']) ?></td></tr>
                        <tr><td class="text-muted">Pass Type</td><td><?= pass_type_badge($app['pass_type']) ?></td></tr>
                        <tr><td class="text-muted">Amount Paid</td><td class="fw-semibold text-success">₹<?= number_format($app['amount_paid'], 2) ?></td></tr>
                        <tr><td class="text-muted">Status</td><td><?= status_badge($app['status']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// ── Default: Route validation queue (list) ────────────────
$page_title = 'Route Validation';
$pdo        = get_db();
$apps_for_validation = $pdo->query("
    SELECT a.*, ap.name AS applicant_name, ap.category AS applicant_category,
           r.route_number, r.route_name, r.stops, r.is_active AS route_active
    FROM applications a
    JOIN applicants ap ON a.applicant_id = ap.id
    JOIN bus_routes  r  ON a.route_id     = r.id
    WHERE a.status IN ('under_review','docs_verified')
    ORDER BY a.created_at ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Applications with verified documents that require route validation.</p>
    <div class="d-flex gap-2">
        <a href="route_validation.php?view=routes" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-signpost-2 me-1"></i>Manage Routes
        </a>
        <span class="badge bg-primary fs-6"><?= count($apps_for_validation) ?> pending</span>
    </div>
</div>

<?php if (empty($apps_for_validation)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
        <h5>No applications pending route validation</h5>
        <p class="text-muted">All current applications have been route-validated or are waiting for document verification first.</p>
        <a href="document_verification.php" class="btn btn-outline-primary">Go to Document Verification</a>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover datatable mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">App No.</th>
                        <th>Applicant</th>
                        <th>Route</th>
                        <th>Boarding → Alighting</th>
                        <th>Route Status</th>
                        <th>Stop Check</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apps_for_validation as $a):
                        $stops     = json_decode($a['stops'] ?? '[]', true) ?: [];
                        $b_valid   = in_array($a['boarding_stop'],  $stops);
                        $al_valid  = in_array($a['alighting_stop'], $stops);
                        $all_ok    = $b_valid && $al_valid && $a['route_active'];
                    ?>
                    <tr>
                        <td class="ps-3 fw-semibold small text-primary"><?= e($a['application_number']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($a['applicant_name']) ?></div>
                            <?= category_badge($a['applicant_category']) ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= e($a['route_number']) ?></span>
                            <div class="text-muted" style="font-size:.72rem;"><?= e($a['route_name']) ?></div>
                        </td>
                        <td class="small">
                            <span class="<?= $b_valid ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <?= e($a['boarding_stop']) ?>
                            </span>
                            <i class="bi bi-arrow-right text-muted mx-1"></i>
                            <span class="<?= $al_valid ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <?= e($a['alighting_stop']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $a['route_active'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $a['route_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($all_ok): ?>
                                <span class="badge bg-success"><i class="bi bi-check-all me-1"></i>All Valid</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-x me-1"></i>Issues Found</span>
                            <?php endif; ?>
                        </td>
                        <td><?= status_badge($a['status']) ?></td>
                        <td class="text-end pe-3">
                            <a href="route_validation.php?id=<?= (int)$a['id'] ?>"
                               class="btn btn-sm btn-<?= $all_ok ? 'outline-primary' : 'outline-warning' ?>">
                                <i class="bi bi-map me-1"></i>Validate
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
