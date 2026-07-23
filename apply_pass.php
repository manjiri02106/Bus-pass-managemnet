<?php
/**
 * Apply Bus Pass - Multi-step Application Form
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student = getCurrentStudent();
$student_id = (int)$_SESSION['student_id'];

// Fetch active routes
$routes_query = "SELECT * FROM routes WHERE status = 'Active' ORDER BY route_name";
$routes_result = mysqli_query($conn, $routes_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $route_id = (int)$_POST['route_id'];
    $pass_type = sanitize($_POST['pass_type']);
    
    // Validation
    if (!isset($_POST['route_id']) || empty($pass_type)) {
        $error = 'Please select a route and pass type.';
    } else {
        $route_id = $_POST['route_id'];
        
        // Handle custom route insertion
        if ($route_id === '0' || $route_id === 0) {
            $custom_source = sanitize($_POST['custom_source'] ?? 'Unknown');
            $custom_dest = sanitize($_POST['custom_dest'] ?? 'Unknown');
            $custom_fare = (float)($_POST['custom_fare'] ?? 0);
            
            $insert_q = "INSERT INTO routes (route_name, source, destination, fare, distance_km, status) VALUES (?, ?, ?, ?, 0, 'Active')";
            $insert_s = mysqli_prepare($conn, $insert_q);
            $custom_name = "Custom: $custom_source to $custom_dest";
            mysqli_stmt_bind_param($insert_s, 'sssd', $custom_name, $custom_source, $custom_dest, $custom_fare);
            
            if (mysqli_stmt_execute($insert_s)) {
                $route_id = mysqli_insert_id($conn);
            } else {
                $error = 'Failed to create custom route.';
            }
        }
        
        if (empty($error)) {
            // Calculate fee and validity
            $route_q = "SELECT * FROM routes WHERE id = ?";
            $route_s = mysqli_prepare($conn, $route_q);
            mysqli_stmt_bind_param($route_s, 'i', $route_id);
            mysqli_stmt_execute($route_s);
            $route = mysqli_fetch_assoc(mysqli_stmt_get_result($route_s));
            
            if (!$route) {
                $error = 'Invalid route selected.';
            } else {
                // If it's a custom route, we already know the fare. If it's DB route, we use DB fare.
                // Wait, if it's custom, the fare was inserted into DB.
                $fee = calculatePassFee($route['fare'], $pass_type);
                $validity = calculateValidity($pass_type);
                $app_no = generateApplicationNo($student_id);
            
            // Handle file uploads
            $upload_dir = UPLOAD_PATH;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $college_id_doc = '';
            $photo_doc = '';
            $address_proof_doc = '';
            $upload_ok = true;
            
            // Upload College ID
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
            } else {
                $error = 'College ID document is required.';
                $upload_ok = false;
            }
            
            // Upload Photo
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
            } else {
                if ($upload_ok) {
                    $error = 'Photo is required.';
                    $upload_ok = false;
                }
            }
            
            // Upload Address Proof
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
            } else {
                if ($upload_ok) {
                    $error = 'Address proof is required.';
                    $upload_ok = false;
                }
            }
            
            if ($upload_ok) {
                $query = "INSERT INTO bus_passes (application_no, student_id, route_id, pass_type, college_id_doc, photo_doc, address_proof_doc, fee, valid_from, valid_until) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'siissssdss', $app_no, $student_id, $route_id, $pass_type, $college_id_doc, $photo_doc, $address_proof_doc, $fee, $validity['from'], $validity['until']);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Create notification
                    $notif_q = "INSERT INTO notifications (student_id, title, message, type) VALUES (?, 'Application Submitted', 'Your bus pass application (Ref: $app_no) has been submitted successfully. Please wait for admin approval.', 'info')";
                    $notif_s = mysqli_prepare($conn, $notif_q);
                    mysqli_stmt_bind_param($notif_s, 'i', $student_id);
                    mysqli_stmt_execute($notif_s);
                    
                    redirect('/my_applications.php', 'Application submitted successfully! Your reference number is: ' . $app_no, 'success');
                } else {
                    $error = 'Failed to submit application. Please try again.';
                }
            }
            }
        } // End of if (empty($error))
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Bus Pass - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="fade-in">
            <h4 class="mb-4"><i class="bi bi-file-earmark-plus text-primary me-2"></i>Apply for Bus Pass</h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Step Wizard -->
            <div class="step-wizard mb-4">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Select Route</div>
                </div>
                <div class="step-connector"></div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Upload Documents</div>
                </div>
                <div class="step-connector"></div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Review & Submit</div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data" id="applyPassForm" class="needs-validation" novalidate>
                        
                        <!-- Step 1: Route & Pass Type -->
                        <div class="step-content" id="step1">
                            <h5 class="fw-bold mb-3"><i class="bi bi-route me-2"></i>Select Route & Pass Type</h5>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Source Stop <span class="text-danger">*</span></label>
                                    <select id="source_stop" class="form-select select2-stop" required>
                                        <option value="">-- Loading Stops... --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination Stop <span class="text-danger">*</span></label>
                                    <select id="dest_stop" class="form-select select2-stop" required>
                                        <option value="">-- Loading Stops... --</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Matching Bus Route <span class="text-danger">*</span></label>
                                    <select name="route_id" id="route_id" class="form-select" required>
                                        <option value="">-- Please select Source and Destination --</option>
                                    </select>
                                    <div id="route_details" class="mt-2 small text-muted"></div>
                                    <div id="distance_details" class="mt-1 small text-primary fw-bold"></div>
                                    <div id="timing_details" class="mt-2 p-2 bg-light rounded border" style="display:none;"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pass Type <span class="text-danger">*</span></label>
                                    <select name="pass_type" id="pass_type" class="form-select" required>
                                        <option value="">-- Select --</option>
                                        <option value="Daily">Daily (1 day)</option>
                                        <option value="Monthly">Monthly (30 days)</option>
                                        <option value="Quarterly">Quarterly (90 days)</option>
                                        <option value="Half Yearly">Half Yearly (180 days)</option>
                                        <option value="Yearly">Yearly (365 days)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Pass Price Summary Card (Step 1) -->
                            <div id="pass_summary_card" class="mt-4" style="display:none;">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                                        <span><i class="bi bi-receipt me-2"></i><strong>Pass Price Summary</strong></span>
                                        <span id="summary_pass_type_badge" class="badge bg-light text-primary"></span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2 mb-3" id="summary_meta">
                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">Route</div>
                                                <div id="summary_route" class="fw-bold small">—</div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">Distance</div>
                                                <div id="summary_distance" class="fw-bold small">—</div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">Daily Fare (CSV)</div>
                                                <div id="summary_daily_fare" class="fw-bold small">—</div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">Valid Until</div>
                                                <div id="summary_valid_until" class="fw-bold small">—</div>
                                            </div>
                                        </div>

                                        <!-- Price breakdown table -->
                                        <div id="fee_display"></div>

                                        <!-- Selected price highlight -->
                                        <div id="selected_price_highlight" class="mt-3 p-3 rounded" style="background:#e8f5e9;display:none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="text-muted small">Total Amount to Pay</div>
                                                    <div id="selected_price_text" class="fs-4 fw-bold text-success">—</div>
                                                </div>
                                                <div class="text-end">
                                                    <div id="selected_validity_text" class="small text-muted"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="validity_display" class="mt-2"></div>
                            <input type="hidden" name="fee" id="fee_input" value="">
                            
                            <!-- Hidden inputs for custom route -->
                            <input type="hidden" name="custom_source" id="custom_source" value="">
                            <input type="hidden" name="custom_dest" id="custom_dest" value="">
                            <input type="hidden" name="custom_fare" id="custom_fare" value="">

                            <div class="mt-4">
                                <button type="button" class="btn btn-primary" onclick="goToStep(2)">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Document Upload -->
                        <div class="step-content" id="step2" style="display:none;">
                            <h5 class="fw-bold mb-3"><i class="bi bi-upload me-2"></i>Upload Documents</h5>
                            <p class="text-muted small">Upload clear, readable copies of the following documents.</p>
                            
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">College ID Card <span class="text-danger">*</span></label>
                                    <div class="upload-area" onclick="document.getElementById('college_id_doc').click()">
                                        <div class="upload-icon"><i class="bi bi-card-text"></i></div>
                                        <p class="mb-1">Click to upload College ID</p>
                                        <span class="file-name text-muted small">JPG, PNG or PDF</span>
                                        <img id="preview_college_id" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                    </div>
                                    <input type="file" name="college_id_doc" id="college_id_doc" class="d-none" 
                                           accept=".jpg,.jpeg,.png,.pdf" data-preview="preview_college_id" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Passport Photo <span class="text-danger">*</span></label>
                                    <div class="upload-area" onclick="document.getElementById('photo_doc').click()">
                                        <div class="upload-icon"><i class="bi bi-person-badge"></i></div>
                                        <p class="mb-1">Click to upload Photo</p>
                                        <span class="file-name text-muted small">JPG or PNG</span>
                                        <img id="preview_photo" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                    </div>
                                    <input type="file" name="photo_doc" id="photo_doc" class="d-none" 
                                           accept=".jpg,.jpeg,.png" data-preview="preview_photo" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Address Proof <span class="text-danger">*</span></label>
                                    <div class="upload-area" onclick="document.getElementById('address_proof_doc').click()">
                                        <div class="upload-icon"><i class="bi bi-house-door"></i></div>
                                        <p class="mb-1">Click to upload Address Proof</p>
                                        <span class="file-name text-muted small">JPG, PNG or PDF</span>
                                        <img id="preview_address" class="img-fluid mt-2 rounded" style="max-height:120px;display:none;">
                                    </div>
                                    <input type="file" name="address_proof_doc" id="address_proof_doc" class="d-none" 
                                           accept=".jpg,.jpeg,.png,.pdf" data-preview="preview_address" required>
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)">
                                    <i class="bi bi-arrow-left me-1"></i> Previous
                                </button>
                                <button type="button" class="btn btn-primary" onclick="goToStep(3)">
                                    Next <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Review & Submit -->
                        <div class="step-content" id="step3" style="display:none;">
                            <h5 class="fw-bold mb-3"><i class="bi bi-check-circle me-2"></i>Review & Submit</h5>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Application Summary</h6>
                                            <table class="table table-sm mb-0">
                                                <tr><td class="text-muted">Source Stop:</td><td class="fw-bold" id="review_source">-</td></tr>
                                                <tr><td class="text-muted">Destination Stop:</td><td class="fw-bold" id="review_dest">-</td></tr>
                                                <tr><td class="text-muted">Route:</td><td class="fw-bold" id="review_route">-</td></tr>
                                                <tr><td class="text-muted">Pass Type:</td><td class="fw-bold text-success" id="review_pass_type">-</td></tr>
                                                <tr><td class="text-muted">Total Fee:</td><td class="fw-bold text-primary fs-5" id="review_fee">-</td></tr>
                                                <tr><td class="text-muted">Valid From:</td><td id="review_valid_from">-</td></tr>
                                                <tr><td class="text-muted">Valid Until:</td><td id="review_valid_until">-</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="fw-bold">Student Details</h6>
                                            <table class="table table-sm mb-0">
                                                <tr><td class="text-muted">Name:</td><td class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></td></tr>
                                                <tr><td class="text-muted">College:</td><td><?php echo htmlspecialchars($student['college_name']); ?></td></tr>
                                                <tr><td class="text-muted">College ID:</td><td><?php echo htmlspecialchars($student['college_id_number']); ?></td></tr>
                                                <tr><td class="text-muted">Course:</td><td><?php echo htmlspecialchars($student['course'] ?? '-'); ?></td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)">
                                    <i class="bi bi-arrow-left me-1"></i> Previous
                                </button>
                                <button type="submit" name="submit_application" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-lg me-1"></i> Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
        // Step navigation
        let currentStep = 1;
        const totalSteps = 3;

        function goToStep(step) {
            // Validate current step before moving forward
            if (step > currentStep) {
                if (currentStep === 1) {
                    const route = document.getElementById('route_id').value;
                    const passType = document.getElementById('pass_type').value;
                    if (!route || !passType) {
                        Swal.fire('Validation Error', 'Please select a route and pass type.', 'warning');
                        return;
                    }
                }
                if (currentStep === 2) {
                    const docs = ['college_id_doc', 'photo_doc', 'address_proof_doc'];
                    let allUploaded = true;
                    docs.forEach(id => {
                        const input = document.getElementById(id);
                        if (!input.files || !input.files[0]) allUploaded = false;
                    });
                    if (!allUploaded) {
                        Swal.fire('Validation Error', 'Please upload all required documents.', 'warning');
                        return;
                    }
                    // Update review summary
                    updateReviewSummary();
                }
            }

            // Update step indicators
            document.querySelectorAll('.step').forEach((el, index) => {
                const stepNum = index + 1;
                el.classList.remove('active');
                if (stepNum < step) {
                    el.classList.add('completed');
                }
            });
            document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
            
            // Update connectors
            document.querySelectorAll('.step-connector').forEach((el, index) => {
                el.classList.toggle('completed', index + 1 < step);
            });

            // Show/hide step content
            document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
            document.getElementById('step' + step).style.display = 'block';
            
            currentStep = step;
        }

        function updateReviewSummary() {
            const routeSelect = document.getElementById('route_id');
            const selectedOption = routeSelect ? routeSelect.options[routeSelect.selectedIndex] : null;
            const passType = document.getElementById('pass_type').value;
            const feeVal = document.getElementById('fee_input').value;
            
            // Source & Destination
            const sourceOpt = $('#source_stop option:selected');
            const destOpt = $('#dest_stop option:selected');
            
            document.getElementById('review_source').textContent = sourceOpt.val() ? sourceOpt.text() : '-';
            document.getElementById('review_dest').textContent = destOpt.val() ? destOpt.text() : '-';
            document.getElementById('review_route').textContent = selectedOption && selectedOption.value ? selectedOption.text.trim() : '-';
            document.getElementById('review_pass_type').textContent = passType ? passType + ' Pass' : '-';
            document.getElementById('review_fee').textContent = feeVal ? '₹' + parseFloat(feeVal).toLocaleString('en-IN') : '-';

            // Calculate validity dates
            const today = new Date();
            let validUntil = new Date();
            switch (passType) {
                case 'Daily':       validUntil.setDate(today.getDate() + 1); break;
                case 'Monthly':     validUntil.setMonth(today.getMonth() + 1); break;
                case 'Quarterly':   validUntil.setMonth(today.getMonth() + 3); break;
                case 'Half Yearly': validUntil.setMonth(today.getMonth() + 6); break;
                case 'Yearly':      validUntil.setFullYear(today.getFullYear() + 1); break;
            }
            const fmt = d => d.toISOString().split('T')[0];
            document.getElementById('review_valid_from').textContent = fmt(today);
            document.getElementById('review_valid_until').textContent = fmt(validUntil);
        }

        // Route details and GTFS Live Bus Timing display
        document.getElementById('route_id').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const details = document.getElementById('route_details');
            if (opt.value) {
                const via = opt.dataset.via ? `Via: ${opt.dataset.via}` : '';
                details.innerHTML = `<i class="bi bi-geo-alt"></i> ${opt.dataset.source} → ${opt.dataset.dest}<br>${via}`;
                
                // Set hidden fields for custom route
                document.getElementById('custom_source').value = opt.dataset.source || '';
                document.getElementById('custom_dest').value = opt.dataset.dest || '';
                document.getElementById('custom_fare').value = opt.dataset.fare || '';
                
                // Fetch live PMPML GTFS schedule timing for the selected route
                const routeNumber = opt.dataset.routeNumber;
                const distanceKm  = opt.dataset.distance || 10;
                
                if (routeNumber) {
                    $('#timing_details').html('<div class="text-muted small py-1"><i class="bi bi-arrow-repeat me-1"></i> Fetching live PMPML GTFS bus schedule...</div>').slideDown();
                    
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>/ajax/get_live_bus.php',
                        method: 'POST',
                        data: {
                            route_number: routeNumber,
                            distance_km: distanceKm
                        },
                        dataType: 'json',
                        success: function(liveRes) {
                            if (liveRes.success) {
                                const nextLabel = liveRes.is_tomorrow ? `Tomorrow ${liveRes.departure}` : `in ${liveRes.next_bus_in} mins`;
                                $('#timing_details').html(`
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div><i class="bi bi-bus-front-fill text-primary me-1"></i> <strong>Next Bus [${liveRes.route_api_name}]:</strong> ${nextLabel}</div>
                                        <div><i class="bi bi-geo-alt-fill text-success me-1"></i> <strong>Departs:</strong> ${liveRes.departure}</div>
                                        <div><i class="bi bi-flag-fill text-danger me-1"></i> <strong>Arrives:</strong> ${liveRes.arrival} (${liveRes.travel_time_mins} mins)</div>
                                    </div>
                                `).slideDown();
                            } else {
                                $('#timing_details').slideUp();
                            }
                        },
                        error: function() {
                            $('#timing_details').slideUp();
                        }
                    });
                }
                
                // If fare is provided via data-fare, update fee display
                if (opt.dataset.fare && document.getElementById('pass_type').value) {
                    calculateDynamicFee();
                }
            } else {
                details.innerHTML = '';
                $('#timing_details').slideUp();
            }
        });

        // ─── PASS FEE CALCULATION ────────────────────────────────────────────────
        // Discount schedule (matches database.php):
        //   Daily        → ×1,    0% off
        //   Monthly      → ×30,   5% off
        //   Quarterly    → ×90,  10% off
        //   Half Yearly  → ×180, 15% off
        //   Yearly       → ×365, 20% off
        // ─────────────────────────────────────────────────────────────────────────
        const PASS_TYPES = [
            { key: 'Daily',       label: 'Daily',       days: 1,   discount: 0  },
            { key: 'Monthly',     label: 'Monthly',     days: 30,  discount: 5  },
            { key: 'Quarterly',   label: 'Quarterly',   days: 90,  discount: 10 },
            { key: 'Half Yearly', label: 'Half Yearly', days: 180, discount: 15 },
            { key: 'Yearly',      label: 'Yearly',      days: 365, discount: 20 },
        ];

        function computeFee(dailyFare, passType) {
            const t = PASS_TYPES.find(p => p.key === passType);
            if (!t) return 0;
            const gross = dailyFare * t.days;
            return Math.round(gross * (1 - t.discount / 100));
        }

        function calculateDynamicFee() {
            const passType  = document.getElementById('pass_type').value;
            const routeSel  = document.getElementById('route_id');
            const routeOpt  = routeSel.options[routeSel.selectedIndex];

            // Need at least a route selected
            if (!routeOpt || !routeOpt.value) return;

            const dailyFare = parseFloat(routeOpt.dataset.fare || 0);
            if (dailyFare <= 0) return;

            // Show the summary card
            document.getElementById('pass_summary_card').style.display = '';

            // Update meta row
            document.getElementById('summary_daily_fare').textContent = '₹' + dailyFare;
            document.getElementById('summary_route').textContent = routeOpt.textContent.trim().substring(0, 30) + (routeOpt.textContent.trim().length > 30 ? '…' : '');

            // Validity dates
            const today = new Date();
            let validUntil = new Date();
            switch (passType) {
                case 'Daily':       validUntil.setDate(today.getDate() + 1); break;
                case 'Monthly':     validUntil.setMonth(today.getMonth() + 1); break;
                case 'Quarterly':   validUntil.setMonth(today.getMonth() + 3); break;
                case 'Half Yearly': validUntil.setMonth(today.getMonth() + 6); break;
                case 'Yearly':      validUntil.setFullYear(today.getFullYear() + 1); break;
            }
            const fmt = d => d.toISOString().split('T')[0];
            if (passType) {
                document.getElementById('summary_valid_until').textContent = fmt(validUntil);
                document.getElementById('summary_pass_type_badge').textContent = passType + ' Pass';
            }

            // Build breakdown table
            let tableRows = '';
            PASS_TYPES.forEach(t => {
                const gross      = dailyFare * t.days;
                const saving     = Math.round(gross * t.discount / 100);
                const finalFee   = gross - saving;
                const isSelected = (t.key === passType);

                const discountBadge = t.discount > 0
                    ? `<span class="badge bg-warning text-dark ms-1">${t.discount}% off</span>`
                    : '';
                const savingHtml = saving > 0
                    ? `<small class="text-success d-block">save ₹${saving.toLocaleString('en-IN')}</small>`
                    : '';

                tableRows += `
                    <tr class="${isSelected ? 'table-success fw-bold' : ''}"
                        style="cursor:pointer"
                        onclick="document.getElementById('pass_type').value='${t.key}';calculateDynamicFee()">
                        <td>
                            ${t.label} <span class="text-muted small">(${t.days}d)</span>
                            ${discountBadge}
                            ${isSelected ? '<span class="badge bg-success ms-1">✓ Selected</span>' : ''}
                        </td>
                        <td class="text-center text-muted small">
                            ₹${dailyFare} × ${t.days}${t.discount ? ` − ${t.discount}%` : ''}
                        </td>
                        <td class="text-end fw-bold">
                            ₹${finalFee.toLocaleString('en-IN')}
                            ${savingHtml}
                        </td>
                    </tr>`;
            });

            document.getElementById('fee_display').innerHTML = `
                <p class="small text-muted mb-1">
                    <i class="bi bi-info-circle me-1"></i>
                    Click any row to select that pass type.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pass Type</th>
                                <th class="text-center">Calculation</th>
                                <th class="text-end">You Pay</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                </div>
            `;

            // Show the highlighted price for the selected pass type
            if (passType) {
                const fee = computeFee(dailyFare, passType);
                document.getElementById('fee_input').value = fee;
                document.getElementById('selected_price_text').textContent = '₹' + fee.toLocaleString('en-IN');
                document.getElementById('selected_validity_text').innerHTML =
                    `Valid: ${fmt(today)} → ${fmt(validUntil)}`;
                document.getElementById('selected_price_highlight').style.display = '';
            } else {
                document.getElementById('selected_price_highlight').style.display = 'none';
            }

            // Keep hidden validity_display in sync (used by form submission)
            document.getElementById('validity_display').innerHTML = '';
        }

        document.getElementById('pass_type').addEventListener('change', calculateDynamicFee);
        // Also recalculate when route changes
        document.getElementById('route_id').addEventListener('change', calculateDynamicFee);


        $(document).ready(function() {
            // Initialize Select2
            $('.select2-stop').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for a stop...',
                width: '100%'
            });
            
            // Load Stops
            $.getJSON('<?php echo BASE_URL; ?>/data/stops.json', function(data) {
                let options = '<option value="">-- Select Stop --</option>';
                data.forEach((stop, index) => {
                    options += `<option value="${index}" data-lat="${stop.lat}" data-lon="${stop.lon}">${stop.name}</option>`;
                });
                $('#source_stop').html(options);
                $('#dest_stop').html(options);
            });
            
            // Handle Stop Change
            $('#source_stop, #dest_stop').on('change', function() {
                const sourceIdx = $('#source_stop').val();
                const destIdx = $('#dest_stop').val();
                
                if (sourceIdx && destIdx) {
                    const sourceOpt = $('#source_stop option:selected');
                    const destOpt = $('#dest_stop option:selected');
                    
                    $('#route_id').html('<option value="">-- Calculating... --</option>');
                    $('#distance_details').html('Calculating distance...');
                    $('#timing_details').slideUp();
                    
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>/ajax/calculate_fare.php',
                        method: 'POST',
                        data: {
                            source_name: sourceOpt.text(),
                            source_lat: sourceOpt.data('lat'),
                            source_lon: sourceOpt.data('lon'),
                            dest_name: destOpt.text(),
                            dest_lat: destOpt.data('lat'),
                            dest_lon: destOpt.data('lon')
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#distance_details').html(`<i class="bi bi-rulers"></i> Estimated Travel Distance: ${response.distance} km`);
                                $('#timing_details').slideUp();
                                
                                let routeOptions = '<option value="">-- Select Route --</option>';
                                
                                if (response.routes.length > 0) {
                                    response.routes.forEach(route => {
                                        routeOptions += `<option value="${route.id}" 
                                            data-fare="${response.fare}" 
                                            data-route-number="${route.route_number}"
                                            data-distance="${response.distance}"
                                            data-source="${route.source}" 
                                            data-dest="${route.destination}"
                                            data-via="">
                                            [${route.route_number}] ${route.route_desc}
                                        </option>`;
                                    });
                                } else {
                                    routeOptions = '<option value="">-- No direct routes found, proceed with custom pass --</option>';
                                    routeOptions += `<option value="0" data-fare="${response.fare}" data-route-number="CUSTOM" data-distance="${response.distance}" data-source="${sourceOpt.text()}" data-dest="${destOpt.text()}" data-via="">Custom Route (${sourceOpt.text()} → ${destOpt.text()})</option>`;
                                }
                                $('#route_id').html(routeOptions);
                            } else {
                                Swal.fire('Error', response.error || 'Failed to calculate fare.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Server error occurred.', 'error');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
