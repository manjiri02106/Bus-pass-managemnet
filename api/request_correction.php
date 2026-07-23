<?php
/**
 * API: Request Corrections
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

$app_id  = (int)($_POST['application_id'] ?? 0);
$fields  = $_POST['fields_to_fix'] ?? [];
$message = clean_input($_POST['correction_message'] ?? '');

if (!$app_id || empty($fields) || empty($message)) {
    json_response(false, 'Please select at least one field and enter a message.');
}

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    $app = get_application($app_id);
    if (!$app) {
        $pdo->rollBack();
        json_response(false, 'Application not found.');
    }

    if (in_array($app['status'], ['approved', 'rejected'])) {
        $pdo->rollBack();
        json_response(false, 'Cannot request corrections for finalized applications.');
    }

    // JSON encode the fields array
    $fields_json = json_encode($fields);

    // Insert correction request
    $stmt = $pdo->prepare("
        INSERT INTO correction_requests (application_id, requested_by, fields_to_fix, message, status, created_at)
        VALUES (:app_id, :requested_by, :fields, :msg, 'open', NOW())
    ");
    $stmt->execute([
        ':app_id'       => $app_id,
        ':requested_by' => $_SESSION['admin_id'],
        ':fields'       => $fields_json,
        ':msg'          => $message
    ]);

    // Update application status to correction_requested
    $fields_text = implode(', ', array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), $fields));
    $notes = "Correction requested on fields: [{$fields_text}]. Message sent: \"{$message}\"";

    $updated = update_application_status($app_id, 'correction_requested', $_SESSION['admin_id'], $notes);
    if (!$updated) {
        $pdo->rollBack();
        json_response(false, 'Failed to update application status.');
    }

    // Stub: Email Notification
    $email_subject = "Action Required: Correction Requested for Bus Pass Application";
    $email_body = "<h1>Dear {$app['applicant_name']},</h1>
                   <p>Your bus pass application <strong>{$app['application_number']}</strong> requires correction.</p>
                   <p><strong>Items to Correct:</strong> {$fields_text}</p>
                   <p><strong>Details:</strong><br>{$message}</p>
                   <p>Please log in to your account, correct the details, and re-submit your application.</p>";
    send_email($app['applicant_email'], $email_subject, $email_body);

    $pdo->commit();
    json_response(true, 'Correction request successfully sent to the applicant.');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Request Correction API Error: ' . $e->getMessage());
    json_response(false, 'An error occurred while sending the correction request.');
}
