<?php
require 'functions.php';

try {
    initializeDatabase();
    $records = getPassRecords();
    echo 'records=' . count($records) . PHP_EOL;

    $types = ['pending','approved','rejected','renewal','route-wise'];
    foreach ($types as $type) {
        $filtered = filterPassRecords($records, ['report_type' => $type, 'route' => '', 'status' => '']);
        echo $type . '=' . count($filtered) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo $e->getMessage() . PHP_EOL;
}
