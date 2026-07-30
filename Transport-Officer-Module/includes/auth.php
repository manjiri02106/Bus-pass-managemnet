<?php
/**
 * Authentication Guard
 * Bus Pass Management System
 *
 * Include this file at the top of every admin page.
 * Redirects to login.php if the admin is not authenticated.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}

// Determine root path dynamically (works whether included from admin/ or root)
$_AUTH_ROOT = dirname(__DIR__);

require_once $_AUTH_ROOT . '/includes/functions.php';

/**
 * Require admin to be logged in.
 * Call this at the top of every protected page.
 */
function require_admin_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        // Preserve the originally requested URL for redirect after login
        $requested = $_SERVER['REQUEST_URI'] ?? '';
        if ($requested) {
            $_SESSION['redirect_after_login'] = $requested;
        }

        $login_url = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/')
            ? '../login.php'
            : 'login.php';

        header('Location: ' . $login_url);
        exit;
    }

    // Session timeout: 2 hours of inactivity
    $timeout = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();

        $login_url = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/')
            ? '../login.php'
            : 'login.php';

        header('Location: ' . $login_url . '?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Regenerate session ID every 30 minutes to prevent fixation
    if (empty($_SESSION['last_regenerated']) || (time() - $_SESSION['last_regenerated']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
}

/**
 * Perform login: verify credentials, set session.
 * Returns true on success, false on failure.
 */
function attempt_login(string $email, string $password): bool
{
    global $_AUTH_ROOT;
    require_once $_AUTH_ROOT . '/includes/db.php';

    $pdo  = get_db();
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1");
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    // Rehash if needed (future-proofing)
    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare("UPDATE admin_users SET password = :pwd WHERE id = :id")
            ->execute([':pwd' => $new_hash, ':id' => $user['id']]);
    }

    // Update last_login timestamp
    $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id")
        ->execute([':id' => $user['id']]);

    // Regenerate session ID on privilege escalation
    session_regenerate_id(true);

    $_SESSION['admin_id']          = $user['id'];
    $_SESSION['admin_name']        = $user['name'];
    $_SESSION['admin_email']       = $user['email'];
    $_SESSION['admin_role']        = $user['role'];
    $_SESSION['last_activity']     = time();
    $_SESSION['last_regenerated']  = time();

    return true;
}

/**
 * Destroy the admin session (logout).
 */
function logout_admin(): void
{
    session_unset();
    session_destroy();

    // Start a new session to write the flash message
    session_start();
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out successfully.'];
}
