<?php
/**
 * Process Payment
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student_id = (int)$_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/my_applications.php', 'Invalid request.', 'danger');
}

$pass_id = (int)($_POST['pass_id'] ?? 0);
$payment_method = sanitize($_POST['payment_method'] ?? '');
$transaction_id = sanitize($_POST['transaction_id'] ?? '');

if (!$pass_id || !$payment_method || !$transaction_id || strlen(trim($transaction_id)) < 12) {
    redirect('/payment.php?pass_id=' . $pass_id, 'Invalid payment details. Transaction ID must be at least 12 characters long.', 'danger');
}

// Fetch pass details with route info
$q = "SELECT bp.*, r.route_name, r.source, r.destination 
      FROM bus_passes bp 
      JOIN routes r ON bp.route_id = r.id 
      WHERE bp.id = ? AND bp.student_id = ?";
$s = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($s, 'ii', $pass_id, $student_id);
mysqli_stmt_execute($s);
$pass = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

if (!$pass) {
    redirect('/my_applications.php', 'Pass not found.', 'danger');
}

if ($pass['payment_status'] === 'Paid') {
    redirect('/my_applications.php', 'Payment already completed.', 'success');
}

// Use user-provided transaction ID
$payment_date = date('Y-m-d H:i:s');

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update bus_passes
    $update_q = "UPDATE bus_passes 
                 SET payment_status = 'Paid', 
                     payment_method = ?, 
                     payment_date = ? 
                 WHERE id = ?";
    $update_s = mysqli_prepare($conn, $update_q);
    mysqli_stmt_bind_param($update_s, 'ssi', $payment_method, $payment_date, $pass_id);
    mysqli_stmt_execute($update_s);

    // Insert into payments table
    $insert_payment_q = "INSERT INTO payments (pass_id, student_id, amount, payment_method, transaction_id, payment_status, payment_date) 
                         VALUES (?, ?, ?, ?, ?, 'Success', ?)";
    $insert_payment_s = mysqli_prepare($conn, $insert_payment_q);
    mysqli_stmt_bind_param($insert_payment_s, 'iidsss', $pass_id, $student_id, $pass['fee'], $payment_method, $transaction_id, $payment_date);
    mysqli_stmt_execute($insert_payment_s);

    // Create notification
    $notif_q = "INSERT INTO notifications (student_id, title, message, type) 
                VALUES (?, 'Payment Successful', 'Your payment for bus pass (Ref: " . $pass['application_no'] . ") has been completed successfully. Transaction ID: " . $transaction_id . "', 'success')";
    $notif_s = mysqli_prepare($conn, $notif_q);
    mysqli_stmt_bind_param($notif_s, 'i', $student_id);
    mysqli_stmt_execute($notif_s);

    // Commit transaction
    mysqli_commit($conn);

    // Decode all data from database first
    $decoded_pass = decode_db_data($pass);
    
    // Store payment details in session for success page
    $_SESSION['payment_success'] = [
        'transaction_id' => decode_db_data($transaction_id),
        'amount' => $decoded_pass['fee'],
        'payment_method' => decode_db_data($payment_method),
        'payment_date' => $payment_date,
        'application_no' => $decoded_pass['application_no'],
        'pass_type' => $decoded_pass['pass_type'],
        'route_name' => $decoded_pass['route_name'],
        'source' => $decoded_pass['source'],
        'destination' => $decoded_pass['destination'],
        'valid_from' => $decoded_pass['valid_from'],
        'valid_until' => $decoded_pass['valid_until']
    ];

    // Redirect to payment success page
    redirect('/payment_success.php');
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    redirect('/payment.php?pass_id=' . $pass_id, 'Payment failed. Please try again.', 'danger');
}
?>