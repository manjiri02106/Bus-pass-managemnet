<?php
/**
 * Renew Bus Pass
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];

// Get active/expired passes eligible for renewal
$q = "SELECT bp.*, r.route_name, r.source, r.destination, r.fare as route_fare 
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      WHERE bp.student_id = ? AND bp.status IN ('Approved', 'Expired') 
      ORDER BY bp.valid_until DESC";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$passes = mysqli_stmt_get_result($s);

$routes_query = "SELECT MIN(id) as id, route_name, source, destination, fare FROM routes WHERE status = 'Active' GROUP BY route_name ORDER BY route_name";
$routes_result = mysqli_query($conn, $routes_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_pass'])) {
    $route_id = (int)$_POST['route_id'];
    $pass_type = sanitize($_POST['pass_type']);

    if (empty($route_id) || empty($pass_type)) {
        $error = 'Please select a route and pass type.';
    } else {
        $route_q = "SELECT * FROM routes WHERE id = ?";
        $route_s = mysqli_prepare($conn, $route_q);
        mysqli_stmt_bind_param($route_s, 'i', $route_id);
        mysqli_stmt_execute($route_s);
        $route = mysqli_fetch_assoc(mysqli_stmt_get_result($route_s));

        if (!$route) {
            $error = 'Invalid route selected.';
        } else {
            $fee = calculatePassFee($route['fare'], $pass_type);
            $validity = calculateValidity($pass_type);
            $app_no = generateApplicationNo($student_id);
            $payment_status = 'Pending';

            $query = "INSERT INTO bus_passes (application_no, student_id, route_id, pass_type, fee, valid_from, valid_until, status, payment_status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'siisssss', $app_no, $student_id, $route_id, $pass_type, $fee, $validity['from'], $validity['until'], $payment_status);

            if (mysqli_stmt_execute($stmt)) {
                $pass_id = mysqli_insert_id($conn);
                // Notification
                $notif_q = "INSERT INTO notifications (student_id, title, message, type) VALUES (?, 'Renewal Submitted', 'Your bus pass renewal application (Ref: $app_no) has been submitted successfully. Please complete the payment.', 'info')";
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

            <!-- Existing Passes -->
            <?php if (mysqli_num_rows($passes) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Your Previous Passes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>App No.</th>
                                    <th>Route</th>
                                    <th>Type</th>
                                    <th>Validity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pass = mysqli_fetch_assoc($passes)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pass['application_no']); ?></td>
                                    <td><?php echo htmlspecialchars($pass['route_name']); ?></td>
                                    <td><?php echo $pass['pass_type']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($pass['valid_from'])); ?> → <?php echo date('d-m-Y', strtotime($pass['valid_until'])); ?></td>
                                    <td>
                                        <span class="badge badge-status <?php echo $pass['status'] === 'Approved' ? 'badge-approved' : 'badge-expired'; ?>">
                                            <?php echo $pass['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No previous passes found. You can apply for a new pass.
            </div>
            <?php endif; ?>

            <!-- Renewal Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Apply for Renewal</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bus Route <span class="text-danger">*</span></label>
                                <select name="route_id" id="route_id" class="form-select" required>
                                    <option value="">-- Select Route --</option>
                                    <?php mysqli_data_seek($routes_result, 0); while ($route = mysqli_fetch_assoc($routes_result)): ?>
                                    <option value="<?php echo $route['id']; ?>" data-fare="<?php echo $route['fare']; ?>">
                                        <?php echo htmlspecialchars($route['route_name']); ?> - ₹<?php echo number_format($route['fare'], 2); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pass Type <span class="text-danger">*</span></label>
                                <select name="pass_type" id="pass_type" class="form-select" required>
                                    <option value="">-- Select --</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                    <option value="Half Yearly">Half Yearly</option>
                                    <option value="Yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Calculated Fee</label>
                                <input type="text" class="form-control" id="calculated_fee" readonly value="Select options">
                            </div>
                        </div>
                        <div id="fee_display" class="mt-3"></div>
                        <button type="submit" name="renew_pass" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-repeat me-1"></i>Submit Renewal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        // Pass type styles for visual distinction
        const passTypeStyles = {
            'Daily': { icon: 'bi-calendar-day', color: '#0ea5e9', bg: '#f0f9ff' },
            'Monthly': { icon: 'bi-calendar-month', color: '#22c55e', bg: '#f0fdf4' },
            'Quarterly': { icon: 'bi-calendar3', color: '#f97316', bg: '#fff7ed' },
            'Half Yearly': { icon: 'bi-calendar-week', color: '#a855f7', bg: '#fdf4ff' },
            'Yearly': { icon: 'bi-calendar-check', color: '#ef4444', bg: '#fef2f2' },
        };

        // Calculate fee based on selected route and pass type
        document.getElementById('route_id').addEventListener('change', calculateFee);
        document.getElementById('pass_type').addEventListener('change', calculateFee);

        function calculateFee() {
            const routeSelect = document.getElementById('route_id');
            const passType = document.getElementById('pass_type').value;
            const feeInput = document.getElementById('calculated_fee');
            const feeDisplay = document.getElementById('fee_display');

            if (!routeSelect.value || !passType) {
                feeInput.value = 'Select options';
                feeDisplay.innerHTML = '';
                return;
            }

            const selectedOption = routeSelect.options[routeSelect.selectedIndex];
            const routeFare = parseFloat(selectedOption.dataset.fare);

            const multipliers = {
                'Daily': 1,
                'Monthly': 30,
                'Quarterly': 90,
                'Half Yearly': 180,
                'Yearly': 365
            };
            const discounts = {
                'Daily': 1.0,
                'Monthly': 0.95,
                'Quarterly': 1.0,
                'Half Yearly': 1.0,
                'Yearly': 1.0
            };

            const baseTotal = routeFare * (multipliers[passType] || 30);
            const totalFee = baseTotal * (discounts[passType] || 1.0);
            feeInput.value = '₹' + totalFee.toLocaleString('en-IN', { minimumFractionDigits: 2 });

            // Show breakdown with visual style
            const style = passTypeStyles[passType];
            const discountNote = discounts[passType] < 1.0 ? '<span class="badge bg-warning text-dark ms-1">5% OFF</span>' : '';
            const calculationText = discounts[passType] < 1.0 
                ? `₹${routeFare.toFixed(2)} × ${multipliers[passType]} × ${(discounts[passType]*100).toFixed(0)}%` 
                : `₹${routeFare.toFixed(2)} × ${multipliers[passType]}`;
            
            feeDisplay.innerHTML = `
                <div class="alert mt-2 p-3" style="background-color: ${style.bg}; border-left: 4px solid ${style.color};">
                    <i class="bi ${style.icon} me-2" style="color: ${style.color}; font-size: 1.2rem;"></i>
                    Calculation: ${calculationText} = <strong>₹${totalFee.toLocaleString('en-IN', { minimumFractionDigits: 2})}</strong>
                    ${discountNote}
                </div>
            `;
        }
    </script>
</body>
</html>
