<?php
/**
 * Administrator Control Center Dashboard
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/db.php';

require_admin();

$error = '';
$success = '';

try {
    $db = Database::connect();

    // Handle Quick Action approvals/rejections from dashboard
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['pass_id'])) {
        $pass_id = intval($_POST['pass_id']);
        $action = $_POST['action']; // 'approve' or 'reject'
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        if (in_array($new_status, ['approved', 'rejected'])) {
            $db->beginTransaction();

            // Update Pass status
            $stmt = $db->prepare("UPDATE passes SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $pass_id]);

            // Update associated payment if approving
            if ($new_status === 'approved') {
                $stmt = $db->prepare("UPDATE payments SET status = 'completed' WHERE pass_id = :pass_id");
                $stmt->execute(['pass_id' => $pass_id]);
            }

            $db->commit();
            set_flash_message('success', "Pass status updated to " . ucfirst($new_status) . ".");
            redirect('/Bus-pass-managemnet/admin/dashboard.php');
        }
    }

    // Aggregate KPI stats
    // 1. Total Passengers
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $kpi_passengers = $stmt->fetchColumn() ?: 0;

    // 2. Active Passes
    $stmt = $db->query("SELECT COUNT(*) FROM passes WHERE status = 'approved' AND end_date >= CURDATE()");
    $kpi_active_passes = $stmt->fetchColumn() ?: 0;

    // 3. Pending Approvals
    $stmt = $db->query("SELECT COUNT(*) FROM passes WHERE status = 'pending'");
    $kpi_pending_passes = $stmt->fetchColumn() ?: 0;

    // 4. Revenue
    $stmt = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
    $kpi_revenue = $stmt->fetchColumn() ?: 0.00;

    // Fetch latest pending applications
    $stmt = $db->query("
        SELECT p.*, u.name AS user_name, u.email AS user_email,
               r.source, r.destination, r.route_code,
               c.name AS category_name
        FROM passes p
        INNER JOIN users u ON p.user_id = u.id
        INNER JOIN routes r ON p.route_id = r.id
        INNER JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at ASC
        LIMIT 5
    ");
    $pending_applications = $stmt->fetchAll();

} catch (Exception $e) {
    $error = 'Database Connection Error: ' . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 30px;">
    <h1 class="text-gradient">Transit Admin Control Center</h1>
    <p style="color: var(--color-text-muted);">Overview of system metrics, active tickets, and passenger approvals.</p>
</div>

<?php if ($error): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--border-radius-md); padding: 12px; color: var(--color-danger); font-size: 14px; margin-bottom: 20px; text-align: center;">
        <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- KPI Metrics Grid -->
<div class="kpi-grid">
    <div class="glass-card kpi-card">
        <div class="kpi-info">
            <h5>Total Passengers</h5>
            <div class="kpi-value"><?= e($kpi_passengers) ?></div>
        </div>
        <div class="kpi-icon kpi-purple">
            <i class="fas fa-users"></i>
        </div>
    </div>
    
    <div class="glass-card kpi-card">
        <div class="kpi-info">
            <h5>Active Passes</h5>
            <div class="kpi-value"><?= e($kpi_active_passes) ?></div>
        </div>
        <div class="kpi-icon kpi-blue">
            <i class="fas fa-id-card"></i>
        </div>
    </div>

    <div class="glass-card kpi-card">
        <div class="kpi-info">
            <h5>Pending Approvals</h5>
            <div class="kpi-value"><?= e($kpi_pending_passes) ?></div>
        </div>
        <div class="kpi-icon kpi-yellow">
            <i class="fas fa-clock"></i>
        </div>
    </div>

    <div class="glass-card kpi-card">
        <div class="kpi-info">
            <h5>Total Earnings</h5>
            <div class="kpi-value">$<?= number_format(e($kpi_revenue), 2) ?></div>
        </div>
        <div class="kpi-icon kpi-green">
            <i class="fas fa-wallet"></i>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
    
    <!-- Pending Approvals Board -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="text-gradient"><i class="fas fa-tasks"></i> Pass Verification Queue</h3>
            <a href="/Bus-pass-managemnet/admin/passes.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">View All Applications</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pass Number</th>
                        <th>Passenger</th>
                        <th>Specs (Route & Tier)</th>
                        <th>Apply Date</th>
                        <th>Fare Due</th>
                        <th style="text-align: right;">Action Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_applications)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 30px 0;">
                                <i class="fas fa-check-circle" style="font-size: 24px; color: var(--color-success); margin-bottom: 8px; display: block;"></i>
                                All applications verified. Queue is clean!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_applications as $app): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 600;"><?= e($app['pass_number']) ?></td>
                                <td>
                                    <strong><?= e($app['user_name']) ?></strong><br>
                                    <span style="font-size: 12px; color: var(--color-text-muted);"><?= e($app['user_email']) ?></span>
                                </td>
                                <td>
                                    <strong style="color: #8b5cf6; font-size: 13px;"><?= e($app['category_name']) ?></strong><br>
                                    <span style="font-size: 13px;"><?= e($app['source']) ?> <i class="fas fa-arrow-right" style="font-size: 10px;"></i> <?= e($app['destination']) ?></span>
                                </td>
                                <td style="font-size: 13px;"><?= e(date('M d, Y', strtotime($app['created_at']))) ?></td>
                                <td><strong>$<?= number_format(e($app['price']), 2) ?></strong></td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <form action="dashboard.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="pass_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" title="Approve and Issue Pass">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form action="dashboard.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="pass_id" value="<?= $app['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" title="Reject Pass Application">
                                                <i class="fas fa-ban"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
