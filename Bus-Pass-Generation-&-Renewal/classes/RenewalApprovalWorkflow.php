<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';
require_once dirname(__FILE__) . '/RenewalRequest.php';
require_once dirname(__FILE__) . '/BusPass.php';

class RenewalApprovalWorkflow {
    private $db;
    private $renewalRequest;
    private $busPass;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->renewalRequest = new RenewalRequest();
        $this->busPass = new BusPass();
    }
    
    /**
     * Approve renewal request and renew the pass
     */
    public function approveRenewalRequest($requestId, $approverId, $comments = null) {
        try {
            // Get renewal request details
            $request = $this->renewalRequest->getRenewalRequest($requestId);
            
            if (!$request) {
                return array('success' => false, 'error' => 'Renewal request not found');
            }
            
            if ($request['status'] !== 'pending') {
                return array('success' => false, 'error' => 'This request is not pending approval');
            }
            
            // Begin transaction
            $this->db->getConnection()->begin_transaction();
            
            try {
                // Update renewal request status to approved
                $approvedAt = date('Y-m-d H:i:s');
                $stmt = $this->db->prepare(
                    "UPDATE renewal_requests SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?"
                );
                $stmt->bind_param("isi", $approverId, $approvedAt, $requestId);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update renewal request");
                }
                $stmt->close();
                
                // Update bus pass expiry date
                $stmt = $this->db->prepare(
                    "UPDATE bus_passes SET expiry_date = ? WHERE id = ?"
                );
                $stmt->bind_param("si", $request['requested_expiry_date'], $request['bus_pass_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to renew pass");
                }
                $stmt->close();
                
                // Log transaction
                $this->logTransaction($request['bus_pass_id'], $request['user_id'], 'renewal', 0, 'completed', $comments);
                
                // Commit transaction
                $this->db->getConnection()->commit();
                
                return array(
                    'success' => true,
                    'request_id' => $requestId,
                    'new_expiry_date' => $request['requested_expiry_date'],
                    'message' => 'Renewal request approved and pass renewed successfully',
                    'notification' => $this->generateApprovalNotification($request, true)
                );
            } catch (Exception $e) {
                $this->db->getConnection()->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Reject renewal request
     */
    public function rejectRenewalRequest($requestId, $approverId, $rejectionReason) {
        try {
            // Get renewal request details
            $request = $this->renewalRequest->getRenewalRequest($requestId);
            
            if (!$request) {
                return array('success' => false, 'error' => 'Renewal request not found');
            }
            
            if ($request['status'] !== 'pending') {
                return array('success' => false, 'error' => 'This request is not pending approval');
            }
            
            // Begin transaction
            $this->db->getConnection()->begin_transaction();
            
            try {
                // Update renewal request status to rejected
                $approvedAt = date('Y-m-d H:i:s');
                $stmt = $this->db->prepare(
                    "UPDATE renewal_requests SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = ? WHERE id = ?"
                );
                $stmt->bind_param("sisi", $rejectionReason, $approverId, $approvedAt, $requestId);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update renewal request");
                }
                $stmt->close();
                
                // Log transaction
                $this->logTransaction($request['bus_pass_id'], $request['user_id'], 'renewal', 0, 'failed', $rejectionReason);
                
                // Commit transaction
                $this->db->getConnection()->commit();
                
                return array(
                    'success' => true,
                    'request_id' => $requestId,
                    'message' => 'Renewal request rejected',
                    'notification' => $this->generateRejectionNotification($request, $rejectionReason)
                );
            } catch (Exception $e) {
                $this->db->getConnection()->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Request changes for renewal request
     */
    public function requestChangesForRenewal($requestId, $approverId, $requiredChanges) {
        try {
            // Get renewal request details
            $request = $this->renewalRequest->getRenewalRequest($requestId);
            
            if (!$request) {
                return array('success' => false, 'error' => 'Renewal request not found');
            }
            
            // Update status to pending (can be a custom status or add comment)
            $stmt = $this->db->prepare(
                "UPDATE renewal_requests SET rejection_reason = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $requiredChanges, $requestId);
            $stmt->execute();
            $stmt->close();
            
            return array(
                'success' => true,
                'request_id' => $requestId,
                'message' => 'Change request sent to user',
                'notification' => $this->generateChangeRequestNotification($request, $requiredChanges)
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Bulk approve renewal requests
     */
    public function bulkApproveRequests($requestIds, $approverId) {
        $results = array(
            'approved' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($requestIds as $requestId) {
            $result = $this->approveRenewalRequest($requestId, $approverId);
            
            if ($result['success']) {
                $results['approved']++;
            } else {
                $results['failed']++;
                $results['errors'][] = array('request_id' => $requestId, 'error' => $result['error']);
            }
        }
        
        return $results;
    }
    
    /**
     * Auto-approve eligible renewal requests
     */
    public function autoApproveEligibleRequests($approverId = null) {
        try {
            // Get all pending requests for users with good standing
            $stmt = $this->db->prepare(
                "SELECT rr.id FROM renewal_requests rr
                 JOIN users u ON rr.user_id = u.id
                 WHERE rr.status = 'pending'
                 AND u.id NOT IN (
                    SELECT DISTINCT user_id FROM bus_passes WHERE status = 'cancelled'
                 )
                 LIMIT 100"
            );
            
            $stmt->execute();
            $result = $stmt->get_result();
            $approved = 0;
            
            while ($row = $result->fetch_assoc()) {
                $approveResult = $this->approveRenewalRequest($row['id'], $approverId ?? 1);
                if ($approveResult['success']) {
                    $approved++;
                }
            }
            
            $stmt->close();
            
            return array(
                'success' => true,
                'auto_approved_count' => $approved,
                'message' => 'Auto-approval completed'
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get workflow status for a renewal request
     */
    public function getWorkflowStatus($requestId) {
        try {
            $request = $this->renewalRequest->getRenewalRequest($requestId);
            
            if (!$request) {
                return array('success' => false, 'error' => 'Request not found');
            }
            
            $status = array(
                'request_id' => $requestId,
                'current_status' => $request['status'],
                'submitted_at' => $request['request_date'],
                'current_expiry' => $request['current_expiry_date'],
                'requested_expiry' => $request['requested_expiry_date'],
                'approved_by' => $request['approved_by'],
                'approved_at' => $request['approved_at'],
                'rejection_reason' => $request['rejection_reason'],
                'timeline' => $this->getTimeline($request)
            );
            
            return array('success' => true, 'status' => $status);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get workflow timeline
     */
    private function getTimeline($request) {
        $timeline = array();
        
        // Submitted
        $timeline[] = array(
            'event' => 'Renewal Requested',
            'date' => $request['request_date'],
            'status' => 'completed'
        );
        
        // Status-specific events
        if ($request['status'] === 'approved' && $request['approved_at']) {
            $timeline[] = array(
                'event' => 'Request Approved',
                'date' => $request['approved_at'],
                'status' => 'completed'
            );
            $timeline[] = array(
                'event' => 'Pass Renewed',
                'date' => $request['approved_at'],
                'status' => 'completed'
            );
        } elseif ($request['status'] === 'rejected' && $request['approved_at']) {
            $timeline[] = array(
                'event' => 'Request Rejected',
                'date' => $request['approved_at'],
                'status' => 'completed'
            );
        } elseif ($request['status'] === 'pending') {
            $timeline[] = array(
                'event' => 'Awaiting Approval',
                'date' => null,
                'status' => 'in-progress'
            );
        }
        
        return $timeline;
    }
    
    /**
     * Log transaction for renewal
     */
    private function logTransaction($passId, $userId, $type, $amount, $status, $notes = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO pass_transactions (bus_pass_id, user_id, transaction_type, amount, payment_status, notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param("iisids", $passId, $userId, $type, $amount, $status, $notes);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Generate approval notification
     */
    private function generateApprovalNotification($request, $approved = true) {
        return array(
            'type' => 'approval',
            'title' => 'Renewal Request Approved',
            'message' => 'Your bus pass renewal request has been approved.',
            'pass_number' => isset($request['pass_number']) ? $request['pass_number'] : '',
            'new_expiry_date' => isset($request['requested_expiry_date']) ? $request['requested_expiry_date'] : '',
            'recipient_email' => isset($request['email']) ? $request['email'] : '',
            'recipient_phone' => isset($request['phone']) ? $request['phone'] : ''
        );
    }
    
    /**
     * Generate rejection notification
     */
    private function generateRejectionNotification($request, $reason) {
        return array(
            'type' => 'rejection',
            'title' => 'Renewal Request Rejected',
            'message' => 'Your bus pass renewal request has been rejected.',
            'reason' => $reason,
            'pass_number' => isset($request['pass_number']) ? $request['pass_number'] : '',
            'recipient_email' => isset($request['email']) ? $request['email'] : '',
            'recipient_phone' => isset($request['phone']) ? $request['phone'] : ''
        );
    }
    
    /**
     * Generate change request notification
     */
    private function generateChangeRequestNotification($request, $changes) {
        return array(
            'type' => 'changes_requested',
            'title' => 'Changes Requested for Renewal',
            'message' => 'Changes have been requested for your renewal request.',
            'required_changes' => $changes,
            'pass_number' => isset($request['pass_number']) ? $request['pass_number'] : '',
            'recipient_email' => isset($request['email']) ? $request['email'] : '',
            'recipient_phone' => isset($request['phone']) ? $request['phone'] : ''
        );
    }
    
    /**
     * Send notification (email/SMS)
     */
    public function sendNotification($notification) {
        try {
            // Email notification
            if (isset($notification['recipient_email'])) {
                $this->sendEmailNotification($notification);
            }
            
            // SMS notification (optional)
            if (isset($notification['recipient_phone'])) {
                $this->sendSMSNotification($notification);
            }
            
            return array('success' => true, 'message' => 'Notifications sent');
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmailNotification($notification) {
        $email = $notification['recipient_email'];
        $subject = $notification['title'];
        
        $body = "Hello,\n\n";
        $body .= $notification['message'] . "\n\n";
        
        if ($notification['type'] === 'approval') {
            $body .= "Pass Number: " . $notification['pass_number'] . "\n";
            $body .= "New Expiry Date: " . $notification['new_expiry_date'] . "\n";
        } elseif ($notification['type'] === 'rejection') {
            $body .= "Pass Number: " . $notification['pass_number'] . "\n";
            $body .= "Reason: " . $notification['reason'] . "\n";
        }
        
        $body .= "\n\nPlease log in to your account for more details.\n\n";
        $body .= "Regards,\nBus Pass Management System";
        
        $headers = "From: noreply@buspass.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail($email, $subject, $body, $headers);
    }
    
    /**
     * Send SMS notification
     */
    private function sendSMSNotification($notification) {
        // Implement with your SMS provider (Twilio, AWS SNS, etc.)
        // This is a placeholder
        $phone = $notification['recipient_phone'];
        $message = $notification['title'] . ": " . $notification['message'];
        
        // Log the SMS notification
        error_log("SMS to $phone: $message");
    }
}

$renewalApprovalWorkflow = new RenewalApprovalWorkflow();
?>
