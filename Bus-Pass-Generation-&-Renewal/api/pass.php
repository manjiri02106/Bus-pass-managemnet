<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/User.php';
require_once dirname(__DIR__) . '/classes/PassNumberGenerator.php';
require_once dirname(__DIR__) . '/classes/QRCodeGenerator.php';
require_once dirname(__DIR__) . '/classes/BusPass.php';
require_once dirname(__DIR__) . '/classes/PassDownloadManager.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'generate_pass':
            generatePass();
            break;
            
        case 'get_pass':
            getPass();
            break;
            
        case 'download_pass':
            downloadPass();
            break;
            
        case 'generate_qrcode':
            generateQRCode();
            break;
            
        case 'verify_pass':
            verifyPass();
            break;
            
        case 'renew_pass':
            renewPass();
            break;
            
        case 'cancel_pass':
            cancelPass();
            break;
            
        case 'get_pass_statistics':
            getPassStatistics();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(array('error' => 'Unknown action'));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}

/**
 * Generate a new pass
 */
function generatePass() {
    global $db;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if pass_type is provided
    if (!isset($data['pass_type'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required field: pass_type'));
        return;
    }
    
    $userId = null;
    
    // If user_id is provided, use it
    if (isset($data['user_id']) && !empty($data['user_id'])) {
        $userId = $data['user_id'];
    } 
    // Otherwise, try to create a new user with provided data
    else if (isset($data['first_name']) && isset($data['last_name']) && isset($data['email'])) {
        // Check if user already exists by email
        $user = new User();
        $existingUser = $user->getUserByEmail($data['email']);
        
        if ($existingUser) {
            $userId = $existingUser['id'];
        } else {
            // Create new user
            $userData = array(
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => isset($data['phone']) ? $data['phone'] : '',
                'address' => isset($data['address']) ? $data['address'] : '',
                'city' => isset($data['city']) ? $data['city'] : '',
                'state' => isset($data['state']) ? $data['state'] : '',
                'postal_code' => isset($data['postal_code']) ? $data['postal_code'] : '',
                'user_type' => isset($data['user_type']) ? $data['user_type'] : 'regular'
            );
            
            $userResult = $user->createUser($userData);
            if (!$userResult['success']) {
                http_response_code(400);
                echo json_encode(array('error' => 'Failed to create user: ' . $userResult['error']));
                return;
            }
            
            $userId = $userResult['user_id'];
        }
    } else {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields: either user_id or (first_name, last_name, email)'));
        return;
    }
    
    // Generate pass number
    $passNumberGenerator = new PassNumberGenerator();
    $passNumber = $passNumberGenerator->generatePassNumber();
    
    // Create pass
    $busPass = new BusPass();
    $result = $busPass->createPass(
        $userId,
        $passNumber,
        $data['pass_type'],
        isset($data['validity_days']) ? $data['validity_days'] : PASS_VALIDITY_DAYS
    );
    
    if ($result['success']) {
        // Generate QR code asynchronously (non-blocking)
        // Return pass immediately for fast response
        // QR code will be generated in background or on-demand
        
        set_time_limit(3); // 3 second timeout for optional QR generation
        
        $qrCodeGenerator = new QRCodeGenerator();
        @$qrResult = $qrCodeGenerator->generateQRCode($passNumber, $userId);
        
        if ($qrResult && $qrResult['success']) {
            @$busPass->updatePassQRCode($result['pass_id'], $qrResult['file_path']);
        }
        // If QR generation fails, we still return the pass - it's optional
        
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Get pass details
 */
function getPass() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $passId = isset($_GET['pass_id']) ? intval($_GET['pass_id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $passNumber = isset($_GET['pass_number']) ? trim($_GET['pass_number']) : null;
    $email = isset($_GET['email']) ? trim($_GET['email']) : null;
    $all = isset($_GET['all']) ? ($_GET['all'] === 'true' || $_GET['all'] === '1') : false;
    $format = isset($_GET['format']) ? $_GET['format'] : null;
    
    if (($format === 'print' || $format === 'html') && $passId) {
        $passDownloadManager = new PassDownloadManager();
        $result = $passDownloadManager->generatePrintablePass($passId);
        if ($result['success']) {
            header('Content-Type: text/html; charset=UTF-8');
            echo $result['html'];
            return;
        }
    }
    
    $busPass = new BusPass();
    
    if ($all) {
        $passes = $busPass->getAllPasses(50);
        echo json_encode(array('success' => true, 'data' => $passes, 'pass' => $passes));
        return;
    }
    
    if ($email) {
        $passes = $busPass->getPassesByEmail($email, 50);
        if (!empty($passes)) {
            echo json_encode(array('success' => true, 'data' => $passes, 'pass' => $passes));
        } else {
            http_response_code(404);
            echo json_encode(array('error' => 'No passes found for this email'));
        }
        return;
    }
    
    if ($passId || $passNumber) {
        $pass = $passId ? $busPass->getPassDetails($passId) : $busPass->getPassByNumber($passNumber);
        if ($pass) {
            echo json_encode(array('success' => true, 'data' => array($pass), 'pass' => $pass));
        } else {
            http_response_code(404);
            echo json_encode(array('error' => 'Pass not found'));
        }
        return;
    }
    
    http_response_code(400);
    echo json_encode(array('error' => 'Missing pass ID, pass number, email, or all parameter'));
}

/**
 * Download pass in various formats
 */
function downloadPass() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $passId = isset($_GET['pass_id']) ? intval($_GET['pass_id']) : (isset($_GET['id']) ? intval($_GET['id']) : null);
    $format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
    
    if (!$passId) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing pass ID'));
        return;
    }
    
    $passDownloadManager = new PassDownloadManager();
    
    if ($format === 'pdf') {
        $result = $passDownloadManager->generatePassPDF($passId);
        if ($result['success'] && isset($result['html'])) {
            header('Content-Type: text/html; charset=UTF-8');
            echo $result['html'];
            return;
        }
    } elseif ($format === 'png' || $format === 'jpg') {
        $result = $passDownloadManager->generatePassImage($passId, $format);
    } elseif ($format === 'html' || $format === 'print') {
        $result = $passDownloadManager->generatePrintablePass($passId);
        if ($result['success']) {
            header('Content-Type: text/html; charset=UTF-8');
            echo $result['html'];
            return;
        }
    } else {
        http_response_code(400);
        echo json_encode(array('error' => 'Invalid format'));
        return;
    }
    
    if ($result['success']) {
        // Return file path for download
        if (isset($result['file_path']) && file_exists($result['file_path'])) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $result['file_name'] . '"');
            readfile($result['file_path']);
        } else {
            echo json_encode($result);
        }
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Generate QR code
 */
function generateQRCode() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pass_number']) || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields'));
        return;
    }
    
    $qrCodeGenerator = new QRCodeGenerator();
    $result = $qrCodeGenerator->generateQRCode($data['pass_number'], $data['user_id']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Verify pass using pass number (GET) or QR code data (POST)
 */
function verifyPass() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // --- GET: verify by pass number (used by verify_pass.html) ---
    if ($method === 'GET') {
        $passNumber = isset($_GET['pass_number']) ? trim($_GET['pass_number']) : null;
        
        if (!$passNumber) {
            http_response_code(400);
            echo json_encode(array('error' => 'Missing pass number'));
            return;
        }
        
        $busPass = new BusPass();
        $pass = $busPass->getPassByNumber($passNumber);
        
        if (!$pass) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'error' => 'Pass not found'));
            return;
        }
        
        $isActive = $pass['status'] === 'active';
        $notExpired = strtotime($pass['expiry_date']) >= strtotime(date('Y-m-d'));
        $valid = $isActive && $notExpired;
        
        echo json_encode(array(
            'success' => true,
            'valid'   => $valid,
            'data'    => $pass,
            'status'  => $valid ? 'valid' : ($isActive ? 'expired' : $pass['status'])
        ));
        return;
    }
    
    // --- POST: verify by QR code data ---
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['qr_data'])) {
            http_response_code(400);
            echo json_encode(array('error' => 'Missing QR data'));
            return;
        }
        
        $qrCodeGenerator = new QRCodeGenerator();
        $result = $qrCodeGenerator->verifyQRCode($data['qr_data']);
        echo json_encode($result);
        return;
    }
    
    http_response_code(405);
    echo json_encode(array('error' => 'Method not allowed'));
}

/**
 * Renew pass
 */
function renewPass() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pass_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing pass ID'));
        return;
    }
    
    $busPass = new BusPass();
    $result = $busPass->renewPass(
        $data['pass_id'],
        isset($data['validity_days']) ? $data['validity_days'] : PASS_VALIDITY_DAYS
    );
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Cancel pass
 */
function cancelPass() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pass_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing pass ID'));
        return;
    }
    
    $busPass = new BusPass();
    $result = $busPass->cancelPass($data['pass_id']);
    
    if ($result) {
        echo json_encode(array('success' => true, 'message' => 'Pass cancelled successfully'));
    } else {
        http_response_code(400);
        echo json_encode(array('error' => 'Failed to cancel pass'));
    }
}

/**
 * Get pass statistics
 */
function getPassStatistics() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $busPass = new BusPass();
    $stats = $busPass->getPassStatistics();
    
    echo json_encode(array('success' => true, 'statistics' => $stats));
}
?>
