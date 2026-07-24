<?php
session_start();

if (!empty($_SESSION['user_id'])) {
    $redirectMap = [
        'admin' => 'admin_dashboard.php',
        'transport_officer' => 'officer_dashboard.php',
        'student' => 'student_dashboard.php'
    ];
    header('Location: ' . $redirectMap[$_SESSION['role']]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 rounded-4 shadow-lg bg-secondary-subtle text-dark">
                    <div class="card-body p-5">
                        <h1 class="fw-bold">Bus Pass Management System</h1>
                        <p class="lead text-muted">Choose your dashboard to view live stats, route analytics, and charts.</p>
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-primary text-white rounded-4">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Admin</h5>
                                        <p class="small mb-3">View approvals, revenue, and system-wide statistics.</p>
                                        <a href="admin_login.php" class="btn btn-light text-primary">Open Admin Dashboard</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-success text-white rounded-4">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Transport Officer</h5>
                                        <p class="small mb-3">Monitor trips, route health, and student scan activity.</p>
                                        <a href="officer_login.php" class="btn btn-light text-success">Open Officer Dashboard</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-info text-white rounded-4">
                                    <div class="card-body">
                                        <h5 class="fw-bold">Student</h5>
                                        <p class="small mb-3">Check your pass status and travel route trends.</p>
                                        <a href="student_login.php" class="btn btn-light text-info">Open Student Dashboard</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="register.php" class="btn btn-outline-dark">Create account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
