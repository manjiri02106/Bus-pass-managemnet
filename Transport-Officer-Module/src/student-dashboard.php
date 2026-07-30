<?php
// src/student-dashboard.php — Student portal
// Uses: RequestCorrectionsService (for resubmitting corrections, feature 4.5)
require_once dirname(__DIR__) . '/config/db-connection.php';
require_once dirname(__DIR__) . '/config/app-config.php';
require_once __DIR__ . '/auth-helper.php';
require_once __DIR__ . '/services/request-corrections.service.php';

check_auth(['student']);
$user_id           = $_SESSION['user_id'];
$correctionService = new RequestCorrectionsService();
$alert_message     = '';
$alert_type        = 'success';

// ── 4.5 Resolve corrections (student resubmission) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resolve_corrections') {
    $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    if ($application_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
            $stmt->execute([$application_id]);
            $pending = $stmt->fetchAll();

            $upload_dir = dirname(__DIR__) . '/uploads';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach ($pending as $req) {
                $field = $req['field_name'];
                if (in_array($field, ['college_id_doc', 'photograph_doc', 'address_proof_doc'])) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $file    = $_FILES[$field];
                        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ALLOWED_DOC_EXTENSIONS) && $file['size'] <= MAX_DOC_SIZE_BYTES) {
                            $fname   = 'correction_' . $field . '_' . time() . '.' . $ext;
                            $target  = 'uploads/' . $fname;
                            if (move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $target)) {
                                $pdo->prepare("UPDATE applications SET $field = ? WHERE id = ?")->execute([$target, $application_id]);
                                $correctionService->resolveFieldCorrection($pdo, $application_id, $field);
                                $clean = str_replace('_doc', '', $field);
                                $pdo->prepare("UPDATE document_verifications SET status='pending', comments='Re-uploaded by student after correction' WHERE application_id=? AND document_type=?")
                                    ->execute([$application_id, $clean]);
                            }
                        }
                    }
                } elseif ($field === 'address') {
                    $new_address = trim($_POST['address'] ?? '');
                    if (!empty($new_address)) {
                        $pdo->prepare("UPDATE students SET address = ? WHERE user_id = ?")->execute([$new_address, $user_id]);
                        $correctionService->resolveFieldCorrection($pdo, $application_id, $field);
                    }
                }
            }
            $alert_message = "Corrections submitted. Your application is now back under verification.";
        } catch (Exception $e) {
            $alert_message = "Failed: " . $e->getMessage(); $alert_type = 'danger';
        }
    }
}

// ── Fetch data ────────────────────────────────────────────────────────────────
try {
    $student = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $student->execute([$user_id]);
    $student = $student->fetch();

    $application = $pass = null;
    $corrections = [];
    if ($student) {
        $stmt_a = $pdo->prepare("SELECT a.*, r.route_number, r.source, r.destination, r.distance FROM applications a JOIN routes r ON a.route_id=r.id WHERE a.student_id=? ORDER BY a.submission_date DESC LIMIT 1");
        $stmt_a->execute([$student['id']]);
        $application = $stmt_a->fetch();

        if ($application) {
            if ($application['status'] === 'approved') {
                $stmt_p = $pdo->prepare("SELECT * FROM passes WHERE application_id = ?");
                $stmt_p->execute([$application['id']]);
                $pass = $stmt_p->fetch();
            }
            $stmt_c = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
            $stmt_c->execute([$application['id']]);
            $corrections = $stmt_c->fetchAll();
        }
    }
} catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — Bus Pass Portal</title>
    <meta name="description" content="Student dashboard to track bus pass application, view corrections, and download your digital bus pass.">
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
        <div class="user-badge"><i class="fa-regular fa-user"></i> <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong> <span class="user-role">student</span></div>
        <form action="logout.php" method="POST" style="margin:0;"><button type="submit" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button></form>
    </div>
</header>
<div class="app-container">
<aside class="sidebar">
    <a href="student-dashboard.php" class="sidebar-link active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="apply.php"             class="sidebar-link"><i class="fa-solid fa-file-signature"></i> Apply for Pass</a>
    <a href="renew.php"             class="sidebar-link"><i class="fa-solid fa-arrows-rotate"></i> Renew Pass</a>
    <hr style="border:0; border-top:1px solid var(--border-color); margin:1rem 0;">
    <form action="logout.php" method="POST" style="width:100%;">
        <button type="submit" class="sidebar-link" style="background:none; border:none; width:100%; text-align:left; cursor:pointer; font-family:inherit;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
    </form>
</aside>
<main class="main-content">

<div class="page-title-section">
    <h2 class="page-title">My Dashboard</h2>
    <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>. Track your pass application and respond to correction requests.</p>
</div>

<?php if (!empty($alert_message)): ?>
<div class="alert alert-<?php echo $alert_type; ?>"><i class="fa-solid fa-circle-info"></i> <?php echo $alert_message; ?></div>
<?php endif; ?>

<?php if (!$student): ?>
<div class="detail-card" style="text-align:center; padding:3rem;">
    <i class="fa-solid fa-circle-exclamation" style="font-size:3rem; color:var(--warning); display:block; margin-bottom:1rem;"></i>
    <h3>Student Profile Not Configured</h3>
    <p style="color:var(--text-secondary); margin-top:0.5rem;">Please contact the Transport Administrator to register your student profile.</p>
</div>
<?php else: ?>

<div class="detail-grid">
<div>

    <!-- Active Pass Widget -->
    <?php if ($application && $application['status'] === 'approved' && $pass): ?>
    <div class="detail-card" style="border-color:rgba(16,185,129,0.2);">
        <div class="detail-card-title">
            <span><i class="fa-solid fa-id-card" style="color:var(--success); margin-right:8px;"></i> Active Digital Bus Pass</span>
            <a href="download-pass.php?id=<?php echo $pass['id']; ?>" id="btn-download-pass" class="btn btn-success btn-sm"><i class="fa-solid fa-download"></i> Download</a>
        </div>
        <div class="pass-ticket">
            <div class="pass-header">
                <div><h3 style="color:white;">STUDENT BUS PASS</h3><span style="font-size:0.75rem; background:var(--success); color:white; padding:2px 6px; border-radius:4px; font-weight:700;">ACTIVE</span></div>
                <div style="text-align:right;"><span style="display:block; font-size:0.8rem; color:var(--text-secondary);">PASS NO</span><strong style="color:var(--accent); font-size:1.1rem;"><?php echo htmlspecialchars($pass['pass_number']); ?></strong></div>
            </div>
            <div class="pass-body">
                <table style="width:100%; border:none;">
                    <?php foreach ([
                        'NAME'=>$_SESSION['full_name'],'PRN'=>$student['prn_number'],
                        'ROUTE'=>$application['route_number'].' ('.$application['source'].'→'.$application['destination'].')',
                        'FROM'=>date('d-M-Y',strtotime($pass['valid_from'])),
                        'UNTIL'=>date('d-M-Y',strtotime($pass['valid_to']))
                    ] as $k=>$v): ?>
                    <tr style="background:none;"><td style="padding:4px 0; border:none; font-size:0.8rem; color:var(--text-muted); width:80px;"><?php echo $k; ?></td><td style="padding:4px 0; border:none; color:white; font-weight:500;"><?php echo htmlspecialchars($v); ?></td></tr>
                    <?php endforeach; ?>
                </table>
                <div><div class="pass-qr"><svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0" y="0" width="100" height="100" fill="none" stroke="black" stroke-width="6"/>
                    <rect x="10" y="10" width="25" height="25" fill="black"/><rect x="15" y="15" width="15" height="15" fill="white"/>
                    <rect x="10" y="65" width="25" height="25" fill="black"/><rect x="15" y="70" width="15" height="15" fill="white"/>
                    <rect x="65" y="10" width="25" height="25" fill="black"/><rect x="70" y="15" width="15" height="15" fill="white"/>
                    <rect x="45" y="20" width="10" height="30" fill="black"/><rect x="20" y="45" width="30" height="10" fill="black"/>
                </svg></div></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4.5 Correction Resolution Form -->
    <?php if ($application && $application['status'] === 'correction_required' && !empty($corrections)): ?>
    <div class="detail-card" style="border-color:rgba(245,158,11,0.4);">
        <div class="detail-card-title" style="color:var(--warning);">
            <span><i class="fa-solid fa-triangle-exclamation"></i> 4.5 Action Required — Correction Requested</span>
        </div>
        <p style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:1.5rem;">
            The transport officer found issues with your application. Please review each instruction and re-submit the corrected information. Routing and final decision are paused until all corrections are resolved.
        </p>
        <form action="student-dashboard.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="resolve_corrections">
            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
            <?php foreach ($corrections as $req): ?>
            <div style="background:var(--bg-tertiary); border-radius:12px; padding:1.25rem; margin-bottom:1.25rem; border:1px solid var(--border-color);">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <strong><?php
                        echo match($req['field_name']) {
                            'address'          => 'Home Residential Address',
                            'college_id_doc'   => 'College ID Document',
                            'photograph_doc'   => 'Photograph',
                            'address_proof_doc'=> 'Address Proof Document',
                            default            => htmlspecialchars($req['field_name'])
                        };
                    ?></strong>
                    <span class="badge badge-rejected" style="font-size:0.65rem;">Action Needed</span>
                </div>
                <div style="font-size:0.9rem; color:var(--text-secondary); margin-bottom:12px; border-left:2px solid var(--warning); padding-left:10px;">
                    <strong>Instruction:</strong> <?php echo htmlspecialchars($req['instruction']); ?>
                </div>
                <?php if ($req['field_name'] === 'address'): ?>
                    <textarea name="address" class="form-control" required placeholder="Enter corrected address..."><?php echo htmlspecialchars($student['address']); ?></textarea>
                <?php else: ?>
                    <input type="file" name="<?php echo htmlspecialchars($req['field_name']); ?>" class="form-control" style="padding:6px 12px;" required>
                    <small style="color:var(--text-muted);">Accepted: PNG, JPG, PDF (max 2MB)</small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <button type="submit" id="btn-submit-corrections" class="btn btn-warning" style="width:100%;"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Corrections</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Application status tracker -->
    <div class="detail-card">
        <div class="detail-card-title"><span><i class="fa-solid fa-receipt" style="color:var(--accent); margin-right:8px;"></i> Application Status</span></div>
        <?php if (!$application): ?>
        <div style="text-align:center; padding:2rem 0;">
            <p style="color:var(--text-muted); margin-bottom:1.5rem;">No application submitted yet.</p>
            <a href="apply.php" id="btn-apply-now" class="btn btn-primary"><i class="fa-solid fa-file-invoice"></i> Apply Now</a>
        </div>
        <?php else: ?>
        <table style="width:100%;">
            <?php foreach ([
                'Status'       => get_status_badge($application['status']),
                'Route'        => htmlspecialchars($application['route_number']).' ('.htmlspecialchars($application['source']).'→'.htmlspecialchars($application['destination']).')',
                'Dept Queue'   => '<span class="badge" style="background:rgba(255,255,255,0.05); color:var(--text-secondary);">'.htmlspecialchars($application['routing_dept']).'</span>',
                'Submitted'    => date('d-M-Y H:i', strtotime($application['submission_date'])),
                'Last Updated' => date('d-M-Y H:i', strtotime($application['last_updated'])),
            ] as $k => $v): ?>
            <tr>
                <td style="font-weight:600; color:var(--text-secondary); padding:8px 0; border-bottom:1px solid var(--border-color);"><?php echo $k; ?></td>
                <td style="padding:8px 0; border-bottom:1px solid var(--border-color);"><?php echo $v; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>

</div>

<!-- Right sidebar -->
<div>
    <div class="action-box" style="margin-bottom:1.5rem;">
        <h3 class="checklist-title"><i class="fa-solid fa-address-card" style="color:var(--accent); margin-right:8px;"></i> Profile Summary</h3>
        <?php foreach (['PRN' => $student['prn_number'], 'Roll' => $student['roll_number'], 'Department' => $student['department'], 'Class' => $student['class'], 'Mobile' => $student['mobile']] as $k => $v): ?>
        <div style="margin-bottom:12px;"><span style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); display:block;"><?php echo $k; ?></span><strong><?php echo htmlspecialchars($v); ?></strong></div>
        <?php endforeach; ?>
    </div>

    <div class="action-box">
        <h3 class="checklist-title"><i class="fa-solid fa-bell" style="color:var(--warning); margin-right:8px;"></i> Notifications</h3>
        <?php if ($application && $application['status'] === 'correction_required'): ?>
        <div style="background:rgba(245,158,11,0.08); border-left:3px solid var(--warning); padding:10px; border-radius:4px; font-size:0.85rem; margin-bottom:10px;">
            <strong>Correction Needed</strong> — The officer has requested document updates. Please re-submit above.
        </div>
        <?php elseif ($application && $application['status'] === 'approved'): ?>
        <div style="background:rgba(16,185,129,0.08); border-left:3px solid var(--success); padding:10px; border-radius:4px; font-size:0.85rem; margin-bottom:10px;">
            <strong>Pass Issued!</strong> Your digital pass is available for download.
        </div>
        <?php elseif ($application && $application['status'] === 'submitted'): ?>
        <div style="background:rgba(63,140,255,0.08); border-left:3px solid var(--accent); padding:10px; border-radius:4px; font-size:0.85rem; margin-bottom:10px;">
            <strong>Application Submitted</strong> — Queued for verification.
        </div>
        <?php else: ?>
        <div style="color:var(--text-muted); font-size:0.85rem;">No active notifications.</div>
        <?php endif; ?>
        <div style="border-top:1px solid var(--border-color); padding-top:10px; margin-top:10px; color:var(--text-muted); font-size:0.75rem;">
            <i class="fa-solid fa-circle-info"></i> Verification completes within 2 business days.
        </div>
    </div>
</div>
</div>

<?php endif; ?>
</main>
</div>
<footer>
    <div>&copy; <?php echo date("Y"); ?> Bus Pass Management System.</div>
    <div class="footer-links"><span>v1.1-feature-only</span></div>
</footer>
</body>
</html>
