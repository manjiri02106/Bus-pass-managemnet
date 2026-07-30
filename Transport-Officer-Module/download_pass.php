<?php
// download_pass.php - Display printable bus pass ticket

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pass_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pass_id) {
    die("Invalid pass request.");
}

try {
    // Fetch pass details with application, student, and route details
    $query = "
        SELECT p.*, a.submission_date, a.college_id_doc, a.photograph_doc,
               s.prn_number, s.roll_number, s.department, s.class, s.mobile,
               u.full_name as student_name,
               r.route_number, r.source, r.destination, r.distance
        FROM passes p
        JOIN applications a ON p.application_id = a.id
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN routes r ON a.route_id = r.id
        WHERE p.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$pass_id]);
    $pass = $stmt->fetch();
    
    if (!$pass) {
        die("Bus Pass record not found.");
    }
    
    // Check security: must be either the owner student, or an officer/admin
    if ($_SESSION['user_role'] === 'student') {
        // Find if this pass belongs to logged in student
        $stmt_stud = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
        $stmt_stud->execute([$_SESSION['user_id']]);
        $student_id = $stmt_stud->fetchColumn();
        
        // Find student_id of the pass
        $stmt_pass_stud = $pdo->prepare("SELECT student_id FROM applications WHERE id = ?");
        $stmt_pass_stud->execute([$pass['application_id']]);
        $pass_student_id = $stmt_pass_stud->fetchColumn();
        
        if ($student_id != $pass_student_id) {
            die("Unauthorized access. You can only view your own passes.");
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass - <?php echo htmlspecialchars($pass['pass_number']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0b0f19;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .ticket-outer {
            max-width: 600px;
            width: 100%;
        }
        
        /* Print media styles */
        @media print {
            body {
                background: white;
                color: black;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .pass-ticket {
                border: 2px solid #000 !important;
                box-shadow: none !important;
                background: white !important;
                color: black !important;
            }
            .pass-ticket td, .pass-ticket strong, .pass-ticket h3 {
                color: black !important;
            }
            .pass-qr {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>

    <div class="ticket-outer">
        <div class="no-print" style="display: flex; justify-content: space-between; margin-bottom: 20px; width: 100%;">
            <a href="student_dashboard.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa-solid fa-print"></i> Print / Save as PDF
            </button>
        </div>
        
        <div class="pass-ticket">
            <div class="pass-header">
                <div>
                    <h3 style="color: white; margin-bottom: 4px;">OFFICIAL BUS PASS</h3>
                    <span style="font-size: 0.75rem; background: var(--success); color: white; padding: 2px 6px; border-radius:4px; font-weight:700;">VALID</span>
                </div>
                <div style="text-align: right;">
                    <span style="color: var(--text-secondary); font-size: 0.8rem; display:block;">PASS NO:</span>
                    <strong style="color: var(--accent); font-size: 1.25rem;"><?php echo htmlspecialchars($pass['pass_number']); ?></strong>
                </div>
            </div>
            <div class="pass-body">
                <div>
                    <table style="width: 100%; border: none;">
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">NAME:</td><td style="padding: 6px 0; border: none; color: white;"><strong><?php echo htmlspecialchars($pass['student_name']); ?></strong></td></tr>
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">PRN:</td><td style="padding: 6px 0; border: none; color: white;"><?php echo htmlspecialchars($pass['prn_number']); ?></td></tr>
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">CLASS/DEPT:</td><td style="padding: 6px 0; border: none; color: white;"><?php echo htmlspecialchars($pass['class'] . ' / ' . $pass['department']); ?></td></tr>
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">ROUTE:</td><td style="padding: 6px 0; border: none; color: white;"><?php echo htmlspecialchars($pass['route_number']); ?> (<?php echo htmlspecialchars($pass['source']); ?> &rarr; <?php echo htmlspecialchars($pass['destination']); ?>)</td></tr>
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID FROM:</td><td style="padding: 6px 0; border: none; color: white;"><?php echo date('d-M-Y', strtotime($pass['valid_from'])); ?></td></tr>
                        <tr style="background: none;"><td style="padding: 6px 0; border: none; font-size: 0.85rem; color: var(--text-muted);">VALID UNTIL:</td><td style="padding: 6px 0; border: none; color: var(--success); font-weight: 600;"><?php echo date('d-M-Y', strtotime($pass['valid_to'])); ?></td></tr>
                    </table>
                </div>
                <div>
                    <div class="pass-qr" title="<?php echo htmlspecialchars($pass['qr_code']); ?>">
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <rect x="0" y="0" width="100" height="100" fill="none" stroke="black" stroke-width="6"/>
                            <rect x="10" y="10" width="25" height="25" fill="black"/>
                            <rect x="15" y="15" width="15" height="15" fill="white"/>
                            <rect x="10" y="65" width="25" height="25" fill="black"/>
                            <rect x="15" y="70" width="15" height="15" fill="white"/>
                            <rect x="65" y="10" width="25" height="25" fill="black"/>
                            <rect x="70" y="15" width="15" height="15" fill="white"/>
                            <rect x="45" y="20" width="10" height="30" fill="black"/>
                            <rect x="20" y="45" width="30" height="10" fill="black"/>
                            <rect x="55" y="55" width="20" height="20" fill="black"/>
                            <rect x="75" y="45" width="15" height="15" fill="black"/>
                            <rect x="45" y="75" width="15" height="15" fill="black"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px dashed var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                Scan QR code on boarding the transport bus. This card is non-transferable.
            </div>
        </div>
    </div>

</body>
</html>
