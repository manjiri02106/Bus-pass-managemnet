<?php
/**
 * Download / View Bus Pass
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student = getCurrentStudent();
$student_id = (int)$_SESSION['student_id'];
$pass_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch approved pass
$q = "SELECT bp.*, r.route_name, r.source, r.destination, r.fare as route_fare 
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      WHERE bp.id = ? AND bp.student_id = ? AND bp.status = 'Approved'";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'ii', $pass_id, $student_id);
mysqli_stmt_execute($s);
$pass = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$pass) {
    redirect('/my_applications.php', 'No approved pass found to download.', 'warning');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Pass - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="fade-in">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class="bi bi-download text-primary me-2"></i>Bus Pass</h4>
                <button class="btn btn-primary print-pass" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print / Download
                </button>
            </div>

            <!-- Bus Pass Card -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="pass-card shadow-sm">
                        <div class="watermark"><i class="bi bi-bus-front"></i></div>
                        
                        <div class="pass-header text-center">
                            <h3 class="text-primary fw-bold"><?php echo APP_NAME; ?></h3>
                            <p class="text-muted mb-0">Student Bus Pass</p>
                        </div>

                        <div class="row mt-4">
                            <div class="col-4 text-center">
                                <img src="<?php echo BASE_URL; ?>/uploads/<?php echo $student['profile_pic'] ?: 'default.png'; ?>" 
                                     alt="Photo" class="img-thumbnail rounded" style="width:120px;height:140px;object-fit:cover;"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&size=120&background=0d6efd&color=fff'">
                            </div>
                            <div class="col-8">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="text-muted" style="width:140px;">Application No:</td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($pass['application_no']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Student Name:</td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">College:</td>
                                        <td><?php echo htmlspecialchars($student['college_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">College ID:</td>
                                        <td><?php echo htmlspecialchars($student['college_id_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Route:</td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($pass['route_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Source → Destination:</td>
                                        <td><?php echo htmlspecialchars($pass['source']); ?> → <?php echo htmlspecialchars($pass['destination']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Pass Type</small>
                                        <h6 class="fw-bold mb-0"><?php echo $pass['pass_type']; ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Valid From</small>
                                        <h6 class="fw-bold text-success mb-0"><?php echo date('d M Y', strtotime($pass['valid_from'])); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center py-2">
                                        <small class="text-muted">Valid Until</small>
                                        <h6 class="fw-bold text-danger mb-0"><?php echo date('d M Y', strtotime($pass['valid_until'])); ?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-6">
                                <small class="text-muted">Fee Paid:</small>
                                <h5 class="fw-bold text-primary">₹<?php echo number_format($pass['fee'], 2); ?></h5>
                            </div>
                            <div class="col-6 text-end">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($pass['application_no']); ?>" 
                                     alt="QR" class="img-thumbnail" style="width:80px;">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-center text-muted small">
                            <p class="mb-0">This pass is valid only for the student mentioned above. Please carry your College ID along with this pass.</p>
                            <p class="mb-0"><?php echo APP_NAME; ?> | <?php echo APP_EMAIL; ?> | <?php echo APP_PHONE; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
