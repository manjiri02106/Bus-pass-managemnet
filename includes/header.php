<?php
/**
 * Global Header Component
 */
require_once __DIR__ . '/functions.php';

// Active menu item helper
function is_active($page) {
    return strpos($_SERVER['REQUEST_URI'], $page) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OmniPass - NextGen Bus Pass Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="/Bus-pass-managemnet/assets/css/style.css">
</head>
<body>

    <header class="navbar">
        <div class="container nav-container">
            <a href="/Bus-pass-managemnet/index.php" class="nav-logo">
                <i class="fas fa-bus-alt"></i> OmniPass
            </a>
            
            <nav>
                <ul class="nav-links">
                    <li><a href="/Bus-pass-managemnet/index.php" class="nav-link <?= is_active('index.php') ?>">Home</a></li>
                    
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                            <li><a href="/Bus-pass-managemnet/admin/dashboard.php" class="nav-link <?= is_active('admin/dashboard.php') ?>">Admin Portal</a></li>
                            <li><a href="/Bus-pass-managemnet/admin/passes.php" class="nav-link <?= is_active('admin/passes.php') ?>">Manage Passes</a></li>
                            <li><a href="/Bus-pass-managemnet/admin/routes.php" class="nav-link <?= is_active('admin/routes.php') ?>">Routes</a></li>
                            <li><a href="/Bus-pass-managemnet/admin/categories.php" class="nav-link <?= is_active('admin/categories.php') ?>">Categories</a></li>
                        <?php else: ?>
                            <li><a href="/Bus-pass-managemnet/dashboard.php" class="nav-link <?= is_active('dashboard.php') ?>">My Pass</a></li>
                            <li><a href="/Bus-pass-managemnet/apply.php" class="nav-link <?= is_active('apply.php') ?>">Apply Pass</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="nav-actions">
                <?php if (is_logged_in()): ?>
                    <span style="margin-right: 15px; font-weight: 500; font-size: 14px; color: var(--color-text-muted);">
                        Hi, <?= e($_SESSION['user_name']) ?> (<?= e(ucfirst($_SESSION['role'])) ?>)
                    </span>
                    <a href="/Bus-pass-managemnet/login.php?logout=1" class="btn-nav"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="/Bus-pass-managemnet/login.php" class="btn-nav" style="margin-right: 10px;">Sign In</a>
                    <a href="/Bus-pass-managemnet/register.php" class="btn-nav btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
