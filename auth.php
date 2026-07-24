<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === 'admin@buspass.com' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'System Administrator';
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid login credentials.';
}

page_header('Admin Login', 'Dashboard');
?>
<h2>Admin Login</h2>
<p>Sign in to access the bus pass management portal.</p>
<?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<form method="post" class="form-grid">
    <label>
        Email
        <input type="email" name="email" required>
    </label>
    <label>
        Password
        <input type="password" name="password" required>
    </label>
    <button type="submit">Login</button>
</form>
<?php page_footer(); ?>
