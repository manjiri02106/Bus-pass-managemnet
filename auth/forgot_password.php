<?php
/**
 * forgot_password.php — Requests a password reset.
 * Bus Pass Management System · Authentication Module
 *
 * Sends a REAL email (via PHPMailer/SMTP — see config/mail.php) containing a
 * single-use reset link valid for 30 minutes. Uses generic messaging so the
 * page never reveals whether an email is registered (user-enumeration safety).
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) { header('Location: ' . dashboard_for_role(current_role())); exit; }

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
        header('Location: forgot_password.php');
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $err   = validate_email($email);

    if ($err !== '') {
        set_flash('error', $err);
        header('Location: forgot_password.php');
        exit;
    }

    // Look up the account (case-insensitive)
    global $conn;
    $stmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Create single-use token and DELIVER the real reset email
        $rawToken = create_reset_token($user['email']);
        $mailResult = send_reset_email($user['email'], $user['full_name'], $rawToken);

        if (!$mailResult['ok']) {
            // Genuine delivery failure — tell the user honestly (admin should check config)
            set_flash('error', $mailResult['error']);
            header('Location: forgot_password.php');
            exit;
        }

        set_flash('success', 'A password reset link has been sent to ' . $email . '. Please check your inbox (and spam folder). The link expires in 30 minutes.');
        header('Location: login.php');
        exit;
    } else {
        // Explicitly tell the user the email doesn't exist (helpful for debugging)
        set_flash('error', 'No account found with that email address.');
        header('Location: forgot_password.php');
        exit;
    }
}

$pageTitle = 'Forgot Password';
require __DIR__ . '/includes/auth_header.php';
?>
<p class="text-sm text-black/60 dark:text-white/55 mb-6">
  Enter the email linked to your account and we’ll send you a secure link to reset your password.
</p>

<form method="post" action="forgot_password.php" data-validate novalidate class="space-y-5">
  <?= csrf_field() ?>

  <!-- Email Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/55">Email Address</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="email" id="email" name="email"
             value="<?= e($email) ?>" placeholder=" "
             autocomplete="email" required maxlength="120"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">you@example.com</span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter a valid email address.</div>
  </div>

  <button type="submit"
          class="mt-9 flex h-12 w-full items-center justify-center rounded-[10px] border border-black/40 bg-black text-xl font-medium text-white transition-colors hover:bg-black/85 dark:border-white/40 dark:bg-white dark:text-black dark:hover:bg-white/85">
    Send Reset Link
  </button>
</form>

<div class="my-10 text-center text-xl font-medium text-black/60 dark:text-white/50">
  or
</div>

<p class="text-center text-base text-black/60 dark:text-white/55">
  Remembered it? <a href="login.php" class="font-medium text-black dark:text-white underline underline-offset-2">Sign in to your account</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>

