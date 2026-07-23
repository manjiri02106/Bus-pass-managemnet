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

if (!$pass_id || !$payment_method) {
    redirect('/payment.php?pass_id=' . $pass_id, 'Invalid payment details.', 'danger');
}

// Fetch pass details
$q = "SELECT * FROM bus_passes WHERE id = ? AND student_id = ?";
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

// Simulate successful payment
$transaction_id = 'TXN' . strtoupper(uniqid());
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

    redirect('/my_applications.php', 'Payment completed successfully! Transaction ID: ' . $transaction_id, 'success');
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    redirect('/payment.php?pass_id=' . $pass_id, 'Payment failed. Please try again.', 'danger');
}
?>