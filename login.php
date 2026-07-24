<?php
session_start();
require_once 'db.php';

if (!empty($_SESSION['user_id'])) {
    $redirectMap = [
        'admin' => 'admin_dashboard.php',
        'transport_officer' => 'officer_dashboard.php',
        'student' => 'student_dashboard.php'
    ];
    header('Location: ' . $redirectMap[$_SESSION['role']]);
    exit;
}

$error = '';
$selectedRole = trim($_POST['role'] ?? 'admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $selectedRole = trim($_POST['role'] ?? 'admin');

    $stmt = $conn->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = ? AND role = ?');
    $stmt->bind_param('ss', $email, $selectedRole);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        $redirectMap = [
            'admin' => 'admin_dashboard.php',
            'transport_officer' => 'officer_dashboard.php',
            'student' => 'student_dashboard.php'
        ];

        header('Location: ' . $redirectMap[$user['role']]);
        exit;
    }

    $error = 'Invalid email or password for the selected role.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Pass Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="bg-dark text-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card border-0 shadow-lg rounded-4 bg-secondary-subtle text-dark">
                    <div class="card-body p-4">
                        <h2 class="fw-bold mb-2">Bus Pass Management</h2>
                        <p class="text-muted">Secure login for admins, transport officers, and students.</p>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="post" class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Select role</label>
                                <select name="role" class="form-select" required>
                                    <option value="admin" <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="transport_officer" <?php echo $selectedRole === 'transport_officer' ? 'selected' : ''; ?>>Transport Officer</option>
                                    <option value="student" <?php echo $selectedRole === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button class="btn btn-primary w-100">Sign In</button>
                        </form>

                        <div class="mt-4 text-muted small">
                            Demo accounts:<br>
                            Admin: admin@example.com / admin123<br>
                            Officer: officer@example.com / officer123<br>
                            Student: student@example.com / student123
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app.js"></script>
</body>
</html>
