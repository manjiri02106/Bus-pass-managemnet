<?php
/**
 * API: Update Application Status (Approve / Reject)
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
$action  = clean_input($_POST['action'] ?? '');
$remarks = clean_input($_POST['remarks'] ?? '');

if (!$app_id || !in_array($action, ['approved', 'rejected'])) {
    json_response(false, 'Missing or invalid parameters.');
}

if ($action === 'rejected' && empty($remarks)) {
    json_response(false, 'Rejection reason is required.');
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
        json_response(false, "Application is already in '{$app['status']}' state.");
    }

    // Role verification for approval
    if ($action === 'approved' && !admin_has_role('admin')) {
        $pdo->rollBack();
        json_response(false, 'Insufficient privileges to approve applications.');
    }

    if ($action === 'approved') {
        // Call stored procedure to generate a new pass number
        $stmt = $pdo->prepare("CALL sp_generate_pass_number(:app_id, :route_number, @pass_num)");
        $stmt->execute([
            ':app_id' => $app_id,
            ':route_number' => $app['route_number']
        ]);
        
        // Fetch the generated pass number
        $pass_row = $pdo->query("SELECT @pass_num AS pass_num")->fetch();
        $pass_number = $pass_row['pass_num'] ?? '';

        if (empty($pass_number)) {
            // Fallback generation logic if stored procedure returns empty
            $pass_number = 'PASS-' . $app['route_number'] . '-' . date('Y') . '-' . str_pad($app_id, 4, '0', STR_PAD_LEFT);
        }

        // Calculate pass dates
        $start_date = date('Y-m-d');
        $duration = match($app['pass_type']) {
            'quarterly' => '+3 months',
            'annual'    => '+1 year',
            default     => '+1 month',
        };
        $end_date = date('Y-m-d', strtotime($duration, strtotime($start_date)));

        $extra_fields = [
            'pass_number'     => $pass_number,
            'pass_start_date' => $start_date,
            'pass_end_date'   => $end_date,
            'admin_remarks'   => $remarks,
            'approved_at'     => date('Y-m-d H:i:s')
        ];

        update_application_status($app_id, 'approved', $_SESSION['admin_id'], "Pass issued successfully.", $extra_fields);

        // Audit Trail Action Detail
        log_application_action(
            $app_id,
            'Application Approved',
            $app['status'],
            'approved',
            $_SESSION['admin_id'],
            "Pass No: {$pass_number} generated. Valid from {$start_date} to {$end_date}."
        );

        // Stub: Email Notification
        $email_subject = "Your Bus Pass Application has been Approved!";
        $email_body = "<h1>Congratulations, {$app['applicant_name']}!</h1>
                       <p>Your bus pass application <strong>{$app['application_number']}</strong> has been approved.</p>
                       <p>Your new bus pass number is <strong>{$pass_number}</strong>.</p>
                       <p>Valid from: {$start_date} to {$end_date}.</p>";
        send_email($app['applicant_email'], $email_subject, $email_body);

        $pdo->commit();
        json_response(true, 'Application successfully approved. Bus pass number generated: ' . $pass_number);

    } else {
        // Action: Reject
        $extra_fields = [
            'rejection_reason' => $remarks
        ];

        update_application_status($app_id, 'rejected', $_SESSION['admin_id'], "Application rejected by reviewer.", $extra_fields);

        log_application_action(
            $app_id,
            'Application Rejected',
            $app['status'],
            'rejected',
            $_SESSION['admin_id'],
            "Reason: {$remarks}"
        );

        // Stub: Email Notification
        $email_subject = "Bus Pass Application Update";
        $email_body = "<h1>Hello, {$app['applicant_name']}</h1>
                       <p>We regret to inform you that your bus pass application <strong>{$app['application_number']}</strong> has been rejected.</p>
                       <p><strong>Reason:</strong> {$remarks}</p>
                       <p>Please log in to submit a fresh application if you wish to apply again.</p>";
        send_email($app['applicant_email'], $email_subject, $email_body);

        $pdo->commit();
        json_response(true, 'Application successfully rejected.');
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Update Application Status API Error: ' . $e->getMessage());
    json_response(false, 'An error occurred while updating the application status.');
}
