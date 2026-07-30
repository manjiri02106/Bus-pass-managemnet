<?php
// src/verify-application.php — Core verification page for all 5 features
// Delegates to: VerifyApplicationsService, DocumentVerificationService,
//               RouteValidationService, ApproveRejectService, RequestCorrectionsService

require_once dirname(__DIR__) . '/config/db-connection.php';
require_once dirname(__DIR__) . '/config/app-config.php';
require_once __DIR__ . '/auth-helper.php';
require_once __DIR__ . '/services/verify-applications.service.php';
require_once __DIR__ . '/services/document-verification.service.php';
require_once __DIR__ . '/services/route-validation.service.php';
require_once __DIR__ . '/services/approve-reject.service.php';
require_once __DIR__ . '/services/request-corrections.service.php';

check_auth(['officer', 'admin']);

$application_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$application_id) { header("Location: officer-dashboard.php"); exit(); }

$verifyService      = new VerifyApplicationsService();
$docService         = new DocumentVerificationService();
$routeService       = new RouteValidationService();
$approveService     = new ApproveRejectService();
$correctionService  = new RequestCorrectionsService();

$alert_message = '';
$alert_type    = 'success';

/* ========== POST ACTION HANDLERS ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── 4.3 Route Validation: Dispatcher ──────────────────────────────────────
    if ($action === 'reroute') {
        $new_dept = trim($_POST['routing_dept'] ?? '');
        $comments = trim($_POST['routing_comments'] ?? '');
        if (!empty($new_dept)) {
            try {
                $routeService->dispatchWorkflowRoute($pdo, $application_id, $new_dept, $comments, $_SESSION['user_id']);
                $alert_message = "Application routed to: " . htmlspecialchars($new_dept);
            } catch (Exception $e) {
                $alert_message = "Routing failed: " . $e->getMessage(); $alert_type = 'danger';
            }
        }
    }

    // ── 4.2 Document Verification: Toggle status ───────────────────────────────
    elseif ($action === 'verify_doc' || $action === 'invalidate_doc') {
        $doc_type = $_POST['doc_type'] ?? '';
        $comments = trim($_POST['doc_comments'] ?? '');
        $status   = ($action === 'verify_doc') ? 'verified' : 'invalid';
        try {
            $docService->recordVerificationStatus($pdo, $application_id, $doc_type, $status, $comments, $_SESSION['user_id']);
            $pdo->prepare("UPDATE applications SET status='under_verification', last_updated=CURRENT_TIMESTAMP WHERE id=?")
                ->execute([$application_id]);
            $alert_message = "Document status set to " . strtoupper($status) . " for: " . str_replace('_', ' ', strtoupper($doc_type));
        } catch (Exception $e) {
            $alert_message = "Document update failed: " . $e->getMessage(); $alert_type = 'danger';
        }
    }

    // ── 4.5 Request Corrections ────────────────────────────────────────────────
    elseif ($action === 'request_corrections') {
        $corrections = $_POST['corrections'] ?? [];
        if (!empty($corrections)) {
            try {
                $correctionService->createCorrectionRequests($pdo, $application_id, $corrections, $_SESSION['user_id']);
                $alert_message = "Correction requests sent. Application paused at 'Correction Required'.";
            } catch (Exception $e) {
                $alert_message = "Failed to send corrections: " . $e->getMessage(); $alert_type = 'danger';
            }
        } else {
            $alert_message = "Check at least one field to request a correction."; $alert_type = 'danger';
        }
    }

    // ── 4.4 Approve Application ────────────────────────────────────────────────
    elseif ($action === 'approve') {
        $months = filter_input(INPUT_POST, 'validity_duration', FILTER_VALIDATE_INT);
        $notes  = trim($_POST['decision_notes'] ?? 'Approved by officer.');
        $result = $approveService->approveApplication($pdo, $application_id, $months, $notes, $_SESSION['user_id']);
        if ($result['success']) {
            $alert_message = "Application APPROVED. Pass issued: " . htmlspecialchars($result['pass_number']);
        } else {
            $alert_message = "Approval failed: " . $result['error']; $alert_type = 'danger';
        }
    }

    // ── 4.4 Reject Application ─────────────────────────────────────────────────
    elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $result = $approveService->rejectApplication($pdo, $application_id, $reason, $_SESSION['user_id']);
        if ($result['success']) {
            $alert_message = "Application REJECTED. Reason recorded."; $alert_type = 'danger';
        } else {
            $alert_message = "Rejection failed: " . $result['error']; $alert_type = 'danger';
        }
    }
}

/* ========== LOAD APPLICATION DATA ========== */
try {
    $stmt = $pdo->prepare("
        SELECT a.*, s.prn_number, s.roll_number, s.department, s.class, s.mobile, s.address,
               u.full_name AS student_name, u.email AS student_email,
               r.route_number, r.source, r.destination, r.distance, r.stops
        FROM applications a
        JOIN students s ON a.student_id = s.id
        JOIN users    u ON s.user_id    = u.id
        JOIN routes   r ON a.route_id   = r.id
        WHERE a.id = ?
    ");
    $stmt->execute([$application_id]);
    $app = $stmt->fetch();
    if (!$app) { header("Location: officer-dashboard.php"); exit(); }

    // Document verifications
    $stmt_dv = $pdo->prepare("SELECT * FROM document_verifications WHERE application_id = ?");
    $stmt_dv->execute([$application_id]);
    $doc_verif = [];
    while ($row = $stmt_dv->fetch()) { $doc_verif[$row['document_type']] = $row; }

    // Pending corrections
    $stmt_cr = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
    $stmt_cr->execute([$application_id]);
    $pending_corrections = $stmt_cr->fetchAll();

    // Decisions log
    $stmt_dec = $pdo->prepare("SELECT d.*, u.full_name AS officer_name FROM decisions d JOIN users u ON d.officer_id=u.id WHERE d.application_id=? ORDER BY d.decided_at DESC");
    $stmt_dec->execute([$application_id]);
    $decisions = $stmt_dec->fetchAll();

    // Pass if approved
    $pass = null;
    if ($app['status'] === 'approved') {
        $stmt_pass = $pdo->prepare("SELECT * FROM passes WHERE application_id = ?");
        $stmt_pass->execute([$application_id]);
        $pass = $stmt_pass->fetch();
    }

    // Route distance validation via service (4.3)
    $routeIsValid  = $routeService->validateDistance($app['distance'], MAX_ROUTE_DISTANCE);
    $suggestedDept = $routeService->determineRoutingQueue($app['distance'], MAX_ROUTE_DISTANCE);

    // 4.1 Completeness and eligibility checks
    $completenessResult = $verifyService->checkCompleteness([
        'prn_number'  => $app['prn_number'],  'roll_number' => $app['roll_number'],
        'department'  => $app['department'],   'class'       => $app['class'],
        'mobile'      => $app['mobile'],       'address'     => $app['address']
    ]);
    $eligibilityResult = $verifyService->checkEligibility(
        ['prn_number' => $app['prn_number'], 'mobile' => $app['mobile']],
        ['email'      => $app['student_email']]
    );

    // 4.2 Automated file checks (document standards)
    $file_checks = [];
    foreach (['college_id' => $app['college_id_doc'], 'photograph' => $app['photograph_doc'], 'address_proof' => $app['address_proof_doc']] as $type => $path) {
        $fullpath = dirname(__DIR__) . '/' . ltrim($path, '/');
        $file_checks[$type] = $docService->validateDocumentFile($fullpath, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
    }

} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Application #<?php echo $application_id; ?> — Bus Pass Portal</title>
    <meta name="description" content="Verify, validate documents, route, and make decisions on student bus pass application.">
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
    <a href="officer-dashboard.php" class="sidebar-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="officer-dashboard.php?view=pending" class="sidebar-link active"><i class="fa-solid fa-user-check"></i> Verify Applications</a>
    <a href="officer-dashboard.php?view=approved" class="sidebar-link"><i class="fa-solid fa-signature"></i> Approved Passes</a>
    <a href="officer-dashboard.php?view=corrections" class="sidebar-link"><i class="fa-solid fa-circle-question"></i> Pending Corrections</a>
    <hr style="border:0; border-top:1px solid var(--border-color); margin:1rem 0;">
    <form action="logout.php" method="POST" style="width:100%;">
        <button type="submit" class="sidebar-link" style="background:none; border:none; width:100%; text-align:left; cursor:pointer; font-family:inherit;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
    </form>
</aside>
<main class="main-content">

<div class="page-title-section" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h2 class="page-title">Application Verification <small style="font-size:0.7em; color:var(--text-muted);">#<?php echo $application_id; ?></small></h2>
        <p class="page-subtitle">Run completeness checks, validate documents, route workflow, and make approval decision.</p>
    </div>
    <a href="officer-dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<?php if (!empty($alert_message)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>"><i class="fa-solid fa-circle-info"></i> <?php echo $alert_message; ?></div>
<?php endif; ?>

<!-- 4.1 Eligibility inline alerts -->
<?php if (!$completenessResult['valid']): ?>
    <div class="alert alert-warning">
        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Completeness Check Failed:</strong>
        <?php echo implode(' | ', array_map('htmlspecialchars', $completenessResult['errors'])); ?>
    </div>
<?php endif; ?>
<?php if (!$eligibilityResult['valid']): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-xmark"></i> <strong>Eligibility Check Failed:</strong>
        <?php echo implode(' | ', array_map('htmlspecialchars', $eligibilityResult['errors'])); ?>
    </div>
<?php endif; ?>

<div class="detail-grid">
<!-- ══════════════════════ LEFT COLUMN ══════════════════════ -->
<div>

    <!-- 4.1 Student Profile -->
    <div class="detail-card">
        <div class="detail-card-title">
            <span><i class="fa-solid fa-user-check" style="color:var(--accent); margin-right:8px;"></i> 4.1 Verify Applications — Student Profile</span>
            <?php echo get_status_badge($app['status']); ?>
        </div>
        <div class="info-list">
            <?php foreach ([
                'Full Name'    => $app['student_name'],
                'PRN Number'   => $app['prn_number'],
                'Roll Number'  => $app['roll_number'],
                'Dept / Class' => $app['department'] . ' – ' . $app['class'],
                'Mobile'       => $app['mobile'],
                'Email'        => $app['student_email'],
            ] as $label => $value): ?>
            <div class="info-item">
                <span class="info-label"><?php echo $label; ?></span>
                <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="info-item" style="grid-column:span 2;">
                <span class="info-label">Home Address</span>
                <span class="info-value"><?php echo htmlspecialchars($app['address']); ?></span>
            </div>
        </div>
        <!-- Completeness check indicators -->
        <div style="margin-top:1.25rem; display:flex; gap:10px; flex-wrap:wrap;">
            <span class="badge <?php echo $completenessResult['valid'] ? 'badge-approved' : 'badge-rejected'; ?>">
                <span class="badge-dot"></span>
                Profile <?php echo $completenessResult['valid'] ? 'Complete' : 'Incomplete'; ?>
            </span>
            <span class="badge <?php echo $eligibilityResult['valid'] ? 'badge-approved' : 'badge-rejected'; ?>">
                <span class="badge-dot"></span>
                Eligibility <?php echo $eligibilityResult['valid'] ? 'Passed' : 'Failed'; ?>
            </span>
        </div>
    </div>

    <!-- 4.2 Document Verification -->
    <div class="detail-card">
        <div class="detail-card-title">
            <span><i class="fa-solid fa-file-shield" style="color:var(--accent); margin-right:8px;"></i> 4.2 Document Verification</span>
            <span style="font-size:0.8rem; font-weight:normal; color:var(--text-secondary);">Auto + manual review</span>
        </div>

        <?php
        $doc_labels = [
            'college_id'   => ['label' => 'College Identity Card',       'icon' => 'fa-id-card-clip', 'path' => $app['college_id_doc']],
            'photograph'   => ['label' => 'Student Photograph',           'icon' => 'fa-image-portrait','path' => $app['photograph_doc']],
            'address_proof'=> ['label' => 'Residential Address Proof',    'icon' => 'fa-file-invoice', 'path' => $app['address_proof_doc']],
        ];
        foreach ($doc_labels as $type => $meta):
            $auto  = $file_checks[$type];
            $manual= $doc_verif[$type] ?? null;
            $row_class = ($manual && $manual['status'] === 'verified')  ? 'verified-highlight'
                       : ($manual && $manual['status'] === 'invalid')   ? 'invalid-highlight' : '';
        ?>
        <div class="doc-item-row <?php echo $row_class; ?>">
            <div class="doc-meta">
                <div class="doc-icon"><i class="fa-solid <?php echo $meta['icon']; ?>"></i></div>
                <div>
                    <div class="doc-name"><?php echo $meta['label']; ?></div>
                    <div class="doc-size">
                        Auto: <?php echo $auto['valid'] ? '✅ ' : '⚠️ '; ?><?php echo htmlspecialchars($auto['message']); ?>
                    </div>
                    <?php if ($manual): ?>
                    <div class="doc-size" style="margin-top:3px;">
                        Manual: <strong style="color:<?php echo ($manual['status']==='verified') ? 'var(--success)' : 'var(--danger)'; ?>">
                            <?php echo strtoupper($manual['status']); ?>
                        </strong>
                        <?php if ($manual['comments']): ?> — <?php echo htmlspecialchars($manual['comments']); ?><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="doc-actions">
                <a href="<?php echo htmlspecialchars($meta['path']); ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa-solid fa-up-right-from-square"></i> View</a>
                <button class="btn btn-primary btn-sm" onclick="openDocModal('<?php echo $type; ?>')">Update Status</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pending corrections list -->
    <?php if (!empty($pending_corrections)): ?>
    <div class="detail-card" style="border-color:rgba(245,158,11,0.4);">
        <div class="detail-card-title" style="color:var(--warning);">
            <span><i class="fa-solid fa-circle-question"></i> Pending Correction Requests Sent</span>
        </div>
        <table>
            <thead><tr><th>Field</th><th>Instruction</th><th>Requested</th></tr></thead>
            <tbody>
            <?php foreach ($pending_corrections as $pc): ?>
            <tr>
                <td><code style="background:rgba(255,255,255,0.05); padding:2px 6px; border-radius:4px;"><?php echo htmlspecialchars($pc['field_name']); ?></code></td>
                <td><?php echo htmlspecialchars($pc['instruction']); ?></td>
                <td><?php echo date('d M Y', strtotime($pc['requested_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Decision log -->
    <?php if (!empty($decisions)): ?>
    <div class="detail-card">
        <div class="detail-card-title"><span><i class="fa-solid fa-clock-rotate-left"></i> Decision History</span></div>
        <?php foreach ($decisions as $dec): ?>
        <div style="border-left:3px solid <?php echo ($dec['decision']==='approve') ? 'var(--success)' : 'var(--danger)'; ?>; padding-left:15px; margin-bottom:12px;">
            <strong><?php echo ($dec['decision']==='approve') ? 'Approved' : 'Rejected'; ?></strong>
            by <?php echo htmlspecialchars($dec['officer_name']); ?>
            <span style="float:right; font-size:0.8rem; color:var(--text-muted);"><?php echo date('d M Y H:i', strtotime($dec['decided_at'])); ?></span>
            <div style="font-size:0.9rem; color:var(--text-secondary); margin-top:4px;">Reason: <?php echo htmlspecialchars($dec['reason']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Pass ticket (if approved) -->
    <?php if ($pass): ?>
    <div class="pass-ticket">
        <div class="pass-header">
            <div><h3 style="color:white;">STUDENT BUS PASS</h3><span style="font-size:0.75rem; background:var(--success); color:white; padding:2px 6px; border-radius:4px; font-weight:700;">ACTIVE</span></div>
            <div style="text-align:right;"><span style="font-size:0.8rem; color:var(--text-secondary); display:block;">PASS NO</span><strong style="color:var(--accent); font-size:1.1rem;"><?php echo htmlspecialchars($pass['pass_number']); ?></strong></div>
        </div>
        <div class="pass-body">
            <table style="width:100%; border:none;">
                <?php foreach ([
                    'NAME'         => $app['student_name'],
                    'PRN'          => $app['prn_number'],
                    'ROUTE'        => $app['route_number'].' ('.$app['source'].'→'.$app['destination'].')',
                    'VALID FROM'   => date('d-M-Y', strtotime($pass['valid_from'])),
                    'VALID UNTIL'  => date('d-M-Y', strtotime($pass['valid_to'])),
                ] as $k => $v): ?>
                <tr style="background:none;">
                    <td style="padding:4px 0; border:none; font-size:0.8rem; color:var(--text-muted); width:100px;"><?php echo $k; ?></td>
                    <td style="padding:4px 0; border:none; color:white; font-weight:500;"><?php echo htmlspecialchars($v); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div><div class="pass-qr">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0" y="0" width="100" height="100" fill="none" stroke="black" stroke-width="6"/>
                    <rect x="10" y="10" width="25" height="25" fill="black"/><rect x="15" y="15" width="15" height="15" fill="white"/>
                    <rect x="10" y="65" width="25" height="25" fill="black"/><rect x="15" y="70" width="15" height="15" fill="white"/>
                    <rect x="65" y="10" width="25" height="25" fill="black"/><rect x="70" y="15" width="15" height="15" fill="white"/>
                    <rect x="45" y="20" width="10" height="30" fill="black"/><rect x="20" y="45" width="30" height="10" fill="black"/>
                    <rect x="55" y="55" width="20" height="20" fill="black"/><rect x="75" y="45" width="15" height="15" fill="black"/>
                </svg>
            </div></div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ══════════════════════ RIGHT COLUMN ══════════════════════ -->
<div>

    <!-- 4.3 Route Validation Panel -->
    <div class="action-box" style="margin-bottom:1.5rem;">
        <h3 class="checklist-title"><i class="fa-solid fa-route" style="color:var(--accent); margin-right:8px;"></i> 4.3 Route Validation</h3>

        <div style="background:rgba(255,255,255,0.02); border:1px solid var(--border-color); border-radius:12px; padding:1rem; margin-bottom:1.25rem;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:0.85rem; color:var(--text-muted);">Route:</span>
                <strong><?php echo htmlspecialchars($app['route_number']); ?></strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:0.85rem; color:var(--text-muted);">Distance:</span>
                <strong><?php echo htmlspecialchars($app['distance']); ?> km</strong>
            </div>
            <div style="font-size:0.8rem; color:var(--text-muted);">Stops: <?php echo htmlspecialchars($app['stops']); ?></div>

            <?php if (!$routeIsValid): ?>
            <div style="margin-top:10px; background:rgba(239,68,68,0.08); color:var(--danger); border:1px solid rgba(239,68,68,0.2); padding:8px; border-radius:8px; font-size:0.8rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Route exceeds <?php echo MAX_ROUTE_DISTANCE; ?>km standard limit. System suggests: <strong><?php echo $suggestedDept; ?></strong>
            </div>
            <?php else: ?>
            <div style="margin-top:10px; background:rgba(16,185,129,0.08); color:var(--success); border:1px solid rgba(16,185,129,0.2); padding:8px; border-radius:8px; font-size:0.8rem;">
                <i class="fa-solid fa-circle-check"></i> Route within standard distance constraints.
            </div>
            <?php endif; ?>
        </div>

        <form action="verify-application.php?id=<?php echo $application_id; ?>" method="POST">
            <input type="hidden" name="action" value="reroute">
            <div class="form-group">
                <label for="routing_dept" class="form-label">Dispatch To Department</label>
                <select name="routing_dept" id="routing_dept" class="form-control">
                    <option value="Transport Dept"  <?php echo ($app['routing_dept']==='Transport Dept') ? 'selected':''; ?>>Transport Dept (Standard)</option>
                    <option value="Academic HOD"    <?php echo ($app['routing_dept']==='Academic HOD') ? 'selected':''; ?>>Academic HOD (Verification)</option>
                    <option value="Admin Exception" <?php echo ($app['routing_dept']==='Admin Exception') ? 'selected':''; ?>>Admin Exception (Long Route)</option>
                </select>
            </div>
            <div class="form-group">
                <input type="text" name="routing_comments" class="form-control" placeholder="Routing comments / notes...">
            </div>
            <button type="submit" id="btn-dispatch" class="btn btn-secondary" style="width:100%;"><i class="fa-solid fa-code-branch"></i> Dispatch Route</button>
        </form>
    </div>

    <!-- 4.4 & 4.5 Decision Hub -->
    <div class="action-box">
        <h3 class="checklist-title"><i class="fa-solid fa-shield-halved" style="color:var(--accent); margin-right:8px;"></i> 4.4 / 4.5 Decision Hub</h3>

        <div class="verification-checklist">
            <div class="checklist-item"><input type="checkbox" id="chk_profile" <?php echo ($completenessResult['valid'] && $eligibilityResult['valid']) ? 'checked' : ''; ?>><label for="chk_profile">Student profile is complete and eligible.</label></div>
            <div class="checklist-item"><input type="checkbox" id="chk_docs" <?php echo (count($doc_verif) >= 3) ? 'checked' : ''; ?>><label for="chk_docs">All documents reviewed.</label></div>
            <div class="checklist-item"><input type="checkbox" id="chk_route"><label for="chk_route">Route maps to student residential area.</label></div>
        </div>

        <?php if (!in_array($app['status'], ['approved','rejected'])): ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <button id="btn-approve" class="btn btn-success" style="width:100%;" onclick="openDecisionModal('approve')"><i class="fa-solid fa-circle-check"></i> Approve &amp; Issue Pass</button>
            <button id="btn-reject"  class="btn btn-danger"  style="width:100%;" onclick="openDecisionModal('reject')"><i class="fa-solid fa-circle-xmark"></i> Reject Application</button>
            <button id="btn-corrections" class="btn btn-warning" style="width:100%;" onclick="openCorrectionModal()"><i class="fa-solid fa-circle-question"></i> Request Corrections</button>
        </div>
        <?php else: ?>
        <div style="background:rgba(255,255,255,0.02); border:1px dashed var(--border-color); border-radius:12px; padding:1.5rem; text-align:center;">
            <i class="fa-solid fa-lock" style="font-size:1.5rem; color:var(--text-muted); display:block; margin-bottom:8px;"></i>
            Decision finalized: <strong><?php echo strtoupper($app['status']); ?></strong>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- ══════════════ MODAL: Document Status ══════════════ -->
<div id="doc_action_modal" class="modal-overlay">
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title">Update Document Status</h4>
        <button class="btn btn-secondary btn-sm" onclick="closeModal('doc_action_modal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="verify-application.php?id=<?php echo $application_id; ?>" method="POST">
        <input type="hidden" name="action"   id="doc_modal_action" value="verify_doc">
        <input type="hidden" name="doc_type" id="doc_modal_type"   value="">
        <div class="modal-body">
            <p style="margin-bottom:1.25rem;">Setting status for: <strong id="doc_modal_label">—</strong></p>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="display:flex; gap:15px; margin-top:6px;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="doc_status_radio" value="verify"     checked onclick="document.getElementById('doc_modal_action').value='verify_doc'">
                        <span class="badge badge-approved">Verified</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="doc_status_radio" value="invalidate" onclick="document.getElementById('doc_modal_action').value='invalidate_doc'">
                        <span class="badge badge-rejected">Flag Invalid</span>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="doc_comments" class="form-label">Comments</label>
                <textarea name="doc_comments" id="doc_comments" class="form-control" placeholder="Describe the review outcome..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('doc_action_modal')">Cancel</button>
            <button type="submit" id="btn-doc-save" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
</div>

<!-- ══════════════ MODAL: Correction Request ══════════════ -->
<div id="correction_modal" class="modal-overlay">
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title">4.5 Request Corrections from Student</h4>
        <button class="btn btn-secondary btn-sm" onclick="closeModal('correction_modal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="verify-application.php?id=<?php echo $application_id; ?>" method="POST">
        <input type="hidden" name="action" value="request_corrections">
        <div class="modal-body">
            <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:1.5rem;">
                Check fields containing errors and provide correction instructions. Routing and approval will be paused until resolved.
            </p>
            <?php foreach ([
                'address'         => 'Home Residential Address',
                'college_id_doc'  => 'College ID Document',
                'photograph_doc'  => 'Photograph Document',
                'address_proof_doc' => 'Address Proof Document',
            ] as $field => $label): ?>
            <div class="form-group" style="border-bottom:1px solid var(--border-color); padding-bottom:1rem;">
                <label style="display:flex; align-items:center; gap:8px; font-weight:600; cursor:pointer;">
                    <input type="checkbox" id="chk_<?php echo $field; ?>" onchange="toggleCorrField('<?php echo $field; ?>')">
                    <?php echo $label; ?>
                </label>
                <div id="div_<?php echo $field; ?>" style="display:none; margin-top:8px;">
                    <input type="text" name="corrections[<?php echo $field; ?>]" class="form-control" placeholder="Instruction for student...">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('correction_modal')">Cancel</button>
            <button type="submit" id="btn-send-corrections" class="btn btn-warning">Send Corrections</button>
        </div>
    </form>
</div>
</div>

<!-- ══════════════ MODAL: Approve / Reject ══════════════ -->
<div id="decision_modal" class="modal-overlay">
<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title" id="decision_modal_title">Decision Panel</h4>
        <button class="btn btn-secondary btn-sm" onclick="closeModal('decision_modal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <!-- Approve Form -->
    <form id="approve_form" action="verify-application.php?id=<?php echo $application_id; ?>" method="POST" style="display:none;">
        <input type="hidden" name="action" value="approve">
        <div class="modal-body">
            <p style="margin-bottom:1.25rem;">Approve pass for <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>.</p>
            <div class="form-group">
                <label for="validity_duration" class="form-label">Pass Validity</label>
                <select name="validity_duration" id="validity_duration" class="form-control">
                    <option value="1">1 Month</option>
                    <option value="6" selected>6 Months (Semester)</option>
                    <option value="12">12 Months (Annual)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="decision_notes_a" class="form-label">Approval Notes</label>
                <textarea name="decision_notes" id="decision_notes_a" class="form-control" placeholder="Optional approval remarks..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('decision_modal')">Cancel</button>
            <button type="submit" id="btn-confirm-approve" class="btn btn-success">Issue Pass</button>
        </div>
    </form>
    <!-- Reject Form -->
    <form id="reject_form"  action="verify-application.php?id=<?php echo $application_id; ?>" method="POST" style="display:none;">
        <input type="hidden" name="action" value="reject">
        <div class="modal-body">
            <p style="margin-bottom:1.25rem; color:var(--danger);">This will permanently reject the application. Please provide a clear reason.</p>
            <div class="form-group">
                <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                <textarea name="rejection_reason" id="rejection_reason" class="form-control" placeholder="Detailed reason for rejection..." required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('decision_modal')">Cancel</button>
            <button type="submit" id="btn-confirm-reject" class="btn btn-danger">Reject Application</button>
        </div>
    </form>
</div>
</div>

<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openDocModal(docType) {
    document.getElementById('doc_modal_type').value  = docType;
    document.getElementById('doc_modal_label').innerText = docType.replace(/_/g, ' ').toUpperCase();
    document.getElementById('doc_comments').value    = '';
    document.getElementById('doc_modal_action').value= 'verify_doc';
    document.querySelector('input[name="doc_status_radio"][value="verify"]').checked = true;
    openModal('doc_action_modal');
}

function openCorrectionModal() { openModal('correction_modal'); }

function toggleCorrField(field) {
    const div = document.getElementById('div_' + field);
    const chk = document.getElementById('chk_' + field);
    div.style.display = chk.checked ? 'block' : 'none';
}

function openDecisionModal(type) {
    const title = document.getElementById('decision_modal_title');
    const af    = document.getElementById('approve_form');
    const rf    = document.getElementById('reject_form');
    if (type === 'approve') {
        title.innerText = 'Authorize & Issue Bus Pass';
        af.style.display = 'block'; rf.style.display = 'none';
    } else {
        title.innerText = 'Reject Bus Pass Application';
        af.style.display = 'none';  rf.style.display = 'block';
    }
    openModal('decision_modal');
}
</script>

</main>
</div>
<footer>
    <div>&copy; <?php echo date("Y"); ?> Bus Pass Management System.</div>
    <div class="footer-links"><span>v1.1-feature-only</span></div>
</footer>
</body>
</html>
