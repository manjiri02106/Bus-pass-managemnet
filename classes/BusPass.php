<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';

class BusPass {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Create a new bus pass
     */
    public function createPass($userId, $passNumber, $passType = 'monthly', $validityDays = PASS_VALIDITY_DAYS) {
        try {
            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime("+$validityDays days"));
            
            $stmt = $this->db->prepare(
                "INSERT INTO bus_passes (user_id, pass_number, pass_type, issue_date, expiry_date, status)
                 VALUES (?, ?, ?, ?, ?, 'active')"
            );
            
            if (!$stmt) {
                throw new Exception("Database prepare error: " . ($this->db->getConnection()->error ?? 'Unknown error'));
            }
            
            $stmt->bind_param("issss", $userId, $passNumber, $passType, $issueDate, $expiryDate);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create pass: " . $stmt->error);
            }
            
            $passId = $this->db->lastInsertId();
            $stmt->close();
            
            return array(
                'success' => true,
                'pass_id' => $passId,
                'pass_number' => $passNumber,
                'issue_date' => $issueDate,
                'expiry_date' => $expiryDate
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Get pass details
     */
    public function getPassDetails($passId) {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone, u.user_type
             FROM bus_passes bp
             JOIN users u ON bp.user_id = u.id
             WHERE bp.id = ?"
        );
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $passId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $pass = $result->fetch_assoc();
        $stmt->close();
        
        return $pass;
    }
    
    /**
     * Get pass by pass number
     */
    public function getPassByNumber($passNumber) {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone
             FROM bus_passes bp
             JOIN users u ON bp.user_id = u.id
             WHERE bp.pass_number = ?"
        );
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $passNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $pass = $result->fetch_assoc();
        $stmt->close();
        
        return $pass;
    }
    
    /**
     * Get user's current pass
     */
    public function getUserCurrentPass($userId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM bus_passes
             WHERE user_id = ? AND status = 'active' AND expiry_date >= CURDATE()
             ORDER BY expiry_date DESC LIMIT 1"
        );
        
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $pass = $result->fetch_assoc();
        $stmt->close();
        
        return $pass;
    }
    
    /**
     * Get user's all passes
     */
    public function getUserPasses($userId, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT * FROM bus_passes
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        
        if (!$stmt) {
            return array();
        }
        
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passes = array();
        while ($row = $result->fetch_assoc()) {
            $passes[] = $row;
        }
        
        $stmt->close();
        return $passes;
    }
    
    /**
     * Update pass QR code
     */
    public function updatePassQRCode($passId, $qrCodePath) {
        $stmt = $this->db->prepare(
            "UPDATE bus_passes SET qr_code = ? WHERE id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("si", $qrCodePath, $passId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Check if pass is eligible for renewal
     */
    public function isEligibleForRenewal($passId) {
        $stmt = $this->db->prepare(
            "SELECT expiry_date FROM bus_passes WHERE id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $passId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $pass = $result->fetch_assoc();
        $stmt->close();
        
        // Check if renewal is allowed within 30 days before expiry
        $expiryDate = strtotime($pass['expiry_date']);
        $renewalStartDate = $expiryDate - (PASS_RENEWAL_DAYS * 24 * 60 * 60);
        $currentTime = time();
        
        return ($currentTime >= $renewalStartDate && $currentTime < $expiryDate);
    }
    
    /**
     * Renew pass
     */
    public function renewPass($passId, $validityDays = PASS_VALIDITY_DAYS) {
        try {
            $pass = $this->getPassDetails($passId);
            
            if (!$pass) {
                throw new Exception("Pass not found");
            }
            
            if (!$this->isEligibleForRenewal($passId)) {
                throw new Exception("Pass is not eligible for renewal");
            }
            
            $newExpiryDate = date('Y-m-d', strtotime("+$validityDays days", strtotime($pass['expiry_date'])));
            
            $stmt = $this->db->prepare(
                "UPDATE bus_passes SET expiry_date = ?, status = 'active' WHERE id = ?"
            );
            
            if (!$stmt) {
                throw new Exception("Database prepare error");
            }
            
            $stmt->bind_param("si", $newExpiryDate, $passId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to renew pass");
            }
            
            $stmt->close();
            
            return array(
                'success' => true,
                'new_expiry_date' => $newExpiryDate,
                'message' => 'Pass renewed successfully'
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Cancel pass
     */
    public function cancelPass($passId, $reason = null) {
        $stmt = $this->db->prepare(
            "UPDATE bus_passes SET status = 'cancelled' WHERE id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $passId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get pass statistics
     */
    public function getPassStatistics() {
        $stats = array();
        
        // Total passes issued
        $result = $this->db->query("SELECT COUNT(*) as total FROM bus_passes");
        $stats['total_passes'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
        
        // Active passes
        $result = $this->db->query(
            "SELECT COUNT(*) as total FROM bus_passes WHERE status = 'active' AND expiry_date >= CURDATE()"
        );
        $stats['active_passes'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
        
        // Expired passes
        $result = $this->db->query(
            "SELECT COUNT(*) as total FROM bus_passes WHERE status = 'expired' OR expiry_date < CURDATE()"
        );
        $stats['expired_passes'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
        
        // Passes expiring soon (within 7 days)
        $result = $this->db->query(
            "SELECT COUNT(*) as total FROM bus_passes 
             WHERE status = 'active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        );
        $stats['expiring_soon'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;
        
        return $stats;
    }
    
    /**
     * Get passes by status
     */
    public function getPassesByStatus($status, $limit = 10, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email FROM bus_passes bp
             JOIN users u ON bp.user_id = u.id
             WHERE bp.status = ?
             ORDER BY bp.created_at DESC
             LIMIT ? OFFSET ?"
        );
        
        if (!$stmt) {
            return array();
        }
        
        $stmt->bind_param("sii", $status, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passes = array();
        while ($row = $result->fetch_assoc()) {
            $passes[] = $row;
        }
        
        $stmt->close();
        return $passes;
    }
    
    /**
     * Get all passes with user details
     */
    public function getAllPasses($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.user_type
             FROM bus_passes bp
             JOIN users u ON bp.user_id = u.id
             ORDER BY bp.created_at DESC
             LIMIT ? OFFSET ?"
        );
        
        if (!$stmt) {
            return array();
        }
        
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passes = array();
        while ($row = $result->fetch_assoc()) {
            $passes[] = $row;
        }
        
        $stmt->close();
        return $passes;
    }
    
    /**
     * Get passes by user email
     */
    public function getPassesByEmail($email, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.user_type
             FROM bus_passes bp
             JOIN users u ON bp.user_id = u.id
             WHERE u.email = ?
             ORDER BY bp.created_at DESC
             LIMIT ?"
        );
        
        if (!$stmt) {
            return array();
        }
        
        $stmt->bind_param("si", $email, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $passes = array();
        while ($row = $result->fetch_assoc()) {
            $passes[] = $row;
        }
        
        $stmt->close();
        return $passes;
    }
}
?>
