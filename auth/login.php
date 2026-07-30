<?php
/**
 * login.php — Sign in with user-level dropdown (Student / Officer / Admin)
 * Bus Pass Management System · Authentication Module
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_functions.php';

if (is_logged_in()) { header('Location: ' . dashboard_for_role(current_role())); exit; }

$old = ['username' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $old      = ['username' => $username, 'role' => $role];

        $result = attempt_login($username, $password, $role);
        if ($result['ok']) {
            header('Location: ' . dashboard_for_role($role));
            exit;
        }
        set_flash('error', $result['error']);
    }
    header('Location: login.php');
    exit;
}

$pageTitle = 'Login';
require __DIR__ . '/includes/auth_header.php';
?>
<form method="post" action="login.php" data-validate novalidate class="space-y-5">
  <?= csrf_field() ?>

  <!-- Select User Level (Role) -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Login as</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-pointer">
      <select id="role" name="role" required class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none appearance-none pr-8 text-base">
        <option value="" disabled <?= $old['role'] === '' ? 'selected' : '' ?>>Select user level…</option>
        <?php foreach (VALID_ROLES as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= $old['role'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50"><i class="bi bi-chevron-down"></i></span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please select your user level.</div>
  </div>

  <!-- Email Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Email</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="text" id="username" name="username"
             value="<?= e($old['username']) ?>" placeholder=" "
             autocomplete="username" required minlength="4" maxlength="40"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Enter your email</span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter your email.</div>
  </div>

  <!-- Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="password" name="password"
             placeholder=" " autocomplete="current-password" required minlength="8"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Enter your password</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter your password.</div>
  </div>

  <!-- Keep Session info & Forgot Password -->
  <div class="flex items-center justify-between text-sm py-2">
    <label class="flex items-center gap-2 cursor-not-allowed opacity-60">
      <input type="checkbox" disabled checked class="rounded border-black/25 dark:border-white/30 text-black focus:ring-0">
      <span class="text-black/55 dark:text-white/55 text-xs">Auto-expires in 20 min</span>
    </label>
    <a href="forgot_password.php" class="font-medium text-black/60 hover:text-black dark:text-white/55 dark:hover:text-white underline underline-offset-2">Forgot password?</a>
  </div>

  <button type="submit"
          class="mt-9 flex h-12 w-full items-center justify-center rounded-[10px] border border-black/40 bg-black text-xl font-medium text-white transition-colors hover:bg-black/85 dark:border-white/40 dark:bg-white dark:text-black dark:hover:bg-white/85">
    Sign In
  </button>
</form>

<div class="my-10 text-center text-xl font-medium text-black/60 dark:text-white/50">
  or
</div>

<p class="text-center text-base text-black/60 dark:text-white/55">
  Don't have an account? <a href="register.php" class="font-medium text-black dark:text-white underline underline-offset-2">Create one</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>

