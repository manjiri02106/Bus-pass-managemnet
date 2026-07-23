<?php
// tests/integration/workflow.test.php
// End-to-end integration test covering the full 5-feature workflow:
// Submit → Verify (4.1) → Doc Check (4.2) → Route Validation (4.3)
// → Request Correction (4.5) → Resolve → Approve & Issue Pass (4.4)

require_once dirname(dirname(__DIR__)) . '/src/services/verify-applications.service.php';
require_once dirname(dirname(__DIR__)) . '/src/services/document-verification.service.php';
require_once dirname(dirname(__DIR__)) . '/src/services/route-validation.service.php';
require_once dirname(dirname(__DIR__)) . '/src/services/approve-reject.service.php';
require_once dirname(dirname(__DIR__)) . '/src/services/request-corrections.service.php';
require_once dirname(dirname(__DIR__)) . '/config/app-config.php';

class WorkflowIntegrationTest {
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() {
        $this->pdo = $this->buildSchema();
    }

    private function assert($condition, $testName) {
        if ($condition) { echo "  ✅ PASS: $testName\n"; $this->passed++; }
        else             { echo "  ❌ FAIL: $testName\n"; $this->failed++; }
    }

    private function buildSchema(): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY, username TEXT, full_name TEXT, role TEXT, email TEXT, password TEXT
            );
            CREATE TABLE students (
                id INTEGER PRIMARY KEY, user_id INTEGER, prn_number TEXT, roll_number TEXT,
                department TEXT, class TEXT, mobile TEXT, address TEXT
            );
            CREATE TABLE routes (
                id INTEGER PRIMARY KEY, route_number TEXT, source TEXT, destination TEXT,
                stops TEXT, distance REAL, bus_number TEXT, status TEXT DEFAULT 'active'
            );
            CREATE TABLE applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INTEGER, route_id INTEGER,
                status TEXT DEFAULT 'submitted',
                college_id_doc TEXT, photograph_doc TEXT, address_proof_doc TEXT,
                routing_dept TEXT DEFAULT 'Transport Dept',
                assigned_officer_id INTEGER,
                last_updated TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE document_verifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER, document_type TEXT,
                status TEXT DEFAULT 'pending', comments TEXT, verified_by INTEGER,
                verified_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE route_validation_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER, route_id INTEGER,
                is_valid INTEGER, validation_notes TEXT, checked_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE correction_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER, field_name TEXT,
                instruction TEXT, status TEXT DEFAULT 'pending', requested_by INTEGER,
                requested_at TEXT DEFAULT CURRENT_TIMESTAMP, resolved_at TEXT
            );
            CREATE TABLE decisions (
                id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER,
                decision TEXT, reason TEXT, officer_id INTEGER,
                decided_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE passes (
                id INTEGER PRIMARY KEY AUTOINCREMENT, application_id INTEGER UNIQUE,
                pass_number TEXT UNIQUE, valid_from TEXT, valid_to TEXT,
                qr_code TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed mock data
        $pdo->exec("INSERT INTO users VALUES (1,'admin','System Admin','admin','a@c.edu','x'), (2,'officer1','Officer One','officer','o@c.edu','x'), (3,'student1','Alice Smith','student','s@c.edu','x')");
        $pdo->exec("INSERT INTO students VALUES (1, 3, 'PRN20001', 'CS-401', 'Computer Science', 'Final Year', '9876543210', 'Flat 5, Pune')");
        $pdo->exec("INSERT INTO routes VALUES (1,'R-101','Station','College Campus','Station,Sec4,Campus',12.5,'MH-12-AB-1234','active')");
        $pdo->exec("INSERT INTO routes VALUES (2,'R-103','Ring Road','College Campus','Ring,Toll,Bypass,Campus',58.5,'MH-12-CD-9012','active')");

        return $pdo;
    }

    private function insertApplication(int $routeId = 1): int {
        $this->pdo->prepare("
            INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept)
            VALUES (1, ?, 'submitted', 'uploads/id.png', 'uploads/photo.jpg', 'uploads/addr.pdf', 'Transport Dept')
        ")->execute([$routeId]);
        return (int)$this->pdo->lastInsertId();
    }

    // ─── Step 1: 4.1 Verify Application ──────────────────────────────────────
    public function step1_verifyApplicationCompleteness() {
        echo "\n  STEP 1 — 4.1 Verify Applications\n";
        $service = new VerifyApplicationsService();

        $studentData = ['prn_number' => 'PRN20001', 'roll_number' => 'CS-401', 'department' => 'Computer Science', 'class' => 'Final Year', 'mobile' => '9876543210', 'address' => 'Flat 5, Pune'];
        $userData    = ['email' => 's@c.edu'];

        $comp = $service->checkCompleteness($studentData);
        $elig = $service->checkEligibility($studentData, $userData);

        $this->assert($comp['valid'] === true, '[4.1] Completeness check passes for fully filled student');
        $this->assert($elig['valid'] === true, '[4.1] Eligibility check passes for valid PRN/email/mobile');
    }

    // ─── Step 2: 4.2 Document Verification ───────────────────────────────────
    public function step2_documentVerification(int $appId) {
        echo "\n  STEP 2 — 4.2 Document Verification\n";
        $service = new DocumentVerificationService();
        $tmpDir  = sys_get_temp_dir();

        // Create mock valid file
        $validFile = $tmpDir . '/college_id_test.png';
        file_put_contents($validFile, str_repeat('A', 500 * 1024)); // 500 KB

        $autoCheck = $service->validateDocumentFile($validFile, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($autoCheck['valid'] === true, '[4.2] Auto file check: valid PNG 500KB → passes');

        // Verify college_id in DB
        $result = $service->recordVerificationStatus($this->pdo, $appId, 'college_id', 'verified', 'Clear scan confirmed.', 2);
        $this->assert($result === true, '[4.2] recordVerificationStatus college_id → inserted');

        // Flag photograph as invalid
        $service->recordVerificationStatus($this->pdo, $appId, 'photograph', 'invalid', 'Blurred image, re-upload needed.', 2);
        $photoStatus = $this->pdo->query("SELECT status FROM document_verifications WHERE application_id=$appId AND document_type='photograph'")->fetchColumn();
        $this->assert($photoStatus === 'invalid', '[4.2] Photograph flagged as invalid in DB');

        @unlink($validFile);
    }

    // ─── Step 3: 4.3 Route Validation ────────────────────────────────────────
    public function step3_routeValidation(int $appId) {
        echo "\n  STEP 3 — 4.3 Route Validation\n";
        $service   = new RouteValidationService();
        $route     = $this->pdo->query("SELECT distance FROM routes WHERE id=1")->fetchColumn(); // 12.5 km

        $valid = $service->validateDistance($route, MAX_ROUTE_DISTANCE);
        $this->assert($valid === true, '[4.3] Standard route 12.5km → within limit');

        $dept  = $service->determineRoutingQueue($route, MAX_ROUTE_DISTANCE);
        $this->assert($dept === 'Transport Dept', '[4.3] Route queue determined as Transport Dept');

        $service->recordValidationLog($this->pdo, $appId, 1, true, 'Distance validation passed.');
        $logCount = $this->pdo->query("SELECT COUNT(*) FROM route_validation_logs WHERE application_id=$appId")->fetchColumn();
        $this->assert((int)$logCount === 1, '[4.3] Route validation log inserted');

        // Long-route scenario
        $longAppId = $this->insertApplication(routeId: 2);
        $longDist  = $this->pdo->query("SELECT distance FROM routes WHERE id=2")->fetchColumn(); // 58.5 km
        $longValid = $service->validateDistance($longDist, MAX_ROUTE_DISTANCE);
        $longDept  = $service->determineRoutingQueue($longDist, MAX_ROUTE_DISTANCE);
        $this->assert($longValid === false, '[4.3] Long-haul route 58.5km → exceeds limit');
        $this->assert($longDept  === 'Admin Exception', '[4.3] Long-haul route → dispatches to Admin Exception');

        return $appId;
    }

    // ─── Step 4: 4.5 Request Corrections (document failure triggered) ─────────
    public function step4_requestCorrections(int $appId) {
        echo "\n  STEP 4 — 4.5 Request Corrections\n";
        $service     = new RequestCorrectionsService();
        $corrections = ['photograph_doc' => 'Upload a clear passport-format photograph.'];

        $result = $service->createCorrectionRequests($this->pdo, $appId, $corrections, 2);
        $this->assert($result === true, '[4.5] Correction request created for photograph_doc');

        $status = $this->pdo->query("SELECT status FROM applications WHERE id=$appId")->fetchColumn();
        $this->assert($status === 'correction_required', '[4.5] Application status paused at correction_required');

        $has_pending = $service->hasPendingCorrections($this->pdo, $appId);
        $this->assert($has_pending === true, '[4.5] hasPendingCorrections → true (routing/decision blocked)');

        // Student resolves the correction
        $service->resolveFieldCorrection($this->pdo, $appId, 'photograph_doc');
        $finalStatus = $this->pdo->query("SELECT status FROM applications WHERE id=$appId")->fetchColumn();
        $this->assert($finalStatus === 'under_verification', '[4.5] After resolution → application transitions back to under_verification');

        $still_pending = $service->hasPendingCorrections($this->pdo, $appId);
        $this->assert($still_pending === false, '[4.5] hasPendingCorrections → false after all resolved (unblocked)');
    }

    // ─── Step 5: 4.4 Approve / Reject ────────────────────────────────────────
    public function step5_approveAndReject(int $appId) {
        echo "\n  STEP 5 — 4.4 Approve / Reject Applications\n";
        $service = new ApproveRejectService();

        // Approve
        $result = $service->approveApplication($this->pdo, $appId, 6, 'All criteria met, pass issued.', 2);
        $this->assert($result['success'] === true, '[4.4] Application approved → success');
        $this->assert(str_starts_with($result['pass_number'], 'BP-'), '[4.4] Pass number generated with BP- prefix');

        $appStatus = $this->pdo->query("SELECT status FROM applications WHERE id=$appId")->fetchColumn();
        $this->assert($appStatus === 'approved', '[4.4] Application status = approved after decision');

        $pass = $this->pdo->query("SELECT * FROM passes WHERE application_id=$appId")->fetch();
        $this->assert($pass !== false, '[4.4] Pass record exists in DB');
        $this->assert(!empty($pass['qr_code']), '[4.4] QR code content stored in pass record');

        // Test reject on a separate application
        $rejectAppId = $this->insertApplication();
        $rejectResult = $service->rejectApplication($this->pdo, $rejectAppId, 'Fraudulent college ID detected.', 2);
        $this->assert($rejectResult['success'] === true, '[4.4] Rejection → success');

        $rejStatus = $this->pdo->query("SELECT status FROM applications WHERE id=$rejectAppId")->fetchColumn();
        $this->assert($rejStatus === 'rejected', '[4.4] Application status = rejected after decision');

        $decision = $this->pdo->query("SELECT * FROM decisions WHERE application_id=$rejectAppId")->fetch();
        $this->assert($decision['decision'] === 'reject', '[4.4] Decision record logs reject decision');
        $this->assert(str_contains($decision['reason'], 'Fraudulent'), '[4.4] Rejection reason correctly stored');
    }

    public function run() {
        echo "\n🔗 INTEGRATION TEST — Full 5-Feature Workflow\n";

        $appId = $this->insertApplication();

        $this->step1_verifyApplicationCompleteness();
        $this->step2_documentVerification($appId);
        $this->step3_routeValidation($appId);
        $this->step4_requestCorrections($appId);
        $this->step5_approveAndReject($appId);

        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
