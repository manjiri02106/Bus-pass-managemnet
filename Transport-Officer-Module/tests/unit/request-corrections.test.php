<?php
// tests/unit/request-corrections.test.php
// Unit tests for RequestCorrectionsService

require_once dirname(dirname(__DIR__)) . '/src/services/request-corrections.service.php';

class RequestCorrectionsTest {
    private RequestCorrectionsService $service;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() { $this->service = new RequestCorrectionsService(); }

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
                status TEXT DEFAULT 'submitted',
                last_updated TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE correction_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER,
                field_name TEXT,
                instruction TEXT,
                status TEXT DEFAULT 'pending',
                requested_by INTEGER,
                requested_at TEXT DEFAULT CURRENT_TIMESTAMP,
                resolved_at TEXT
            );
        ");
        $pdo->exec("INSERT INTO applications (id, status) VALUES (1, 'under_verification')");
        return $pdo;
    }

    public function test_createCorrectionRequests_validFields_insertsRows() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => 'Please provide your full address.', 'college_id_doc' => 'Upload a clearer photo.'];
        $result      = $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $this->assert($result === true, 'createCorrectionRequests → returns true');
        $count = $pdo->query("SELECT COUNT(*) FROM correction_requests")->fetchColumn();
        $this->assert((int)$count === 2, 'createCorrectionRequests → 2 correction rows inserted');
    }

    public function test_createCorrectionRequests_setsApplicationStatus() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => 'Full address required.'];
        $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $status = $pdo->query("SELECT status FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($status === 'correction_required', 'createCorrectionRequests → application status = correction_required');
    }

    public function test_createCorrectionRequests_emptyInstructions_skipsRow() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => '', 'college_id_doc' => 'Upload clearer image.'];
        $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $count = $pdo->query("SELECT COUNT(*) FROM correction_requests")->fetchColumn();
        $this->assert((int)$count === 1, 'createCorrectionRequests → empty instruction skipped');
    }

    public function test_hasPendingCorrections_withPending_returnsTrue() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => 'Provide correct address.'];
        $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $result = $this->service->hasPendingCorrections($pdo, 1);
        $this->assert($result === true, 'hasPendingCorrections with pending tasks → returns true');
    }

    public function test_hasPendingCorrections_noPending_returnsFalse() {
        $pdo    = $this->makeMemoryPdo();
        $result = $this->service->hasPendingCorrections($pdo, 1);
        $this->assert($result === false, 'hasPendingCorrections with no tasks → returns false');
    }

    public function test_resolveFieldCorrection_withSinglePending_transitionsToUnderVerification() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => 'Provide full address.'];
        $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $this->service->resolveFieldCorrection($pdo, 1, 'address');
        $status = $pdo->query("SELECT status FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($status === 'under_verification', 'resolveFieldCorrection (last pending) → status transitions to under_verification');
    }

    public function test_resolveFieldCorrection_withMultiplePending_keepsCorrectionRequired() {
        $pdo         = $this->makeMemoryPdo();
        $corrections = ['address' => 'Full address.', 'college_id_doc' => 'Clearer ID.'];
        $this->service->createCorrectionRequests($pdo, 1, $corrections, 2);
        $this->service->resolveFieldCorrection($pdo, 1, 'address'); // resolve only one
        $status = $pdo->query("SELECT status FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($status === 'correction_required', 'resolveFieldCorrection (remaining pending) → status stays correction_required');
    }

    public function run() {
        echo "\n📋 RequestCorrectionsService Tests:\n";
        $this->test_createCorrectionRequests_validFields_insertsRows();
        $this->test_createCorrectionRequests_setsApplicationStatus();
        $this->test_createCorrectionRequests_emptyInstructions_skipsRow();
        $this->test_hasPendingCorrections_withPending_returnsTrue();
        $this->test_hasPendingCorrections_noPending_returnsFalse();
        $this->test_resolveFieldCorrection_withSinglePending_transitionsToUnderVerification();
        $this->test_resolveFieldCorrection_withMultiplePending_keepsCorrectionRequired();
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
