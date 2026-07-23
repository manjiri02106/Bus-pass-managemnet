<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/QRCodeGenerator.php';

$qr = new QRCodeGenerator();
$res = $qr->generateQRCode('TESTPASS123', 1);
echo "Result:\n";
print_r($res);
if (isset($res['file_path']) && file_exists($res['file_path'])) {
    echo "File size: " . filesize($res['file_path']) . " bytes\n";
}

