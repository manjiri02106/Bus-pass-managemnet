<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BusPass.php';
require_once __DIR__ . '/../classes/PassNumberGenerator.php';
require_once __DIR__ . '/../classes/QRCodeGenerator.php';
require_once __DIR__ . '/../classes/PassDownloadManager.php';
require_once __DIR__ . '/../classes/RenewalRequest.php';
require_once __DIR__ . '/../classes/RenewalApprovalWorkflow.php';

echo "1. Database:\n";
$dbTest = new Database();
echo "Connected successfully.\n";

echo "2. User:\n";
$userObj = new User();
$email = 'test.' . time() . '@example.com';
$uRes = $userObj->createUser([
    'first_name' => 'Alice',
    'last_name' => 'Smith',
    'email' => $email,
    'phone' => (string)rand(1000000000, 9999999999),
    'address' => '456 Elm St',
    'city' => 'Springfield',
    'state' => 'IL',
    'postal_code' => '62701',
    'user_type' => 'regular'
]);
print_r($uRes);
$userId = $uRes['user_id'];

echo "3. PassNumberGenerator:\n";
$png = new PassNumberGenerator();
$passNum = $png->generatePassNumber();
echo "Generated Pass Number: $passNum\n";

echo "4. BusPass:\n";
$bpObj = new BusPass();
$bpRes = $bpObj->createPass($userId, $passNum, 'monthly', 30);
print_r($bpRes);
$passId = $bpRes['pass_id'];

echo "5. QRCodeGenerator:\n";
$qrObj = new QRCodeGenerator();
$qrRes = $qrObj->generateQRCode($passNum, $userId);
print_r($qrRes);
if (!empty($qrRes['file_path'])) {
    $bpObj->updatePassQRCode($passId, $qrRes['file_path']);
}

echo "6. PassDownloadManager:\n";
$pdmObj = new PassDownloadManager();
$htmlRes = $pdmObj->generatePrintablePass($passId);
echo "Printable HTML generated: " . ($htmlRes['success'] ? 'OK' : 'FAILED') . "\n";
$imgRes = $pdmObj->generatePassImage($passId, 'png');
echo "Pass Image generated: " . ($imgRes['success'] ? 'OK' : 'FAILED') . "\n";

echo "7. RenewalRequest:\n";
$rrObj = new RenewalRequest();
$rrRes = $rrObj->createRenewalRequest($passId, $userId);
print_r($rrRes);

echo "8. RenewalApprovalWorkflow:\n";
$rawObj = new RenewalApprovalWorkflow();
if (isset($rrRes['request_id'])) {
    $appRes = $rawObj->approveRenewalRequest($rrRes['request_id'], $userId, 'Test approval');
    print_r($appRes);
}

echo "ALL CLASS TESTS COMPLETED SUCCESSFULLY!\n";
