<?php
/**
 * User Registration Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/database/db.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('/Bus-pass-managemnet/admin/dashboard.php');
    } else {
        redirect('/Bus-pass-managemnet/dashboard.php');
    }
}

$name = '';
$email = '';
$error = '';

// Handle Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db = Database::connect();

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                // Securely hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert User
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);

                set_flash_message('success', 'Registration successful! You can now sign in.');
                redirect('/Bus-pass-managemnet/login.php');
            }
        } catch (Exception $e) {
            $error = 'An error occurred during registration. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="glass-card">
        <div class="auth-title">
            <h2 class="text-gradient"><i class="fas fa-user-plus"></i> Bus Pass Management Sign Up</h2>
            <p style="font-size: 14px; color: var(--color-text-muted); margin-top: 5px;">Create your account to apply and
                manage your daily bus pass.</p>
        </div>

        <?php if ($error): ?>
            <div
                style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--border-radius-md); padding: 12px; color: var(--color-danger); font-size: 14px; margin-bottom: 20px; text-align: center;">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Jane Doe"
                    value="<?= e($name) ?>" required autocomplete="name">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="jane@domain.com"
                    value="<?= e($email) ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    required autocomplete="new-password">
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                    placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-user-check"></i>
                Register Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="/Bus-pass-managemnet/login.php">Sign In Here</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>