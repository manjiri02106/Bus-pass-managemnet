<?php
// tests/unit/verify-applications.test.php
// Unit tests for VerifyApplicationsService

require_once dirname(dirname(__DIR__)) . '/src/services/verify-applications.service.php';

class VerifyApplicationsTest {
    private VerifyApplicationsService $service;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() { $this->service = new VerifyApplicationsService(); }

    private function assert($condition, $testName) {
        if ($condition) {
            echo "  ✅ PASS: $testName\n";
            $this->passed++;
        } else {
            echo "  ❌ FAIL: $testName\n";
            $this->failed++;
        }
    }

    public function test_completeness_allFieldsPresent_returnsValid() {
        $student = ['prn_number' => 'PRN20001', 'roll_number' => 'CS-101', 'department' => 'CS',
                    'class' => 'FY', 'mobile' => '9876543210', 'address' => '123 Main Street'];
        $result = $this->service->checkCompleteness($student);
        $this->assert($result['valid'] === true, 'completeness: all fields present → valid');
        $this->assert(empty($result['errors']), 'completeness: no errors returned for complete profile');
    }

    public function test_completeness_missingMobile_returnsError() {
        $student = ['prn_number' => 'PRN20001', 'roll_number' => 'CS-101', 'department' => 'CS',
                    'class' => 'FY', 'mobile' => '', 'address' => '123 Main Street'];
        $result = $this->service->checkCompleteness($student);
        $this->assert($result['valid'] === false, 'completeness: missing mobile → invalid');
        $this->assert(count($result['errors']) === 1, 'completeness: exactly 1 error for 1 missing field');
    }

    public function test_completeness_multipleFieldsMissing_returnsAllErrors() {
        $student = ['prn_number' => '', 'roll_number' => '', 'department' => 'CS',
                    'class' => 'FY', 'mobile' => '', 'address' => ''];
        $result = $this->service->checkCompleteness($student);
        $this->assert($result['valid'] === false, 'completeness: multiple missing fields → invalid');
        $this->assert(count($result['errors']) === 4, 'completeness: 4 errors for 4 missing fields');
    }

    public function test_eligibility_validInputs_returnsValid() {
        $student = ['prn_number' => 'PRN20001', 'mobile' => '9876543210'];
        $user    = ['email' => 'student@college.edu'];
        $result  = $this->service->checkEligibility($student, $user);
        $this->assert($result['valid'] === true, 'eligibility: valid PRN, mobile, email → valid');
    }

    public function test_eligibility_invalidPRNFormat_returnsError() {
        $student = ['prn_number' => 'BADFORMAT', 'mobile' => '9876543210'];
        $user    = ['email' => 'student@college.edu'];
        $result  = $this->service->checkEligibility($student, $user);
        $this->assert($result['valid'] === false, 'eligibility: malformed PRN → invalid');
    }

    public function test_eligibility_invalidEmail_returnsError() {
        $student = ['prn_number' => 'PRN20001', 'mobile' => '9876543210'];
        $user    = ['email' => 'not-an-email'];
        $result  = $this->service->checkEligibility($student, $user);
        $this->assert($result['valid'] === false, 'eligibility: invalid email format → invalid');
    }

    public function test_eligibility_shortMobile_returnsError() {
        $student = ['prn_number' => 'PRN20001', 'mobile' => '12345'];
        $user    = ['email' => 'student@college.edu'];
        $result  = $this->service->checkEligibility($student, $user);
        $this->assert($result['valid'] === false, 'eligibility: mobile too short → invalid');
    }

    public function run() {
        echo "\n📋 VerifyApplicationsService Tests:\n";
        $this->test_completeness_allFieldsPresent_returnsValid();
        $this->test_completeness_missingMobile_returnsError();
        $this->test_completeness_multipleFieldsMissing_returnsAllErrors();
        $this->test_eligibility_validInputs_returnsValid();
        $this->test_eligibility_invalidPRNFormat_returnsError();
        $this->test_eligibility_invalidEmail_returnsError();
        $this->test_eligibility_shortMobile_returnsError();
        return ['passed' => $this->passed, 'failed' => $this->failed];
    }
}
