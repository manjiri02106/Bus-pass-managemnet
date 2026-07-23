<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';

class PassNumberGenerator {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Generate a unique pass number
     * Format: BP-YYYY-MMDD-XXXXX
     * BP = Bus Pass prefix
     * YYYY = Year
     * MMDD = Month and Day
     * XXXXX = Sequential 5-digit number
     */
    public function generatePassNumber() {
        $prefix = 'BP';
        $date = date('YmdHi'); // YYYYMMDDHH format
        $random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        
        $passNumber = $prefix . '-' . substr($date, 0, 4) . '-' . substr($date, 4, 4) . '-' . $random;
        
        // Ensure uniqueness
        while ($this->passNumberExists($passNumber)) {
            $random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $passNumber = $prefix . '-' . substr($date, 0, 4) . '-' . substr($date, 4, 4) . '-' . $random;
        }
        
        return $passNumber;
    }
    
    /**
     * Check if pass number already exists
     */
    private function passNumberExists($passNumber) {
        $stmt = $this->db->prepare("SELECT id FROM bus_passes WHERE pass_number = ?");
        $stmt->bind_param("s", $passNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    
    /**
     * Generate sequential pass number (alternative format)
     * Format: BP-2026-000001
     */
    public function generateSequentialPassNumber() {
        $currentYear = date('Y');
        $prefix = 'BP-' . $currentYear . '-';
        
        // Get the last sequential number for this year
        $stmt = $this->db->prepare(
            "SELECT pass_number FROM bus_passes 
             WHERE pass_number LIKE ? 
             ORDER BY id DESC LIMIT 1"
        );
        $pattern = $prefix . '%';
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastNumber = $row['pass_number'];
            // Extract the numeric part
            $numeric = (int)substr($lastNumber, strlen($prefix));
            $nextNumber = str_pad($numeric + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '000001';
        }
        
        $stmt->close();
        return $prefix . $nextNumber;
    }
    
    /**
     * Generate pass number with custom prefix (for different pass types)
     */
    public function generateCustomPassNumber($passType = 'standard') {
        $typePrefix = $this->getPassTypePrefix($passType);
        $date = date('Ymd');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        $passNumber = $typePrefix . '-' . $date . '-' . $random;
        
        // Ensure uniqueness
        while ($this->passNumberExists($passNumber)) {
            $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $passNumber = $typePrefix . '-' . $date . '-' . $random;
        }
        
        return $passNumber;
    }
    
    /**
     * Get prefix based on pass type
     */
    private function getPassTypePrefix($passType) {
        $prefixes = array(
            'monthly' => 'BPM',
            'quarterly' => 'BPQ',
            'annual' => 'BPA',
            'special' => 'BPS'
        );
        
        return isset($prefixes[$passType]) ? $prefixes[$passType] : 'BPM';
    }
    
    /**
     * Validate pass number format
     */
    public function validatePassNumber($passNumber) {
        // BP-YYYY-MMDD-XXXXX format
        $pattern = '/^BP-\d{4}-\d{4}-\d{5}$/';
        return preg_match($pattern, $passNumber) === 1;
    }
}

$passNumberGenerator = new PassNumberGenerator();
?>
