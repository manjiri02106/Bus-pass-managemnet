<?php
/**
 * User Login Authentication Page
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/database/db.php';

// Handle Logout
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    session_start(); // restart session to set flash msg
    set_flash_message('success', 'Logged out successfully.');
    redirect('/Bus-pass-managemnet/login.php');
}

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('/Bus-pass-managemnet/admin/dashboard.php');
    } else {
        redirect('/Bus-pass-managemnet/dashboard.php');
    }
}

$email = '';
$error = '';

// Handle Post Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for security (prevent session fixation)
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                set_flash_message('success', "Welcome back, " . $user['name'] . "!");

                if ($user['role'] === 'admin') {
                    redirect('/Bus-pass-managemnet/admin/dashboard.php');
                } else {
                    redirect('/Bus-pass-managemnet/dashboard.php');
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during authentication. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="glass-card">
        <div class="auth-title">
            <h2 class="text-gradient"><i class="fas fa-bus-alt"></i> Bus Pass Management Sign In</h2>
            <p style="font-size: 14px; color: var(--color-text-muted); margin-top: 5px;">Enter your credentials to
                manage your transit passes.</p>
        </div>

        <?php if ($error): ?>
            <div
                style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--border-radius-md); padding: 12px; color: var(--color-danger); font-size: 14px; margin-bottom: 20px; text-align: center;">
                <i class="fas fa-exclamation-triangle"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="name@domain.com"
                    value="<?= e($email) ?>" required autocomplete="email">
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-sign-in-alt"></i>
                Authenticate Account</button>
        </form>

        <div class="auth-footer">
            Don't have an account yet? <a href="/Bus-pass-managemnet/register.php">Sign Up Free</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>