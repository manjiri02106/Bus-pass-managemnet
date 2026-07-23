<?php
/**
 * Payment Status Page
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];

// Fetch all payments with pass details
$q = "SELECT p.*, bp.application_no, bp.pass_type, bp.fee as pass_fee, bp.status as pass_status,
             r.route_name
      FROM payments p 
      JOIN bus_passes bp ON p.pass_id = bp.id 
      JOIN routes r ON bp.route_id = r.id
      WHERE p.student_id = ? 
      ORDER BY p.created_at DESC";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$payments = mysqli_stmt_get_result($s);

// Also get pending payments from bus_passes
$q2 = "SELECT bp.*, r.route_name 
       FROM bus_passes bp 
       JOIN routes r ON bp.route_id = r.id 
       WHERE bp.student_id = ? AND bp.payment_status = 'Pending' AND bp.status = 'Approved'
       ORDER BY bp.applied_at DESC";
$s2 = mysqli_prepare($conn, $q2);
mysqli_stmt_bind_param($s2, 'i', $student_id);
mysqli_stmt_execute($s2);
$pending_payments = mysqli_stmt_get_result($s2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="fade-in">
            <h4 class="mb-4"><i class="bi bi-credit-card text-primary me-2"></i>Payment Status</h4>

            <!-- Pending Payments Alert -->
            <?php if (mysqli_num_rows($pending_payments) > 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                You have <strong><?php echo mysqli_num_rows($pending_payments); ?></strong> approved pass(es) with pending payment. Please complete the payment to download your pass.
            </div>
            <?php endif; ?>

            <!-- Payment History -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>App No.</th>
                                    <th>Route</th>
                                    <th>Pass Type</th>
                                    <th>Amount (₹)</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($payments) > 0): ?>
                                    <?php $i = 1; while ($p = mysqli_fetch_assoc($payments)): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($p['application_no']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['route_name']); ?></td>
                                        <td><?php echo $p['pass_type']; ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($p['amount'], 2); ?></td>
                                        <td><?php echo $p['payment_method'] ?: '-'; ?></td>
                                        <td><small><?php echo $p['transaction_id'] ?: '-'; ?></small></td>
                                        <td>
                                            <?php
                                            $status_class = match($p['payment_status']) {
                                                'Success' => 'payment-paid',
                                                'Pending' => 'payment-pending',
                                                'Failed' => 'payment-failed',
                                                default => ''
                                            };
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo $p['payment_status']; ?></span>
                                        </td>
                                        <td><small><?php echo $p['payment_date'] ? date('d-m-Y h:i A', strtotime($p['payment_date'])) : '-'; ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="bi bi-wallet2 display-6 text-muted"></i>
                                            <p class="mt-2 text-muted">No payment records found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
