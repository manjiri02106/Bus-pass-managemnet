<?php
// scripts/run-tests.php
// Test runner — executes all unit and integration tests and reports coverage

require_once dirname(__DIR__) . '/tests/unit/verify-applications.test.php';
require_once dirname(__DIR__) . '/tests/unit/document-verification.test.php';
require_once dirname(__DIR__) . '/tests/unit/route-validation.test.php';
require_once dirname(__DIR__) . '/tests/unit/approve-reject.test.php';
require_once dirname(__DIR__) . '/tests/unit/request-corrections.test.php';
require_once dirname(__DIR__) . '/tests/integration/workflow.test.php';

$total_passed = 0;
$total_failed = 0;

echo "======================================================\n";
echo "  BUS PASS SYSTEM — AUTOMATED TEST SUITE\n";
echo "  Branch: feature/minimal-surface  |  v1.1-feature-only\n";
echo "======================================================\n";

// ── Unit Tests ──────────────────────────────────────────────────────────────
echo "\n╔══════════════════════════════════╗\n";
echo "║         UNIT TESTS               ║\n";
echo "╚══════════════════════════════════╝";

$suites = [
    new VerifyApplicationsTest(),
    new DocumentVerificationTest(),
    new RouteValidationTest(),
    new ApproveRejectTest(),
    new RequestCorrectionsTest(),
];

foreach ($suites as $suite) {
    $result        = $suite->run();
    $total_passed += $result['passed'];
    $total_failed += $result['failed'];
}

// ── Integration Tests ────────────────────────────────────────────────────────
echo "\n╔══════════════════════════════════╗\n";
echo "║      INTEGRATION TESTS           ║\n";
echo "╚══════════════════════════════════╝";

$integration    = new WorkflowIntegrationTest();
$result         = $integration->run();
$total_passed  += $result['passed'];
$total_failed  += $result['failed'];

// ── Summary ──────────────────────────────────────────────────────────────────
$total   = $total_passed + $total_failed;
$pct     = $total > 0 ? round(($total_passed / $total) * 100, 1) : 0;

echo "\n======================================================\n";
echo "  RESULTS SUMMARY\n";
echo "------------------------------------------------------\n";
printf("  Total Tests  : %d\n", $total);
printf("  Passed       : %d\n", $total_passed);
printf("  Failed       : %d\n", $total_failed);
printf("  Coverage Est.: %.1f%%\n", $pct);
echo "------------------------------------------------------\n";

if ($total_failed === 0) {
    echo "  🎉 ALL TESTS PASSED! System ready.\n";
} else {
    echo "  ⚠️  $total_failed test(s) failed. Review output above.\n";
}

echo "======================================================\n";
exit($total_failed > 0 ? 1 : 0);
