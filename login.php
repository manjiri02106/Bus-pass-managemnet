<?php
/**
 * Admin Login Page
 * Bus Pass Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in → redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/index.php');
    exit;
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = clean_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (attempt_login($email, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? 'admin/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Bus Pass Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-card fade-in-up">
    <!-- Logo -->
    <div class="login-logo">
        <i class="bi bi-bus-front-fill"></i>
    </div>

    <h1 class="text-center fs-4 fw-bold mb-1">Bus Pass Admin</h1>
    <p class="text-center text-muted small mb-4">Sign in to the management portal</p>

    <!-- Timeout notice -->
    <?php if ($timeout): ?>
    <div class="alert alert-warning d-flex gap-2 align-items-center py-2">
        <i class="bi bi-clock-history"></i>
        Your session expired due to inactivity. Please sign in again.
    </div>
    <?php endif; ?>

    <!-- Flash message -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : 'danger') ?> py-2">
        <?= e($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-center py-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="login.php" autocomplete="on" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="admin@buspass.gov" required autofocus autocomplete="username">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter your password" required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Show/hide password">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>

    <hr class="my-4">
    <div class="text-center text-muted small">
        <i class="bi bi-shield-lock me-1"></i>
        Secured Portal — Authorised Access Only<br>
        <span class="opacity-60">Bus Pass Management System &copy; <?= date('Y') ?></span>
    </div>

    <!-- Demo credentials hint (remove in production) -->
    <div class="alert alert-info mt-3 py-2 small">
        <strong>Demo credentials:</strong><br>
        Email: <code>superadmin@buspass.gov</code><br>
        Password: <code>Admin@123</code>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pw  = document.getElementById('password');
        const eye = document.getElementById('eyeIcon');
        if (pw.type === 'password') {
            pw.type = 'text';
            eye.className = 'bi bi-eye-slash';
        } else {
            pw.type = 'password';
            eye.className = 'bi bi-eye';
        }
    });
</script>
</body>
</html>
