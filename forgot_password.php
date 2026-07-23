<?php
/**
 * Forgot Password - Password Reset
 * Bus Pass Management System
 */
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';
$show_form = true;
$reset_token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Request reset
    if (isset($_POST['request_reset'])) {
        $email = sanitize($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email exists
            $q = "SELECT id, full_name FROM students WHERE email = ?";
            $s = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($s, 's', $email);
            mysqli_stmt_execute($s);
            $result = mysqli_stmt_get_result($s);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // In production, send an email with reset link
                // For demo, show the reset token directly
                $reset_token = md5($email . time() . rand());
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $reset_token;
                $_SESSION['reset_expiry'] = time() + 1800; // 30 minutes
                
                $success = 'A password reset link has been sent to your email. Please check your inbox.';
                $show_form = false;
                
                // For demo purposes, redirect to reset page with token
                // redirect('/reset_password.php?token=' . $reset_token, 'Check your email for reset link.', 'info');
            } else {
                $error = 'No account found with this email address.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
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
                        <i class="bi bi-question-circle"></i>
                    </div>
                    <h3 class="fw-bold">Forgot Password?</h3>
                    <p class="text-muted">Enter your email to receive a reset link</p>
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
                    <?php if ($reset_token): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i>
                        Demo Mode: Use this token to reset: <strong><?php echo $reset_token; ?></strong>
                        <br><a href="reset_password.php?token=<?php echo $reset_token; ?>" class="alert-link">Click here to reset password</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($show_form): ?>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" 
                                   placeholder="you@college.edu" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="request_reset" class="btn btn-primary btn-lg">
                            <i class="bi bi-send me-2"></i>Send Reset Link
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <p class="mb-1">
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
