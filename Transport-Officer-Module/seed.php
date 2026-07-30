<?php
// seed.php - Seed database with initial records

require_once __DIR__ . '/db.php';

try {
    // 1. Create upload folder if not exists
    $upload_dir = __DIR__ . '/uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Write some dummy content to simulate uploaded files
    file_put_contents($upload_dir . '/college_id_PRN2026001.png', 'MOCK COLLEGE ID IMAGE DATA');
    file_put_contents($upload_dir . '/photo_PRN2026001.jpg', 'MOCK PHOTOGRAPH IMAGE DATA');
    file_put_contents($upload_dir . '/address_PRN2026001.pdf', 'MOCK ADDRESS PROOF PDF DATA');

    // Clean existing tables to prevent duplicate key errors during seeding
    $pdo->exec("DELETE FROM passes");
    $pdo->exec("DELETE FROM decisions");
    $pdo->exec("DELETE FROM correction_requests");
    $pdo->exec("DELETE FROM route_validation_logs");
    $pdo->exec("DELETE FROM document_verifications");
    $pdo->exec("DELETE FROM applications");
    $pdo->exec("DELETE FROM routes");
    $pdo->exec("DELETE FROM students");

    // Reset sqlite sequences if using SQLite
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec("DELETE FROM sqlite_sequence");
    }

    // 2. Insert Users (using INSERT IGNORE for unified auth table)
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
    
    // Passwords hashed using bcrypt
    $admin_pw = password_hash('admin123', PASSWORD_DEFAULT);
    $officer_pw = password_hash('officer123', PASSWORD_DEFAULT);
    $student_pw = password_hash('student123', PASSWORD_DEFAULT);
    $student2_pw = password_hash('student456', PASSWORD_DEFAULT);

    $stmt->execute(['admin_officer', $admin_pw, 'admin@college.edu', 'admin', 'System Administrator']);
    $stmt->execute(['officer_demo', $officer_pw, 'officer@college.edu', 'officer', 'Officer John Doe']);
    $stmt->execute(['student_demo1', $student_pw, 'student@college.edu', 'student', 'Alice Smith']);
    $stmt->execute(['student_demo2', $student2_pw, 'student2@college.edu', 'student', 'Bob Jones']);

    // Fetch user IDs
    $stmt_get_user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_get_user->execute(['student@college.edu']);
    $student_user_id = $stmt_get_user->fetchColumn();

    $stmt_get_user->execute(['student2@college.edu']);
    $student2_user_id = $stmt_get_user->fetchColumn();

    // 3. Insert Students
    $stmt_student = $pdo->prepare("INSERT INTO students (user_id, prn_number, roll_number, department, class, mobile, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_student->execute([$student_user_id, 'PRN2026001', 'CS-401', 'Computer Science', 'Final Year', '9876543210', 'Flat 102, Green Avenue, Sector 5, Pune']);
    $student_id = $pdo->lastInsertId();

    $stmt_student->execute([$student2_user_id, 'PRN2026002', 'ME-302', 'Mechanical Engineering', 'Third Year', '9123456789', 'Bungalow 4, River View Road, Pune']);
    $student2_id = $pdo->lastInsertId();

    // 4. Insert Routes
    $stmt_route = $pdo->prepare("INSERT INTO routes (route_number, source, destination, stops, distance, bus_number, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_route->execute(['R-101', 'Station', 'College Campus', 'Station, Highway, Sector 4, Campus', 12.5, 'MH-12-AQ-1234', 'active']);
    $route1_id = $pdo->lastInsertId();
    
    $stmt_route->execute(['R-102', 'Downtown', 'College Campus', 'Downtown, Metro Hub, Bypass Road, Campus', 24.0, 'MH-12-AQ-5678', 'active']);
    $route2_id = $pdo->lastInsertId();

    $stmt_route->execute(['R-103', 'Outer Ring Road', 'College Campus', 'Outer Ring, Toll Plaza, Bypass, Campus', 58.5, 'MH-12-AQ-9012', 'active']);
    $route3_id = $pdo->lastInsertId();

    // 5. Insert Applications
    $stmt_app = $pdo->prepare("INSERT INTO applications (student_id, route_id, status, college_id_doc, photograph_doc, address_proof_doc, routing_dept) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Application 1: Alice has submitted on R-101, needs verification
    $stmt_app->execute([
        $student_id,
        $route1_id,
        'submitted',
        'uploads/college_id_PRN2026001.png',
        'uploads/photo_PRN2026001.jpg',
        'uploads/address_PRN2026001.pdf',
        'Transport Dept'
    ]);

    // Application 2: Bob has submitted on R-103 (Long distance route, triggers route validation warnings)
    $stmt_app->execute([
        $student2_id,
        $route3_id,
        'submitted',
        'uploads/college_id_PRN2026001.png',
        'uploads/photo_PRN2026001.jpg',
        'uploads/address_PRN2026001.pdf',
        'Transport Dept'
    ]);

    echo "Database seeded successfully!\n";
    echo "Users created:\n";
    echo "  - Admin: admin / admin123\n";
    echo "  - Officer: officer / officer123\n";
    echo "  - Student: student / student123\n";
    echo "  - Student 2: student2 / student456\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage());
}
?>
