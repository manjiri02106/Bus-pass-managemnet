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
$q = "SELECT bp.*, r.route_name, r.source, r.destination 
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      WHERE bp.id = ? AND bp.student_id = ?";
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
                                <p class="fw-bold"><?php echo htmlspecialchars($pass['route_name']); ?></p>
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

                <!-- Payment Methods -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="process_payment.php">
                                <input type="hidden" name="pass_id" value="<?php echo $pass_id; ?>">
                                
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="Credit Card" checked>
                                        <label class="form-check-label" for="credit_card">
                                            <i class="bi bi-credit-card-2-front me-2"></i> Credit / Debit Card
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="upi" value="UPI">
                                        <label class="form-check-label" for="upi">
                                            <i class="bi bi-phone me-2"></i> UPI
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="netbanking" value="Net Banking">
                                        <label class="form-check-label" for="netbanking">
                                            <i class="bi bi-bank me-2"></i> Net Banking
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="wallet" value="Wallet">
                                        <label class="form-check-label" for="wallet">
                                            <i class="bi bi-wallet me-2"></i> Digital Wallet
                                        </label>
                                    </div>
                                </div>

                                <!-- Simulated Card Details (for demo) -->
                                <div id="card_details" class="mb-4">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Card Number</label>
                                            <input type="text" class="form-control" placeholder="1234 5678 9010 1112" maxlength="19">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CVV</label>
                                            <input type="password" class="form-control" placeholder="123" maxlength="3">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-lock me-2"></i> Pay ₹<?php echo number_format($pass['fee'], 2); ?>
                                    </button>
                                    <a href="my_applications.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i> Pay Later
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
</body>
</html>
