<?php
// scripts/seed.php - Seeds default users, routes, and test applications

require_once dirname(__DIR__) . '/config/db-connection.php';

try {
    $upload_dir = dirname(__DIR__) . '/uploads';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    file_put_contents($upload_dir . '/college_id_PRN2026001.png', 'MOCK COLLEGE ID IMAGE DATA');
    file_put_contents($upload_dir . '/photo_PRN2026001.jpg',      'MOCK PHOTOGRAPH IMAGE DATA');
    file_put_contents($upload_dir . '/address_PRN2026001.pdf',    'MOCK ADDRESS PROOF PDF DATA');

    // Wipe existing seed data
    foreach (['passes','decisions','correction_requests','route_validation_logs','document_verifications','applications','routes','students','users'] as $tbl) {
        $pdo->exec("DELETE FROM $tbl");
    }
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("DELETE FROM sqlite_sequence");
    }

    // Users
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?,?,?,?,?)");
    $stmt->execute(['admin',    password_hash('admin123',   PASSWORD_DEFAULT), 'admin@college.edu',    'admin',   'System Administrator']);
    $stmt->execute(['officer',  password_hash('officer123', PASSWORD_DEFAULT), 'officer@college.edu',  'officer', 'Officer John Doe']);
    $stmt->execute(['student',  password_hash('student123', PASSWORD_DEFAULT), 'student@college.edu',  'student', 'Alice Smith']);
    $s_id = $pdo->lastInsertId();
    $stmt->execute(['student2', password_hash('student456', PASSWORD_DEFAULT), 'student2@college.edu', 'student', 'Bob Jones']);
    $s2_id = $pdo->lastInsertId();

    // Students
    $st = $pdo->prepare("INSERT INTO students (user_id, prn_number, roll_number, department, class, mobile, address) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$s_id,  'PRN2026001', 'CS-401', 'Computer Science',       'Final Year',  '9876543210', 'Flat 102, Green Avenue, Pune']);
    $stu1 = $pdo->lastInsertId();
    $st->execute([$s2_id, 'PRN2026002', 'ME-302', 'Mechanical Engineering', 'Third Year',  '9123456789', 'Bungalow 4, River View Road, Pune']);
    $stu2 = $pdo->lastInsertId();

    // Routes
    $rt = $pdo->prepare("INSERT INTO routes (route_number, source, destination, stops, distance, bus_number, status) VALUES (?,?,?,?,?,?,?)");
    $rt->execute(['R-101', 'Station',       'College Campus', 'Station, Highway, Sector 4, Campus', 12.5, 'MH-12-AQ-1234', 'active']);
    $r1 = $pdo->lastInsertId();
    $rt->execute(['R-102', 'Downtown',      'College Campus', 'Downtown, Metro Hub, Bypass, Campus', 24.0, 'MH-12-AQ-5678', 'active']);
    $rt->execute(['R-103', 'Outer Ring Rd', 'College Campus', 'Outer Ring, Toll, Bypass, Campus',    58.5, 'MH-12-AQ-9012', 'active']);
    $r3 = $pdo->lastInsertId();

    // Applications
    $ap = $pdo->prepare("INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept) VALUES (?,?,?,?,?,?,?)");
    $ap->execute([$stu1, $r1, 'submitted', 'uploads/college_id_PRN2026001.png', 'uploads/photo_PRN2026001.jpg', 'uploads/address_PRN2026001.pdf', 'Transport Dept']);
    $ap->execute([$stu2, $r3, 'submitted', 'uploads/college_id_PRN2026001.png', 'uploads/photo_PRN2026001.jpg', 'uploads/address_PRN2026001.pdf', 'Transport Dept']);

    echo "✅ Database seeded successfully!\n";
    echo "   Accounts: admin/admin123 | officer/officer123 | student/student123 | student2/student456\n";
} catch (Exception $e) {
    die("❌ Seeding failed: " . $e->getMessage());
}
