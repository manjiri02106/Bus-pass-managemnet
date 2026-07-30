<?php
// tests/unit/route-validation.test.php
// Unit tests for RouteValidationService

require_once dirname(dirname(__DIR__)) . '/src/services/route-validation.service.php';
require_once dirname(dirname(__DIR__)) . '/config/app-config.php';

class RouteValidationTest {
    private RouteValidationService $service;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() { $this->service = new RouteValidationService(); }

    private function assert($condition, $testName) {
        if ($condition) { echo "  ✅ PASS: $testName\n"; $this->passed++; }
        else             { echo "  ❌ FAIL: $testName\n"; $this->failed++; }
    }

    private function makeMemoryPdo(): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("
            CREATE TABLE applications (
                id INTEGER PRIMARY KEY,
                routing_dept TEXT,
                last_updated TEXT
            );
            CREATE TABLE route_validation_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER,
                route_id INTEGER,
                is_valid INTEGER,
                validation_notes TEXT,
                checked_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $pdo->exec("INSERT INTO applications (id, routing_dept, last_updated) VALUES (1, 'Transport Dept', CURRENT_TIMESTAMP)");
        return $pdo;
    }

    public function test_validateDistance_withinLimit_returnsTrue() {
        $result = $this->service->validateDistance(25.0, MAX_ROUTE_DISTANCE);
        $this->assert($result === true, 'distance 25km within 50km limit → valid');
    }

    public function test_validateDistance_exactlyAtLimit_returnsTrue() {
        $result = $this->service->validateDistance(50.0, MAX_ROUTE_DISTANCE);
        $this->assert($result === true, 'distance exactly 50km → valid (boundary)');
    }

    public function test_validateDistance_exceedsLimit_returnsFalse() {
        $result = $this->service->validateDistance(75.5, MAX_ROUTE_DISTANCE);
        $this->assert($result === false, 'distance 75.5km exceeds 50km limit → invalid');
    }

    public function test_determineRoutingQueue_standardDistance_returnsTransportDept() {
        $dept = $this->service->determineRoutingQueue(30.0, MAX_ROUTE_DISTANCE);
        $this->assert($dept === 'Transport Dept', 'short distance → routes to Transport Dept');
    }

    public function test_determineRoutingQueue_longDistance_returnsAdminException() {
        $dept = $this->service->determineRoutingQueue(60.0, MAX_ROUTE_DISTANCE);
        $this->assert($dept === 'Admin Exception', 'long distance > 50km → routes to Admin Exception');
    }

    public function test_recordValidationLog_insertsRecord() {
        $pdo = $this->makeMemoryPdo();
        $result = $this->service->recordValidationLog($pdo, 1, 1, true, 'Standard validation passed.');
        $this->assert($result === true, 'recordValidationLog → inserts successfully');

        $count = $pdo->query("SELECT COUNT(*) FROM route_validation_logs")->fetchColumn();
        $this->assert((int)$count === 1, 'recordValidationLog → exactly 1 log row inserted');
    }

    public function test_dispatchWorkflowRoute_validDept_updatesApplication() {
        $pdo = $this->makeMemoryPdo();
        $result = $this->service->dispatchWorkflowRoute($pdo, 1, 'Academic HOD', 'Needs HOD sign-off', 1);
        $this->assert($result === true, 'dispatchWorkflowRoute → returns true');

        $dept = $pdo->query("SELECT routing_dept FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($dept === 'Academic HOD', 'dispatchWorkflowRoute → application routing_dept updated');
    }

    public function test_dispatchWorkflowRoute_invalidDept_throwsException() {
        $pdo = $this->makeMemoryPdo();
        $thrown = false;
        try {
            $this->service->dispatchWorkflowRoute($pdo, 1, 'Fake Dept', '', 1);
        } catch (InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assert($thrown, 'dispatchWorkflowRoute with invalid dept → throws InvalidArgumentException');
    }

    public function run() {
        echo "\n📋 RouteValidationService Tests:\n";
        $this->test_validateDistance_withinLimit_returnsTrue();
        $this->test_validateDistance_exactlyAtLimit_returnsTrue();
        $this->test_validateDistance_exceedsLimit_returnsFalse();
        $this->test_determineRoutingQueue_standardDistance_returnsTransportDept();
        $this->test_determineRoutingQueue_longDistance_returnsAdminException();
        $this->test_recordValidationLog_insertsRecord();
        $this->test_dispatchWorkflowRoute_validDept_updatesApplication();
        $this->test_dispatchWorkflowRoute_invalidDept_throwsException();
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
