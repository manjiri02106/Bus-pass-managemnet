<?php
/**
 * Renew Bus Pass
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student = getCurrentStudent();
$student_id = $student['id'] ?? (int)($_SESSION['student_id'] ?? 0);

// Get latest approved Monthly bus pass
$latest_pass_query = "SELECT p.*, a.route_id, a.college_id_doc, a.photograph_doc, a.address_proof_doc, a.application_no, a.fee, r.source, r.destination, r.route_number, r.distance
                      FROM passes p
                      JOIN applications a ON p.application_id = a.id
                      JOIN routes r ON a.route_id = r.id
                      WHERE a.student_id = ? AND a.status = 'approved' AND a.pass_type = 'Monthly'
                      ORDER BY p.valid_to DESC LIMIT 1";
$latest_pass_stmt = mysqli_prepare($conn, $latest_pass_query);
mysqli_stmt_bind_param($latest_pass_stmt, 'i', $student_id);
mysqli_stmt_execute($latest_pass_stmt);
$latest_pass_result = mysqli_stmt_get_result($latest_pass_stmt);
$original_pass = mysqli_fetch_assoc($latest_pass_result);

if (!$original_pass) {
    redirect('/dashboard.php', 'You don\'t have an approved Monthly bus pass to renew.', 'warning');
}

// Check if there's already a pending renewal for this pass
$pending_query = "SELECT * FROM applications 
                  WHERE student_id = ? AND status IN ('submitted', 'under_verification') AND original_pass_id = ?
                  ORDER BY id DESC LIMIT 1";
$pending_stmt = mysqli_prepare($conn, $pending_query);
mysqli_stmt_bind_param($pending_stmt, 'ii', $student_id, $original_pass['application_id']);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);
$pending_renewal = mysqli_fetch_assoc($pending_result);

if ($pending_renewal) {
    redirect('/payment.php?pass_id=' . $pending_renewal['id'], 'You already have a pending renewal. Please complete payment.', 'info');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_pass'])) {
    // Use same route as original pass
    $route_id = $original_pass['route_id'];
    $pass_type = 'Monthly'; // Fixed to Monthly for renewal as per requirement
    $original_pass_id = $original_pass['id'];

    // Calculate fee and validity
    $route_q = "SELECT * FROM routes WHERE id = ?";
    $route_s = mysqli_prepare($conn, $route_q);
    mysqli_stmt_bind_param($route_s, 'i', $route_id);
    mysqli_stmt_execute($route_s);
    $route = mysqli_fetch_assoc(mysqli_stmt_get_result($route_s));

    if (!$route) {
        $error = 'Invalid route.';
    } else {
        $base_fare = $route['fare'] ?? getDailyFareFromCSV($route['distance'] ?? 0, 10);
        $fee = calculatePassFee($base_fare, $pass_type);
        $validity = calculateValidity($pass_type);
        $app_no = generateApplicationNo($student_id);

        // Handle file uploads - keep existing or replace
        $upload_dir = UPLOAD_PATH;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $college_id_doc = $original_pass['college_id_doc'];
        $photo_doc = $original_pass['photograph_doc'];
        $address_proof_doc = $original_pass['address_proof_doc'];
        $upload_ok = true;

        // Replace College ID if new file is uploaded
        if (isset($_FILES['college_id_doc']) && $_FILES['college_id_doc']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['college_id_doc']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (in_array($ext, $allowed)) {
                $college_id_doc = 'college_id_' . $app_no . '.' . $ext;
                move_uploaded_file($_FILES['college_id_doc']['tmp_name'], $upload_dir . $college_id_doc);
            } else {
                $error = 'College ID must be JPG, PNG or PDF.';
                $upload_ok = false;
            }
        }

        // Replace Photo if new file is uploaded
        if ($upload_ok && isset($_FILES['photo_doc']) && $_FILES['photo_doc']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo_doc']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $photo_doc = 'photo_' . $app_no . '.' . $ext;
                move_uploaded_file($_FILES['photo_doc']['tmp_name'], $upload_dir . $photo_doc);
            } else {
                $error = 'Photo must be JPG or PNG.';
                $upload_ok = false;
            }
        }

        // Replace Address Proof if new file is uploaded
        if ($upload_ok && isset($_FILES['address_proof_doc']) && $_FILES['address_proof_doc']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['address_proof_doc']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            if (in_array($ext, $allowed)) {
                $address_proof_doc = 'address_' . $app_no . '.' . $ext;
                move_uploaded_file($_FILES['address_proof_doc']['tmp_name'], $upload_dir . $address_proof_doc);
            } else {
                $error = 'Address proof must be JPG, PNG or PDF.';
                $upload_ok = false;
            }
        }

        if ($upload_ok) {
            // Insert renewal pass
            $query = "INSERT INTO applications (application_no, student_id, route_id, original_pass_id, pass_type, college_id_doc, photograph_doc, address_proof_doc, fee, valid_from, valid_until, payment_status, status, routing_dept) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'submitted', '')";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'siiissssdss', $app_no, $student_id, $route_id, $original_pass_id, $pass_type, $college_id_doc, $photo_doc, $address_proof_doc, $fee, $validity['from'], $validity['until']);

            if (mysqli_stmt_execute($stmt)) {
                $pass_id = mysqli_insert_id($conn);
                // Notification
                $notif_q = "INSERT INTO notifications (student_id, title, message, type) VALUES (?, 'Renewal Request Submitted', 'Your bus pass renewal request (Ref: $app_no) has been submitted successfully. Please complete the payment.', 'info')";
                $notif_s = mysqli_prepare($conn, $notif_q);
                mysqli_stmt_bind_param($notif_s, 'i', $student_id);
                mysqli_stmt_execute($notif_s);

                redirect('/payment.php?pass_id=' . $pass_id, '', 'success');
            } else {
                $error = 'Failed to submit renewal. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Pass - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="fade-in">
            <h4 class="mb-4"><i class="bi bi-arrow-repeat text-primary me-2"></i>Renew Bus Pass</h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Original Pass Summary -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-card-checklist me-2"></i>Original Pass Details</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><td class="text-muted">Application No:</td><td class="fw-bold"><?php echo htmlspecialchars($original_pass['application_no']); ?></td></tr>
                                <tr><td class="text-muted">Route:</td><td class="fw-bold"><?php echo htmlspecialchars($original_pass['route_number'] ?? ($original_pass['source'] . ' → ' . $original_pass['destination'])); ?></td></tr>
                                <tr><td class="text-muted">Source:</td><td><?php echo htmlspecialchars($original_pass['source']); ?></td></tr>
                                <tr><td class="text-muted">Destination:</td><td><?php echo htmlspecialchars($original_pass['destination']); ?></td></tr>
                                <tr><td class="text-muted">Valid From:</td><td><?php echo date('d M Y', strtotime($original_pass['valid_from'])); ?></td></tr>
                                <tr><td class="text-muted">Valid Until:</td><td><?php echo date('d M Y', strtotime($original_pass['valid_to'])); ?></td></tr>
                                <tr><td class="text-muted">Original Fee:</td><td>₹<?php echo number_format($original_pass['fee'], 2); ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Renewal Form -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Renewal Form</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" enctype="multipart/form-data" id="renewPassForm">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Student Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">PRN / College ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['college_id_number'] ?? ''); ?>" disabled>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Pass Type</label>
                                        <input type="text" class="form-control" value="Monthly" disabled>
                                        <input type="hidden" name="pass_type" value="Monthly">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Bus Route (Fixed)</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($original_pass['route_number'] ?? ($original_pass['source'] . ' → ' . $original_pass['destination'])); ?>" disabled>
                                        <input type="hidden" name="route_id" value="<?php echo $original_pass['route_id']; ?>">
                                    </div>
                                </div>

                                <!-- Renewal Price Summary -->
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                                        <span><i class="bi bi-receipt me-2"></i><strong>Renewal Price Summary</strong></span>
                                    </div>
                                    <div class="card-body p-3">
                                        <?php
                                        $route_fare = getDailyFareFromCSV($original_pass['distance'] ?? 0, 10);
                                        $renewal_fee = calculatePassFee($route_fare, 'Monthly');
                                        $today = new DateTime();
                                        $valid_until = new DateTime();
                                        $valid_until->add(new DateInterval('P1M'));
                                        ?>
                                        <p class="mb-1">Daily Fare: <strong>₹<?php echo number_format($route_fare, 2); ?></strong></p>
                                        <p class="mb-1">Calculation: ₹<?php echo $route_fare; ?> × 30 days × 95% (5% off)</p>
                                        <p class="fs-4 fw-bold text-success mb-0">Total Amount to Pay: ₹<?php echo number_format($renewal_fee, 2); ?></p>
                                        <p class="text-muted small mt-2">Validity: <?php echo $today->format('d M Y'); ?> → <?php echo $valid_until->format('d M Y'); ?></p>
                                    </div>
                                </div>

                                <!-- Document Upload (Replace Option) -->
                                <h6 class="fw-bold mt-4 mb-3"><i class="bi bi-upload me-2"></i>Documents (Replace if needed)</h6>
                                <p class="text-muted small mb-3">Your existing documents are already attached. You may replace them if required.</p>
                                
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">College ID Card</label>
                                        <div class="upload-area" onclick="document.getElementById('college_id_doc').click()">
                                            <div class="upload-icon"><i class="bi bi-card-text"></i></div>
                                            <p class="mb-1">Click to replace College ID</p>
                                            <span class="file-name text-muted small">Existing file: <?php echo htmlspecialchars(basename($original_pass['college_id_doc'])); ?></span>
                                            <img id="preview_college_id" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                        </div>
                                        <input type="file" name="college_id_doc" id="college_id_doc" class="d-none" 
                                               accept=".jpg,.jpeg,.png,.pdf" data-preview="preview_college_id">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Passport Photo</label>
                                        <div class="upload-area" onclick="document.getElementById('photo_doc').click()">
                                            <div class="upload-icon"><i class="bi bi-person-badge"></i></div>
                                            <p class="mb-1">Click to replace Photo</p>
                                            <span class="file-name text-muted small">Existing file: <?php echo htmlspecialchars(basename($original_pass['photo_doc'])); ?></span>
                                            <img id="preview_photo" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                        </div>
                                        <input type="file" name="photo_doc" id="photo_doc" class="d-none" 
                                               accept=".jpg,.jpeg,.png" data-preview="preview_photo">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Address Proof</label>
                                        <div class="upload-area" onclick="document.getElementById('address_proof_doc').click()">
                                            <div class="upload-icon"><i class="bi bi-house-door"></i></div>
                                            <p class="mb-1">Click to replace Address Proof</p>
                                            <span class="file-name text-muted small">Existing file: <?php echo htmlspecialchars(basename($original_pass['address_proof_doc'])); ?></span>
                                            <img id="preview_address" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                        </div>
                                        <input type="file" name="address_proof_doc" id="address_proof_doc" class="d-none" 
                                               accept=".jpg,.jpeg,.png,.pdf" data-preview="preview_address">
                                    </div>
                                </div>

                                <div class="mt-4 d-grid gap-2">
                                    <button type="submit" name="renew_pass" class="btn btn-success btn-lg">
                                        <i class="bi bi-arrow-repeat me-1"></i> Proceed to Payment
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const previewId = this.dataset.preview;
                const previewImg = document.getElementById(previewId);
                if (this.files && this.files[0]) {
                    if (previewImg && this.files[0].type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            previewImg.src = e.target.result;
                            previewImg.style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                    // Update file name
                    const uploadArea = this.closest('.col-md-4').querySelector('.file-name');
                    uploadArea.textContent = 'New file: ' + this.files[0].name;
                }
            });
        });
    </script>
</body>
</html>
