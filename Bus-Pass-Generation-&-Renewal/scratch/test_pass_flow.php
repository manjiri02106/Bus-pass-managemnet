<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BusPass.php';
require_once __DIR__ . '/../classes/PassNumberGenerator.php';
require_once __DIR__ . '/../classes/QRCodeGenerator.php';
require_once __DIR__ . '/../classes/PassDownloadManager.php';

// 1. Create a user
$user = new User();
$uResult = $user->createUser(array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe.' . time() . '@example.com',
    'phone' => '9876543210',
    'address' => '123 Main St',
    'city' => 'Metropolis',
    'state' => 'State',
    'postal_code' => '123456',
    'user_type' => 'student'
));

echo "User Result:\n";
print_r($uResult);
$userId = $uResult['user_id'];

// 2. Generate pass
$gen = new PassNumberGenerator();
$passNumber = $gen->generatePassNumber();

$busPass = new BusPass();
$pResult = $busPass->createPass($userId, $passNumber, 'monthly', 30);
echo "Pass Result:\n";
print_r($pResult);
$passId = $pResult['pass_id'];

// 3. Generate QR code & update DB
$qrGen = new QRCodeGenerator();
$qrResult = $qrGen->generateQRCode($passNumber, $userId);
echo "QR Result:\n";
print_r($qrResult);

if (!empty($qrResult['file_path'])) {
    $upResult = $busPass->updatePassQRCode($passId, $qrResult['file_path']);
    echo "Update Pass QR Result: " . ($upResult ? "SUCCESS" : "FAILED") . "\n";
}

// 4. Test PassDownloadManager HTML generation
$pdm = new PassDownloadManager();
$htmlResult = $pdm->generatePrintablePass($passId);
echo "HTML Result Success: " . ($htmlResult['success'] ? "YES" : "NO") . "\n";
if (strpos($htmlResult['html'], 'data:image/png;base64') !== false || strpos($htmlResult['html'], 'qrserver.com') !== false) {
    echo "QR CODE IS PRESENT IN RENDERED HTML!\n";
} else {
    echo "WARNING: QR CODE IS MISSING IN HTML!\n";
}
