<?php
/**
 * register.php — Secure self-registration with hashed passwords
 * Bus Pass Management System · Authentication Module
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/auth_functions.php';

if (is_logged_in()) { header('Location: ' . dashboard_for_role(current_role())); exit; }

$old = ['full_name' => '', 'email' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Security check failed. Please refresh and try again.');
        header('Location: register.php');
        exit;
    }

    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'email'     => $_POST['email']     ?? '',
        'password'  => $_POST['password']  ?? '',
        'confirm'   => $_POST['confirm_password'] ?? '',
        'role'      => 'student', // Security enforcement: public registration is strictly for Students
        'terms'     => !empty($_POST['terms']),
    ];
    $old = [
        'full_name' => $data['full_name'],
        'email' => $data['email'],
    ];

    $result = register_user($data);
    if ($result['ok']) {
        set_flash('success', $result['message']);
        header('Location: login.php');
        exit;
    }
    set_flash('error', $result['error']);
    header('Location: register.php');
    exit;
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/auth_header.php';
?>
<form method="post" action="register.php" data-validate novalidate class="space-y-5">
  <?= csrf_field() ?>

  <!-- Defaulted Account Type Badge -->
  <div class="flex items-center gap-3 p-4 rounded-[10px] bg-slate-50 dark:bg-white/5 border border-black/5 dark:border-white/5 mb-6">
    <div class="flex size-10 items-center justify-center rounded-[8px] bg-orange-100 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400">
      <i class="bi bi-mortarboard-fill text-lg"></i>
    </div>
    <div>
      <div class="text-sm font-semibold text-black dark:text-white">Student Registration</div>
      <div class="text-xs text-black/55 dark:text-white/45">Account type defaulted to Student</div>
    </div>
  </div>

  <!-- Full Name Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Full Name</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="text" id="full_name" name="full_name"
             value="<?= e($old['full_name']) ?>" placeholder=" "
             autocomplete="name" required minlength="3" maxlength="80"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">e.g. Aarav Sharma</span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter your full name.</div>
  </div>

  <!-- Email Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Email Address</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="email" id="email" name="email"
             value="<?= e($old['email']) ?>" placeholder=" "
             autocomplete="email" required maxlength="120"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">you@example.com</span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Please enter a valid email address.</div>
  </div>

  <!-- Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Password</span>
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
    <div id="strengthLabel" class="text-[11px] text-black/45 dark:text-white/40 mt-1">Use 8+ characters with mixed cases, numbers & symbols.</div>
  </div>

  <!-- Confirm Password Input -->
  <div class="space-y-1">
    <span class="text-sm font-medium text-black/60 dark:text-white/50">Confirm Password</span>
    <label class="flex h-14 items-center justify-between gap-4 rounded-[10px] border border-black/25 bg-white px-5 text-lg leading-none dark:border-white/15 dark:bg-white/5 relative cursor-text">
      <input type="password" id="confirm_password" name="confirm_password"
             placeholder=" " autocomplete="new-password" required minlength="8"
             class="peer min-w-0 flex-1 bg-transparent text-black dark:text-white outline-none placeholder:text-black/30 dark:placeholder:text-white/35 text-base pr-8">
      <span class="peer-focus:hidden peer-[:not(:placeholder-shown)]:hidden shrink-0 text-black/40 dark:text-white/35 text-sm font-medium">Re-enter your password</span>
      <button type="button" class="pwd-toggle absolute right-5 top-1/2 -translate-y-1/2 text-black/50 dark:text-white/50 hover:text-black dark:hover:text-white" data-toggle-password="confirm_password" aria-label="Show password">
        <i class="bi bi-eye"></i>
      </button>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">Passwords must match.</div>
  </div>

  <!-- Terms Agreement Checkbox -->
  <div class="space-y-1 pt-2">
    <label class="flex items-start gap-3 text-sm leading-snug cursor-pointer">
      <span class="relative mt-0.5 size-4 shrink-0">
        <input type="checkbox" id="terms" name="terms" value="1" required
               class="peer size-full appearance-none rounded-[4px] border border-black/25 bg-white checked:border-black checked:bg-black dark:border-white/30 dark:bg-white/5 dark:checked:border-white dark:checked:bg-white focus:ring-0">
        <svg viewBox="0 0 12 12" class="pointer-events-none absolute inset-0 hidden size-full p-0.5 text-white peer-checked:block dark:text-black" fill="none" aria-hidden="true">
          <path d="M3 6.2 5 8.1 9 3.9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </span>
      <span class="text-black/60 dark:text-white/55">
        By creating an account, you agree to our <a href="#" class="font-medium text-black dark:text-white underline underline-offset-2">Terms and Services</a> and <a href="#" class="font-medium text-black dark:text-white underline underline-offset-2">Privacy Policy</a>.
      </span>
    </label>
    <div class="invalid-feedback text-xs text-red-500 mt-1 hidden">You must agree before submitting.</div>
  </div>

  <button type="submit"
          class="mt-9 flex h-12 w-full items-center justify-center rounded-[10px] border border-black/40 bg-black text-xl font-medium text-white transition-colors hover:bg-black/85 dark:border-white/40 dark:bg-white dark:text-black dark:hover:bg-white/85">
    Create Account
  </button>
</form>

<div class="my-10 text-center text-xl font-medium text-black/60 dark:text-white/50">
  or
</div>

<p class="text-center text-base text-black/60 dark:text-white/55">
  Already registered? <a href="login.php" class="font-medium text-black dark:text-white underline underline-offset-2">Sign in to your account</a>
</p>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>

