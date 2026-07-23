<?php
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/Database.php';

class PassDownloadManager {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Generate PDF pass document
     */
    public function generatePassPDF($passId) {
        if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
        }
        
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            return $this->generatePrintablePass($passId);
        }
        
        try {
            // Get pass details
            $stmt = $this->db->prepare(
                "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.postal_code
                 FROM bus_passes bp
                 JOIN users u ON bp.user_id = u.id
                 WHERE bp.id = ?"
            );
            
            $stmt->bind_param("i", $passId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'error' => 'Pass not found');
            }
            
            $pass = $result->fetch_assoc();
            $stmt->close();
            
            // Create PDF using TCPDF or similar library
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Bus Pass Management System');
            $pdf->SetAuthor('Bus Authority');
            $pdf->SetTitle('Bus Pass - ' . $pass['pass_number']);
            $pdf->SetSubject('Digital Bus Pass');
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont('courier');
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add page
            $pdf->AddPage();
            
            // Title
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->Cell(0, 10, 'BUS PASS', 0, 1, 'C');
            
            // Separator
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Line(15, 35, 195, 35);
            
            // Pass Information
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetXY(15, 40);
            $pdf->Cell(60, 8, 'Pass Number:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $pass['pass_number'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Passenger Name:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $pass['first_name'] . ' ' . $pass['last_name'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Email:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $pass['email'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Phone:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $pass['phone'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Address:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, $pass['address'] . ', ' . $pass['city'] . ' - ' . $pass['postal_code'], 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Pass Type:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, ucfirst($pass['pass_type']), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Issue Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, date('d-m-Y', strtotime($pass['issue_date'])), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Expiry Date:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 8, date('d-m-Y', strtotime($pass['expiry_date'])), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(60, 8, 'Status:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetTextColor(0, 128, 0);
            $pdf->Cell(0, 8, strtoupper($pass['status']), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            
            // Add QR Code if available
            if ($pass['qr_code']) {
                $pdf->SetXY(130, 40);
                $pdf->Image($pass['qr_code'], 130, 40, 50, 50, 'PNG');
            }
            
            // Add footer
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->SetXY(15, 220);
            $pdf->Cell(0, 5, 'Generated on: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
            $pdf->Cell(0, 5, 'This is an official document. Please keep it safe.', 0, 1, 'C');
            
            // Output PDF
            $fileName = 'BusPass_' . $pass['pass_number'] . '_' . time() . '.pdf';
            $filePath = UPLOAD_PATH . '/passes/' . $fileName;
            
            // Create directory if not exists
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            
            $pdf->Output($filePath, 'F');
            
            // Log download
            $this->logDownload($passId, 'pdf');
            
            return array(
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Generate pass image (PNG/JPG)
     */
    public function generatePassImage($passId, $format = 'png') {
        try {
            // Get pass details
            $stmt = $this->db->prepare(
                "SELECT bp.*, u.first_name, u.last_name, u.user_type
                 FROM bus_passes bp
                 JOIN users u ON bp.user_id = u.id
                 WHERE bp.id = ?"
            );
            
            $stmt->bind_param("i", $passId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'error' => 'Pass not found');
            }
            
            $pass = $result->fetch_assoc();
            $stmt->close();
            
            if (!function_exists('imagecreatetruecolor')) {
                return array('success' => false, 'error' => 'GD library extension is not enabled in PHP php.ini.');
            }
            
            // Create image using GD library
            $width = 400;
            $height = 600;
            $image = imagecreatetruecolor($width, $height);
            
            // Define colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $blue = imagecolorallocate($image, 0, 102, 204);
            $lightGray = imagecolorallocate($image, 240, 240, 240);
            
            // Fill background
            imagefilledrectangle($image, 0, 0, $width, $height, $white);
            
            // Add header with blue background
            imagefilledrectangle($image, 0, 0, $width, 80, $blue);
            
            // Add text to image
            $fontFile = __DIR__ . '/fonts/arial.ttf'; // Ensure font file exists
            
            if (file_exists($fontFile)) {
                // Title
                imagettftext($image, 24, 0, 50, 50, $white, $fontFile, 'BUS PASS');
                
                // Pass number
                imagettftext($image, 12, 0, 20, 120, $black, $fontFile, 'Pass #: ' . $pass['pass_number']);
                
                // Passenger name
                imagettftext($image, 12, 0, 20, 150, $black, $fontFile, 'Name: ' . $pass['first_name'] . ' ' . $pass['last_name']);
                
                // User type
                imagettftext($image, 11, 0, 20, 180, $black, $fontFile, 'Type: ' . ucfirst($pass['user_type']));
                
                // Dates
                imagettftext($image, 10, 0, 20, 210, $black, $fontFile, 'Valid From: ' . date('d-m-Y', strtotime($pass['issue_date'])));
                imagettftext($image, 10, 0, 20, 235, $black, $fontFile, 'Valid Till: ' . date('d-m-Y', strtotime($pass['expiry_date'])));
                
                // Status
                $statusColor = ($pass['status'] === 'active') ? $blue : imagecolorallocate($image, 255, 0, 0);
                imagettftext($image, 12, 0, 20, 270, $statusColor, $fontFile, 'Status: ' . strtoupper($pass['status']));
                
                // QR Code placeholder
                if ($pass['qr_code'] && file_exists($pass['qr_code'])) {
                    // Embed QR code image
                    $qrImage = imagecreatefrompng($pass['qr_code']);
                    imagecopy($image, $qrImage, 250, 350, 0, 0, 100, 100);
                    imagedestroy($qrImage);
                }
            } else {
                // Fallback to imagestring if font file not available
                imagestring($image, 5, 50, 50, 'BUS PASS', $white);
                imagestring($image, 3, 20, 120, 'Pass #: ' . $pass['pass_number'], $black);
                imagestring($image, 3, 20, 150, 'Name: ' . $pass['first_name'] . ' ' . $pass['last_name'], $black);
            }
            
            // Save image
            $fileName = 'BusPass_' . $pass['pass_number'] . '_' . time() . '.' . $format;
            $filePath = UPLOAD_PATH . '/passes/' . $fileName;
            
            if (!is_dir(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            
            if ($format === 'png') {
                imagepng($image, $filePath);
            } else {
                imagejpeg($image, $filePath, 90);
            }
            
            imagedestroy($image);
            
            // Log download
            $this->logDownload($passId, $format);
            
            return array(
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Log pass download
     */
    private function logDownload($passId, $format, $ipAddress = null, $userAgent = null) {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        if (!$userAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        }
        
        $downloadDate = date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare(
            "INSERT INTO pass_downloads (bus_pass_id, download_date, download_format, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param("issss", $passId, $downloadDate, $format, $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get download history for a pass
     */
    public function getDownloadHistory($passId, $limit = 10) {
        $stmt = $this->db->prepare(
            "SELECT * FROM pass_downloads WHERE bus_pass_id = ? ORDER BY download_date DESC LIMIT ?"
        );
        
        $stmt->bind_param("ii", $passId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $downloads = array();
        while ($row = $result->fetch_assoc()) {
            $downloads[] = $row;
        }
        
        $stmt->close();
        return $downloads;
    }
    
    /**
     * Generate printable pass (HTML format)
     */
    public function generatePrintablePass($passId) {
        try {
            // Get pass details
            $stmt = $this->db->prepare(
                "SELECT bp.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.state, u.postal_code, u.user_type
                 FROM bus_passes bp
                 JOIN users u ON bp.user_id = u.id
                 WHERE bp.id = ?"
            );
            
            $stmt->bind_param("i", $passId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'error' => 'Pass not found');
            }
            
            $pass = $result->fetch_assoc();
            $issueDate = date('d-m-Y', strtotime($pass['issue_date']));
            $expiryDate = date('d-m-Y', strtotime($pass['expiry_date']));
            $generatedOn = date('d-m-Y H:i:s');
            $qrHtml = $this->getQRCodeHTML($pass['qr_code'], $pass['pass_number'], $pass['user_id'], $pass['id']);
            
            // Generate HTML
            $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bus Pass - {$pass['pass_number']}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .pass-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border: 3px solid #0066cc;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            background-color: #0066cc;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
        }
        .pass-info {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #0066cc;
        }
        .info-value {
            color: #333;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 150px;
            border: 2px solid #0066cc;
            padding: 10px;
            background-color: white;
        }
        .status {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            background-color: #e8f5e9;
            color: #2e7d32;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .print-button {
                display: none;
            }
        }
        .print-button {
            display: block;
            width: 150px;
            margin: 20px auto;
            padding: 10px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-button:hover {
            background-color: #0052a3;
        }
    </style>
</head>
<body>
    <div class="pass-container">
        <div class="header">
            <h1>🎫 BUS PASS</h1>
        </div>
        
        <div class="status">
            Status: <span style="color: green;">{$pass['status']}</span>
        </div>
        
        <div class="pass-info">
            <div class="info-row">
                <span class="info-label">Pass Number:</span>
                <span class="info-value">{$pass['pass_number']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Passenger Name:</span>
                <span class="info-value">{$pass['first_name']} {$pass['last_name']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{$pass['email']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{$pass['phone']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value">{$pass['address']}, {$pass['city']}, {$pass['state']} - {$pass['postal_code']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">User Type:</span>
                <span class="info-value">{$pass['user_type']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Pass Type:</span>
                <span class="info-value">{$pass['pass_type']}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Issue Date:</span>
                <span class="info-value">{$issueDate}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Expiry Date:</span>
                <span class="info-value">{$expiryDate}</span>
            </div>
        </div>
        
        {$qrHtml}
        
        <div class="footer">
            <p>Generated on: {$generatedOn}</p>
            <p>This is an official digital bus pass. Please keep it safe and present it when required.</p>
        </div>
    </div>
    
    <button class="print-button" onclick="window.print();">🖨️ Print Pass</button>
</body>
</html>
HTML;
            
            return array(
                'success' => true,
                'html' => $html
            );
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    private function getQRCodeHTML($qrCodePath, $passNumber = null, $userId = null, $passId = null) {
        if ((!$qrCodePath || !file_exists($qrCodePath)) && $passNumber && $userId) {
            require_once __DIR__ . '/QRCodeGenerator.php';
            require_once __DIR__ . '/BusPass.php';
            $qrGenerator = new QRCodeGenerator();
            $qrResult = $qrGenerator->generateQRCode($passNumber, $userId);
            if ($qrResult && !empty($qrResult['file_path']) && file_exists($qrResult['file_path'])) {
                $qrCodePath = $qrResult['file_path'];
                if ($passId) {
                    $busPass = new BusPass();
                    $busPass->updatePassQRCode($passId, $qrCodePath);
                }
            }
        }
        
        if ($qrCodePath && file_exists($qrCodePath)) {
            $imageData = base64_encode(file_get_contents($qrCodePath));
            $src = 'data:image/png;base64,' . $imageData;
            return '<div class="qr-code"><img src="' . $src . '" alt="QR Code" style="max-width: 160px; border: 2px solid #0066cc; padding: 10px; background-color: white;"></div>';
        }
        
        if ($passNumber) {
            $qrData = json_encode(array('pass_number' => $passNumber, 'user_id' => $userId));
            $src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($qrData);
            return '<div class="qr-code"><img src="' . $src . '" alt="QR Code" style="max-width: 160px; border: 2px solid #0066cc; padding: 10px; background-color: white;"></div>';
        }
        
        return '';
    }
}
?>
