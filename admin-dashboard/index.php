<?php
// admin-dashboard/index.php
require_once __DIR__ . '/../auth/config/session.php';

// Enforce admin role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../Transport-Officer-Module/db.php';

try {
    // Fetch live stats from MySQL
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_routes = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    $pending_apps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted', 'under_verification')")->fetchColumn();
    
    // Fetch setting
    $site_name = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")->fetchColumn() ?: 'Bus Pass Management';

    // Fetch data for Status Chart
    $status_data = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM applications 
        GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Group submitted and under_verification as Pending
    $pending_count = ($status_data['submitted'] ?? 0) + ($status_data['under_verification'] ?? 0);
    $approved_count = $status_data['approved'] ?? 0;
    $rejected_count = $status_data['rejected'] ?? 0;

    // Fetch data for Route Popularity Chart
    $route_data = $pdo->query("
        SELECT r.route_number, COUNT(a.id) as count 
        FROM applications a 
        JOIN routes r ON a.route_id = r.id 
        GROUP BY a.route_id 
        ORDER BY count DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    $route_labels = [];
    $route_counts = [];
    foreach ($route_data as $row) {
        $route_labels[] = $row['route_number'];
        $route_counts[] = $row['count'];
    }

    // Fetch recent users for the table
    $recent_users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h2 class="page-title" style="display:inline-block;">Dashboard</h2>
        <span class="page-subtitle">ICE Market data | Own analysis | Historic market data</span>
    </div>
    <div class="header-actions">
        <div class="date-range">
            <i class="fa-solid fa-angle-left"></i>
            <span><?= date('d/m/Y', strtotime('-7 days')) ?> - <?= date('d/m/Y') ?></span>
            <i class="fa-solid fa-angle-right"></i>
        </div>
        <button class="btn-primary" style="background:#e4e6f6; color:#1a36d6;" onclick="alert('Date filtering functionality is under development.')">All Day <i class="fa-solid fa-angle-down" style="margin-left:5px;"></i></button>
        <span style="color:#1a36d6; font-size:0.85rem; font-weight:500; cursor:pointer;" onclick="alert('Advanced Options panel')">Advanced Options</span>
        <button class="btn-primary" onclick="window.location.href='users.php'">New</button>
        <button class="btn-primary" style="background:#e4e6f6; color:#1a36d6;" onclick="window.print()">Export <i class="fa-solid fa-angle-down" style="margin-left:5px;"></i></button>
    </div>
</div>

<div class="unified-stats-card">
    <div class="stat-block">
        <div class="stat-block-info">
            <h2><?php echo number_format($total_users); ?></h2>
            <h4>System Users</h4>
            <p>+14.00(+0.50%)</p>
        </div>
        <div class="stat-sparkline" style="background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Sparkline_example.svg/120px-Sparkline_example.svg.png') no-repeat center; background-size: contain; filter: invert(40%) sepia(90%) saturate(1000%) hue-rotate(200deg);"></div>
    </div>
    <div class="stat-block">
        <div class="stat-block-info">
            <h2><?php echo number_format($total_students); ?></h2>
            <h4>Registered Students</h4>
            <p>+138.97(+0.54%)</p>
        </div>
        <div class="stat-sparkline" style="background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Sparkline_example.svg/120px-Sparkline_example.svg.png') no-repeat center; background-size: contain; filter: invert(40%) sepia(90%) saturate(1000%) hue-rotate(200deg);"></div>
    </div>
    <div class="stat-block">
        <div class="stat-block-info">
            <h2><?php echo number_format($total_routes); ?></h2>
            <h4>Active Routes</h4>
            <p>+57.62(+0.76%)</p>
        </div>
        <div class="stat-sparkline" style="background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Sparkline_example.svg/120px-Sparkline_example.svg.png') no-repeat center; background-size: contain; filter: invert(40%) sepia(90%) saturate(1000%) hue-rotate(200deg);"></div>
    </div>
    <div class="stat-block">
        <div class="stat-block-info">
            <h2><?php echo number_format($pending_apps); ?></h2>
            <h4>Pending Reviews</h4>
            <p>+138.97(+0.54%)</p>
        </div>
        <div class="stat-sparkline" style="background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/c/cd/Sparkline_example.svg/120px-Sparkline_example.svg.png') no-repeat center; background-size: contain; filter: invert(40%) sepia(90%) saturate(1000%) hue-rotate(200deg);"></div>
    </div>
</div>

<div class="card-grid">
    <!-- Chart: Popular Routes (Left - Sales Statistics Style) -->
    <div class="white-card">
        <h4>Route Popularity Statistics</h4>
        <p class="card-desc">Most commonly applied routes</p>
        
        <div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
            <div>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Total Applications</p>
                <div style="display:flex; align-items:baseline; gap:10px;">
                    <h2 style="font-size:1.5rem; font-weight:700; color:var(--text-dark);"><?= number_format(array_sum($route_counts)) ?></h2>
                    <span style="font-size:0.75rem; font-weight:700;">89.5% <span style="font-weight:normal; color:var(--text-muted);">of Total</span></span>
                </div>
            </div>
            <div style="display:flex; gap:15px; align-items:center;">
                <span style="font-size:0.8rem;"><span style="display:inline-block; width:12px; height:3px; background:#b66dff; margin-right:5px; vertical-align:middle;"></span> Popular</span>
                <span style="font-size:0.8rem;"><span style="display:inline-block; width:12px; height:3px; background:#00d284; margin-right:5px; vertical-align:middle;"></span> Others</span>
            </div>
        </div>

        <div style="position: relative; height: 250px; width: 100%;">
            <canvas id="routeChart"></canvas>
        </div>
    </div>
    
    <!-- Chart: Application Statuses (Right - Net Profit Margin Style) -->
    <div class="white-card">
        <h4>Application Status Margin</h4>
        <p class="card-desc">Started collecting data from <?= date('F Y', strtotime('-6 months')) ?></p>
        
        <div style="display:flex; gap:15px; align-items:center; margin-bottom:20px;">
            <span style="font-size:0.8rem;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#00d284; margin-right:5px;"></span> Approved</span>
            <span style="font-size:0.8rem;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#b66dff; margin-right:5px;"></span> Pending</span>
            <span style="font-size:0.8rem;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#ff4747; margin-right:5px;"></span> Rejected</span>
        </div>

        <div style="position: relative; height: 250px; width: 100%;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<div class="table-container">
    <h3 class="table-title">Recent System Users</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge badge-admin">ADMIN</span>
                        <?php elseif ($user['role'] === 'officer'): ?>
                            <span class="badge badge-officer">OFFICER</span>
                        <?php else: ?>
                            <span class="badge badge-student">STUDENT</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Staradmin Net Profit Margin Style Doughnut Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [<?= $approved_count ?>, <?= $pending_count ?>, <?= $rejected_count ?>],
                backgroundColor: [
                    '#00d284', // Green
                    '#b66dff', // Purple
                    '#ff4747'  // Red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Staradmin Line Chart Style
    const ctxRoute = document.getElementById('routeChart').getContext('2d');
    new Chart(ctxRoute, {
        type: 'line',
        data: {
            labels: <?= json_encode($route_labels) ?>,
            datasets: [{
                label: 'Applications',
                data: <?= json_encode($route_counts) ?>,
                borderColor: '#b66dff',
                backgroundColor: 'rgba(182, 109, 255, 0.1)', // Light purple fill
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#b66dff',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f3f3', drawBorder: false },
                    ticks: { color: '#8d9498', padding: 10 }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#8d9498' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
