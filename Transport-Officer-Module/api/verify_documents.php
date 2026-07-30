<?php
/**
 * API: Verify / Reject Document
 * Bus Pass Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Auth Guard
if (empty($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// CSRF Verification
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    json_response(false, 'Security token validation failed (CSRF).');
}

$doc_id = (int)($_POST['doc_id'] ?? 0);
$action = clean_input($_POST['action'] ?? '');
$app_id = (int)($_POST['application_id'] ?? 0);
$notes  = clean_input($_POST['notes'] ?? '');

if (!$doc_id || !$app_id || !in_array($action, ['verified', 'rejected'])) {
    json_response(false, 'Missing or invalid parameters.');
}

if ($action === 'rejected' && empty($notes)) {
    json_response(false, 'Rejection notes are required when rejecting a document.');
}

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    // Verify document exists and belongs to application
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = :id AND application_id = :app_id LIMIT 1");
    $stmt->execute([':id' => $doc_id, ':app_id' => $app_id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        $pdo->rollBack();
        json_response(false, 'Document not found.');
    }

    // Update document status
    $stmt = $pdo->prepare("
        UPDATE documents 
        SET status = :status, admin_notes = :notes, verified_by = :verifier, verified_at = NOW() 
        WHERE id = :id
    ");
    $stmt->execute([
        ':status' => $action,
        ':notes' => $notes,
        ':verifier' => $_SESSION['admin_id'],
        ':id' => $doc_id
    ]);

    // Check if all documents for this application are now verified
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified FROM documents WHERE application_id = :app_id");
    $stmt->execute([':app_id' => $app_id]);
    $counts = $stmt->fetch();

    $log_action = ($action === 'verified') ? 'Document Verified' : 'Document Rejected';
    $log_notes = "Document '{$doc['doc_label']}' marked as " . ucfirst($action) . ". " . ($notes ? "Notes: {$notes}" : "");
    
    // Log the individual document verification action
    log_application_action($app_id, $log_action, null, null, $_SESSION['admin_id'], $log_notes);

    // If all documents verified successfully, transition application status
    if ($counts && (int)$counts['total'] > 0 && (int)$counts['total'] === (int)$counts['verified']) {
        // Fetch current application status
        $stmt = $pdo->prepare("SELECT status FROM applications WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $app_id]);
        $app_row = $stmt->fetch();
        
        if ($app_row && in_array($app_row['status'], ['pending', 'under_review', 'correction_requested'])) {
            update_application_status($app_id, 'docs_verified', $_SESSION['admin_id'], 'All uploaded documents verified successfully.');
        }
    }

    $pdo->commit();
    json_response(true, 'Document status updated successfully.');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Verify Document API Error: ' . $e->getMessage());
    json_response(false, 'An error occurred while updating the document status.');
}
