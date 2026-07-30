<?php
/**
 * Reset Password - Complete password reset
 * Bus Pass Management System
 */
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';
$token_valid = false;

// Check token from URL
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

if (!empty($token) && isset($_SESSION['reset_token']) && isset($_SESSION['reset_email']) && isset($_SESSION['reset_expiry'])) {
    if ($token === $_SESSION['reset_token'] && time() < $_SESSION['reset_expiry']) {
        $token_valid = true;
    } else {
        $error = 'Invalid or expired reset token. Please request a new one.';
    }
} elseif (!empty($token)) {
    $error = 'Invalid reset link. Please request a new password reset.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $token_valid) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $q = "UPDATE students SET password = ? WHERE email = ?";
        $s = mysqli_prepare($conn, $q);
        mysqli_stmt_bind_param($s, 'ss', $hashed, $email);
        
        if (mysqli_stmt_execute($s)) {
            // Clear session vars
            unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expiry']);
            $success = 'Password has been reset successfully! You can now login with your new password.';
            $token_valid = false;
        } else {
            $error = 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card card fade-in">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="auth-logo">
                        <i class="bi bi-key"></i>
                    </div>
                    <h3 class="fw-bold">Reset Password</h3>
                    <p class="text-muted">Enter your new password</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login Now
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($token_valid): ?>
                <form method="POST" action="?token=<?php echo htmlspecialchars($token); ?>" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" class="form-control" id="new_password" 
                                   placeholder="Min 6 characters" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" 
                                   placeholder="Re-enter password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm_password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="reset_password" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <p class="mb-0">
                        <a href="login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Back to Login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
