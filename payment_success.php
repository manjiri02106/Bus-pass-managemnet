<?php
/**
 * Payment Success Page
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

// Get current student details
$student = getCurrentStudent();

// Check if payment success data exists in session
if (!isset($_SESSION['payment_success'])) {
    // For localhost/XAMPP demo: simulate payment success if no session data
    $_SESSION['payment_success'] = [
        'transaction_id' => 'TXN' . rand(1000000000, 9999999999),
        'amount' => rand(100, 5000),
        'payment_method' => 'UPI',
        'payment_date' => date('Y-m-d H:i:s'),
        'application_no' => 'BP-' . date('Y') . '-' . rand(100000, 999999) . '-' . rand(100, 999),
        'pass_type' => 'Monthly',
        'route_name' => 'Route 1: City Center - University',
        'source' => 'City Center',
        'destination' => 'University Main Campus',
        'valid_from' => date('Y-m-d'),
        'valid_until' => date('Y-m-d', strtotime('+1 month'))
    ];
}

// Decode session data
$payment = decode_db_data($_SESSION['payment_success']);
$student = decode_db_data(getCurrentStudent());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Success Page CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/success.css">
    
    <!-- jsPDF for Receipt Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <!-- Loading Screen (2-3 seconds) -->
    <div id="loading-screen" class="loading-screen">
        <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-primary">Processing Payment...</p>
    </div>

    <!-- Success Content -->
    <div id="success-content" class="success-content hidden">
        <!-- Confetti Container -->
        <div id="confetti-container" class="confetti-container"></div>
        
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-8 col-lg-6">
                    <!-- Success Card -->
                    <div class="card shadow-lg border-0 success-card">
                        <!-- Animated Checkmark -->
                        <div class="success-icon">
                            <div class="circle">
                                <div class="checkmark">
                                    <svg class="checkmark-svg" viewBox="0 0 52 52">
                                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Success Messages -->
                        <div class="card-body text-center pt-0">
                            <h1 class="text-success fw-bold mb-2 success-title">Payment Successful!</h1>
                            <p class="text-muted mb-4 success-subtitle">Your bus pass application has been submitted successfully.</p>
                            
                            <!-- Payment Details Card -->
                            <div class="payment-details bg-light rounded-3 p-4 mb-4 text-start">
                                <h6 class="text-muted mb-3 fw-bold"><i class="bi bi-receipt me-2"></i>Payment Details</h6>
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Transaction ID</div>
                                    <div class="col-7 fw-bold text-break"><?php echo decode_display($payment['transaction_id']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Amount Paid</div>
                                    <div class="col-7 fw-bold text-success">₹<?php echo number_format($payment['amount'], 2, '.', ','); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-5 text-muted">Payment Method</div>
                                    <div class="col-7 fw-bold"><?php echo decode_display($payment['payment_method']); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-5 text-muted">Date & Time</div>
                                    <div class="col-7 fw-bold"><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></div>
                                </div>
                            </div>

                            <!-- Redirect Countdown & Buttons -->
            <div class="mb-3">
                <p class="text-muted mb-2">Redirecting to Dashboard in <span id="countdown" class="fw-bold text-primary">12</span> seconds...</p>
            </div>
            <button id="printReceiptBtn" class="btn btn-outline-success btn-lg w-100 mb-2">
                <i class="bi bi-printer me-2"></i>Print Receipt
            </button>
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-house me-2"></i>Go to Dashboard
            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Define BASE_URL and Receipt Data for JavaScript -->
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const receiptData = <?php 
            $receipt_array = [
                'studentName' => $student['full_name'],
                'applicationNo' => $payment['application_no'],
                'transactionId' => $payment['transaction_id'],
                'amount' => number_format($payment['amount'], 2, '.', ''),
                'amountFormatted' => number_format($payment['amount'], 2, '.', ','),
                'paymentMethod' => $payment['payment_method'],
                'paymentDate' => date('d M Y, h:i A', strtotime($payment['payment_date'])),
                'passType' => $payment['pass_type'],
                'routeName' => $payment['route_name'],
                'source' => $payment['source'],
                'destination' => $payment['destination'],
                'validFrom' => date('d M Y', strtotime($payment['valid_from'])),
                'validUntil' => date('d M Y', strtotime($payment['valid_until'])),
                'merchantName' => APP_NAME,
                'merchantEmail' => APP_EMAIL,
                'merchantPhone' => APP_PHONE
            ];
            echo json_encode($receipt_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); 
        ?>;
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Success Page JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/success.js"></script>
</body>
</html>
