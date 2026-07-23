<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';

class RenewalRequest {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Create a renewal request
     */
    public function createRenewalRequest($passId, $userId) {
        try {
            // Get current pass details
            $stmt = $this->db->prepare(
                "SELECT expiry_date FROM bus_passes WHERE id = ? AND user_id = ?"
            );
            $stmt->bind_param("ii", $passId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'error' => 'Pass not found');
            }
            
            $pass = $result->fetch_assoc();
            $stmt->close();
            
            // Check if already has pending renewal request
            $stmt = $this->db->prepare(
                "SELECT id FROM renewal_requests WHERE bus_pass_id = ? AND status = 'pending'"
            );
            $stmt->bind_param("i", $passId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return array('success' => false, 'error' => 'Already has a pending renewal request');
            }
            $stmt->close();
            
            // Calculate requested expiry date (extend by 1 year from current expiry)
            $requestedExpiryDate = date('Y-m-d', strtotime('+' . PASS_VALIDITY_DAYS . ' days', strtotime($pass['expiry_date'])));
            $requestDate = date('Y-m-d H:i:s');
            $currentExpiryDate = $pass['expiry_date'];
            
            // Insert renewal request
            $stmt = $this->db->prepare(
                "INSERT INTO renewal_requests (bus_pass_id, user_id, request_date, current_expiry_date, requested_expiry_date, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            
            $stmt->bind_param("iisss", $passId, $userId, $requestDate, $currentExpiryDate, $requestedExpiryDate);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create renewal request: " . $stmt->error);
            }
            
            $requestId = $this->db->lastInsertId();
            $stmt->close();
            
            return array(
                'success' => true,
                'request_id' => $requestId,
                'current_expiry' => $currentExpiryDate,
                'requested_expiry' => $requestedExpiryDate,
                'message' => 'Renewal request submitted successfully'
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get renewal request details
     */
    public function getRenewalRequest($requestId) {
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.first_name, u.last_name, u.email, bp.pass_number
             FROM renewal_requests rr
             JOIN users u ON rr.user_id = u.id
             JOIN bus_passes bp ON rr.bus_pass_id = bp.id
             WHERE rr.id = ?"
        );
        
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $request = $result->fetch_assoc();
        $stmt->close();
        
        return $request;
    }
    
    /**
     * Get renewal requests for a user
     */
    public function getUserRenewalRequests($userId, $status = null, $limit = 10, $offset = 0) {
        $query = "SELECT rr.*, bp.pass_number FROM renewal_requests rr
                  JOIN bus_passes bp ON rr.bus_pass_id = bp.id
                  WHERE rr.user_id = ?";
        
        if ($status) {
            $query .= " AND rr.status = ?";
        }
        
        $query .= " ORDER BY rr.request_date DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        
        if ($status) {
            $stmt->bind_param("isii", $userId, $status, $limit, $offset);
        } else {
            $stmt->bind_param("iii", $userId, $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = array();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        $stmt->close();
        return $requests;
    }
    
    /**
     * Get all pending renewal requests (for admin/approver)
     */
    public function getPendingRequests($limit = 20, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.first_name, u.last_name, u.email, u.phone, bp.pass_number
             FROM renewal_requests rr
             JOIN users u ON rr.user_id = u.id
             JOIN bus_passes bp ON rr.bus_pass_id = bp.id
             WHERE rr.status = 'pending'
             ORDER BY rr.request_date ASC
             LIMIT ? OFFSET ?"
        );
        
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = array();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        $stmt->close();
        return $requests;
    }
    
    /**
     * Get total pending renewal requests count
     */
    public function getPendingRequestsCount() {
        $result = $this->db->query("SELECT COUNT(*) as total FROM renewal_requests WHERE status = 'pending'");
        return $result->fetch_assoc()['total'];
    }
    
    /**
     * Get renewal requests by status
     */
    public function getRequestsByStatus($status, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.first_name, u.last_name, u.email, bp.pass_number
             FROM renewal_requests rr
             JOIN users u ON rr.user_id = u.id
             JOIN bus_passes bp ON rr.bus_pass_id = bp.id
             WHERE rr.status = ?
             ORDER BY rr.request_date DESC
             LIMIT ? OFFSET ?"
        );
        
        $stmt->bind_param("sii", $status, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = array();
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        $stmt->close();
        return $requests;
    }
    
    /**
     * Cancel renewal request
     */
    public function cancelRenewalRequest($requestId) {
        $stmt = $this->db->prepare(
            "UPDATE renewal_requests SET status = 'cancelled' WHERE id = ?"
        );
        
        $stmt->bind_param("i", $requestId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get renewal request statistics
     */
    public function getRenewalStatistics() {
        $stats = array();
        
        // Total requests
        $result = $this->db->query("SELECT COUNT(*) as total FROM renewal_requests");
        $stats['total_requests'] = $result->fetch_assoc()['total'];
        
        // Pending requests
        $result = $this->db->query("SELECT COUNT(*) as total FROM renewal_requests WHERE status = 'pending'");
        $stats['pending_requests'] = $result->fetch_assoc()['total'];
        
        // Approved requests
        $result = $this->db->query("SELECT COUNT(*) as total FROM renewal_requests WHERE status = 'approved'");
        $stats['approved_requests'] = $result->fetch_assoc()['total'];
        
        // Rejected requests
        $result = $this->db->query("SELECT COUNT(*) as total FROM renewal_requests WHERE status = 'rejected'");
        $stats['rejected_requests'] = $result->fetch_assoc()['total'];
        
        return $stats;
    }
}

$renewalRequest = new RenewalRequest();
?>
