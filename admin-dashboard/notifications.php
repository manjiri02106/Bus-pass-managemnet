<?php
// admin-dashboard/notifications.php - Global System Activity Feed for Admin
require_once __DIR__ . '/../Transport-Officer-Module/db.php';
require_once __DIR__ . '/../auth/config/session.php';

// Enforce access for Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_GET['mark_read']]);
    header("Location: notifications.php");
    exit;
}
if (isset($_GET['mark_all_read'])) {
    $pdo->query("UPDATE notifications SET is_read = 1");
    header("Location: notifications.php");
    exit;
}

// Fetch all notifications
$query = "
    SELECT n.*, u.full_name as student_name, s.prn_number
    FROM notifications n
    LEFT JOIN students s ON n.student_id = s.id
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 100
";
$stmt = $pdo->query($query);
$notifications = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="page-header mb-4">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="fa-solid fa-bell"></i>
            </span> System Activity Feed
        </h3>
        <p class="text-muted mt-2">Global notifications and alerts for all student activities and system events.</p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="card-title mb-0">Recent Activity</h4>
                <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-outline-secondary">Mark All as Read</a>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-bell-slash fs-1 d-block mb-2"></i>
                        No recent activity found.
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                            $icon = 'fa-solid fa-circle-info text-info';
                            $bg = $notif['is_read'] ? '#fff' : '#f8f9fa';
                            $border = $notif['is_read'] ? '1px solid #ebedf2' : '1px solid #ebedf2; border-left: 4px solid #303af6';
                            
                            if ($notif['type'] === 'success') $icon = 'fa-solid fa-circle-check text-success';
                            if ($notif['type'] === 'warning') $icon = 'fa-solid fa-triangle-exclamation text-warning';
                            if ($notif['type'] === 'danger') $icon = 'fa-solid fa-circle-xmark text-danger';
                        ?>
                        <div style="background-color: <?= $bg ?>; border: <?= $border ?>; border-radius: 6px; padding: 15px; display: flex; gap: 15px; align-items: flex-start; text-decoration: none; color: inherit;">
                            <div style="font-size: 1.5rem; margin-top: 2px;">
                                <i class="<?= $icon ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">
                                    <h5 style="margin: 0; font-size: 1rem; font-weight: 500; color: #1d1e21;">
                                        <?= htmlspecialchars($notif['title']) ?>
                                    </h5>
                                    <small style="color: #8d9498; white-space: nowrap;">
                                        <?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                                    </small>
                                </div>
                                <p style="margin: 0 0 8px 0; font-size: 0.9rem; color: #555;">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </p>
                                <?php if ($notif['student_name']): ?>
                                    <small style="color: #8d9498; display: block; margin-bottom: 8px;">
                                        <strong>Student:</strong> <?= htmlspecialchars($notif['student_name']) ?> 
                                        (<?= htmlspecialchars($notif['prn_number']) ?>)
                                    </small>
                                <?php endif; ?>
                                
                                <?php if (!$notif['is_read']): ?>
                                    <a href="notifications.php?mark_read=<?= $notif['id'] ?>" style="font-size: 0.8rem; color: #303af6; text-decoration: none;">Mark as read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
