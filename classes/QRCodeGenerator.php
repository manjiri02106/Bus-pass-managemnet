<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__FILE__) . '/Database.php';

class QRCodeGenerator {
    private $db;
    private $apiKey;
    
    public function __construct($apiKey = null) {
        global $db;
        $this->db = $db;
        // Using QR code API (can be replaced with local library)
        $this->apiKey = $apiKey;
    }
    
    /**
     * Generate QR code for bus pass
     * Uses Google Charts API (free alternative: qrserver.com)
     */
    public function generateQRCode($passNumber, $userId, $fileName = null) {
        // Create QR code data
        $qrData = $this->createQRData($passNumber, $userId);
        
        // Generate file name if not provided
        if (!$fileName) {
            $fileName = 'qr_' . $passNumber . '_' . time() . '.png';
        }
        
        $filePath = UPLOAD_PATH . '/qrcodes/' . $fileName;
        
        // Create directory if not exists
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        // Stream context options to handle SSL verification on local servers (XAMPP)
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5
            ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));

        // Method 1: Using QR Server API (free, no API key needed)
        $qrUrl = $this->generateQRCodeFromAPI($qrData);
        
        if ($qrUrl) {
            $imageContent = @file_get_contents($qrUrl, false, $context);
            if ($imageContent !== false && strlen($imageContent) > 0) {
                file_put_contents($filePath, $imageContent);
                return array(
                    'success' => true,
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'url' => str_replace('\\', '/', $filePath)
                );
            }
        }

        // Method 2: Fallback to QuickChart QR API
        $fallbackUrl = 'https://quickchart.io/qr?size=' . QR_CODE_SIZE . '&text=' . urlencode($qrData);
        $imageContent = @file_get_contents($fallbackUrl, false, $context);
        if ($imageContent !== false && strlen($imageContent) > 0) {
            file_put_contents($filePath, $imageContent);
            return array(
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'url' => str_replace('\\', '/', $filePath)
            );
        }
        
        return array(
            'success' => false,
            'file_path' => null,
            'file_name' => null,
            'message' => 'Failed to generate QR code image'
        );
    }
    
    /**
     * Generate QR code data string
     */
    private function createQRData($passNumber, $userId) {
        $data = array(
            'pass_number' => $passNumber,
            'user_id' => $userId,
            'timestamp' => time(),
            'verification_url' => APP_URL . '/verify.php?pass=' . $passNumber
        );
        return json_encode($data);
    }
    
    /**
     * Generate QR code using online API
     */
    private function generateQRCodeFromAPI($data) {
        // Using qrserver.com API
        $encodedData = urlencode($data);
        $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
        
        $params = array(
            'size' => QR_CODE_SIZE . 'x' . QR_CODE_SIZE,
            'data' => $data,
            'ecc' => QR_CODE_ERROR_CORRECTION,
            'format' => 'png'
        );
        
        $url = $apiUrl . '?' . http_build_query($params);
        return $url;
    }
    
    /**
     * Generate QR code using local library (phpqrcode)
     * Requires: composer require davidscops/qrcode
     */
    private function generateQRCodeUsingLibrary($data, $filePath, $fileName) {
        try {
            // Check if QR Code library is available
            if (!class_exists('QRcode')) {
                // Fallback to API
                return $this->generateQRCodeFromAPIAndSave($data, $filePath, $fileName);
            }
            
            \QRcode::png($data, $filePath, QR_CODE_ERROR_CORRECTION, 10, 2);
            
            return array(
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'url' => str_replace('\\', '/', $filePath)
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Generate and save QR code from API
     */
    private function generateQRCodeFromAPIAndSave($data, $filePath, $fileName) {
        try {
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . QR_CODE_SIZE . 'x' . QR_CODE_SIZE . '&data=' . urlencode($data);
            
            $imageContent = @file_get_contents($url);
            if ($imageContent === false) {
                return array(
                    'success' => false,
                    'error' => 'Failed to generate QR code from API'
                );
            }
            
            if (!file_put_contents($filePath, $imageContent)) {
                return array(
                    'success' => false,
                    'error' => 'Failed to save QR code file'
                );
            }
            
            return array(
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'url' => str_replace('\\', '/', $filePath)
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Verify QR code data
     */
    public function verifyQRCode($qrData) {
        try {
            $data = json_decode($qrData, true);
            
            if (!$data || !isset($data['pass_number'])) {
                return array('valid' => false, 'error' => 'Invalid QR data');
            }
            
            // Verify pass exists in database
            $stmt = $this->db->prepare("SELECT id, user_id, status FROM bus_passes WHERE pass_number = ?");
            $stmt->bind_param("s", $data['pass_number']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('valid' => false, 'error' => 'Pass not found');
            }
            
            $pass = $result->fetch_assoc();
            $stmt->close();
            
            // Check if pass is active
            if ($pass['status'] !== 'active') {
                return array('valid' => false, 'error' => 'Pass is not active');
            }
            
            return array(
                'valid' => true,
                'pass_number' => $data['pass_number'],
                'user_id' => $pass['user_id'],
                'status' => $pass['status']
            );
        } catch (Exception $e) {
            return array('valid' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Generate QR code as inline SVG or Base64
     */
    public function generateQRCodeInline($data, $format = 'svg') {
        $encodedData = urlencode($data);
        
        if ($format === 'svg') {
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . QR_CODE_SIZE . 'x' . QR_CODE_SIZE . '&format=svg&data=' . $encodedData;
        } else {
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . QR_CODE_SIZE . 'x' . QR_CODE_SIZE . '&format=png&data=' . $encodedData;
        }
        
        return $url;
    }
}

$qrCodeGenerator = new QRCodeGenerator();
?>
