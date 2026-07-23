<?php
// verify_application.php - Detailed Verification Portal

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce Transport Officer access
check_auth(['officer']);

$application_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$application_id) {
    header("Location: officer_dashboard.php");
    exit();
}

$alert_message = '';
$alert_type = 'success';

// Handle POST actions (Decision, Route Change, Document Status update, Correction Requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Route Dispatching / Route Validation Routing (4.3)
    if ($action === 'reroute') {
        $new_dept = trim($_POST['routing_dept'] ?? '');
        $comments = trim($_POST['routing_comments'] ?? '');
        if (!empty($new_dept)) {
            try {
                $pdo->beginTransaction();
                
                // Get current dept
                $stmt = $pdo->prepare("SELECT routing_dept FROM applications WHERE id = ?");
                $stmt->execute([$application_id]);
                $old_dept = $stmt->fetchColumn();

                // Update application routing dept
                $stmt = $pdo->prepare("UPDATE applications SET routing_dept = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$new_dept, $application_id]);

                // Log routing change
                $stmt_log = $pdo->prepare("INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) VALUES (?, 0, 1, ?)");
                $stmt_log->execute([
                    $application_id, 
                    "Routed from '$old_dept' to '$new_dept'. Officer Comments: $comments"
                ]);

                $pdo->commit();
                $alert_message = "Application successfully routed to: " . htmlspecialchars($new_dept);
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert_message = "Routing failed: " . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    }

    // Action 2: Document Status toggle (4.2)
    elseif ($action === 'verify_doc' || $action === 'invalidate_doc') {
        $doc_type = $_POST['doc_type'] ?? '';
        $comments = trim($_POST['doc_comments'] ?? '');
        $status = ($action === 'verify_doc') ? 'verified' : 'invalid';

        if (in_array($doc_type, ['college_id', 'photograph', 'address_proof'])) {
            try {
                // Check if doc verification record exists
                $stmt = $pdo->prepare("SELECT id FROM document_verifications WHERE application_id = ? AND document_type = ?");
                $stmt->execute([$application_id, $doc_type]);
                $existing_id = $stmt->fetchColumn();

                if ($existing_id) {
                    $stmt_upd = $pdo->prepare("UPDATE document_verifications SET status = ?, comments = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt_upd->execute([$status, $comments, $_SESSION['user_id'], $existing_id]);
                } else {
                    $stmt_ins = $pdo->prepare("INSERT INTO document_verifications (application_id, document_type, status, comments, verified_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$application_id, $doc_type, $status, $comments, $_SESSION['user_id']]);
                }

                // If marked invalid, auto transition application status to under_verification to reflect progress
                $stmt_status = $pdo->prepare("UPDATE applications SET status = 'under_verification', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_status->execute([$application_id]);

                $alert_message = "Document status updated for: " . str_replace('_', ' ', strtoupper($doc_type));
            } catch (Exception $e) {
                $alert_message = "Doc status update failed: " . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    }

    // Action 3: Request Corrections (4.5)
    elseif ($action === 'request_corrections') {
        $corrections = $_POST['corrections'] ?? []; // Array of field => instruction
        if (!empty($corrections)) {
            try {
                $pdo->beginTransaction();

                // 1. Create correction requests
                $stmt_ins = $pdo->prepare("INSERT INTO correction_requests (application_id, field_name, instruction, status, requested_by) VALUES (?, ?, ?, 'pending', ?)");
                foreach ($corrections as $field => $instruction) {
                    if (!empty($instruction)) {
                        $stmt_ins->execute([$application_id, $field, $instruction, $_SESSION['user_id']]);
                    }
                }

                // 2. Set application status to correction_required
                $stmt_status = $pdo->prepare("UPDATE applications SET status = 'correction_required', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_status->execute([$application_id]);

                $pdo->commit();
                $alert_message = "Correction requests sent successfully. Application status updated to 'Correction Required'.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert_message = "Failed to request corrections: " . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    }

    // Action 4: Approve Application and Generate pass (4.4)
    elseif ($action === 'approve') {
        $validity_months = filter_input(INPUT_POST, 'validity_duration', FILTER_VALIDATE_INT);
        $notes = trim($_POST['decision_notes'] ?? 'Application approved.');

        if ($validity_months) {
            try {
                $pdo->beginTransaction();

                // 1. Insert Decision
                $stmt_dec = $pdo->prepare("INSERT INTO decisions (application_id, decision, reason, officer_id) VALUES (?, 'approve', ?, ?)");
                $stmt_dec->execute([$application_id, $notes, $_SESSION['user_id']]);

                // 2. Generate Digital Pass
                $pass_number = 'BP-' . date('Y') . '-' . sprintf('%06d', $application_id);
                $valid_from = date('Y-m-d');
                $valid_to = date('Y-m-d', strtotime("+$validity_months months"));
                
                // Construct a mock QR code string containing passenger info
                $qr_content = "PassNumber:$pass_number|AppID:$application_id|ValidTo:$valid_to";
                
                $stmt_pass = $pdo->prepare("INSERT INTO passes (application_id, pass_number, valid_from, valid_to, qr_code) VALUES (?, ?, ?, ?, ?)");
                $stmt_pass->execute([$application_id, $pass_number, $valid_from, $valid_to, $qr_content]);

                // 3. Update application status to approved
                $stmt_app = $pdo->prepare("UPDATE applications SET status = 'approved', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_app->execute([$application_id]);

                // 4. Record successful validation logs
                $stmt_log = $pdo->prepare("INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) VALUES (?, 0, 1, ?)");
                $stmt_log->execute([$application_id, "Route validation and documentation criteria completed. Pass issued."]);

                $pdo->commit();
                $alert_message = "Application APPROVED. Digital Bus Pass $pass_number has been generated!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert_message = "Approval failed: " . $e->getMessage();
                $alert_type = 'danger';
            }
        }
    }

    // Action 5: Reject Application (4.4)
    elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (!empty($reason)) {
            try {
                $pdo->beginTransaction();

                // 1. Insert Decision
                $stmt_dec = $pdo->prepare("INSERT INTO decisions (application_id, decision, reason, officer_id) VALUES (?, 'reject', ?, ?)");
                $stmt_dec->execute([$application_id, $reason, $_SESSION['user_id']]);

                // 2. Update Application status
                $stmt_app = $pdo->prepare("UPDATE applications SET status = 'rejected', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_app->execute([$application_id]);

                $pdo->commit();
                $alert_message = "Application has been REJECTED. Reason recorded.";
                $alert_type = 'danger';
            } catch (Exception $e) {
                $pdo->rollBack();
                $alert_message = "Rejection failed: " . $e->getMessage();
                $alert_type = 'danger';
            }
        } else {
            $alert_message = "Rejection reason is required.";
            $alert_type = 'danger';
        }
    }
}

// Fetch current application data
try {
    $query = "
        SELECT a.*, 
               s.prn_number, s.roll_number, s.department, s.class, s.mobile, s.address,
               u.full_name as student_name, u.email as student_email,
               r.route_number, r.source, r.destination, r.distance, r.stops
        FROM applications a
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN routes r ON a.route_id = r.id
        WHERE a.id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$application_id]);
    $app = $stmt->fetch();

    if (!$app) {
        header("Location: officer_dashboard.php");
        exit();
    }

    // Fetch document verification logs
    $stmt_doc = $pdo->prepare("SELECT * FROM document_verifications WHERE application_id = ?");
    $stmt_doc->execute([$application_id]);
    $doc_verifications = [];
    while ($row = $stmt_doc->fetch()) {
        $doc_verifications[$row['document_type']] = $row;
    }

    // Fetch unresolved correction requests
    $stmt_corr = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
    $stmt_corr->execute([$application_id]);
    $pending_corrections = $stmt_corr->fetchAll();

    // Fetch Decision logs (if any)
    $stmt_dec = $pdo->prepare("SELECT d.*, u.full_name as officer_name FROM decisions d JOIN users u ON d.officer_id = u.id WHERE d.application_id = ? ORDER BY d.decided_at DESC");
    $stmt_dec->execute([$application_id]);
    $decisions = $stmt_dec->fetchAll();

    // Fetch Pass info if approved
    $pass = null;
    if ($app['status'] === 'approved') {
        $stmt_pass = $pdo->prepare("SELECT * FROM passes WHERE application_id = ?");
        $stmt_pass->execute([$application_id]);
        $pass = $stmt_pass->fetch();
    }

} catch (PDOException $e) {
    die("Database fetch error: " . $e->getMessage());
}

// Automated Document Check Helpers (4.2)
function run_automated_checks($filepath) {
    $results = [
        'exists' => false,
        'size' => 'Unknown',
        'type' => 'Unknown',
        'valid' => false,
        'message' => ''
    ];
    
    if (file_exists($filepath)) {
        $results['exists'] = true;
        $size_bytes = filesize($filepath);
        $results['size'] = round($size_bytes / 1024, 2) . ' KB';
        
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $results['type'] = strtoupper($ext);
        
        // Simple mock authenticity standard checks:
        // Size under 2MB and format is image or pdf
        if ($size_bytes < 2 * 1024 * 1024 && in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'])) {
            $results['valid'] = true;
            $results['message'] = "Standards Match: Format {$results['type']} & Size {$results['size']} within limits.";
        } else {
            $results['message'] = "Warning: Document format/size out of limits.";
        }
    } else {
        $results['message'] = "Critical: Document file not found on disk.";
    }
    return $results;
}

$college_id_checks = run_automated_checks($app['college_id_doc']);
$photo_checks = run_automated_checks($app['photograph_doc']);
$address_checks = run_automated_checks($app['address_proof_doc']);

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 class="page-title">Application Verification</h2>
        <p class="page-subtitle">Verify submitted information, inspect documents, route exceptions, and authorize passes.</p>
    </div>
    <a href="officer_dashboard.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if (!empty($alert_message)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>">
        <i class="fa-solid fa-circle-info"></i> <?php echo $alert_message; ?>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <!-- Left Column: Verification Content -->
    <div>
        <!-- 4.1 Verify Applications (Completeness & Accuracy) -->
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="fa-solid fa-user-check" style="color: var(--accent); margin-right: 8px;"></i> Student Profile Details</span>
                <?php echo get_status_badge($app['status']); ?>
            </div>
            
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['student_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRN Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['prn_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Roll Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['roll_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Department &amp; Class</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['department'] . ' - ' . $app['class']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mobile Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['mobile']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['student_email']); ?></span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <span class="info-label">Home Address</span>
                    <span class="info-value"><?php echo htmlspecialchars($app['address']); ?></span>
                </div>
            </div>
        </div>

        <!-- 4.2 Document Verification -->
        <div class="detail-card">
            <div class="detail-card-title">
                <span><i class="fa-solid fa-file-shield" style="color: var(--accent); margin-right: 8px;"></i> Document Authenticity Validation</span>
                <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-secondary);">Validating against system criteria</span>
            </div>

            <!-- College ID Document Row -->
            <div class="doc-item-row <?php 
                echo (isset($doc_verifications['college_id']) && $doc_verifications['college_id']['status'] === 'invalid') ? 'invalid-highlight' : '';
                echo (isset($doc_verifications['college_id']) && $doc_verifications['college_id']['status'] === 'verified') ? 'verified-highlight' : '';
            ?>">
                <div class="doc-meta">
                    <div class="doc-icon"><i class="fa-solid fa-id-card-clip"></i></div>
                    <div>
                        <div class="doc-name">College Identity Card</div>
                        <div class="doc-size">
                            <?php echo $college_id_checks['message']; ?> 
                            [Format: <?php echo $college_id_checks['type']; ?>]
                        </div>
                    </div>
                </div>
                <div class="doc-actions">
                    <a href="<?php echo htmlspecialchars($app['college_id_doc']); ?>" target="_blank" class="btn btn-secondary btn-sm" title="View Document File">
                        <i class="fa-solid fa-up-right-from-square"></i> View File
                    </a>
                    
                    <?php if (isset($doc_verifications['college_id']) && $doc_verifications['college_id']['status'] === 'verified'): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-check"></i> Verified</span>
                    <?php elseif (isset($doc_verifications['college_id']) && $doc_verifications['college_id']['status'] === 'invalid'): ?>
                        <span class="badge badge-rejected"><i class="fa-solid fa-circle-exclamation"></i> Flagged</span>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary btn-sm" onclick="openDocActionModal('college_id')">Update Status</button>
                </div>
            </div>
            <?php if (isset($doc_verifications['college_id']['comments']) && !empty($doc_verifications['college_id']['comments'])): ?>
                <div style="font-size: 0.85rem; margin: -5px 0 15px 65px; color: var(--text-secondary);">
                    <strong>Verification Comment:</strong> <?php echo htmlspecialchars($doc_verifications['college_id']['comments']); ?>
                </div>
            <?php endif; ?>

            <!-- Photograph Document Row -->
            <div class="doc-item-row <?php 
                echo (isset($doc_verifications['photograph']) && $doc_verifications['photograph']['status'] === 'invalid') ? 'invalid-highlight' : '';
                echo (isset($doc_verifications['photograph']) && $doc_verifications['photograph']['status'] === 'verified') ? 'verified-highlight' : '';
            ?>">
                <div class="doc-meta">
                    <div class="doc-icon"><i class="fa-solid fa-image-portrait"></i></div>
                    <div>
                        <div class="doc-name">Student Photograph</div>
                        <div class="doc-size">
                            <?php echo $photo_checks['message']; ?>
                            [Format: <?php echo $photo_checks['type']; ?>]
                        </div>
                    </div>
                </div>
                <div class="doc-actions">
                    <a href="<?php echo htmlspecialchars($app['photograph_doc']); ?>" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-up-right-from-square"></i> View File
                    </a>
                    
                    <?php if (isset($doc_verifications['photograph']) && $doc_verifications['photograph']['status'] === 'verified'): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-check"></i> Verified</span>
                    <?php elseif (isset($doc_verifications['photograph']) && $doc_verifications['photograph']['status'] === 'invalid'): ?>
                        <span class="badge badge-rejected"><i class="fa-solid fa-circle-exclamation"></i> Flagged</span>
                    <?php endif; ?>

                    <button class="btn btn-primary btn-sm" onclick="openDocActionModal('photograph')">Update Status</button>
                </div>
            </div>
            <?php if (isset($doc_verifications['photograph']['comments']) && !empty($doc_verifications['photograph']['comments'])): ?>
                <div style="font-size: 0.85rem; margin: -5px 0 15px 65px; color: var(--text-secondary);">
                    <strong>Verification Comment:</strong> <?php echo htmlspecialchars($doc_verifications['photograph']['comments']); ?>
                </div>
            <?php endif; ?>

            <!-- Address Proof Document Row -->
            <div class="doc-item-row <?php 
                echo (isset($doc_verifications['address_proof']) && $doc_verifications['address_proof']['status'] === 'invalid') ? 'invalid-highlight' : '';
                echo (isset($doc_verifications['address_proof']) && $doc_verifications['address_proof']['status'] === 'verified') ? 'verified-highlight' : '';
            ?>">
                <div class="doc-meta">
                    <div class="doc-icon"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <div class="doc-name">Residential Address Proof</div>
                        <div class="doc-size">
                            <?php echo $address_checks['message']; ?>
                            [Format: <?php echo $address_checks['type']; ?>]
                        </div>
                    </div>
                </div>
                <div class="doc-actions">
                    <a href="<?php echo htmlspecialchars($app['address_proof_doc']); ?>" target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-up-right-from-square"></i> View File
                    </a>
                    
                    <?php if (isset($doc_verifications['address_proof']) && $doc_verifications['address_proof']['status'] === 'verified'): ?>
                        <span class="badge badge-approved"><i class="fa-solid fa-check"></i> Verified</span>
                    <?php elseif (isset($doc_verifications['address_proof']) && $doc_verifications['address_proof']['status'] === 'invalid'): ?>
                        <span class="badge badge-rejected"><i class="fa-solid fa-circle-exclamation"></i> Flagged</span>
                    <?php endif; ?>

                    <button class="btn btn-primary btn-sm" onclick="openDocActionModal('address_proof')">Update Status</button>
                </div>
            </div>
            <?php if (isset($doc_verifications['address_proof']['comments']) && !empty($doc_verifications['address_proof']['comments'])): ?>
                <div style="font-size: 0.85rem; margin: -5px 0 15px 65px; color: var(--text-secondary);">
                    <strong>Verification Comment:</strong> <?php echo htmlspecialchars($doc_verifications['address_proof']['comments']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Correction Requests Status -->
        <?php if (!empty($pending_corrections)): ?>
            <div class="detail-card" style="border-color: rgba(245, 158, 11, 0.4);">
                <div class="detail-card-title" style="color: var(--warning);">
                    <span><i class="fa-solid fa-circle-question"></i> Pending Correction Instructions Sent</span>
                </div>
                <table style="width:100%;">
                    <thead>
                        <tr>
                            <th>Field Name</th>
                            <th>Instruction Provided</th>
                            <th>Date Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pending_corrections as $pc): ?>
                            <tr>
                                <td><code style="background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius:4px;"><?php echo htmlspecialchars($pc['field_name']); ?></code></td>
                                <td><?php echo htmlspecialchars($pc['instruction']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($pc['requested_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Historical Decision Logs -->
        <?php if (!empty($decisions)): ?>
            <div class="detail-card">
                <div class="detail-card-title">
                    <span><i class="fa-solid fa-clock-rotate-left"></i> Decision Log</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($decisions as $dec): ?>
                        <div style="border-left: 3px solid <?php echo ($dec['decision'] === 'approve') ? 'var(--success)' : 'var(--danger)'; ?>; padding-left: 15px; margin-bottom: 5px;">
                            <div>
                                <strong><?php echo ($dec['decision'] === 'approve') ? 'Approved' : 'Rejected'; ?></strong> 
                                by <?php echo htmlspecialchars($dec['officer_name']); ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted); float: right;"><?php echo date('M d, Y H:i', strtotime($dec['decided_at'])); ?></span>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px;">
                                Reason: <?php echo htmlspecialchars($dec['reason']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Render Digital Pass Ticket if Approved -->
        <?php if ($app['status'] === 'approved' && $pass): ?>
            <div class="pass-ticket">
                <div class="pass-header">
                    <div>
                        <h3 style="color: white; margin-bottom: 4px;">STUDENT BUS PASS</h3>
                        <span style="font-size: 0.75rem; background: var(--success); color: white; padding: 2px 6px; border-radius:4px; font-weight:700;">ACTIVE PASS</span>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: var(--text-secondary); font-size: 0.8rem; display:block;">PASS NO:</span>
                        <strong style="color: var(--accent); font-size: 1.1rem;"><?php echo htmlspecialchars($pass['pass_number']); ?></strong>
                    </div>
                </div>
                <div class="pass-body">
                    <div>
                        <table style="width: 100%; border: none;">
                            <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">NAME:</td><td style="padding: 4px 0; border: none; color: white;"><strong><?php echo htmlspecialchars($app['student_name']); ?></strong></td></tr>
                            <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">PRN:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo htmlspecialchars($app['prn_number']); ?></td></tr>
                            <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">ROUTE:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo htmlspecialchars($app['route_number']); ?> (<?php echo htmlspecialchars($app['source']); ?> &rarr; <?php echo htmlspecialchars($app['destination']); ?>)</td></tr>
                            <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID FROM:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo date('d-M-Y', strtotime($pass['valid_from'])); ?></td></tr>
                            <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID UNTIL:</td><td style="padding: 4px 0; border: none; color: var(--success); font-weight: 600;"><?php echo date('d-M-Y', strtotime($pass['valid_to'])); ?></td></tr>
                        </table>
                    </div>
                    <div>
                        <!-- Embedded Mock QR code using SVG lines -->
                        <div class="pass-qr" title="<?php echo htmlspecialchars($pass['qr_code']); ?>">
                            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                <!-- Draw border square -->
                                <rect x="0" y="0" width="100" height="100" fill="none" stroke="black" stroke-width="6"/>
                                <!-- Draw mock QR patterns -->
                                <rect x="10" y="10" width="25" height="25" fill="black"/>
                                <rect x="15" y="15" width="15" height="15" fill="white"/>
                                <rect x="10" y="65" width="25" height="25" fill="black"/>
                                <rect x="15" y="70" width="15" height="15" fill="white"/>
                                <rect x="65" y="10" width="25" height="25" fill="black"/>
                                <rect x="70" y="15" width="15" height="15" fill="white"/>
                                <!-- Some random blocks -->
                                <rect x="45" y="20" width="10" height="30" fill="black"/>
                                <rect x="20" y="45" width="30" height="10" fill="black"/>
                                <rect x="55" y="55" width="20" height="20" fill="black"/>
                                <rect x="75" y="45" width="15" height="15" fill="black"/>
                                <rect x="45" y="75" width="15" height="15" fill="black"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Routing & Decision Actions -->
    <div>
        <!-- 4.3 Route Validation and Dispatcher Panel -->
        <div class="action-box" style="margin-bottom: 1.5rem;">
            <h3 class="checklist-title"><i class="fa-solid fa-route" style="color: var(--accent); margin-right: 8px;"></i> Route &amp; Routing Validation</h3>
            
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">Selected Route:</span>
                    <strong><?php echo htmlspecialchars($app['route_number']); ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">Route Distance:</span>
                    <strong><?php echo htmlspecialchars($app['distance']); ?> km</strong>
                </div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom: 8px;">
                    <strong>Path stops:</strong> <?php echo htmlspecialchars($app['stops']); ?>
                </div>

                <!-- Distance rule checker -->
                <?php if ($app['distance'] > 50.0): ?>
                    <div style="margin-top: 10px; background: rgba(239, 68, 68, 0.08); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 8px; border-radius: 8px; font-size: 0.8rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        <strong>Route Alert:</strong> Distance exceeds standard local limit (> 50 km). Dispatching exception approval workflow rules apply.
                    </div>
                <?php else: ?>
                    <div style="margin-top: 10px; background: rgba(16, 185, 129, 0.08); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); padding: 8px; border-radius: 8px; font-size: 0.8rem;">
                        <i class="fa-solid fa-circle-check"></i> Standard route distance constraints validated.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Route Dispatcher Form -->
            <form action="verify_application.php?id=<?php echo $application_id; ?>" method="POST">
                <input type="hidden" name="action" value="reroute">
                <div class="form-group">
                    <label for="routing_dept" class="form-label">Forward Routing Dispatcher</label>
                    <select name="routing_dept" id="routing_dept" class="form-control">
                        <option value="Transport Dept" <?php echo ($app['routing_dept'] === 'Transport Dept') ? 'selected' : ''; ?>>Transport Officer (Standard Verification)</option>
                        <option value="Academic HOD" <?php echo ($app['routing_dept'] === 'Academic HOD') ? 'selected' : ''; ?>>Academic HOD (Verify Academic Standing)</option>
                        <option value="Admin Exception" <?php echo ($app['routing_dept'] === 'Admin Exception') ? 'selected' : ''; ?>>System Admin (Exception Review)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="routing_comments" class="form-label">Routing Notes</label>
                    <input type="text" name="routing_comments" id="routing_comments" class="form-control" placeholder="Comments detailing routing decision">
                </div>
                <button type="submit" class="btn btn-secondary" style="width: 100%;">
                    <i class="fa-solid fa-code-branch"></i> Dispatch Workflow Route
                </button>
            </form>
        </div>

        <!-- 4.4 / 4.5 Decision and Corrections Control Box -->
        <div class="action-box">
            <h3 class="checklist-title"><i class="fa-solid fa-shield-halved" style="color: var(--accent); margin-right: 8px;"></i> Decision &amp; Action Hub</h3>
            
            <!-- Checklists to prompt completeness checks -->
            <div class="verification-checklist">
                <div class="checklist-item">
                    <input type="checkbox" id="chk_prn" checked>
                    <label for="chk_prn">All student details (PRN, Roll) are cross-checked and correct.</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="chk_docs" <?php echo (count($doc_verifications) >= 3) ? 'checked' : ''; ?>>
                    <label for="chk_docs">All uploaded documents are verified or flagged.</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="chk_route">
                    <label for="chk_route">Select route maps properly to student's residential area.</label>
                </div>
            </div>

            <!-- Decision Triggers -->
            <?php if ($app['status'] !== 'approved' && $app['status'] !== 'rejected'): ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Approve Button -->
                    <button class="btn btn-success" style="width: 100%;" onclick="openDecisionModal('approve')">
                        <i class="fa-solid fa-circle-check"></i> Approve Application
                    </button>
                    
                    <!-- Reject Button -->
                    <button class="btn btn-danger" style="width: 100%;" onclick="openDecisionModal('reject')">
                        <i class="fa-solid fa-circle-xmark"></i> Reject Application
                    </button>

                    <!-- Request Correction Trigger -->
                    <button class="btn btn-warning" style="width: 100%;" onclick="openCorrectionModal()">
                        <i class="fa-solid fa-circle-question"></i> Request Corrections
                    </button>
                </div>
            <?php else: ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); border-radius: 12px; padding: 1.5rem; text-align: center;">
                    <i class="fa-solid fa-lock" style="font-size: 1.5rem; color: var(--text-muted); margin-bottom: 8px; display:block;"></i>
                    <span style="font-size: 0.9rem; color: var(--text-muted);">
                        This application is locked. Decision of <strong><?php echo strtoupper($app['status']); ?></strong> has been finalized.
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal 1: Document Verification Status Update -->
<div id="doc_action_modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">Verify Uploaded Document</h4>
            <button class="btn btn-secondary btn-sm" onclick="closeModal('doc_action_modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="verify_application.php?id=<?php echo $application_id; ?>" method="POST">
            <input type="hidden" name="action" id="doc_modal_action" value="verify_doc">
            <input type="hidden" name="doc_type" id="doc_modal_type" value="">
            
            <div class="modal-body">
                <p style="font-size: 0.95rem; margin-bottom: 1.25rem;">
                    Confirm verification status for document type: <strong id="doc_modal_title_label">COLLEGE ID</strong>.
                </p>
                
                <div class="form-group">
                    <label class="form-label">Review Status</label>
                    <div style="display: flex; gap: 15px; margin-top: 6px;">
                        <label style="display:flex; align-items:center; gap: 6px; cursor:pointer;">
                            <input type="radio" name="doc_status_radio" value="verify" checked onclick="setDocModalAction('verify')"> 
                            <span class="badge badge-approved">Valid &amp; Verified</span>
                        </label>
                        <label style="display:flex; align-items:center; gap: 6px; cursor:pointer;">
                            <input type="radio" name="doc_status_radio" value="invalidate" onclick="setDocModalAction('invalidate')"> 
                            <span class="badge badge-rejected">Flag for Correction</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="doc_comments" class="form-label">Feedback Comments</label>
                    <textarea name="doc_comments" id="doc_comments" class="form-control" placeholder="Describe authenticity confirmation or why it's invalid..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('doc_action_modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Correction Request Form -->
<div id="correction_modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">Request Corrections from Student</h4>
            <button class="btn btn-secondary btn-sm" onclick="closeModal('correction_modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="verify_application.php?id=<?php echo $application_id; ?>" method="POST">
            <input type="hidden" name="action" value="request_corrections">
            
            <div class="modal-body">
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Check the fields containing errors and provide instructions on how the student can correct them.
                </p>
                
                <!-- Input field: Home Address Correction -->
                <div class="form-group" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chk_corr_address" onchange="toggleCorrectionInput('corr_address')"> 
                        Home Residential Address
                    </label>
                    <div id="div_corr_address" style="display:none; margin-top: 8px;">
                        <input type="text" name="corrections[address]" class="form-control" placeholder="e.g. Please provide your complete street address details.">
                    </div>
                </div>

                <!-- Input field: College ID Upload Correction -->
                <div class="form-group" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chk_corr_college_id" onchange="toggleCorrectionInput('corr_college_id')"> 
                        College ID Document
                    </label>
                    <div id="div_corr_college_id" style="display:none; margin-top: 8px;">
                        <input type="text" name="corrections[college_id_doc]" class="form-control" placeholder="e.g. Uploaded card is blurred, upload a high-resolution photo.">
                    </div>
                </div>

                <!-- Input field: Photo upload Correction -->
                <div class="form-group" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chk_corr_photograph" onchange="toggleCorrectionInput('corr_photograph')"> 
                        Photograph Document
                    </label>
                    <div id="div_corr_photograph" style="display:none; margin-top: 8px;">
                        <input type="text" name="corrections[photograph_doc]" class="form-control" placeholder="e.g. Upload passport format image with clear plain white background.">
                    </div>
                </div>

                <!-- Input field: Address Proof upload Correction -->
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap: 8px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="chk_corr_address_proof" onchange="toggleCorrectionInput('corr_address_proof')"> 
                        Address Proof Document
                    </label>
                    <div id="div_corr_address_proof" style="display:none; margin-top: 8px;">
                        <input type="text" name="corrections[address_proof_doc]" class="form-control" placeholder="e.g. Proof must be a utility bill or local bank letter within 3 months.">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('correction_modal')">Cancel</button>
                <button type="submit" class="btn btn-warning">Send Correction Requests</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Approve / Reject Decision Confirmation -->
<div id="decision_modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title" id="decision_modal_title">Decision Panel</h4>
            <button class="btn btn-secondary btn-sm" onclick="closeModal('decision_modal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <!-- Action target form: Approve -->
        <form id="approve_form" action="verify_application.php?id=<?php echo $application_id; ?>" method="POST" style="display:none;">
            <input type="hidden" name="action" value="approve">
            <div class="modal-body">
                <p style="margin-bottom: 1.25rem; font-size: 0.95rem;">
                    Confirm approval of the bus pass application for <strong><?php echo htmlspecialchars($app['student_name']); ?></strong>.
                </p>
                <div class="form-group">
                    <label for="validity_duration" class="form-label">Pass Validity Duration</label>
                    <select name="validity_duration" id="validity_duration" class="form-control">
                        <option value="1">1 Month (Trial Pass)</option>
                        <option value="6" selected>6 Months (Semester Pass)</option>
                        <option value="12">12 Months (Annual Academic Pass)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="decision_notes_app" class="form-label">Approval Comments / Notes</label>
                    <textarea name="decision_notes" id="decision_notes_app" class="form-control" placeholder="Verification notes or special remarks..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('decision_modal')">Cancel</button>
                <button type="submit" class="btn btn-success">Issue Pass</button>
            </div>
        </form>

        <!-- Action target form: Reject -->
        <form id="reject_form" action="verify_application.php?id=<?php echo $application_id; ?>" method="POST" style="display:none;">
            <input type="hidden" name="action" value="reject">
            <div class="modal-body">
                <p style="margin-bottom: 1.25rem; font-size: 0.95rem; color: var(--danger);">
                    Are you sure you want to REJECT this application? A rejection is final and requires a clear stated reason.
                </p>
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                    <textarea name="rejection_reason" id="rejection_reason" class="form-control" placeholder="e.g. Residence details do not match college records or fraud document detected..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('decision_modal')">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Application</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Document status config
function openDocActionModal(docType) {
    document.getElementById('doc_modal_type').value = docType;
    let label = docType.replace('_', ' ').toUpperCase();
    document.getElementById('doc_modal_title_label').innerText = label;
    
    // reset inputs
    document.getElementById('doc_comments').value = '';
    document.getElementById('doc_modal_action').value = 'verify_doc';
    document.querySelector('input[name="doc_status_radio"][value="verify"]').checked = true;
    
    openModal('doc_action_modal');
}

function setDocModalAction(type) {
    let actionInput = document.getElementById('doc_modal_action');
    if (type === 'verify') {
        actionInput.value = 'verify_doc';
    } else {
        actionInput.value = 'invalidate_doc';
    }
}

// Correction requests
function openCorrectionModal() {
    openModal('correction_modal');
}

function toggleCorrectionInput(fieldId) {
    let div = document.getElementById('div_' + fieldId);
    let chk = document.getElementById('chk_' + fieldId);
    if (chk.checked) {
        div.style.display = 'block';
        div.querySelector('input').setAttribute('required', 'required');
    } else {
        div.style.display = 'none';
        div.querySelector('input').removeAttribute('required');
        div.querySelector('input').value = '';
    }
}

// Approve / Reject decision Modal router
function openDecisionModal(type) {
    let titleEl = document.getElementById('decision_modal_title');
    let appForm = document.getElementById('approve_form');
    let rejForm = document.getElementById('reject_form');
    
    if (type === 'approve') {
        titleEl.innerText = "Authorize & Issue Bus Pass";
        appForm.style.display = 'block';
        rejForm.style.display = 'none';
    } else {
        titleEl.innerText = "Reject Bus Pass Application";
        appForm.style.display = 'none';
        rejForm.style.display = 'block';
    }
    
    openModal('decision_modal');
}
</script>

<?php 
include __DIR__ . '/footer.php';
?>
