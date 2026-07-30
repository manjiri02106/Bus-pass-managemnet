<?php
// request-corrections.service.php - Service class to handle correction tasks and block workflows until resolved

class RequestCorrectionsService {
    
    /**
     * Registers correction requests for specific fields and pauses routing.
     * @param PDO $pdo
     * @param int $applicationId
     * @param array $corrections Assoc array of [field_name => instruction]
     * @param int $officerId
     * @return bool
     */
    public function createCorrectionRequests(PDO $pdo, $applicationId, array $corrections, $officerId) {
        $validFields = ['address', 'college_id_doc', 'photograph_doc', 'address_proof_doc'];
        $inserted = 0;
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO correction_requests (application_id, field_name, instruction, status, requested_by) 
                VALUES (?, ?, ?, 'pending', ?)
            ");
            
            foreach ($corrections as $field => $instruction) {
                if (in_array($field, $validFields) && !empty($instruction)) {
                    $stmt->execute([$applicationId, $field, $instruction, $officerId]);
                    $inserted++;
                }
            }
            
            if ($inserted > 0) {
                // Update applications status to correction_required
                $stmt_app = $pdo->prepare("UPDATE applications SET status = 'correction_required', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_app->execute([$applicationId]);
            }
            
            $pdo->commit();
            return $inserted > 0;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    
    /**
     * Resolves a correction request and shifts status back once all corrections are completed.
     * @param PDO $pdo
     * @param int $applicationId
     * @param string $field The resolved field
     * @return bool
     */
    public function resolveFieldCorrection(PDO $pdo, $applicationId, $field) {
        try {
            $stmt = $pdo->prepare("
                UPDATE correction_requests 
                SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP 
                WHERE application_id = ? AND field_name = ? AND status = 'pending'
            ");
            $stmt->execute([$applicationId, $field]);
            
            // Check if there are any remaining pending corrections for this application
            if (!$this->hasPendingCorrections($pdo, $applicationId)) {
                // Transition application status back to under_verification
                $stmt_app = $pdo->prepare("UPDATE applications SET status = 'under_verification', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_app->execute([$applicationId]);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Checks if there are unresolved correction requests.
     * @param PDO $pdo
     * @param int $applicationId
     * @return bool
     */
    public function hasPendingCorrections(PDO $pdo, $applicationId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM correction_requests WHERE application_id = ? AND status = 'pending'");
        $stmt->execute([$applicationId]);
        return $stmt->fetchColumn() > 0;
    }
}
?>
