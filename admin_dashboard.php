<?php
require_once 'auth.php';
require_role('admin');
require_once 'db.php';

$stmt = $conn->query('SELECT full_name, role FROM users ORDER BY id');
$users = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Admin Dashboard</h2>
                <p class="text-muted mb-0">Manage system access and monitor transport operations.</p>
            </div>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="card bg-primary text-white border-0 rounded-4"><div class="card-body"><h6>Total Users</h6><h3><?php echo count($users); ?></h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white border-0 rounded-4"><div class="card-body"><h6>Approved Passes</h6><h3>289</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark border-0 rounded-4"><div class="card-body"><h6>Pending Requests</h6><h3>31</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white border-0 rounded-4"><div class="card-body"><h6>Revenue</h6><h3>$12.8K</h3></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Monthly pass demand</h5>
                        <div class="chart-box mt-3">
                            <div class="bar-chart">
                                <div class="bar-item"><span>Jan</span><div class="fill" style="height:60%"></div></div>
                                <div class="bar-item"><span>Feb</span><div class="fill" style="height:80%"></div></div>
                                <div class="bar-item"><span>Mar</span><div class="fill" style="height:72%"></div></div>
                                <div class="bar-item"><span>Apr</span><div class="fill" style="height:90%"></div></div>
                                <div class="bar-item"><span>May</span><div class="fill" style="height:84%"></div></div>
                                <div class="bar-item"><span>Jun</span><div class="fill" style="height:96%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Approval rate</h5>
                        <div class="progress mt-3" style="height: 16px;">
                            <div class="progress-bar bg-success" style="width:84%"></div>
                        </div>
                        <p class="mt-3 mb-0">84% approved in the last 30 days</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold">System users</h5>
                <table class="table table-striped mt-3">
                    <thead>
                        <tr><th>Name</th><th>Role</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars(str_replace('_', ' ', $user['role'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
