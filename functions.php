<?php

function getDbConnection(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    $database = getenv('DB_NAME') ?: 'bus_pass_management';

    $connection = mysqli_connect($host, $user, $password);
    if ($connection === false) {
        throw new RuntimeException('Unable to connect to MySQL server: ' . mysqli_connect_error());
    }

    if (!mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $database) . '`')) {
        throw new RuntimeException('Unable to create database: ' . mysqli_error($connection));
    }

    if (!mysqli_select_db($connection, $database)) {
        throw new RuntimeException('Unable to select database: ' . mysqli_error($connection));
    }

    mysqli_set_charset($connection, 'utf8mb4');

    return $connection;
}

function initializeDatabase(): void
{
    $connection = getDbConnection();

    $createTableSql = "
        CREATE TABLE IF NOT EXISTS bus_pass_records (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(100) NOT NULL,
            route VARCHAR(100) NOT NULL,
            status VARCHAR(20) NOT NULL,
            pass_type VARCHAR(50) NOT NULL,
            issued_date DATE NOT NULL,
            expiry_date DATE NOT NULL,
            renewal_date DATE NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!mysqli_query($connection, $createTableSql)) {
        throw new RuntimeException('Unable to create table: ' . mysqli_error($connection));
    }

    $countResult = mysqli_query($connection, 'SELECT COUNT(*) AS total FROM bus_pass_records');
    if ($countResult === false) {
        throw new RuntimeException('Unable to count records: ' . mysqli_error($connection));
    }

    $countRow = mysqli_fetch_assoc($countResult);
    if ((int) ($countRow['total'] ?? 0) === 0) {
        $sampleRecords = [
            ['Asha Verma', 'Route A', 'Pending', 'Student', '2026-01-05', '2026-06-30', '2026-06-15'],
            ['Rahul Kumar', 'Route B', 'Approved', 'Student', '2026-02-10', '2026-07-15', null],
            ['Neha Sharma', 'Route A', 'Rejected', 'Student', '2026-02-18', '2026-07-20', null],
            ['Aniket Rao', 'Route C', 'Approved', 'Student', '2026-03-01', '2026-08-01', '2026-07-25'],
            ['Kavya Patil', 'Route B', 'Pending', 'Student', '2026-03-16', '2026-08-16', null],
            ['Mohan Das', 'Route C', 'Approved', 'Student', '2026-04-03', '2026-09-01', '2026-08-10'],
            ['Pooja Singh', 'Route A', 'Pending', 'Student', '2026-04-10', '2026-09-10', '2026-08-20'],
            ['Suresh Nair', 'Route D', 'Rejected', 'Student', '2026-04-24', '2026-09-24', null],
        ];

        foreach ($sampleRecords as $record) {
            $insertSql = sprintf(
                "INSERT INTO bus_pass_records (student_name, route, status, pass_type, issued_date, expiry_date, renewal_date) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', %s)",
                mysqli_real_escape_string($connection, $record[0]),
                mysqli_real_escape_string($connection, $record[1]),
                mysqli_real_escape_string($connection, $record[2]),
                mysqli_real_escape_string($connection, $record[3]),
                mysqli_real_escape_string($connection, $record[4]),
                mysqli_real_escape_string($connection, $record[5]),
                $record[6] === null ? 'NULL' : "'" . mysqli_real_escape_string($connection, $record[6]) . "'"
            );

            if (!mysqli_query($connection, $insertSql)) {
                throw new RuntimeException('Unable to seed sample data: ' . mysqli_error($connection));
            }
        }
    }
}

function getPassRecords(): array
{
    initializeDatabase();

    $connection = getDbConnection();
    $result = mysqli_query($connection, 'SELECT id, student_name, route, status, pass_type, issued_date, expiry_date, renewal_date FROM bus_pass_records ORDER BY id');
    if ($result === false) {
        throw new RuntimeException('Unable to fetch pass records: ' . mysqli_error($connection));
    }

    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['renewal_date'] = $row['renewal_date'] === null ? '' : $row['renewal_date'];
        $records[] = $row;
    }

    return $records;
}

function getRoutes(): array
{
    initializeDatabase();

    $connection = getDbConnection();
    $result = mysqli_query($connection, 'SELECT DISTINCT route FROM bus_pass_records ORDER BY route');
    if ($result === false) {
        throw new RuntimeException('Unable to fetch routes: ' . mysqli_error($connection));
    }

    $routes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $routes[] = $row['route'];
    }

    return $routes;
}

function filterPassRecords(array $records, array $filters): array
{
    $reportType = $filters['report_type'] ?? 'all';
    $route = $filters['route'] ?? '';
    $status = $filters['status'] ?? '';

    $filtered = $records;

    if ($route !== '') {
        $filtered = array_values(array_filter($filtered, static fn($record): bool => strtolower($record['route']) === strtolower($route)));
    }

    if ($status !== '') {
        $filtered = array_values(array_filter($filtered, static fn($record): bool => strtolower($record['status']) === strtolower($status)));
    }

    if (in_array($reportType, ['pending', 'approved', 'rejected'], true)) {
        $filtered = array_values(array_filter($filtered, static fn($record): bool => strtolower($record['status']) === $reportType));
    }

    if ($reportType === 'renewal') {
        $filtered = array_values(array_filter($filtered, static fn($record): bool => $record['renewal_date'] !== ''));
    }

    return $filtered;
}

function buildReportRows(array $records, string $reportType): array
{
    if ($reportType === 'route-wise') {
        $grouped = [];
        foreach ($records as $record) {
            $route = $record['route'];
            if (!isset($grouped[$route])) {
                $grouped[$route] = [
                    'route' => $route,
                    'total' => 0,
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'renewals' => 0,
                ];
            }

            $grouped[$route]['total']++;
            $statusKey = strtolower($record['status']);
            if (isset($grouped[$route][$statusKey])) {
                $grouped[$route][$statusKey]++;
            }
            if ($record['renewal_date'] !== '') {
                $grouped[$route]['renewals']++;
            }
        }

        return array_values($grouped);
    }

    return $records;
}

function getReportTitle(string $reportType, string $route, string $status): string
{
    $typeName = match ($reportType) {
        'pending' => 'Pending Passes',
        'approved' => 'Approved Passes',
        'rejected' => 'Rejected Passes',
        'route-wise' => 'Route-wise Summary',
        'renewal' => 'Renewal Report',
        default => 'Student Pass Report',
    };

    if ($route !== '' && $status !== '') {
        return $typeName . ' for ' . $route . ' / ' . $status;
    }

    if ($route !== '') {
        return $typeName . ' for ' . $route;
    }

    if ($status !== '') {
        return $typeName . ' for ' . $status;
    }

    return $typeName;
}

function getReportHeaders(string $reportType): array
{
    if ($reportType === 'route-wise') {
        return ['Route', 'Total Passes', 'Pending', 'Approved', 'Rejected', 'Renewals'];
    }

    return ['ID', 'Student', 'Route', 'Status', 'Issued Date', 'Expiry Date', 'Renewal Date'];
}

function buildPdfContent(array $rows, array $filters): string
{
    $reportType = $filters['report_type'] ?? 'all';
    $route = $filters['route'] ?? '';
    $status = $filters['status'] ?? '';
    $title = getReportTitle($reportType, $route, $status);
    $headers = getReportHeaders($reportType);

    $tableRows = [];
    $tableRows[] = $headers;

    if ($reportType === 'route-wise') {
        foreach ($rows as $row) {
            $tableRows[] = [$row['route'], $row['total'], $row['pending'], $row['approved'], $row['rejected'], $row['renewals']];
        }
    } else {
        foreach ($rows as $row) {
            $tableRows[] = [$row['id'], $row['student_name'], $row['route'], $row['status'], $row['issued_date'], $row['expiry_date'], $row['renewal_date']];
        }
    }

    $content = [];
    $content[] = $title;
    $content[] = 'Generated at ' . date('Y-m-d H:i:s');
    $content[] = str_repeat('-', 110);
    $rowText = [];
    foreach ($headers as $header) {
        $rowText[] = str_pad(substr((string) $header, 0, 20), 20);
    }
    $content[] = implode(' | ', $rowText);
    $content[] = str_repeat('-', 110);

    foreach (array_slice($tableRows, 1) as $row) {
        $rowText = [];
        foreach ($row as $index => $cell) {
            $maxWidth = $index === 1 ? 30 : 20;
            $rowText[] = str_pad(substr((string) $cell, 0, $maxWidth), $maxWidth);
        }
        $content[] = implode(' | ', $rowText);
    }

    $text = implode("\n", $content);
    $escapedText = escapePdfText($text);
    $lines = explode("\n", $escapedText);

    $contentStream = "";
    $y = 760;
    $lineHeight = 12;
    foreach ($lines as $line) {
        $contentStream .= "BT\n/F1 10 Tf\n72 {$y} Td\n({$line}) Tj\nET\n";
        $y -= $lineHeight;
    }

    $contentLength = strlen($contentStream);
    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
    $objects[] = "4 0 obj\n<< /Length {$contentLength} >>\nstream\n" . $contentStream . "\nendstream\nendobj";
    $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    $currentOffset = strlen($pdf);

    foreach ($objects as $object) {
        $offsets[] = $currentOffset;
        $pdf .= $object . "\n";
        $currentOffset = strlen($pdf);
    }

    $xrefPosition = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF";

    return $pdf;
}

function exportPdf(array $rows, array $filters): void
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="bus-pass-report.pdf"');
    echo buildPdfContent($rows, $filters);
    exit;
}

function buildExcelContent(array $rows, array $filters): string
{
    $reportType = $filters['report_type'] ?? 'all';
    $title = getReportTitle($reportType, $filters['route'] ?? '', $filters['status'] ?? '');
    $headers = getReportHeaders($reportType);
    $rowsToWrite = [];
    $rowsToWrite[] = [$title];
    $rowsToWrite[] = $headers;

    if ($reportType === 'route-wise') {
        foreach ($rows as $row) {
            $rowsToWrite[] = [$row['route'], $row['total'], $row['pending'], $row['approved'], $row['rejected'], $row['renewals']];
        }
    } else {
        foreach ($rows as $row) {
            $rowsToWrite[] = [$row['id'], $row['student_name'], $row['route'], $row['status'], $row['issued_date'], $row['expiry_date'], $row['renewal_date']];
        }
    }

    $escapeCsvValue = static function ($value): string {
        $value = str_replace(["\r", "\n", '"'], [' ', ' ', '""'], (string) $value);
        return '"' . $value . '"';
    };

    $lines = [];
    foreach ($rowsToWrite as $row) {
        $lines[] = implode(',', array_map($escapeCsvValue, $row));
    }

    return "\xEF\xBB\xBF" . implode(PHP_EOL, $lines) . PHP_EOL;
}

function exportExcel(array $rows, array $filters): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="bus-pass-report.xls"');
    echo buildExcelContent($rows, $filters);
    exit;
}

function escapePdfText(string $value): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}
