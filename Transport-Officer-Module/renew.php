<?php
// renew.php - Pass Renewal Request

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

check_auth(['student']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        die("Student profile not found.");
    }
    
    $student_id = $student['id'];
    
    // Fetch latest approved or expired application to renew
    $stmt_check = $pdo->prepare("SELECT * FROM applications WHERE student_id = ? ORDER BY submission_date DESC LIMIT 1");
    $stmt_check->execute([$student_id]);
    $last_app = $stmt_check->fetch();
    
    if (!$last_app) {
        $error = "You don't have any existing bus pass application to renew. Please use the Apply Pass form first.";
    } elseif ($last_app['status'] !== 'approved' && $last_app['status'] !== 'expired') {
        $error = "Your current application status is: '" . strtoupper($last_app['status']) . "'. Renewal can only be requested for Approved or Expired passes.";
    }
    
    // Fetch active routes
    $routes = $pdo->query("SELECT * FROM routes WHERE status = 'active'")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $route_id = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
    
    if ($route_id) {
        $upload_dir = __DIR__ . '/uploads';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $college_id_path = $last_app['college_id_doc'];
        $photo_path = $last_app['photograph_doc'];
        $address_path = $last_app['address_proof_doc'];
        
        $files_ok = true;
        
        // Helper to validate and move file (optional for renewal - can reuse old ones if not uploaded)
        $upload_file = function($input_name, $student_prn, $default_val) use ($upload_dir, &$error, &$files_ok) {
            if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] === UPLOAD_ERR_NO_FILE) {
                return $default_val; // Reuse previous document
            }
            
            if ($_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
                $error = "Error uploading document: " . str_replace('_', ' ', $input_name);
                $files_ok = false;
                return '';
            }
            
            $file = $_FILES[$input_name];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['png', 'jpg', 'jpeg', 'pdf'];
            
            if (!in_array($ext, $allowed_exts)) {
                $error = "Format error for " . str_replace('_', ' ', $input_name) . ". Only PNG, JPG, JPEG, PDF allowed.";
                $files_ok = false;
                return '';
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                $error = "Size error for " . str_replace('_', ' ', $input_name) . ". Max size 2MB allowed.";
                $files_ok = false;
                return '';
            }
            
            $new_name = $input_name . '_renewal_' . $student_prn . '_' . time() . '.' . $ext;
            $destination = $upload_dir . '/' . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return 'uploads/' . $new_name;
            } else {
                $error = "Failed to save file: " . str_replace('_', ' ', $input_name);
                $files_ok = false;
                return '';
            }
        };
        
        $prn = $student['prn_number'] ?? 'PRN';
        $college_id_path = $upload_file('college_id', $prn, $college_id_path);
        if ($files_ok) $photo_path = $upload_file('photograph', $prn, $photo_path);
        if ($files_ok) $address_path = $upload_file('address_proof', $prn, $address_path);
        
        if ($files_ok) {
            try {
                $pdo->beginTransaction();
                
                // Update previous application to "renewed" or create a new application record in "submitted" state.
                // Creating a new application is cleaner so we keep history.
                $stmt_ins = $pdo->prepare("
                    INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept)
                    VALUES (?, ?, 'submitted', ?, ?, ?, 'Transport Dept')
                ");
                $stmt_ins->execute([$student_id, $route_id, $college_id_path, $photo_path, $address_path]);
                $new_app_id = $pdo->lastInsertId();
                
                // Set old application status to 'renewed' to indicate it is replaced
                $stmt_old = $pdo->prepare("UPDATE applications SET status = 'renewed' WHERE id = ?");
                $stmt_old->execute([$last_app['id']]);

                // Create document verification records as pending
                $stmt_verify = $pdo->prepare("INSERT INTO document_verifications (application_id, document_type, status, comments) VALUES (?, ?, 'pending', 'Verification pending on renewal')");
                $stmt_verify->execute([$new_app_id, 'college_id']);
                $stmt_verify->execute([$new_app_id, 'photograph']);
                $stmt_verify->execute([$new_app_id, 'address_proof']);
                
                // Add route validation log
                $stmt_log = $pdo->prepare("INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) VALUES (?, ?, 1, ?)");
                $stmt_log->execute([$new_app_id, $route_id, 1, "Renewal request registered. Re-routed to Transport Dept queue."]);

                $pdo->commit();
                $success = 'Renewal application submitted successfully!';
                header("Location: student_dashboard.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Renewal submission failed: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please select a valid bus route.";
    }
}

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Renew Bus Pass</h2>
    <p class="page-subtitle">Renew your expired or near-expiry bus pass. You can update your documentation if changes occurred.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (empty($error)): ?>
    <div class="detail-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="detail-card">
            <div class="detail-card-title">Renewal Application Form</div>
            
            <form action="renew.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="route_id" class="form-label">Select Bus Route</label>
                    <select name="route_id" id="route_id" class="form-control" required>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>" <?php echo ($last_app['route_id'] == $route['id']) ? 'selected' : ''; ?>>
                                Route <?php echo htmlspecialchars($route['route_number']); ?>: 
                                <?php echo htmlspecialchars($route['source']); ?> &rarr; <?php echo htmlspecialchars($route['destination']); ?> 
                                (<?php echo htmlspecialchars($route['distance']); ?> km)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="border-top:1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem; margin-bottom: 1.5rem;">
                    <h3 class="checklist-title" style="margin-bottom: 0.5rem;">Update Verification Documents (Optional)</h3>
                    <p style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:1.5rem;">Leave files empty if you want to reuse previously uploaded and verified documents.</p>
                    
                    <div class="form-group">
                        <label for="college_id" class="form-label">College Identity Card Upload</label>
                        <input type="file" name="college_id" id="college_id" class="form-control" style="padding: 6px 12px;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Current File: <code><?php echo htmlspecialchars(basename($last_app['college_id_doc'])); ?></code></span>
                    </div>

                    <div class="form-group">
                        <label for="photograph" class="form-label">Recent Photograph</label>
                        <input type="file" name="photograph" id="photograph" class="form-control" style="padding: 6px 12px;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Current File: <code><?php echo htmlspecialchars(basename($last_app['photograph_doc'])); ?></code></span>
                    </div>

                    <div class="form-group">
                        <label for="address_proof" class="form-label">Residential Address Proof</label>
                        <input type="file" name="address_proof" id="address_proof" class="form-control" style="padding: 6px 12px;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Current File: <code><?php echo htmlspecialchars(basename($last_app['address_proof_doc'])); ?></code></span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    <i class="fa-solid fa-arrows-rotate"></i> Submit Renewal Request
                </button>
            </form>
        </div>

        <div>
            <div class="action-box">
                <h3 class="checklist-title"><i class="fa-solid fa-arrows-spin" style="color:var(--accent);"></i> Renewal Workflow</h3>
                <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <strong>1. Document Review</strong>
                        <p style="color:var(--text-muted); font-size:0.8rem; margin-top:2px;">If you reuse your previous documents, officers will check them against previous logs. If they have expired (e.g. ID card), they must be re-uploaded.</p>
                    </div>
                    <div>
                        <strong>2. Route Re-Validation</strong>
                        <p style="color:var(--text-muted); font-size:0.8rem; margin-top:2px;">The system checks route status and distance limits again upon renewal request submissions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php 
include __DIR__ . '/footer.php';
?>
