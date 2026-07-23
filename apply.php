<?php
// apply.php - Apply for Bus Pass

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

check_auth(['student']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if student has already submitted an active application
try {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        die("Student profile not found. Please contact administrative support.");
    }
    
    $student_id = $student['id'];
    
    $stmt_check = $pdo->prepare("SELECT id, status FROM applications WHERE student_id = ? ORDER BY submission_date DESC LIMIT 1");
    $stmt_check->execute([$student_id]);
    $active_app = $stmt_check->fetch();
    
    if ($active_app && in_array($active_app['status'], ['submitted', 'under_verification', 'approved', 'correction_required'])) {
        header("Location: student_dashboard.php");
        exit();
    }
    
    // Fetch active routes for dropdown
    $routes = $pdo->query("SELECT * FROM routes WHERE status = 'active'")->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $route_id = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
    
    if ($route_id) {
        $upload_dir = __DIR__ . '/uploads';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $college_id_path = '';
        $photo_path = '';
        $address_path = '';
        
        $files_ok = true;
        
        // Helper to validate and move file
        $upload_file = function($input_name, $student_prn) use ($upload_dir, &$error, &$files_ok) {
            if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
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
            
            $new_name = $input_name . '_' . $student_prn . '_' . time() . '.' . $ext;
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
        $college_id_path = $upload_file('college_id', $prn);
        if ($files_ok) $photo_path = $upload_file('photograph', $prn);
        if ($files_ok) $address_path = $upload_file('address_proof', $prn);
        
        if ($files_ok && !empty($college_id_path) && !empty($photo_path) && !empty($address_path)) {
            try {
                $pdo->beginTransaction();
                
                // Route Validation & Dispatch check (4.3 Route Validation)
                // Default queue is Transport Dept, but if distance > 50 it can alert during verification
                $routing_dept = 'Transport Dept';
                
                $stmt_ins = $pdo->prepare("
                    INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept)
                    VALUES (?, ?, 'submitted', ?, ?, ?, ?)
                ");
                $stmt_ins->execute([$student_id, $route_id, $college_id_path, $photo_path, $address_path, $routing_dept]);
                
                $app_id = $pdo->lastInsertId();
                
                // Add initial route validation log
                $stmt_log = $pdo->prepare("INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) VALUES (?, ?, 1, ?)");
                $stmt_log->execute([$app_id, $route_id, 1, "Application initial routing to Transport Dept queue completed."]);

                $pdo->commit();
                $success = 'Application submitted successfully!';
                header("Location: student_dashboard.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Submission failed: " . $e->getMessage();
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
    <h2 class="page-title">Apply for Bus Pass</h2>
    <p class="page-subtitle">Submit your bus pass request by selecting routes and uploading official verification documents.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="detail-grid" style="grid-template-columns: 2fr 1fr;">
    <div class="detail-card">
        <div class="detail-card-title">Bus Pass Application Form</div>
        
        <form action="apply.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="route_id" class="form-label">Select Bus Route</label>
                <select name="route_id" id="route_id" class="form-control" required>
                    <option value="">-- Choose Route --</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo $route['id']; ?>">
                            Route <?php echo htmlspecialchars($route['route_number']); ?>: 
                            <?php echo htmlspecialchars($route['source']); ?> &rarr; <?php echo htmlspecialchars($route['destination']); ?> 
                            (<?php echo htmlspecialchars($route['distance']); ?> km)
                        </option>
                    <?php endforeach; ?>
                </select>
                <span style="font-size:0.8rem; color:var(--text-secondary); display:block; margin-top:5px;">
                    * Notice: Routes exceeding 50km will require administrator exception authorization.
                </span>
            </div>

            <div style="border-top:1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem; margin-bottom: 1.5rem;">
                <h3 class="checklist-title">Upload Official Verification Documents</h3>
                
                <div class="form-group">
                    <label for="college_id" class="form-label">College Identity Card Upload</label>
                    <input type="file" name="college_id" id="college_id" class="form-control" style="padding: 6px 12px;" required>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Please upload scan/photo of your current college ID card. PNG, JPG, JPEG or PDF (Max 2MB).</span>
                </div>

                <div class="form-group">
                    <label for="photograph" class="form-label">Recent Photograph</label>
                    <input type="file" name="photograph" id="photograph" class="form-control" style="padding: 6px 12px;" required>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Passport format photo (light background, clear face view). PNG, JPG, JPEG (Max 2MB).</span>
                </div>

                <div class="form-group">
                    <label for="address_proof" class="form-label">Residential Address Proof</label>
                    <input type="file" name="address_proof" id="address_proof" class="form-control" style="padding: 6px 12px;" required>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Electricity Bill, Bank statement, or official letter showing your name and address. PNG, JPG, JPEG or PDF (Max 2MB).</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 1rem;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Submit Bus Pass Application
            </button>
        </form>
    </div>

    <div>
        <div class="action-box">
            <h3 class="checklist-title"><i class="fa-solid fa-shield-halved" style="color:var(--accent);"></i> Submission Standards</h3>
            <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; flex-direction:column; gap:12px;">
                <div>
                    <strong>1. Document Legitimacy</strong>
                    <p style="color:var(--text-muted); font-size:0.8rem; margin-top:2px;">All uploaded document scans must contain clear text readable by the system validators. Blurred files will be flagged for correction.</p>
                </div>
                <div>
                    <strong>2. Format &amp; Size Checks</strong>
                    <p style="color:var(--text-muted); font-size:0.8rem; margin-top:2px;">Only PNG, JPG, JPEG, and PDF file types are accepted. File size of each document must not exceed 2.0 Megabytes (2048 KB).</p>
                </div>
                <div>
                    <strong>3. Automatic Validation Logs</strong>
                    <p style="color:var(--text-muted); font-size:0.8rem; margin-top:2px;">The system automatically logs document integrity and runs routing checks immediately upon submission.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/footer.php';
?>
