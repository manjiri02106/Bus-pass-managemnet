<?php
/**
 * Student Dashboard
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student = getCurrentStudent();
$student_id = (int)$_SESSION['student_id'];

// Get statistics
$stats = [];

// Total applications
$q = "SELECT COUNT(*) as cnt FROM bus_passes WHERE student_id = ?";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$stats['total'] = mysqli_fetch_assoc($r)['cnt'];

// Pending applications
$q = "SELECT COUNT(*) as cnt FROM bus_passes WHERE student_id = ? AND status = 'Pending'";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$stats['pending'] = mysqli_fetch_assoc($r)['cnt'];

// Approved applications
$q = "SELECT COUNT(*) as cnt FROM bus_passes WHERE student_id = ? AND status = 'Approved'";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$stats['approved'] = mysqli_fetch_assoc($r)['cnt'];

// Rejected
$q = "SELECT COUNT(*) as cnt FROM bus_passes WHERE student_id = ? AND status = 'Rejected'";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$stats['rejected'] = mysqli_fetch_assoc($r)['cnt'];

// Active pass (approved and valid)
$q = "SELECT * FROM bus_passes WHERE student_id = ? AND status = 'Approved' AND valid_from <= CURDATE() AND valid_until >= CURDATE() ORDER BY id DESC LIMIT 1";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$active_pass = mysqli_fetch_assoc($r);

// Recent notifications (last 5)
$q = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 5";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$notifications = mysqli_stmt_get_result($s);

// Count unread notifications
$q = "SELECT COUNT(*) as cnt FROM notifications WHERE student_id = ? AND is_read = 0";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$unread_count = mysqli_fetch_assoc($r)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="bi bi-person-circle me-2"></i>Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h1>
                    <p class="mb-0">
                        <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($student['college_name']); ?> | 
                        <i class="bi bi-card-text me-1"></i><?php echo htmlspecialchars($student['college_id_number']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="<?php echo BASE_URL; ?>/apply_pass.php" class="btn btn-light me-2">
                        <i class="bi bi-file-earmark-plus me-1"></i>Apply Pass
                    </a>
                    <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-outline-light position-relative">
                        <i class="bi bi-bell me-1"></i>Alerts
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $unread_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card border-start border-primary shadow-sm h-100">
                    <div class="card-body position-relative">
                        <div class="stat-icon"><i class="bi bi-files"></i></div>
                        <div class="stat-value text-primary"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card border-start border-warning shadow-sm h-100">
                    <div class="card-body position-relative">
                        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-value text-warning"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card border-start border-success shadow-sm h-100">
                    <div class="card-body position-relative">
                        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-value text-success"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card stat-card border-start border-danger shadow-sm h-100">
                    <div class="card-body position-relative">
                        <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-value text-danger"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Active Pass Card -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bus-front text-primary me-2"></i>Active Bus Pass</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($active_pass): ?>
                            <?php 
                            $q_route = "SELECT * FROM routes WHERE id = ?";
                            $s_route = mysqli_prepare($conn, $q_route);
                            mysqli_stmt_bind_param($s_route, 'i', $active_pass['route_id']);
                            mysqli_stmt_execute($s_route);
                            $route = mysqli_fetch_assoc(mysqli_stmt_get_result($s_route));
                            ?>
                            <div class="pass-card p-3">
                                <div class="watermark"><i class="bi bi-bus-front"></i></div>
                                <div class="pass-header d-flex justify-content-between align-items-center">
                                    <h5 class="text-primary fw-bold mb-0"><?php echo htmlspecialchars($route['route_name']); ?></h5>
                                    <span class="badge bg-success badge-status">Active</span>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <small class="text-muted">Valid From</small>
                                        <p class="fw-bold mb-0"><?php echo date('d M Y', strtotime($active_pass['valid_from'])); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Valid Until</small>
                                        <p class="fw-bold mb-0"><?php echo date('d M Y', strtotime($active_pass['valid_until'])); ?></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Pass Type: <span class="fw-bold text-dark"><?php echo $active_pass['pass_type']; ?></span></small>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <a href="<?php echo BASE_URL; ?>/download_pass.php?id=<?php echo $active_pass['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/renew_pass.php" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i>Renew
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bus-front display-4 text-muted"></i>
                                <p class="mt-3 text-muted">No active bus pass found.</p>
                                <a href="<?php echo BASE_URL; ?>/apply_pass.php" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-plus me-1"></i>Apply Now
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bell text-primary me-2"></i>Recent Notifications</h5>
                        <a href="<?php echo BASE_URL; ?>/notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (mysqli_num_rows($notifications) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($notif = mysqli_fetch_assoc($notifications)): ?>
                                <div class="list-group-item notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" data-id="<?php echo $notif['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php
                                                $notif_icons = ['info' => 'bi-info-circle text-info', 'success' => 'bi-check-circle text-success', 'warning' => 'bi-exclamation-triangle text-warning', 'danger' => 'bi-x-circle text-danger'];
                                                $icon_class = $notif_icons[$notif['type']] ?? 'bi-info-circle text-info';
                                                ?>
                                                <i class="bi <?php echo $icon_class; ?> me-2"></i>
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="badge bg-primary rounded-pill ms-2">New</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        </div>
                                        <small class="notification-time text-nowrap ms-3">
                                            <?php echo date('d M, h:i A', strtotime($notif['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash display-4 text-muted"></i>
                                <p class="mt-2 text-muted">No notifications yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
