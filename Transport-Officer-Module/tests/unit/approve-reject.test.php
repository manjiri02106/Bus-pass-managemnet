<?php
// tests/unit/approve-reject.test.php
// Unit tests for ApproveRejectService

require_once dirname(dirname(__DIR__)) . '/src/services/approve-reject.service.php';

class ApproveRejectTest {
    private ApproveRejectService $service;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() { $this->service = new ApproveRejectService(); }

    private function assert($condition, $testName) {
        if ($condition) { echo "  ✅ PASS: $testName\n"; $this->passed++; }
        else             { echo "  ❌ FAIL: $testName\n"; $this->failed++; }
    }

    private function makeMemoryPdo(string $initialStatus = 'submitted'): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("
            CREATE TABLE applications (
                id INTEGER PRIMARY KEY,
                status TEXT DEFAULT 'submitted',
                last_updated TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE decisions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER,
                decision TEXT,
                reason TEXT,
                officer_id INTEGER,
                decided_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE passes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                application_id INTEGER UNIQUE,
                pass_number TEXT UNIQUE,
                valid_from TEXT,
                valid_to TEXT,
                qr_code TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $pdo->exec("INSERT INTO applications (id, status) VALUES (1, '$initialStatus')");
        return $pdo;
    }

    public function test_approveApplication_validInput_returnsSuccess() {
        $pdo    = $this->makeMemoryPdo();
        $result = $this->service->approveApplication($pdo, 1, 6, 'Approved after full verification.', 2);
        $this->assert($result['success'] === true, 'approveApplication → returns success');
        $this->assert(!empty($result['pass_number']), 'approveApplication → pass number generated');
        $this->assert(str_starts_with($result['pass_number'], 'BP-'), 'approveApplication → pass number starts with BP-');
    }

    public function test_approveApplication_passRecordCreated() {
        $pdo = $this->makeMemoryPdo();
        $this->service->approveApplication($pdo, 1, 6, 'Approved.', 2);
        $pass = $pdo->query("SELECT * FROM passes WHERE application_id = 1")->fetch();
        $this->assert($pass !== false, 'approveApplication → pass record inserted in DB');
        $this->assert(!empty($pass['qr_code']), 'approveApplication → QR code content stored');
    }

    public function test_approveApplication_applicationStatusUpdated() {
        $pdo = $this->makeMemoryPdo();
        $this->service->approveApplication($pdo, 1, 6, 'Approved.', 2);
        $status = $pdo->query("SELECT status FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($status === 'approved', 'approveApplication → application status set to approved');
    }

    public function test_approveApplication_alreadyApproved_returnsFailure() {
        $pdo    = $this->makeMemoryPdo('approved');
        $result = $this->service->approveApplication($pdo, 1, 6, 'Try again.', 2);
        $this->assert($result['success'] === false, 'approveApplication on already-approved → returns failure');
        $this->assert(!empty($result['error']), 'approveApplication on already-approved → error message returned');
    }

    public function test_rejectApplication_validReason_returnsSuccess() {
        $pdo    = $this->makeMemoryPdo();
        $result = $this->service->rejectApplication($pdo, 1, 'Fraudulent document detected.', 2);
        $this->assert($result['success'] === true, 'rejectApplication with reason → returns success');
    }

    public function test_rejectApplication_applicationStatusUpdated() {
        $pdo = $this->makeMemoryPdo();
        $this->service->rejectApplication($pdo, 1, 'Fraudulent document detected.', 2);
        $status = $pdo->query("SELECT status FROM applications WHERE id = 1")->fetchColumn();
        $this->assert($status === 'rejected', 'rejectApplication → application status set to rejected');
    }

    public function test_rejectApplication_emptyReason_returnsFailure() {
        $pdo    = $this->makeMemoryPdo();
        $result = $this->service->rejectApplication($pdo, 1, '', 2);
        $this->assert($result['success'] === false, 'rejectApplication with empty reason → returns failure');
        $this->assert(!empty($result['error']), 'rejectApplication with empty reason → error message returned');
    }

    public function test_rejectApplication_alreadyRejected_returnsFailure() {
        $pdo    = $this->makeMemoryPdo('rejected');
        $result = $this->service->rejectApplication($pdo, 1, 'Another reason.', 2);
        $this->assert($result['success'] === false, 'rejectApplication on already-rejected → returns failure');
    }

    public function run() {
        echo "\n📋 ApproveRejectService Tests:\n";
        $this->test_approveApplication_validInput_returnsSuccess();
        $this->test_approveApplication_passRecordCreated();
        $this->test_approveApplication_applicationStatusUpdated();
        $this->test_approveApplication_alreadyApproved_returnsFailure();
        $this->test_rejectApplication_validReason_returnsSuccess();
        $this->test_rejectApplication_applicationStatusUpdated();
        $this->test_rejectApplication_emptyReason_returnsFailure();
        $this->test_rejectApplication_alreadyRejected_returnsFailure();
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
