<?php
/**
 * change_password.php — Logged-in users change their own password.
 * Bus Pass Management System · Authentication Module
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_functions.php';

require_login();   // session timeout is enforced automatically by config/session.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
        header('Location: change_password.php');
        exit;
    }

    $result = change_password(
        (int)$_SESSION['user_id'],
        $_POST['current_password'] ?? '',
        $_POST['password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );

    set_flash($result['ok'] ? 'success' : 'error', $result['ok'] ? $result['message'] : $result['error']);
    header('Location: ' . ($result['ok'] ? dashboard_for_role(current_role()) : 'change_password.php'));
    exit;
}

$pageTitle = 'Change Password';
require __DIR__ . '/includes/auth_header.php';
?>
<p class="text-sm text-black/60 dark:text-white/55 mb-6">
  Signed in as <b><?= e($_SESSION['user_name']) ?></b> (<?= e(VALID_ROLES[current_role()]) ?>)
</p>

<form method="post" action="change_password.php" data-validate novalidate class="space-y-5">
  <?= csrf_field() ?>

  <!-- Current Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Current Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="current_password" name="current_password"
             placeholder=" " autocomplete="current-password" required
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Enter current password</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="current_password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter your current password.</div>
  </div>

  <!-- New Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">New Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="password" name="password"
             placeholder=" " autocomplete="new-password" required minlength="8"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Minimum 8 characters</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <!-- Strength Meter -->
    <div class="h-1 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden mt-2">
      <div id="strengthBar" class="h-full w-0 transition-all duration-300"></div>
    </div>
    <div id="strengthLabel" class="text-[11px] text-black/45 dark:text-white/40 mt-1">Must differ from your current password.</div>
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
    Update Password
  </button>
</form>

<div class="my-10 text-center text-xl font-medium text-black/60 dark:text-white/50">
  or
</div>

<p class="text-center text-base text-black/60 dark:text-white/55">
  <a href="<?= e(dashboard_for_role(current_role())) ?>" class="font-medium text-black dark:text-white underline underline-offset-2">Back to dashboard</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>

