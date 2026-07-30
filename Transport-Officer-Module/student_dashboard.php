<?php
// student_dashboard.php - Student Portal Dashboard

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce Student role access
check_auth(['student']);

$user_id = $_SESSION['user_id'];
$alert_message = '';
$alert_type = 'success';

// Handle POST correction submissions (4.5 Resolving Corrections)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resolve_corrections') {
    $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    if ($application_id) {
        try {
            $pdo->beginTransaction();
            
            // Fetch correction requests to resolve
            $stmt = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
            $stmt->execute([$application_id]);
            $pending = $stmt->fetchAll();

            $upload_dir = __DIR__ . '/uploads';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Keep track of what we updated
            $updates = [];
            
            foreach ($pending as $req) {
                $field = $req['field_name'];
                
                // If it is a document field
                if (in_array($field, ['college_id_doc', 'photograph_doc', 'address_proof_doc'])) {
                    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES[$field]['tmp_name'];
                        $filename = uniqid('student_') . '_' . basename($_FILES[$field]['name']);
                        $target_path = 'uploads/' . $filename;
                        
                        if (move_uploaded_file($tmp_name, __DIR__ . '/' . $target_path)) {
                            // Update the application table
                            $stmt_upd = $pdo->prepare("UPDATE applications SET $field = ? WHERE id = ?");
                            $stmt_upd->execute([$target_path, $application_id]);
                            
                            // Mark correction as resolved
                            $stmt_res = $pdo->prepare("UPDATE correction_requests SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
                            $stmt_res->execute([$req['id']]);
                            
                            // Reset document verification status
                            $clean_type = str_replace('_doc', '', $field);
                            $stmt_verify = $pdo->prepare("UPDATE document_verifications SET status = 'pending', comments = 'Re-uploaded by student' WHERE application_id = ? AND document_type = ?");
                            $stmt_verify->execute([$application_id, $clean_type]);
                        }
                    }
                } 
                // If it is text input field (e.g. address)
                elseif ($field === 'address') {
                    $new_address = trim($_POST['address'] ?? '');
                    if (!empty($new_address)) {
                        // Update students table
                        $stmt_std = $pdo->prepare("UPDATE students SET address = ? WHERE user_id = ?");
                        $stmt_std->execute([$new_address, $user_id]);
                        
                        // Mark correction as resolved
                        $stmt_res = $pdo->prepare("UPDATE correction_requests SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt_res->execute([$req['id']]);
                    }
                }
            }

            // Set application status back to under_verification
            $stmt_status = $pdo->prepare("UPDATE applications SET status = 'under_verification', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_status->execute([$application_id]);

            $pdo->commit();
            $alert_message = "Corrections submitted successfully. Your application status is now 'Under Verification'.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $alert_message = "Failed to submit corrections: " . $e->getMessage();
            $alert_type = 'danger';
        }
    }
}

// Fetch student & application details
try {
    // 1. Fetch student info
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();

    $application = null;
    $pass = null;
    $corrections = [];
    
    if ($student) {
        // 2. Fetch latest application
        $stmt_app = $pdo->prepare("
            SELECT a.*, r.route_number, r.source, r.destination, r.distance 
            FROM applications a
            JOIN routes r ON a.route_id = r.id
            WHERE a.student_id = ?
            ORDER BY a.submission_date DESC LIMIT 1
        ");
        $stmt_app->execute([$student['id']]);
        $application = $stmt_app->fetch();
        
        if ($application) {
            // 3. Fetch pass details if approved
            if ($application['status'] === 'approved') {
                $stmt_pass = $pdo->prepare("SELECT * FROM passes WHERE application_id = ?");
                $stmt_pass->execute([$application['id']]);
                $pass = $stmt_pass->fetch();
            }
            
            // 4. Fetch pending corrections
            $stmt_corr = $pdo->prepare("SELECT * FROM correction_requests WHERE application_id = ? AND status = 'pending'");
            $stmt_corr->execute([$application['id']]);
            $corrections = $stmt_corr->fetchAll();
        }
    }
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Student Dashboard</h2>
    <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>. Track application status, view passes, or update information.</p>
</div>

<?php if (!empty($alert_message)): ?>
    <div class="alert alert-<?php echo $alert_type; ?>">
        <i class="fa-solid fa-circle-info"></i> <?php echo $alert_message; ?>
    </div>
<?php endif; ?>

<!-- Check if student account is setup -->
<?php if (!$student): ?>
    <div class="detail-card" style="text-align: center; padding: 3rem;">
        <i class="fa-solid fa-circle-exclamation" style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem;"></i>
        <h3>Student Profile Not Configured</h3>
        <p style="color: var(--text-secondary); margin-top: 0.5rem; margin-bottom: 1.5rem;">Please contact the Transport Administrator to register your PRN/Student profile details in the system.</p>
    </div>
<?php else: ?>

    <div class="detail-grid">
        <!-- Left Column: Pass & Application View -->
        <div>
            <!-- Active Pass Widget -->
            <?php if ($application && $application['status'] === 'approved' && $pass): ?>
                <div class="detail-card" style="background: rgba(16, 185, 129, 0.03); border-color: rgba(16, 185, 129, 0.2);">
                    <div class="detail-card-title">
                        <span><i class="fa-solid fa-id-card" style="color: var(--success); margin-right: 8px;"></i> Active Digital Bus Pass</span>
                        <a href="download_pass.php?id=<?php echo $pass['id']; ?>" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-download"></i> Download Pass (PDF)
                        </a>
                    </div>
                    
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
                                    <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">NAME:</td><td style="padding: 4px 0; border: none; color: white;"><strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></td></tr>
                                    <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">PRN:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo htmlspecialchars($student['prn_number']); ?></td></tr>
                                    <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">ROUTE:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo htmlspecialchars($application['route_number']); ?> (<?php echo htmlspecialchars($application['source']); ?> &rarr; <?php echo htmlspecialchars($application['destination']); ?>)</td></tr>
                                    <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID FROM:</td><td style="padding: 4px 0; border: none; color: white;"><?php echo date('d-M-Y', strtotime($pass['valid_from'])); ?></td></tr>
                                    <tr style="background: none;"><td style="padding: 4px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID UNTIL:</td><td style="padding: 4px 0; border: none; color: var(--success); font-weight: 600;"><?php echo date('d-M-Y', strtotime($pass['valid_to'])); ?></td></tr>
                                </table>
                            </div>
                            <div>
                                <div class="pass-qr" title="<?php echo htmlspecialchars($pass['qr_code']); ?>">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0" y="0" width="100" height="100" fill="none" stroke="black" stroke-width="6"/>
                                        <rect x="10" y="10" width="25" height="25" fill="black"/>
                                        <rect x="15" y="15" width="15" height="15" fill="white"/>
                                        <rect x="10" y="65" width="25" height="25" fill="black"/>
                                        <rect x="15" y="70" width="15" height="15" fill="white"/>
                                        <rect x="65" y="10" width="25" height="25" fill="black"/>
                                        <rect x="70" y="15" width="15" height="15" fill="white"/>
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
                </div>
            <?php endif; ?>

            <!-- Correction Requests Trigger (4.5 Request Corrections Form) -->
            <?php if ($application && $application['status'] === 'correction_required' && !empty($corrections)): ?>
                <div class="detail-card" style="border: 1px solid var(--warning); background: rgba(245, 158, 11, 0.02);">
                    <div class="detail-card-title" style="color: var(--warning);">
                        <span><i class="fa-solid fa-triangle-exclamation"></i> Correction Action Required</span>
                        <span class="badge badge-correction">Clarification Needed</span>
                    </div>
                    
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                        The transport authority has found issues with your application and requested the following corrections. Please upload updated documents/information below to resume your pass verification.
                    </p>

                    <form action="student_dashboard.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="resolve_corrections">
                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">

                        <?php foreach ($corrections as $req): ?>
                            <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; border: 1px solid var(--border-color);">
                                <div style="display:flex; justify-content:space-between; margin-bottom: 8px;">
                                    <span style="font-weight:600; color:var(--text-primary);">
                                        <?php 
                                            if ($req['field_name'] == 'address') echo 'Home Residential Address';
                                            elseif ($req['field_name'] == 'college_id_doc') echo 'College Identity Card Upload';
                                            elseif ($req['field_name'] == 'photograph_doc') echo 'Student Photograph Upload';
                                            elseif ($req['field_name'] == 'address_proof_doc') echo 'Address Proof Upload';
                                        ?>
                                    </span>
                                    <span class="badge badge-rejected" style="padding: 2px 8px; font-size:0.65rem;">Action Needed</span>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 12px; border-left: 2px solid var(--warning); padding-left: 10px;">
                                    <strong>Instruction:</strong> <?php echo htmlspecialchars($req['instruction']); ?>
                                </div>

                                <!-- Dynamic Input Fields based on type -->
                                <?php if ($req['field_name'] === 'address'): ?>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label for="address_input" class="form-label">Corrected Address</label>
                                        <textarea id="address_input" name="address" class="form-control" placeholder="Enter corrected residential address..." required><?php echo htmlspecialchars($student['address']); ?></textarea>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Upload New File (PNG, JPG, JPEG, PDF < 2MB)</label>
                                        <input type="file" name="<?php echo htmlspecialchars($req['field_name']); ?>" class="form-control" style="padding: 6px 12px;" required>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn btn-warning" style="width: 100%;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Submit Corrected Files
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Latest Application Tracker -->
            <div class="detail-card" id="applications">
                <div class="detail-card-title">
                    <span><i class="fa-solid fa-receipt" style="color: var(--accent); margin-right: 8px;"></i> Latest Application Status</span>
                </div>
                
                <?php if (!$application): ?>
                    <div style="text-align: center; padding: 2rem 0;">
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You haven't submitted any bus pass application yet.</p>
                        <a href="apply.php" class="btn btn-primary">
                            <i class="fa-solid fa-file-invoice"></i> Apply Now
                        </a>
                    </div>
                <?php else: ?>
                    <table style="width: 100%;">
                        <tr>
                            <td style="color: var(--text-secondary); font-weight: 600;">Status</td>
                            <td><?php echo get_status_badge($application['status']); ?></td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-secondary); font-weight: 600;">Requested Route</td>
                            <td>
                                <strong><?php echo htmlspecialchars($application['route_number']); ?></strong>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($application['source']); ?> &rarr; <?php echo htmlspecialchars($application['destination']); ?> (<?php echo htmlspecialchars($application['distance']); ?> km)</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-secondary); font-weight: 600;">Current Department Queue</td>
                            <td>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-secondary);">
                                    <?php echo htmlspecialchars($application['routing_dept']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-secondary); font-weight: 600;">Submission Date</td>
                            <td><?php echo date('d-M-Y H:i', strtotime($application['submission_date'])); ?></td>
                        </tr>
                        <tr>
                            <td style="color: var(--text-secondary); font-weight: 600;">Last Update</td>
                            <td><?php echo date('d-M-Y H:i', strtotime($application['last_updated'])); ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($application['status'] === 'expired' || $application['status'] === 'rejected'): ?>
                        <div style="margin-top: 1.5rem;">
                            <a href="apply.php" class="btn btn-primary">Apply Again</a>
                        </div>
                    <?php elseif ($application['status'] === 'approved'): ?>
                        <div style="margin-top: 1.5rem;">
                            <a href="renew.php" class="btn btn-secondary">Apply for Pass Renewal</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Profile Summary & Alerts -->
        <div>
            <!-- Profile Summary Card -->
            <div class="action-box" style="width:100%; margin-bottom: 1.5rem;">
                <h3 class="checklist-title"><i class="fa-solid fa-address-card" style="color: var(--accent); margin-right: 8px;"></i> Profile Summary</h3>
                <div style="font-size: 0.9rem; display:flex; flex-direction:column; gap:12px;">
                    <div><span style="color:var(--text-muted); display:block; font-size:0.75rem; text-transform:uppercase;">PRN Number</span> <strong><?php echo htmlspecialchars($student['prn_number']); ?></strong></div>
                    <div><span style="color:var(--text-muted); display:block; font-size:0.75rem; text-transform:uppercase;">Roll Number</span> <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></div>
                    <div><span style="color:var(--text-muted); display:block; font-size:0.75rem; text-transform:uppercase;">Department</span> <strong><?php echo htmlspecialchars($student['department']); ?></strong></div>
                    <div><span style="color:var(--text-muted); display:block; font-size:0.75rem; text-transform:uppercase;">Class</span> <strong><?php echo htmlspecialchars($student['class']); ?></strong></div>
                    <div><span style="color:var(--text-muted); display:block; font-size:0.75rem; text-transform:uppercase;">Mobile Number</span> <strong><?php echo htmlspecialchars($student['mobile']); ?></strong></div>
                </div>
            </div>

            <!-- Notifications & Alerts Box -->
            <div class="action-box" style="width:100%;">
                <h3 class="checklist-title"><i class="fa-solid fa-bell" style="color: var(--warning); margin-right: 8px;"></i> System Notifications</h3>
                
                <div style="display:flex; flex-direction:column; gap:10px; font-size:0.85rem;">
                    <?php if ($application && $application['status'] === 'correction_required'): ?>
                        <div style="background: rgba(245, 158, 11, 0.08); border-left: 3px solid var(--warning); padding: 10px; border-radius: 4px;">
                            <strong>Correction Needed:</strong> The officer has requested updates to your documents. Please review and re-submit.
                        </div>
                    <?php elseif ($application && $application['status'] === 'approved'): ?>
                        <div style="background: rgba(16, 185, 129, 0.08); border-left: 3px solid var(--success); padding: 10px; border-radius: 4px;">
                            <strong>Pass Issued!</strong> Your digital pass is ready for download. Check validity periods.
                        </div>
                    <?php elseif ($application && $application['status'] === 'submitted'): ?>
                        <div style="background: rgba(63, 140, 255, 0.08); border-left: 3px solid var(--accent); padding: 10px; border-radius: 4px;">
                            <strong>Application Submitted:</strong> We have received your application. It is queued for Verification.
                        </div>
                    <?php else: ?>
                        <div style="color: var(--text-muted); text-align: center; padding: 1rem 0;">No active notifications.</div>
                    <?php endif; ?>
                    
                    <div style="border-top:1px solid var(--border-color); padding-top: 10px; margin-top: 5px; color:var(--text-muted); font-size:0.75rem;">
                        <i class="fa-solid fa-circle-info"></i> Standard verification completes within 2 business days.
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php 
include __DIR__ . '/footer.php';
?>
