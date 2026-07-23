<?php
// verify-applications.service.php - Service class to verify application completeness and eligibility

class VerifyApplicationsService {
    
    /**
     * Checks if all required student profile fields are present and not empty.
     * @param array $studentData The student profile details row
     * @return array Array with 'valid' (bool) and 'errors' (array)
     */
    public function checkCompleteness(array $studentData) {
        $requiredFields = ['prn_number', 'roll_number', 'department', 'class', 'mobile', 'address'];
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($studentData[$field]) || trim($studentData[$field]) === '') {
                $errors[] = "Field '" . str_replace('_', ' ', $field) . "' is required and cannot be empty.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validates student eligibility based on format constraints (PRN, Email, Mobile).
     * @param array $studentData
     * @param array $userData
     * @return array Array with 'valid' (bool) and 'errors' (array)
     */
    public function checkEligibility(array $studentData, array $userData) {
        $errors = [];
        
        // 1. Validate Mobile Number (Must be numeric and between 10 to 15 digits)
        if (isset($studentData['mobile'])) {
            $mobile = preg_replace('/[^0-9]/', '', $studentData['mobile']);
            if (strlen($mobile) < 10 || strlen($mobile) > 15) {
                $errors[] = "Mobile number must be a valid numeric contact number (10-15 digits).";
            }
        }
        
        // 2. Validate PRN Format (Must start with letters e.g. PRN, followed by numeric digits)
        if (isset($studentData['prn_number'])) {
            if (!preg_match('/^[A-Z]{3}\d{4,10}$/i', $studentData['prn_number'])) {
                $errors[] = "PRN number format is invalid. Must start with a 3-letter prefix (e.g. PRN) followed by 4-10 digits.";
            }
        }
        
        // 3. Validate Email format
        if (isset($userData['email'])) {
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email format is invalid.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
