<?php
/**
 * REST API Endpoint - Pass Verification Service
 * Used for QR Code validation by transit inspectors / conductor devices.
 * Accepts a 'code' parameter (e.g. VAL-BP-xxxxxxxx-xxxx).
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../database/db.php';

$code = trim($_GET['code'] ?? $_POST['code'] ?? '');

if (empty($code)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'Missing verification token parameter: code'
    ]);
    exit();
}

try {
    $db = Database::connect();
    
    // Fetch pass and passenger details
    $stmt = $db->prepare("
        SELECT p.pass_number, p.start_date, p.end_date, p.status, p.price,
               u.name AS passenger_name, u.email AS passenger_email, u.profile_pic,
               r.route_code, r.source, r.destination,
               c.name AS category_name
        FROM passes p
        INNER JOIN users u ON p.user_id = u.id
        INNER JOIN routes r ON p.route_id = r.id
        INNER JOIN categories c ON p.category_id = c.id
        WHERE p.qr_code_data = :code
        LIMIT 1
    ");
    $stmt->execute(['code' => $code]);
    $pass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pass) {
        http_response_code(404);
        echo json_encode([
            'success' => true,
            'valid' => false,
            'message' => 'Invalid pass: Token signature not found in transit ledger.'
        ]);
        exit();
    }

    $today = date('Y-m-d');
    $is_active = ($pass['status'] === 'approved');
    $is_expired = ($today > $pass['end_date']);
    $is_started = ($today >= $pass['start_date']);

    $valid = false;
    $status_msg = '';

    if (!$is_active) {
        if ($pass['status'] === 'pending') {
            $status_msg = 'Verification Pending: This pass has not been authorized by administrator.';
        } else {
            $status_msg = 'Pass Rejected: This pass application was declined.';
        }
    } elseif ($is_expired) {
        $status_msg = 'Pass Expired: Validity period ended on ' . date('M d, Y', strtotime($pass['end_date'])) . '.';
    } elseif (!$is_started) {
        $status_msg = 'Pass Not Yet Active: Validity starts on ' . date('M d, Y', strtotime($pass['start_date'])) . '.';
    } else {
        $valid = true;
        $status_msg = 'Pass Verified: Valid active transit ticket.';
    }

    // Construct response
    $response = [
        'success' => true,
        'valid' => $valid,
        'message' => $status_msg,
        'details' => [
            'pass_number' => $pass['pass_number'],
            'passenger' => [
                'name' => $pass['passenger_name'],
                'email' => $pass['passenger_email'],
                'photo' => $pass['profile_pic'] ? '/Bus-pass-managemnet/assets/uploads/' . $pass['profile_pic'] : null
            ],
            'route' => [
                'code' => $pass['route_code'],
                'source' => $pass['source'],
                'destination' => $pass['destination']
            ],
            'tier' => $pass['category_name'],
            'validity' => [
                'starts' => $pass['start_date'],
                'expires' => $pass['end_date']
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'Verification Service Error: ' . $e->getMessage()
    ]);
}
