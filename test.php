<?php
/**
 * Test Script for Bus Pass Management System
 * This script tests all major components
 */

require_once 'config.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/PassNumberGenerator.php';
require_once 'classes/BusPass.php';
require_once 'classes/QRCodeGenerator.php';
require_once 'classes/PassDownloadManager.php';
require_once 'classes/RenewalRequest.php';
require_once 'classes/RenewalApprovalWorkflow.php';

echo "=== Bus Pass Management System - Test Suite ===\n\n";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: Database Connection
echo "Test 1: Database Connection\n";
try {
    global $db;
    if ($db && $db->getConnection()) {
        echo "✓ Database connection successful\n";
        $tests_passed++;
    } else {
        echo "✗ Database connection failed\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 2: Pass Number Generation
echo "Test 2: Pass Number Generation\n";
try {
    $passGen = new PassNumberGenerator();
    $passNum1 = $passGen->generatePassNumber();
    $passNum2 = $passGen->generateSequentialPassNumber();
    $passNum3 = $passGen->generateCustomPassNumber('annual');
    
    if ($passGen->validatePassNumber($passNum1)) {
        echo "✓ Pass number generation working\n";
        echo "  - Format 1: $passNum1\n";
        echo "  - Format 2: $passNum2\n";
        echo "  - Format 3: $passNum3\n";
        $tests_passed++;
    } else {
        echo "✗ Pass number validation failed\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "✗ Pass number generation failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 3: User Creation
echo "Test 3: User Creation\n";
try {
    $user = new User();
    $result = $user->createUser([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test' . time() . '@example.com',
        'phone' => '98765' . rand(10000, 99999),
        'address' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'postal_code' => '12345',
        'user_type' => 'student'
    ]);
    
    if ($result['success']) {
        echo "✓ User creation successful\n";
        echo "  User ID: " . $result['user_id'] . "\n";
        $test_user_id = $result['user_id'];
        $tests_passed++;
    } else {
        echo "✗ User creation failed: " . $result['error'] . "\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "✗ User creation failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 4: Bus Pass Creation
echo "Test 4: Bus Pass Creation\n";
try {
    if (isset($test_user_id)) {
        $busPass = new BusPass();
        $passGen = new PassNumberGenerator();
        $passNumber = $passGen->generatePassNumber();
        
        $result = $busPass->createPass($test_user_id, $passNumber, 'monthly', 30);
        
        if ($result['success']) {
            echo "✓ Bus pass creation successful\n";
            echo "  Pass ID: " . $result['pass_id'] . "\n";
            echo "  Pass Number: " . $result['pass_number'] . "\n";
            echo "  Issue Date: " . $result['issue_date'] . "\n";
            echo "  Expiry Date: " . $result['expiry_date'] . "\n";
            $test_pass_id = $result['pass_id'];
            $tests_passed++;
        } else {
            echo "✗ Bus pass creation failed: " . $result['error'] . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo "✗ Bus pass creation failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 5: QR Code Generation
echo "Test 5: QR Code Generation (Online API)\n";
try {
    if (isset($test_pass_id) && isset($test_user_id)) {
        $qrGen = new QRCodeGenerator();
        $busPass = new BusPass();
        $pass = $busPass->getPassDetails($test_pass_id);
        
        $result = $qrGen->generateQRCode($pass['pass_number'], $test_user_id);
        
        if ($result['success']) {
            echo "✓ QR code generation successful\n";
            echo "  File: " . $result['file_name'] . "\n";
            $tests_passed++;
        } else {
            echo "✗ QR code generation failed: " . $result['error'] . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo "✗ QR code generation failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 6: Renewal Request Creation
echo "Test 6: Renewal Request Creation\n";
try {
    if (isset($test_pass_id) && isset($test_user_id)) {
        $renewal = new RenewalRequest();
        $result = $renewal->createRenewalRequest($test_pass_id, $test_user_id);
        
        if ($result['success']) {
            echo "✓ Renewal request creation successful\n";
            echo "  Request ID: " . $result['request_id'] . "\n";
            echo "  Status: Pending\n";
            $test_renewal_id = $result['request_id'];
            $tests_passed++;
        } else {
            echo "✗ Renewal request creation failed: " . $result['error'] . "\n";
            $tests_failed++;
        }
    }
} catch (Exception $e) {
    echo "✗ Renewal request creation failed: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 7: Get Pass Statistics
echo "Test 7: Get Pass Statistics\n";
try {
    $busPass = new BusPass();
    $stats = $busPass->getPassStatistics();
    
    echo "✓ Pass statistics retrieved\n";
    echo "  Total Passes: " . $stats['total_passes'] . "\n";
    echo "  Active Passes: " . $stats['active_passes'] . "\n";
    echo "  Expired Passes: " . $stats['expired_passes'] . "\n";
    echo "  Expiring Soon: " . $stats['expiring_soon'] . "\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "✗ Failed to get statistics: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Test 8: Get Renewal Statistics
echo "Test 8: Get Renewal Statistics\n";
try {
    $renewal = new RenewalRequest();
    $stats = $renewal->getRenewalStatistics();
    
    echo "✓ Renewal statistics retrieved\n";
    echo "  Total Requests: " . $stats['total_requests'] . "\n";
    echo "  Pending Requests: " . $stats['pending_requests'] . "\n";
    echo "  Approved Requests: " . $stats['approved_requests'] . "\n";
    echo "  Rejected Requests: " . $stats['rejected_requests'] . "\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "✗ Failed to get renewal statistics: " . $e->getMessage() . "\n";
    $tests_failed++;
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Tests Passed: ✓ $tests_passed\n";
echo "Tests Failed: ✗ $tests_failed\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n✓ All tests passed! System is working correctly.\n";
} else {
    echo "\n✗ Some tests failed. Please check the errors above.\n";
}
?>
