<?php
/**
 * Student Navigation Sidebar
 * Bus Pass Management System
 */
require_once __DIR__ . '/../config/auth.php';

global $conn;

$current_user = null;
$student_id = 0;
$unread_count = 0;

if (isLoggedIn()) {
    $current_user = getCurrentStudent();
    $student_id = (int) ($_SESSION['student_id'] ?? 0);

    // Count unread notifications
    // Notifications system is disabled/not migrated to central DB yet
    if ($student_id > 0) {
        $unread_count = 0;
    }
}
?>
<!-- Mobile Toggle Button -->
<button class="sidebar-toggle btn btn-primary d-lg-none" type="button" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Left Sidebar Navigation -->
<nav class="sidebar" id="sidebarNav">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-bus-front-fill"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">PMPML</span>
                <span class="brand-subtitle">Bus Pass</span>
            </div>
        </div>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'apply_pass.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/apply_pass.php">
                    <i class="bi bi-file-earmark-plus"></i>
                    <span>Apply Bus Pass</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'renew_pass.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/renew_pass.php">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Renew Pass</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_applications.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/my_applications.php">
                    <i class="bi bi-files"></i>
                    <span>My Applications</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'download_pass.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/download_pass.php">
                    <i class="bi bi-download"></i>
                    <span>Download Pass</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payment_status.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/payment_status.php">
                    <i class="bi bi-credit-card"></i>
                    <span>Payment Status</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/notifications.php">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Footer (Profile) -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-info">
                <span
                    class="user-name"><?php echo $current_user ? htmlspecialchars($current_user['full_name']) : 'Account'; ?></span>
                <span class="user-role">Student</span>
            </div>
        </div>
        <div class="sidebar-user-menu">
            <a href="<?php echo BASE_URL; ?>/profile.php" class="user-menu-item">
                <i class="bi bi-person"></i> Profile
            </a>
            <a href="<?php echo BASE_URL; ?>/change_password.php" class="user-menu-item">
                <i class="bi bi-key"></i> Change Password
            </a>
            <hr class="user-menu-divider">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="user-menu-item text-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- Main Content Wrapper Start -->
<div class="main-content">