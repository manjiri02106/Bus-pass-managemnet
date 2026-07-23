<?php
/**
 * My Applications - View all bus pass applications
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];

// Handle cancellation
if (isset($_GET['cancel']) && (int)$_GET['cancel'] > 0) {
    $pass_id = (int)$_GET['cancel'];
    $q = "UPDATE bus_passes SET status = 'Cancelled' WHERE id = ? AND student_id = ? AND status = 'Pending'";
    $s = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($s, 'ii', $pass_id, $student_id);
    if (mysqli_stmt_execute($s) && mysqli_affected_rows($conn) > 0) {
        redirect('/my_applications.php', 'Application cancelled successfully.', 'info');
    } else {
        redirect('/my_applications.php', 'Unable to cancel application. Only pending applications can be cancelled.', 'warning');
    }
}

// Fetch all applications with route details
$q = "SELECT bp.*, r.route_name, r.source, r.destination 
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      WHERE bp.student_id = ? 
      ORDER BY bp.applied_at DESC";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$applications = mysqli_stmt_get_result($s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <h4 class="mb-0"><i class="bi bi-files text-primary me-2"></i>My Applications</h4>
            <a href="<?php echo BASE_URL; ?>/apply_pass.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Application
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable" id="applicationsTable">
                        <thead>
                            <tr>
                                <th>App No.</th>
                                <th>Route</th>
                                <th>Pass Type</th>
                                <th>Fee (₹)</th>
                                <th>Valid From</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Applied On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($applications) > 0): ?>
                                <?php while ($app = mysqli_fetch_assoc($applications)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($app['application_no']); ?></strong></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($app['route_name']); ?></small>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($app['source']); ?> → <?php echo htmlspecialchars($app['destination']); ?></small>
                                    </td>
                                    <td><?php echo $app['pass_type']; ?></td>
                                    <td>₹<?php echo number_format($app['fee'], 2); ?></td>
                                    <td><?php echo $app['valid_from'] ? date('d-m-Y', strtotime($app['valid_from'])) : '-'; ?></td>
                                    <td><?php echo $app['valid_until'] ? date('d-m-Y', strtotime($app['valid_until'])) : '-'; ?></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'Pending' => 'badge-pending',
                                            'Approved' => 'badge-approved',
                                            'Rejected' => 'badge-rejected',
                                            'Cancelled' => 'badge-cancelled',
                                            'Expired' => 'badge-expired'
                                        ];
                                        $badge_class = $status_badges[$app['status']] ?? 'badge-pending';
                                        ?>
                                        <span class="badge badge-status <?php echo $badge_class; ?>">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_class = match($app['payment_status']) {
                                            'Paid' => 'payment-paid',
                                            'Pending' => 'payment-pending',
                                            'Failed' => 'payment-failed',
                                            'Refunded' => 'text-info',
                                            default => ''
                                        };
                                        ?>
                                        <span class="<?php echo $payment_class; ?>"><?php echo $app['payment_status']; ?></span>
                                    </td>
                                    <td><small><?php echo date('d-m-Y', strtotime($app['applied_at'])); ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="showDetails(<?php echo htmlspecialchars(json_encode($app)); ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($app['payment_status'] === 'Pending'): ?>
                                            <a href="<?php echo BASE_URL; ?>/payment.php?pass_id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-success">
                                                <i class="bi bi-credit-card"></i> Pay Now
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($app['status'] === 'Pending'): ?>
                                            <a href="?cancel=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-danger cancel-application">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($app['status'] === 'Approved' && $app['payment_status'] === 'Paid' && strtotime($app['valid_until']) >= time()): ?>
                                            <a href="<?php echo BASE_URL; ?>/download_pass.php?id=<?php echo $app['id']; ?>" 
                                               class="btn btn-outline-success">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="bi bi-inbox display-6 text-muted"></i>
                                        <p class="mt-2 text-muted">No applications found.</p>
                                        <a href="<?php echo BASE_URL; ?>/apply_pass.php" class="btn btn-primary btn-sm">Apply Now</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-text me-2"></i>Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsBody">
                    <!-- Filled by JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        function showDetails(app) {
            const badgeClass = {
                'Pending': 'badge-pending',
                'Approved': 'badge-approved',
                'Rejected': 'badge-rejected',
                'Cancelled': 'badge-cancelled',
                'Expired': 'badge-expired'
            }[app.status] || 'badge-pending';

            const paymentClass = {
                'Paid': 'payment-paid',
                'Pending': 'payment-pending',
                'Failed': 'payment-failed',
                'Refunded': 'text-info'
            }[app.payment_status] || '';

            document.getElementById('detailsBody').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><td class="text-muted">Application No:</td><td class="fw-bold">${app.application_no}</td></tr>
                            <tr><td class="text-muted">Route:</td><td>${app.route_name}</td></tr>
                            <tr><td class="text-muted">Source → Destination:</td><td>${app.source} → ${app.destination}</td></tr>
                            <tr><td class="text-muted">Pass Type:</td><td>${app.pass_type}</td></tr>
                            <tr><td class="text-muted">Fee:</td><td class="fw-bold text-primary">₹${parseFloat(app.fee).toFixed(2)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><td class="text-muted">Status:</td><td><span class="badge badge-status ${badgeClass}">${app.status}</span></td></tr>
                            <tr><td class="text-muted">Payment:</td><td><span class="${paymentClass}">${app.payment_status}</span></td></tr>
                            <tr><td class="text-muted">Valid From:</td><td>${app.valid_from || '-'}</td></tr>
                            <tr><td class="text-muted">Valid Until:</td><td>${app.valid_until || '-'}</td></tr>
                            <tr><td class="text-muted">Applied On:</td><td>${app.applied_at}</td></tr>
                        </table>
                    </div>
                </div>
                ${app.admin_remark ? `<div class="alert alert-info mt-2"><strong>Admin Remark:</strong> ${app.admin_remark}</div>` : ''}
            `;
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
    </script>
</body>
</html>
