<?php
require_once 'functions.php';

$filters = [
    'report_type' => $_GET['report_type'] ?? 'all',
    'route' => $_GET['route'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$records = [];
$routes = [];
$reportRows = [];
$reportTitle = getReportTitle($filters['report_type'], $filters['route'], $filters['status']);
$headers = getReportHeaders($filters['report_type']);
$databaseError = '';

try {
    initializeDatabase();
    $records = getPassRecords();

    if (isset($_GET['export_pdf'])) {
        $filtered = filterPassRecords($records, $filters);
        $rows = buildReportRows($filtered, $filters['report_type']);
        exportPdf($rows, $filters);
    }

    if (isset($_GET['export_excel'])) {
        $filtered = filterPassRecords($records, $filters);
        $rows = buildReportRows($filtered, $filters['report_type']);
        exportExcel($rows, $filters);
    }

    $filteredRecords = filterPassRecords($records, $filters);
    $reportRows = buildReportRows($filteredRecords, $filters['report_type']);
    $routes = getRoutes();
} catch (Throwable $exception) {
    $databaseError = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass Reports</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #f4f8ff 0%, #e9f2ff 45%, #fdfefe 100%);
            color: #1f2937;
        }
        .card {
            background: linear-gradient(145deg, #ffffff 0%, #f9fbff 100%);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 16px 40px rgba(37, 99, 235, 0.14);
            border: 1px solid #dbeafe;
        }
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 18px;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            justify-content: flex-start;
        }
        .filter-row .actions {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 0;
        }
        label {
            display: flex;
            flex-direction: column;
            font-size: 13px;
            gap: 4px;
            color: #2563eb;
            font-weight: 700;
        }
        select, input[type="submit"] {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #2563eb;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.16);
        }
        select:focus, input[type="submit"]:focus {
            outline: 2px solid #93c5fd;
            border-color: #1d4ed8;
        }
        .actions {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
        }
        .actions select {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #2563eb;
            min-width: 220px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.16);
            font-weight: 600;
        }
        .actions select option {
            background: #fff;
            color: #1f2937;
        }
        select option {
            color: #1f2937;
            background: #fff;
        }
        select {
            appearance: auto;
            -webkit-appearance: menulist;
        }
        .actions button,
        .actions .print-btn {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #2563eb;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.16);
        }
        .actions form {
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-size: 12px;
        }
        tr:nth-child(even) td {
            background: #f8fbff;
        }
        tr:hover td {
            background: #eef6ff;
        }
        .summary {
            margin-top: 16px;
            color: #475569;
            line-height: 1.6;
            font-size: 15px;
        }
        h2 {
            margin-top: 0;
            color: #000;
            letter-spacing: 0.3px;
            font-size: 28px;
        }
        h3 {
            color: #1e40af;
            margin-bottom: 10px;
            font-size: 20px;
        }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="card">
        <h2>Bus Pass Management Reports</h2>
        <p class="summary">Generate student pass reports, status-based reports, route-wise reports, renewal reports, and export them to PDF or Excel.</p>

        <div class="no-print">
            <form method="get" id="filterForm">
                <div class="filter-row">
                    <label>
                        Report Type
                        <select name="report_type" onchange="document.getElementById('filterForm').submit()">
                            <option value="all" <?= $filters['report_type'] === 'all' ? 'selected' : '' ?>>All Student Pass Reports</option>
                            <option value="pending" <?= $filters['report_type'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $filters['report_type'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $filters['report_type'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="renewal" <?= $filters['report_type'] === 'renewal' ? 'selected' : '' ?>>Renewal</option>
                            <option value="route-wise" <?= $filters['report_type'] === 'route-wise' ? 'selected' : '' ?>>Route-wise</option>
                        </select>
                    </label>
                    <label>
                        Route
                        <select name="route" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Routes</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= htmlspecialchars($route) ?>" <?= $filters['route'] === $route ? 'selected' : '' ?>><?= htmlspecialchars($route) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Status
                        <select name="status" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= $filters['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= $filters['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </label>
                    <div class="actions">
                        <select name="export_option" onchange="handleExportChange(this.value)">
                            <option value="" selected disabled>Select export</option>
                            <option value="pdf">Export PDF</option>
                            <option value="excel">Export Excel</option>
                        </select>

                        <a class="print-btn" href="#" onclick="event.preventDefault(); window.print();">Print</a>
                    </div>
                </div>
            </form>

            <script>
                function handleExportChange(value) {
                    if (!value) return;

                    const form = document.getElementById('filterForm');
                    const params = new URLSearchParams(new FormData(form));
                    params.delete('export_option');

                    if (value === 'pdf') {
                        params.set('export_pdf', '1');
                        params.delete('export_excel');
                    } else if (value === 'excel') {
                        params.set('export_excel', '1');
                        params.delete('export_pdf');
                    }

                    window.location.href = '?' + params.toString();
                }
            </script>
        </div>

        <h3><?= htmlspecialchars($reportTitle) ?></h3>

        <?php if ($databaseError !== ''): ?>
            <p style="color: #b91c1c; background: #fee2e2; padding: 10px; border-radius: 6px;">
                Unable to load data from the database. <?= htmlspecialchars($databaseError) ?>
            </p>
        <?php elseif ($filters['report_type'] === 'route-wise'): ?>
            <div style="margin-top: 12px; padding: 10px; background: #f8fafc; border-radius: 6px;">
                <strong>Route-wise Summary</strong>
            </div>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th><?= htmlspecialchars($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['route']) ?></td>
                            <td><?= htmlspecialchars($row['total']) ?></td>
                            <td><?= htmlspecialchars($row['pending']) ?></td>
                            <td><?= htmlspecialchars($row['approved']) ?></td>
                            <td><?= htmlspecialchars($row['rejected']) ?></td>
                            <td><?= htmlspecialchars($row['renewals']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php if (empty($reportRows)): ?>
                <p style="padding: 12px; background: #f8fafc; border-radius: 6px; color: #475569;">No matching pass requests found.</p>
            <?php else: ?>
                <div style="margin-top: 12px; padding: 10px; background: #f8fafc; border-radius: 6px;">
                    <strong><?= htmlspecialchars($filters['report_type'] === 'all' ? 'All Student Pass Reports' : ucfirst($filters['report_type']) . ' Passes') ?></strong>
                </div>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($headers as $header): ?>
                                <th><?= htmlspecialchars($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['route']) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                                <td><?= htmlspecialchars($row['issued_date']) ?></td>
                                <td><?= htmlspecialchars($row['expiry_date']) ?></td>
                                <td><?= htmlspecialchars($row['renewal_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
