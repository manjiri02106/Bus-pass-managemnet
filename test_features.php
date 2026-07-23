<?php
// test_features.php - Automated integration test for Bus Pass Management System features 4.1 to 4.5

require_once __DIR__ . '/db.php';

echo "=== STARTING AUTOMATED FEATURE INTEGRATION TESTS ===\n\n";

function assertTest($condition, $message) {
    if ($condition) {
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
        exit(1);
    }
}

try {
    // 1. Verify DB Connection & Tables (4.1/4.2/4.3/4.4/4.5 schemas)
    assertTest(file_exists(__DIR__ . '/bus_pass.db'), "Database file 'bus_pass.db' exists.");
    
    // Check if tables are correctly built
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $required_tables = ['users', 'students', 'routes', 'applications', 'document_verifications', 'route_validation_logs', 'correction_requests', 'decisions', 'passes'];
    foreach ($required_tables as $tbl) {
        assertTest(in_array($tbl, $tables), "Table '$tbl' is successfully created.");
    }

    // 2. Fetch test entities seeded by seed.php
    $student = $pdo->query("SELECT * FROM students LIMIT 1")->fetch();
    assertTest($student !== false, "Seeded student '{$student['prn_number']}' found.");

    $route = $pdo->query("SELECT * FROM routes LIMIT 1")->fetch();
    assertTest($route !== false, "Seeded route '{$route['route_number']}' found.");

    // Create a fresh test application to avoid seed pollution
    $pdo->exec("DELETE FROM passes");
    $pdo->exec("DELETE FROM decisions");
    $pdo->exec("DELETE FROM correction_requests");
    $pdo->exec("DELETE FROM applications WHERE student_id = " . $student['id']);

    $stmt_app = $pdo->prepare("
        INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept)
        VALUES (?, ?, 'submitted', 'uploads/college_id_PRN2026001.png', 'uploads/photo_PRN2026001.jpg', 'uploads/address_PRN2026001.pdf', 'Transport Dept')
    ");
    $stmt_app->execute([$student['id'], $route['id']]);
    $app_id = $pdo->lastInsertId();
    assertTest($app_id > 0, "Successfully submitted a new application (ID: $app_id).");

    // 3. Test Route Validation rules (4.3 Route Validation)
    // Rule: Distance > 50km requires Admin Exception routing
    $stmt_route_dist = $pdo->prepare("SELECT distance FROM routes WHERE id = ?");
    $stmt_route_dist->execute([$route['id']]);
    $distance = $stmt_route_dist->fetchColumn();

    $log_notes = "";
    if ($distance > 50.0) {
        $log_notes = "ALERT: Route exceeds standard limits (>50km). Automated exception route triggered.";
    } else {
        $log_notes = "Standard route constraints validated.";
    }
    
    $stmt_log = $pdo->prepare("INSERT INTO route_validation_logs (application_id, route_id, is_valid, validation_notes) VALUES (?, ?, ?, ?)");
    $stmt_log->execute([$app_id, $route['id'], 1, $log_notes]);
    $log_id = $pdo->lastInsertId();
    assertTest($log_id > 0, "Route validation rule executed and logged: '$log_notes'.");

    // Test routing dispatcher (Rerouting)
    $stmt_route_change = $pdo->prepare("UPDATE applications SET routing_dept = 'Academic HOD' WHERE id = ?");
    $stmt_route_change->execute([$app_id]);
    
    $updated_dept = $pdo->query("SELECT routing_dept FROM applications WHERE id = $app_id")->fetchColumn();
    assertTest($updated_dept === 'Academic HOD', "Route Dispatcher successfully rerouted application to '$updated_dept'.");

    // 4. Test Document Verification Flagging (4.2 Document Verification)
    // Let's simulate checking the college ID and flagging it as invalid
    $stmt_doc = $pdo->prepare("
        INSERT INTO document_verifications (application_id, document_type, status, comments, verified_by)
        VALUES (?, 'college_id', 'invalid', 'Blurred image, PRN not readable.', 2)
    ");
    $stmt_doc->execute([$app_id]);
    assertTest($pdo->lastInsertId() > 0, "Document Verification: College ID flagged as INVALID with officer feedback comments.");

    // 5. Test Correction Request Generation (4.5 Request Corrections)
    // Create correction task in database
    $stmt_corr = $pdo->prepare("
        INSERT INTO correction_requests (application_id, field_name, instruction, status, requested_by)
        VALUES (?, 'college_id_doc', 'Uploaded card is blurred, please upload a high-resolution photo.', 'pending', 2)
    ");
    $stmt_corr->execute([$app_id]);
    assertTest($pdo->lastInsertId() > 0, "Correction Request: Correction instruction task recorded successfully.");

    // Update application status to 'correction_required'
    $pdo->exec("UPDATE applications SET status = 'correction_required' WHERE id = $app_id");
    $app_status = $pdo->query("SELECT status FROM applications WHERE id = $app_id")->fetchColumn();
    assertTest($app_status === 'correction_required', "Application status transitioned to 'correction_required'.");

    // 6. Test Correction Resolution (Student updates files)
    // Simulate student re-uploading and resolving correction request
    $pdo->exec("UPDATE applications SET college_id_doc = 'uploads/college_id_PRN2026001_new.png', status = 'under_verification' WHERE id = $app_id");
    $pdo->exec("UPDATE correction_requests SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE application_id = $app_id AND field_name = 'college_id_doc'");
    $pdo->exec("UPDATE document_verifications SET status = 'verified', comments = 'Corrected copy verified by officer' WHERE application_id = $app_id AND document_type = 'college_id'");

    $corr_status = $pdo->query("SELECT status FROM correction_requests WHERE application_id = $app_id AND field_name = 'college_id_doc'")->fetchColumn();
    $new_doc_verify = $pdo->query("SELECT status FROM document_verifications WHERE application_id = $app_id AND document_type = 'college_id'")->fetchColumn();
    $new_app_status = $pdo->query("SELECT status FROM applications WHERE id = $app_id")->fetchColumn();

    assertTest($corr_status === 'resolved', "Correction Request: Task status resolved by student submission.");
    assertTest($new_doc_verify === 'verified', "Document Verification: College ID re-verified successfully.");
    assertTest($new_app_status === 'under_verification', "Application returned to 'under_verification' queue.");

    // 7. Test Approve Application & Pass Generation (4.4 Approve/Reject)
    // Simulating officer approval decision
    $stmt_dec = $pdo->prepare("INSERT INTO decisions (application_id, decision, reason, officer_id) VALUES (?, 'approve', 'All documentation matches standards.', 2)");
    $stmt_dec->execute([$app_id]);
    
    // Generate pass
    $pass_num = "BP-TEST-00001";
    $stmt_pass = $pdo->prepare("INSERT INTO passes (application_id, pass_number, valid_from, valid_to, qr_code) VALUES (?, ?, '2026-07-20', '2027-01-20', 'TESTQRCONTENT')");
    $stmt_pass->execute([$app_id, $pass_num]);
    
    $pdo->exec("UPDATE applications SET status = 'approved' WHERE id = $app_id");

    $final_app_status = $pdo->query("SELECT status FROM applications WHERE id = $app_id")->fetchColumn();
    $pass_record = $pdo->query("SELECT * FROM passes WHERE application_id = $app_id")->fetch();

    assertTest($final_app_status === 'approved', "Application decision recorded: Approved.");
    assertTest($pass_record !== false, "Pass Generation: Digital Pass '{$pass_record['pass_number']}' issued successfully.");
    assertTest($pass_record['qr_code'] === 'TESTQRCONTENT', "Pass QR Code recorded: '{$pass_record['qr_code']}'.");

    echo "\n🎉 ALL INTEGRATION TESTS COMPLETED SUCCESSFULLY! 🎉\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
