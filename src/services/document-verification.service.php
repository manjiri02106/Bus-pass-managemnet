<?php
// document-verification.service.php - Service class to validate documents against standards

class DocumentVerificationService {
    
    /**
     * Validates file size, format, and existence against predefined standards.
     * @param string $filepath Path to the document file (relative to root)
     * @param array $allowedExtensions Array of allowed file formats
     * @param int $maxSizeBytes Maximum size constraint
     * @return array Array with 'valid' (bool) and 'message' (string)
     */
    public function validateDocumentFile($filepath, array $allowedExtensions, $maxSizeBytes) {
        if (empty($filepath) || !file_exists($filepath)) {
            return [
                'valid' => false,
                'message' => 'Document file not found on server storage.'
            ];
        }
        
        $size = filesize($filepath);
        if ($size > $maxSizeBytes) {
            return [
                'valid' => false,
                'message' => 'File size (' . round($size / 1024 / 1024, 2) . 'MB) exceeds maximum limit of ' . ($maxSizeBytes / 1024 / 1024) . 'MB.'
            ];
        }
        
        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return [
                'valid' => false,
                'message' => 'File extension (.' . $ext . ') is not supported. Allowed formats: ' . implode(', ', $allowedExtensions) . '.'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Document meets size and format criteria.'
        ];
    }
    
    /**
     * Registers or updates a manual document review status in database.
     * @param PDO $pdo PDO connection object
     * @param int $applicationId
     * @param string $docType
     * @param string $status 'verified' or 'invalid'
     * @param string $comments
     * @param int $officerId
     * @return bool Success status
     */
    public function recordVerificationStatus(PDO $pdo, $applicationId, $docType, $status, $comments, $officerId) {
        if (!in_array($docType, ['college_id', 'photograph', 'address_proof'])) {
            throw new InvalidArgumentException("Invalid document type: $docType");
        }
        if (!in_array($status, ['verified', 'invalid', 'pending'])) {
            throw new InvalidArgumentException("Invalid verification status: $status");
        }
        
        $stmt = $pdo->prepare("SELECT id FROM document_verifications WHERE application_id = ? AND document_type = ?");
        $stmt->execute([$applicationId, $docType]);
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            $stmtUpd = $pdo->prepare("
                UPDATE document_verifications 
                SET status = ?, comments = ?, verified_by = ?, verified_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmtUpd->execute([$status, $comments, $officerId, $existingId]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO document_verifications (application_id, document_type, status, comments, verified_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmtIns->execute([$applicationId, $docType, $status, $comments, $officerId]);
        }
    }
}
?>
