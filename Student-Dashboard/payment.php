<?php
/**
 * Payment Page
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];
$pass_id = (int)($_GET['pass_id'] ?? 0);

if (!$pass_id) {
    redirect('/my_applications.php', 'Invalid pass ID.', 'danger');
}

// Fetch pass details
$q = "SELECT a.*, r.source, r.destination, r.route_number 
      FROM applications a 
      JOIN routes r ON a.route_id = r.id 
      WHERE a.id = ? AND a.student_id = ?";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'ii', $pass_id, $student_id);
mysqli_stmt_execute($s);
$pass = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$pass) {
    redirect('/my_applications.php', 'Pass not found.', 'danger');
}

if ($pass['payment_status'] === 'Paid') {
    redirect('/my_applications.php', 'Payment already completed for this pass.', 'success');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - <?php echo APP_NAME; ?></title>
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
            <h4 class="mb-4"><i class="bi bi-credit-card text-primary me-2"></i>Complete Payment</h4>

            <div class="row">
                <!-- Order Summary -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted">Application Number</h6>
                                <p class="fw-bold"><?php echo htmlspecialchars($pass['application_no']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted">Route</h6>
                                <p class="fw-bold"><?php echo htmlspecialchars($pass['route_number'] ?? ($pass['source'] . ' → ' . $pass['destination'])); ?></p>
                                <small class="text-muted"><?php echo htmlspecialchars($pass['source']); ?> → <?php echo htmlspecialchars($pass['destination']); ?></small>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted">Pass Type</h6>
                                <p class="fw-bold"><?php echo htmlspecialchars($pass['pass_type']); ?></p>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted">Validity</h6>
                                <p class="fw-bold"><?php echo date('d-m-Y', strtotime($pass['valid_from'])); ?> → <?php echo date('d-m-Y', strtotime($pass['valid_until'])); ?></p>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total Amount</h5>
                                <h3 class="text-success fw-bold">₹<?php echo number_format($pass['fee'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- UPI QR Payment -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-qr-code-scan me-2"></i>UPI Payment</h5>
                                    <?php 
                                    // Build UPI URI with all required parameters as per user's example
                                    $merchant_name = "Student Bus Pass";
                                    $transaction_note = "BusPassApplication_" . $pass['application_no'];
                                    $amount_formatted = number_format($pass['fee'], 2, '.', ''); // Ensure 2 decimal places, no commas
                                    $upi_uri = "upi://pay?pa=9960024125@fam&pn=" . urlencode($merchant_name) . "&am=" . $amount_formatted . "&cu=INR&tn=" . urlencode($transaction_note);
                                    ?>
                                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=1024x1024&data=<?php echo urlencode($upi_uri); ?>" 
                                       class="btn btn-sm btn-outline-primary" download="BusPass_QR_<?php echo $pass['application_no']; ?>.png">
                                        <i class="bi bi-download me-1"></i>Download High-Res QR
                                    </a>
                                </div>
                                <div class="card-body text-center">
                                    <p class="text-muted mb-3">Scan the QR code below with any UPI app (Google Pay, PhonePe, Paytm, BHIM, WhatsApp Pay) – amount will auto-fill!</p>
                                    
                                    <!-- Original default size (200x200) UPI QR Code -->
                                    <div class="mb-4">
                                        <img id="upi-qr" 
                                             src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($upi_uri); ?>" 
                                             alt="UPI QR Code for ₹<?php echo number_format($pass['fee'], 2); ?>" 
                                             class="img-thumbnail"
                                             style="max-width: 100%; height: auto;"
                                             onerror="handleQrError()">
                                    </div>
                                    
                                    <!-- Locked Total Amount & UPI Details -->
                                    <div class="mb-4 p-3 bg-light rounded">
                                        <p class="mb-2"><strong>UPI ID:</strong> <span class="text-primary">9960024125@fam</span></p>
                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Total Amount (Locked & Auto-Filled)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="text" class="form-control text-center fs-4 fw-bold" 
                                                       value="<?php echo number_format($pass['fee'], 2); ?>" 
                                                       readonly style="background-color: #e9ecef;">
                                            </div>
                                        </div>
                                        <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i>Amount cannot be modified; all major UPI apps will auto-fill it</p>
                                        <p class="mb-0 mt-2"><strong>Reference:</strong> <?php echo htmlspecialchars($transaction_note); ?></p>
                                    </div>
                                    
                                    <!-- Fallback for apps that don't support pre-filled amount -->
                                    <div class="alert alert-warning d-none" id="qr-fallback">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Note:</strong> If your UPI app doesn't pre-fill the amount, please manually enter <strong>₹<?php echo number_format($pass['fee'], 2); ?></strong>
                                    </div>
                                    
                                    <!-- Payment Form -->
                                    <form method="POST" action="process_payment.php" id="payment-form">
                                        <input type="hidden" name="pass_id" value="<?php echo $pass_id; ?>">
                                        <input type="hidden" name="payment_method" value="UPI">
                                        
                                        <div class="mb-3">
                                            <label class="form-label"><i class="bi bi-receipt me-1"></i>Enter Transaction ID (after payment)</label>
                                            <input type="text" name="transaction_id" id="transactionId" class="form-control" required placeholder="Enter 12+ digit transaction ID" autocomplete="off" minlength="12">
                                            <div class="invalid-feedback" id="transactionIdError">Transaction ID must be at least 12 characters long.</div>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" id="submitBtn" class="btn btn-success btn-lg">
                                                <i class="bi bi-check-circle me-2"></i>I've Paid, Verify
                                            </button>
                                            <a href="my_applications.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-left me-2"></i>Pay Later
                                            </a>
                                        </div>
                                    </form>
                                    
                                    <script>
                                        const form = document.getElementById('payment-form');
                                        const transactionIdInput = document.getElementById('transactionId');
                                        const transactionIdError = document.getElementById('transactionIdError');
                                        const submitBtn = document.getElementById('submitBtn');
                                        
                                        form.addEventListener('submit', function(e) {
                                            if (transactionIdInput.value.trim().length < 12) {
                                                e.preventDefault();
                                                transactionIdInput.classList.add('is-invalid');
                                                transactionIdError.style.display = 'block';
                                            } else {
                                                transactionIdInput.classList.remove('is-invalid');
                                                transactionIdError.style.display = 'none';
                                            }
                                        });
                                        
                                        transactionIdInput.addEventListener('input', function() {
                                            if (transactionIdInput.value.trim().length >= 12) {
                                                transactionIdInput.classList.remove('is-invalid');
                                                transactionIdError.style.display = 'none';
                                            }
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        function handleQrError() {
            document.getElementById('qr-fallback').classList.remove('d-none');
            document.getElementById('upi-qr').style.display = 'none';
        }
    </script>
</body>
</html>
