<?php
require_once 'auth.php';
require_role('student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Student Dashboard</h2>
                <p class="text-muted mb-0">View your pass status and route history.</p>
            </div>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="card bg-primary text-white border-0 rounded-4"><div class="card-body"><h6>Pass Status</h6><h3>Active</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white border-0 rounded-4"><div class="card-body"><h6>Trips Used</h6><h3>24</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark border-0 rounded-4"><div class="card-body"><h6>Balance</h6><h3>$18</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white border-0 rounded-4"><div class="card-body"><h6>Route Preference</h6><h3>North Loop</h3></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Weekly travel trend</h5>
                        <div class="chart-box mt-3">
                            <div class="bar-chart">
                                <div class="bar-item"><span>Mon</span><div class="fill" style="height:50%"></div></div>
                                <div class="bar-item"><span>Tue</span><div class="fill" style="height:72%"></div></div>
                                <div class="bar-item"><span>Wed</span><div class="fill" style="height:64%"></div></div>
                                <div class="bar-item"><span>Thu</span><div class="fill" style="height:86%"></div></div>
                                <div class="bar-item"><span>Fri</span><div class="fill" style="height:92%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Pass validity</h5>
                        <div class="d-flex justify-content-center mt-4">
                            <div class="donut-chart">
                                <span>73%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold">My recent route activity</h5>
                <ul class="list-group list-group-flush mt-3">
                    <li class="list-group-item">North Loop - 08:00 AM - 18 min</li>
                    <li class="list-group-item">University Link - 05:00 PM - 22 min</li>
                    <li class="list-group-item">Harbor Street - 06:30 AM - 15 min</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
