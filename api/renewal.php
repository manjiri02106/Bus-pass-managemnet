<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/RenewalRequest.php';
require_once dirname(__DIR__) . '/classes/RenewalApprovalWorkflow.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'create_renewal_request':
            createRenewalRequest();
            break;
            
        case 'get_renewal_request':
            getRenewalRequest();
            break;
            
        case 'get_user_renewal_requests':
            getUserRenewalRequests();
            break;
            
        case 'get_pending_requests':
            getPendingRequests();
            break;
            
        case 'approve_renewal_request':
            approveRenewalRequest();
            break;
            
        case 'reject_renewal_request':
            rejectRenewalRequest();
            break;
            
        case 'cancel_renewal_request':
            cancelRenewalRequest();
            break;
            
        case 'bulk_approve_requests':
            bulkApproveRequests();
            break;
            
        case 'auto_approve_requests':
            autoApproveRequests();
            break;
            
        case 'get_workflow_status':
            getWorkflowStatus();
            break;
            
        case 'get_renewal_statistics':
            getRenewalStatistics();
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
 * Create renewal request
 */
function createRenewalRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['pass_id']) || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields'));
        return;
    }
    
    $renewalRequest = new RenewalRequest();
    $result = $renewalRequest->createRenewalRequest($data['pass_id'], $data['user_id']);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Get renewal request details
 */
function getRenewalRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $requestId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing request ID'));
        return;
    }
    
    $renewalRequest = new RenewalRequest();
    $request = $renewalRequest->getRenewalRequest($requestId);
    
    if ($request) {
        echo json_encode(array('success' => true, 'request' => $request));
    } else {
        http_response_code(404);
        echo json_encode(array('error' => 'Request not found'));
    }
}

/**
 * Get renewal requests for a user
 */
function getUserRenewalRequests() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing user ID'));
        return;
    }
    
    $renewalRequest = new RenewalRequest();
    $requests = $renewalRequest->getUserRenewalRequests($userId, $status, $limit, $offset);
    
    echo json_encode(array('success' => true, 'requests' => $requests));
}

/**
 * Get all pending renewal requests
 */
function getPendingRequests() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $renewalRequest = new RenewalRequest();
    $requests = $renewalRequest->getPendingRequests($limit, $offset);
    $count = $renewalRequest->getPendingRequestsCount();
    
    echo json_encode(array(
        'success' => true,
        'requests' => $requests,
        'total' => $count,
        'limit' => $limit,
        'offset' => $offset
    ));
}

/**
 * Approve renewal request
 */
function approveRenewalRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['request_id']) || !isset($data['approver_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields'));
        return;
    }
    
    $comments = isset($data['comments']) ? $data['comments'] : null;
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->approveRenewalRequest($data['request_id'], $data['approver_id'], $comments);
    
    if ($result['success']) {
        // Send notification if available
        if (isset($result['notification'])) {
            $workflow->sendNotification($result['notification']);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Reject renewal request
 */
function rejectRenewalRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['request_id']) || !isset($data['approver_id']) || !isset($data['rejection_reason'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields'));
        return;
    }
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->rejectRenewalRequest($data['request_id'], $data['approver_id'], $data['rejection_reason']);
    
    if ($result['success']) {
        // Send notification if available
        if (isset($result['notification'])) {
            $workflow->sendNotification($result['notification']);
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Cancel renewal request
 */
function cancelRenewalRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['request_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing request ID'));
        return;
    }
    
    $renewalRequest = new RenewalRequest();
    $result = $renewalRequest->cancelRenewalRequest($data['request_id']);
    
    if ($result) {
        echo json_encode(array('success' => true, 'message' => 'Renewal request cancelled'));
    } else {
        http_response_code(400);
        echo json_encode(array('error' => 'Failed to cancel renewal request'));
    }
}

/**
 * Bulk approve renewal requests
 */
function bulkApproveRequests() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['request_ids']) || !isset($data['approver_id'])) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing required fields'));
        return;
    }
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->bulkApproveRequests($data['request_ids'], $data['approver_id']);
    
    echo json_encode(array('success' => true, 'result' => $result));
}

/**
 * Auto approve eligible renewal requests
 */
function autoApproveRequests() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $approverId = isset($data['approver_id']) ? $data['approver_id'] : null;
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->autoApproveEligibleRequests($approverId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Get workflow status
 */
function getWorkflowStatus() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : null;
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(array('error' => 'Missing request ID'));
        return;
    }
    
    $workflow = new RenewalApprovalWorkflow();
    $result = $workflow->getWorkflowStatus($requestId);
    
    echo json_encode($result);
}

/**
 * Get renewal statistics
 */
function getRenewalStatistics() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(array('error' => 'Method not allowed'));
        return;
    }
    
    $renewalRequest = new RenewalRequest();
    $stats = $renewalRequest->getRenewalStatistics();
    
    echo json_encode(array('success' => true, 'statistics' => $stats));
}
?>
