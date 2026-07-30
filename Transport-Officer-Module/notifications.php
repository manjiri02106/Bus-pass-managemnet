<?php
// notifications.php - Global System Activity Feed for Transport Officer
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce access for Officer or Admin
check_auth(['officer', 'admin']);

// Handle mark as read if necessary (optional for global feed)
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

// Fetch all notifications (Global Feed)
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

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">System Activity Feed</h2>
    <p class="page-subtitle">Global notifications and alerts for all student activities and system events.</p>
</div>

<div class="white-card shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold" style="color: var(--text-dark);"><i class="bi bi-bell-fill" style="color: var(--star-blue); margin-right: 10px;"></i>Recent Activity</h4>
        <a href="notifications.php?mark_all_read=1" class="btn btn-sm" style="border: 1px solid var(--border-color); color: var(--text-muted);">Mark All as Read</a>
    </div>
    <div class="list-group list-group-flush">
        <?php if (empty($notifications)): ?>
            <div class="list-group-item text-center py-5 text-muted">
                <i class="bi bi-bell-slash fs-1 d-block mb-2 text-light"></i>
                No recent activity found.
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <?php
                    $icon = 'bi-info-circle text-info';
                    $bg = $notif['is_read'] ? 'bg-white' : 'bg-light border-start border-4 border-primary';
                    
                    if ($notif['type'] === 'success') $icon = 'bi-check-circle-fill text-success';
                    elseif ($notif['type'] === 'warning') $icon = 'bi-exclamation-triangle-fill text-warning';
                    elseif ($notif['type'] === 'danger') $icon = 'bi-x-circle-fill text-danger';
                ?>
                <div class="list-group-item <?= $bg ?> py-3">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="d-flex align-items-start gap-3">
                            <div class="fs-4 mt-1"><i class="bi <?= $icon ?>"></i></div>
                            <div>
                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($notif['title']) ?></h6>
                                <p class="mb-1 text-secondary"><?= htmlspecialchars($notif['message']) ?></p>
                                <?php if ($notif['student_name']): ?>
                                    <small class="text-primary fw-medium">
                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($notif['student_name']) ?> (<?= htmlspecialchars($notif['prn_number']) ?>)
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block mb-2"><?= date('d M Y, h:i A', strtotime($notif['created_at'])) ?></small>
                            <?php if (!$notif['is_read']): ?>
                                <a href="notifications.php?mark_read=<?= $notif['id'] ?>" class="badge bg-secondary text-decoration-none">Mark Read</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
