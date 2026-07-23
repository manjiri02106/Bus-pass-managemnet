<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/BusPass.php';

$bp = new BusPass();

echo "Testing getAllPasses():\n";
$allPasses = $bp->getAllPasses(10);
echo "Count: " . count($allPasses) . "\n";
if (!empty($allPasses)) {
    print_r($allPasses[0]);
    $sampleEmail = $allPasses[0]['email'];
    echo "\nTesting getPassesByEmail($sampleEmail):\n";
    $emailPasses = $bp->getPassesByEmail($sampleEmail, 10);
    echo "Count: " . count($emailPasses) . "\n";
}
