<?php
// approve-reject.service.php - Service class to approve or reject applications and issue passes

class ApproveRejectService {
    
    /**
     * Approves the student application, records decision, and generates a digital pass.
     * @param PDO $pdo
     * @param int $applicationId
     * @param int $validityMonths Pass duration
     * @param string $notes Officer comments
     * @param int $officerId
     * @return array Array with 'success' (bool), 'pass_number' (string), and 'error' (string)
     */
    public function approveApplication(PDO $pdo, $applicationId, $validityMonths, $notes, $officerId) {
        try {
            // Check status of application
            $stmt = $pdo->prepare("SELECT status FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $status = $stmt->fetchColumn();
            
            if ($status === 'approved' || $status === 'rejected') {
                return ['success' => false, 'pass_number' => '', 'error' => 'Application decision has already been finalized.'];
            }
            
            $pdo->beginTransaction();
            
            // 1. Log Decision
            $stmt_dec = $pdo->prepare("INSERT INTO decisions (application_id, decision, reason, officer_id) VALUES (?, 'approve', ?, ?)");
            $stmt_dec->execute([$applicationId, $notes, $officerId]);
            
            // 2. Generate unique Pass Number
            $passNumber = $this->generatePassNumber($applicationId);
            $validFrom = date('Y-m-d');
            $validTo = date('Y-m-d', strtotime("+$validityMonths months"));
            $qrCodeContent = "PassNo:$passNumber|AppID:$applicationId|Expires:$validTo";
            
            // 3. Insert Pass record
            $stmt_pass = $pdo->prepare("
                INSERT INTO passes (application_id, pass_number, valid_from, valid_to, qr_code) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_pass->execute([$applicationId, $passNumber, $validFrom, $validTo, $qrCodeContent]);
            
            // 4. Update Application Status
            $stmt_app = $pdo->prepare("UPDATE applications SET status = 'approved', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_app->execute([$applicationId]);
            
            $pdo->commit();
            return [
                'success' => true,
                'pass_number' => $passNumber,
                'error' => ''
            ];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'pass_number' => '', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Rejects the application and logs the reason.
     * @param PDO $pdo
     * @param int $applicationId
     * @param string $reason Rejection feedback
     * @param int $officerId
     * @return array Array with 'success' (bool) and 'error' (string)
     */
    public function rejectApplication(PDO $pdo, $applicationId, $reason, $officerId) {
        if (empty($reason)) {
            return ['success' => false, 'error' => 'Rejection reason is required.'];
        }
        
        try {
            // Check status of application
            $stmt = $pdo->prepare("SELECT status FROM applications WHERE id = ?");
            $stmt->execute([$applicationId]);
            $status = $stmt->fetchColumn();
            
            if ($status === 'approved' || $status === 'rejected') {
                return ['success' => false, 'error' => 'Application decision has already been finalized.'];
            }
            
            $pdo->beginTransaction();
            
            // 1. Log Decision
            $stmt_dec = $pdo->prepare("INSERT INTO decisions (application_id, decision, reason, officer_id) VALUES (?, 'reject', ?, ?)");
            $stmt_dec->execute([$applicationId, $reason, $officerId]);
            
            // 2. Update Application Status
            $stmt_app = $pdo->prepare("UPDATE applications SET status = 'rejected', last_updated = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_app->execute([$applicationId]);
            
            $pdo->commit();
            return ['success' => true, 'error' => ''];
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generates a standardized pass number format.
     */
    private function generatePassNumber($applicationId) {
        return 'BP-' . date('Y') . '-' . sprintf('%06d', $applicationId);
    }
}
?>
