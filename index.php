<?php
/**
 * Public Landing Page
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/database/db.php';

// Fetch dynamic stats from database (fallback to defaults if DB setup is in progress)
$stats = [
    'passes' => 120,
    'routes' => 5,
    'categories' => 4,
    'revenue' => 4500
];

try {
    $db = Database::connect();

    // Count active passes
    $stmt = $db->query("SELECT COUNT(*) FROM passes WHERE status = 'approved'");
    $stats['passes'] = $stmt->fetchColumn() ?: $stats['passes'];

    // Count routes
    $stmt = $db->query("SELECT COUNT(*) FROM routes");
    $stats['routes'] = $stmt->fetchColumn() ?: $stats['routes'];

    // Count categories
    $stmt = $db->query("SELECT COUNT(*) FROM categories");
    $stats['categories'] = $stmt->fetchColumn() ?: $stats['categories'];

    // Total earnings
    $stmt = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
    $stats['revenue'] = $stmt->fetchColumn() ?: $stats['revenue'];
} catch (Exception $e) {
    // Silently log or ignore during initial installation
}
?>

<section class="hero">
    <h1 class="text-gradient">Transit Simplified.</h1>
    <h1>Bus Pass Management System</h1>
    <p>Apply, renew, and verify your municipal bus passes instantly through a modern holographic digital pass dashboard.
    </p>

    <div style="margin-top: 20px; display: flex; justify-content: center; gap: 15px;">
        <?php if (is_logged_in()): ?>
            <a href="/Bus-pass-managemnet/dashboard.php" class="btn btn-primary"><i class="fas fa-columns"></i> Go to
                Dashboard</a>
        <?php else: ?>
            <a href="/Bus-pass-managemnet/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Apply for
                Bus Pass</a>
            <a href="/Bus-pass-managemnet/register.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Create
                Account</a>
        <?php endif; ?>
    </div>
</section>

<!-- Dynamic System Stats Bar -->
<section class="glass-card stats-bar">
    <div class="stat-item">
        <div class="stat-number text-gradient"><?= e($stats['passes']) ?>+</div>
        <div class="stat-label">Active Passes</div>
    </div>
    <div class="stat-item">
        <div class="stat-number text-gradient"><?= e($stats['routes']) ?></div>
        <div class="stat-label">Active Routes</div>
    </div>
    <div class="stat-item">
        <div class="stat-number text-gradient"><?= e($stats['categories']) ?></div>
        <div class="stat-label">Pass Tiers</div>
    </div>
    <div class="stat-item">
        <div class="stat-number text-gradient">$<?= number_format(e($stats['revenue']), 2) ?></div>
        <div class="stat-label">Processed Payments</div>
    </div>
</section>

<!-- Pricing Tiers & Categories -->
<section style="margin: 60px 0;">
    <h2 style="text-align: center; margin-bottom: 40px;" class="text-gradient">Tailored Transit Categories</h2>
    <div class="features-grid">
        <?php
        try {
            $stmt = $db->query("SELECT * FROM categories ORDER BY discount_percentage DESC");
            $categories = $stmt->fetchAll();
            foreach ($categories as $cat):
                ?>
                <div class="glass-card feature-card">
                    <div class="feature-icon">
                        <i class="fas <?= $cat['discount_percentage'] > 0 ? 'fa-percentage' : 'fa-ticket-alt' ?>"></i>
                    </div>
                    <h3><?= e($cat['name']) ?></h3>
                    <p style="margin: 10px 0 15px; font-size: 14px; color: var(--color-text-muted);">
                        <?= e($cat['description']) ?></p>
                    <?php if ($cat['discount_percentage'] > 0): ?>
                        <span class="badge badge-success"><?= e(number_format($cat['discount_percentage'], 0)) ?>% Off Fare</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Standard Pricing</span>
                    <?php endif; ?>
                </div>
            <?php
            endforeach;
        } catch (Exception $e) {
            echo "<p>Loading discount categories...</p>";
        }
        ?>
    </div>
</section>

<!-- Active Routes Table -->
<section class="glass-card" style="margin: 60px 0 40px;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 class="text-gradient">Covered Municipal Routes</h2>
            <p style="font-size: 14px; color: var(--color-text-muted);">View all available source and destination pairs
                with default fare limits.</p>
        </div>
        <i class="fas fa-route" style="font-size: 32px; color: #8b5cf6;"></i>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Source Station</th>
                    <th>Destination Station</th>
                    <th>Standard Fare</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $db->query("SELECT * FROM routes ORDER BY route_code ASC");
                    $routes = $stmt->fetchAll();
                    if (empty($routes)):
                        ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--color-text-muted);">No routes defined yet.
                            </td>
                        </tr>
                        <?php
                    else:
                        foreach ($routes as $route):
                            ?>
                            <tr>
                                <td><strong><?= e($route['route_code']) ?></strong></td>
                                <td><?= e($route['source']) ?></td>
                                <td><?= e($route['destination']) ?></td>
                                <td><strong>$<?= number_format(e($route['standard_price']), 2) ?></strong></td>
                            </tr>
                        <?php
                        endforeach;
                    endif;
                } catch (Exception $e) {
                    echo "<tr><td colspan='4'>Error loading routes database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>