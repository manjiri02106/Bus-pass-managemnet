<?php
// src/login.php - Secure login gate
require_once dirname(__DIR__) . '/config/db-connection.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin')         header("Location: officer-dashboard.php");
    elseif ($_SESSION['role'] === 'officer')   header("Location: officer-dashboard.php");
    else                                        header("Location: student-dashboard.php");
    exit();
}

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['full_name']    = $user['full_name'];
                $_SESSION['last_activity']= time();

                if ($user['role'] === 'officer' || $user['role'] === 'admin') {
                    header("Location: officer-dashboard.php");
                } else {
                    header("Location: student-dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Bus Pass Portal</title>
    <meta name="description" content="Sign in to the Bus Pass Management Portal to manage student transportation pass applications.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1.5rem;">
    <div class="login-container">
        <div class="login-logo"><div class="logo-icon">BP</div></div>
        <h1 class="login-title">Bus Pass Portal</h1>
        <p class="login-subtitle">Sign in to manage student transport passes</p>

        <?php if ($msg === 'timeout'): ?>
            <div class="alert alert-warning"><i class="fa-solid fa-clock"></i> Session expired. Please sign in again.</div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="officer / student" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Your password" required autocomplete="current-password">
            </div>
            <button type="submit" id="btn-login" class="btn btn-primary" style="width:100%; margin-top:1rem; padding:12px;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div style="margin-top:2rem; border-top:1px solid var(--border-color); padding-top:1.5rem;">
            <p style="font-size:0.8rem; color:var(--text-muted); text-align:center; margin-bottom:0.5rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">
                Test Accounts
            </p>
            <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; flex-direction:column; gap:4px;">
                <div>🔑 <strong>Officer:</strong> officer / officer123</div>
                <div>🔑 <strong>Student:</strong> student / student123</div>
            </div>
        </div>
    </div>
</body>
</html>
