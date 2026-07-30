<?php
/**
 * reset_password.php — Consumes the emailed token and sets a new password.
 * Bus Pass Management System · Authentication Module
 *
 * URL format (generated in includes/mailer.php):
 *   reset_password.php?email=...&token=...
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_functions.php';

if (is_logged_in()) { header('Location: ' . dashboard_for_role(current_role())); exit; }

// Token comes from the email link (GET) or the posted form (hidden fields)
$email = $_REQUEST['email'] ?? '';
$token = $_REQUEST['token'] ?? '';

if ($email === '' || $token === '' || validate_email($email) !== '') {
    set_flash('error', 'Invalid reset link. Please request a new password reset.');
    header('Location: forgot_password.php');
    exit;
}

// Validate token BEFORE showing/processing the form
$check = verify_reset_token($email, $token);
if (!$check || isset($check['invalid'])) {
    set_flash('error', $check['reason'] ?? 'Invalid reset link.');
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
        header('Location: reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token));
        exit;
    }

    $new     = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        set_flash('error', 'Passwords do not match.');
        header('Location: reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token));
        exit;
    }
    if ($err = validate_password($new)) {
        set_flash('error', $err);
        header('Location: reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token));
        exit;
    }

    // Re-verify token (it may have expired while the user was typing)
    $recheck = verify_reset_token($email, $token);
    if (!$recheck || isset($recheck['invalid'])) {
        set_flash('error', $recheck['reason'] ?? 'This link is no longer valid.');
        header('Location: forgot_password.php');
        exit;
    }

    if (consume_reset_token((int)$recheck['id'], $email, $new)) {
        set_flash('success', 'Your password has been reset successfully. Please sign in with your new password.');
    } else {
        set_flash('error', 'Could not reset the password. Please try requesting a new link.');
    }
    header('Location: login.php');
    exit;
}

$pageTitle = 'Set New Password';
require __DIR__ . '/includes/auth_header.php';
?>
<p class="text-sm text-black/60 dark:text-white/55 mb-6">
  Choose a strong new password for <b><?= e($email) ?></b>.
</p>

<form method="post" action="reset_password.php" data-validate novalidate class="space-y-5">
  <?= csrf_field() ?>
  <input type="hidden" name="email" value="<?= e($email) ?>">
  <input type="hidden" name="token" value="<?= e($token) ?>">

  <!-- New Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">New Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="password" name="password"
             placeholder=" " autocomplete="new-password" required minlength="8"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">New Password</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <!-- Strength Meter -->
    <div class="h-1 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden mt-2">
      <div id="strengthBar" class="h-full w-0 transition-all duration-300"></div>
    </div>
    <div id="strengthLabel" class="text-[11px] text-black/45 dark:text-white/40 mt-1">Use 8+ characters with mixed cases, numbers & symbols.</div>
  </div>

  <!-- Confirm New Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Confirm New Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="confirm_password" name="confirm_password"
             placeholder=" " autocomplete="new-password" required minlength="8"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Confirm New Password</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="confirm_password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Passwords must match.</div>
  </div>

  <button type="submit"
          class="mt-9 flex h-12 w-full items-center justify-center rounded-[10px] border border-black/40 bg-black text-xl font-medium text-white transition-colors hover:bg-black/85 dark:border-white/40 dark:bg-white dark:text-black dark:hover:bg-white/85">
    Reset Password
  </button>
</form>

<div class="my-10 text-center text-xl font-medium text-black/60 dark:text-white/50">
  or
</div>

<p class="text-center text-base text-black/60 dark:text-white/55">
  Need a new link? <a href="forgot_password.php" class="font-medium text-black dark:text-white underline underline-offset-2">Request another reset email</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>

