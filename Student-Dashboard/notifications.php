<?php
/**
 * Notifications Page
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $q = "UPDATE notifications SET is_read = 1 WHERE student_id = ?";
    $s = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($s, 'i', $student_id);
    mysqli_stmt_execute($s);
    redirect('/notifications.php', 'All notifications marked as read.', 'success');
}

// Fetch all notifications
$q = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'i', $student_id);
mysqli_stmt_execute($s);
$notifications = mysqli_stmt_get_result($s);

// Count unread
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
    <title>Notifications - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
            <h4 class="mb-0">
                <i class="bi bi-bell text-primary me-2"></i>Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </h4>
            <?php if ($unread_count > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-check-all me-1"></i>Mark All as Read
            </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (mysqli_num_rows($notifications) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($notif = mysqli_fetch_assoc($notifications)): ?>
                        <div class="list-group-item notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notif['id']; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="d-flex align-items-start">
                                    <?php
                                    $icons = [
                                        'info' => ['bi-info-circle', 'text-info'],
                                        'success' => ['bi-check-circle', 'text-success'],
                                        'warning' => ['bi-exclamation-triangle', 'text-warning'],
                                        'danger' => ['bi-x-circle', 'text-danger']
                                    ];
                                    $icon = $icons[$notif['type']] ?? ['bi-info-circle', 'text-info'];
                                    ?>
                                    <div class="me-3 mt-1">
                                        <i class="bi <?php echo $icon[0]; ?> fs-4 <?php echo $icon[1]; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="badge bg-primary rounded-pill ms-2">New</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="notification-time">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Notifications</h5>
                        <p class="text-muted">You don't have any notifications at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
