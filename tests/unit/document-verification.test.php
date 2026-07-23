<?php
// tests/unit/document-verification.test.php
// Unit tests for DocumentVerificationService

require_once dirname(dirname(__DIR__)) . '/src/services/document-verification.service.php';
require_once dirname(dirname(__DIR__)) . '/config/app-config.php';

class DocumentVerificationTest {
    private DocumentVerificationService $service;
    private string $tmpDir;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() {
        $this->service = new DocumentVerificationService();
        $this->tmpDir  = sys_get_temp_dir() . '/bp_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    public function __destruct() {
        // Cleanup temp files
        foreach (glob($this->tmpDir . '/*') as $f) @unlink($f);
        @rmdir($this->tmpDir);
    }

    private function createFakeFile(string $name, int $sizeBytes = 1024): string {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, str_repeat('X', $sizeBytes));
        return $path;
    }

    private function assert($condition, $testName) {
        if ($condition) { echo "  ✅ PASS: $testName\n"; $this->passed++; }
        else             { echo "  ❌ FAIL: $testName\n"; $this->failed++; }
    }

    public function test_validFile_passesAllChecks() {
        $path   = $this->createFakeFile('college-id.png', 500 * 1024); // 500 KB
        $result = $this->service->validateDocumentFile($path, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($result['valid'] === true, 'valid PNG file within size → passes');
    }

    public function test_nonexistentFile_fails() {
        $result = $this->service->validateDocumentFile('/nonexistent/path/file.pdf', ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($result['valid'] === false, 'non-existent file → fails validation');
        $this->assert(str_contains($result['message'], 'not found'), 'non-existent file → correct error message');
    }

    public function test_oversizedFile_fails() {
        $path   = $this->createFakeFile('big.jpg', MAX_DOC_SIZE_BYTES + 1); // exceeds limit
        $result = $this->service->validateDocumentFile($path, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($result['valid'] === false, 'oversized file → fails validation');
        $this->assert(str_contains($result['message'], 'exceeds'), 'oversized file → correct error message');
    }

    public function test_invalidExtension_fails() {
        $path   = $this->createFakeFile('document.exe', 100);
        $result = $this->service->validateDocumentFile($path, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($result['valid'] === false, 'invalid extension .exe → fails validation');
    }

    public function test_pdfFile_passes() {
        $path   = $this->createFakeFile('address-proof.pdf', 200 * 1024);
        $result = $this->service->validateDocumentFile($path, ALLOWED_DOC_EXTENSIONS, MAX_DOC_SIZE_BYTES);
        $this->assert($result['valid'] === true, 'PDF file within limits → passes');
    }

    public function test_recordVerificationStatus_invalidDocType_throwsException() {
        $pdo = new PDO('sqlite::memory:');
        $thrown = false;
        try {
            $this->service->recordVerificationStatus($pdo, 1, 'invalid_type', 'verified', '', 1);
        } catch (InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assert($thrown, 'invalid doc_type → throws InvalidArgumentException');
    }

    public function test_recordVerificationStatus_invalidStatus_throwsException() {
        $pdo = new PDO('sqlite::memory:');
        $thrown = false;
        try {
            $this->service->recordVerificationStatus($pdo, 1, 'college_id', 'unknown_status', '', 1);
        } catch (InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assert($thrown, 'invalid status → throws InvalidArgumentException');
    }

    public function run() {
        echo "\n📋 DocumentVerificationService Tests:\n";
        $this->test_validFile_passesAllChecks();
        $this->test_nonexistentFile_fails();
        $this->test_oversizedFile_fails();
        $this->test_invalidExtension_fails();
        $this->test_pdfFile_passes();
        $this->test_recordVerificationStatus_invalidDocType_throwsException();
        $this->test_recordVerificationStatus_invalidStatus_throwsException();
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
