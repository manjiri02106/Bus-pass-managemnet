<?php
require_once 'auth.php';
require_role('transport_officer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Officer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Transport Officer Dashboard</h2>
                <p class="text-muted mb-0">Monitor route operations and student checks.</p>
            </div>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3"><div class="card bg-primary text-white border-0 rounded-4"><div class="card-body"><h6>Trips Today</h6><h3>128</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white border-0 rounded-4"><div class="card-body"><h6>Students Scanned</h6><h3>1,092</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-warning text-dark border-0 rounded-4"><div class="card-body"><h6>Issues Flagged</h6><h3>14</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-info text-white border-0 rounded-4"><div class="card-body"><h6>Route Compliance</h6><h3>96%</h3></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Peak hour demand</h5>
                        <div class="chart-box mt-3">
                            <div class="bar-chart">
                                <div class="bar-item"><span>06:00</span><div class="fill" style="height:58%"></div></div>
                                <div class="bar-item"><span>08:00</span><div class="fill" style="height:92%"></div></div>
                                <div class="bar-item"><span>12:00</span><div class="fill" style="height:48%"></div></div>
                                <div class="bar-item"><span>17:00</span><div class="fill" style="height:88%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold">Attendance</h5>
                        <div class="d-flex justify-content-center mt-4">
                            <div class="donut-chart">
                                <span>88%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-secondary-subtle text-dark border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold">Current assignments</h5>
                <ul class="list-group list-group-flush mt-3">
                    <li class="list-group-item">Bus 04 - R-12 Central - On time</li>
                    <li class="list-group-item">Bus 09 - R-07 Harbor - Delayed</li>
                    <li class="list-group-item">Bus 11 - R-05 University - Ready</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
