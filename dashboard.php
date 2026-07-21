<?php
/**
 * User Dashboard Portal
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/database/db.php';

require_login();

$user_id = $_SESSION['user_id'];
$passes = [];
$active_pass = null;

try {
    $db = Database::connect();
    
    // Fetch all passes for this user
    $stmt = $db->prepare("
        SELECT p.*, 
               r.source, r.destination, r.route_code, r.standard_price,
               c.name AS category_name,
               pay.transaction_id, pay.status AS payment_status
        FROM passes p
        INNER JOIN routes r ON p.route_id = r.id
        INNER JOIN categories c ON p.category_id = c.id
        LEFT JOIN payments pay ON pay.pass_id = p.id
        WHERE p.user_id = :user_id
        ORDER BY p.id DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $passes = $stmt->fetchAll();

    // Check if there is an active/approved pass or pending pass
    foreach ($passes as $p) {
        if ($p['status'] === 'approved' || $p['status'] === 'pending') {
            $active_pass = $p;
            break; // Grab the most recent active/pending one
        }
    }
} catch (Exception $e) {
    set_flash_message('error', 'Error loading account data.');
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1 class="text-gradient">Welcome back, <?= e($_SESSION['user_name']) ?>!</h1>
        <p style="color: var(--color-text-muted);">Manage your active digital pass and view your booking records.</p>
    </div>
    <div>
        <a href="/Bus-pass-managemnet/apply.php" class="btn btn-primary"><i class="fas fa-plus"></i> Apply for New Pass</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 20px;">

    <!-- Ticket Pass Section -->
    <?php if ($active_pass): ?>
        <div style="display: flex; flex-direction: column; align-items: center;">
            <h2 class="text-gradient" style="margin-bottom: 10px;">Your Active Digital Transit Pass</h2>
            <div class="ticket-wrapper">
                <div class="bus-ticket">
                    <!-- Ticket Header -->
                    <div class="ticket-header">
                        <span class="ticket-title"><i class="fas fa-bus-alt"></i> OMNIPASS TRANSIT</span>
                        <div>
                            <?php if ($active_pass['status'] === 'approved'): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending Approval</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ticket Tear Dots -->
                    <div class="ticket-tear">
                        <div class="tear-dot"></div>
                        <div class="tear-dot"></div>
                    </div>

                    <!-- Ticket Body -->
                    <div class="ticket-body">
                        <?php if ($active_pass['profile_pic']): ?>
                            <img class="ticket-avatar" src="/Bus-pass-managemnet/assets/uploads/<?= e($active_pass['profile_pic']) ?>" alt="Passenger Photo">
                        <?php else: ?>
                            <div class="ticket-avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="ticket-details">
                            <h4><?= e($_SESSION['user_name']) ?></h4>
                            <p style="font-weight: 600; color: #8b5cf6; margin-bottom: 2px;"><?= e($active_pass['category_name']) ?></p>
                            <p><i class="far fa-envelope"></i> <?= e($_SESSION['user_email']) ?></p>
                        </div>
                    </div>

                    <!-- Route Section -->
                    <div class="ticket-route">
                        <div class="route-stop">
                            <span>Origin</span>
                            <strong><?= e($active_pass['source']) ?></strong>
                        </div>
                        <div class="route-arrow">
                            <i class="fas fa-long-arrow-alt-right"></i>
                        </div>
                        <div class="route-stop">
                            <span>Destination</span>
                            <strong><?= e($active_pass['destination']) ?></strong>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="ticket-dates">
                        <div class="ticket-date-item">
                            <span>Valid From</span>
                            <strong><?= e(date('M d, Y', strtotime($active_pass['start_date']))) ?></strong>
                        </div>
                        <div class="ticket-date-item" style="text-align: right;">
                            <span>Expires On</span>
                            <strong><?= e(date('M d, Y', strtotime($active_pass['end_date']))) ?></strong>
                        </div>
                    </div>

                    <!-- Ticket Tear Dots -->
                    <div class="ticket-tear" style="margin: 20px -34px 10px;">
                        <div class="tear-dot"></div>
                        <div class="tear-dot"></div>
                    </div>

                    <!-- Ticket Footer with QR Code -->
                    <div class="ticket-footer">
                        <?php if ($active_pass['status'] === 'approved'): ?>
                            <div class="ticket-qr">
                                <!-- Secure Dynamic QR code using QR server public API -->
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($active_pass['qr_code_data']) ?>&color=090b10" alt="Conductor Scan QR">
                            </div>
                            <span style="font-size: 11px; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Scan QR for Validity Check</span>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
                                <i class="fas fa-hourglass-half" style="font-size: 24px; color: var(--color-warning); margin-bottom: 8px; display: block;"></i>
                                Verification pending. Your QR code will activate upon administrator approval.
                            </div>
                        <?php endif; ?>
                        <div class="ticket-number"><?= e($active_pass['pass_number']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="glass-card" style="text-align: center; padding: 50px 20px;">
            <i class="fas fa-id-card" style="font-size: 48px; color: var(--color-text-muted); margin-bottom: 15px;"></i>
            <h3>No Active Bus Pass</h3>
            <p style="color: var(--color-text-muted); max-width: 400px; margin: 10px auto 20px;">You do not currently have any active or pending bus passes. Click below to apply for a pass and start saving on your daily transit.</p>
            <a href="/Bus-pass-managemnet/apply.php" class="btn btn-primary"><i class="fas fa-plus"></i> Apply for Pass</a>
        </div>
    <?php endif; ?>

    <!-- History Panel -->
    <div class="glass-card">
        <h3 class="text-gradient" style="margin-bottom: 20px;"><i class="fas fa-history"></i> Transit Pass & Billing History</h3>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pass Number</th>
                        <th>Route</th>
                        <th>Type</th>
                        <th>Validity Range</th>
                        <th>Cost</th>
                        <th>Pass Status</th>
                        <th>Payment ID</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($passes)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--color-text-muted);">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($passes as $p): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 600;"><?= e($p['pass_number']) ?></td>
                                <td><?= e($p['source']) ?> <i class="fas fa-arrow-right" style="font-size: 10px; color: #8b5cf6;"></i> <?= e($p['destination']) ?></td>
                                <td><?= e($p['category_name']) ?></td>
                                <td style="font-size: 13px;">
                                    <?= e(date('Y-m-d', strtotime($p['start_date']))) ?> to <?= e(date('Y-m-d', strtotime($p['end_date']))) ?>
                                </td>
                                <td><strong>$<?= number_format(e($p['price']), 2) ?></strong></td>
                                <td><?= get_status_badge($p['status']) ?></td>
                                <td style="font-family: monospace; font-size: 12px;"><?= e($p['transaction_id'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($p['payment_status'] === 'completed'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Paid</span>
                                    <?php elseif ($p['payment_status'] === 'pending'): ?>
                                        <span class="badge badge-warning"><i class="fas fa-spinner"></i> Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Unpaid</span>
                                    <?php endif; ?>
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
require_once __DIR__ . '/includes/footer.php';
?>
