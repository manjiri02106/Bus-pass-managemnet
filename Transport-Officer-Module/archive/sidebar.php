<?php
// sidebar.php - Dynamic role-based navigation sidebar
require_once __DIR__ . '/auth_helpers.php';

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<aside class="sidebar">
    <?php if ($role === 'admin'): ?>
        <!-- Admin Navigation -->
        <a href="admin_dashboard.php" class="sidebar-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="user_management.php" class="sidebar-link <?php echo ($current_page == 'user_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-users-gear"></i> User Management
        </a>
        <a href="route_management.php" class="sidebar-link <?php echo ($current_page == 'route_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-route"></i> Bus Route Management
        </a>
        <a href="bus_management.php" class="sidebar-link <?php echo ($current_page == 'bus_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bus"></i> Bus Management
        </a>
        <a href="student_management.php" class="sidebar-link <?php echo ($current_page == 'student_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-graduation-cap"></i> Student Management
        </a>
        <a href="pass_management.php" class="sidebar-link <?php echo ($current_page == 'pass_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-id-card"></i> Pass Management
        </a>
        <a href="reports.php" class="sidebar-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Reports
        </a>
        <a href="notifications.php" class="sidebar-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bell"></i> Notifications
        </a>
        <a href="settings.php" class="sidebar-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-sliders"></i> Settings
        </a>

    <?php elseif ($role === 'officer'): ?>
        <!-- Transport Officer Navigation -->
        <a href="officer_dashboard.php" class="sidebar-link <?php echo ($current_page == 'officer_dashboard.php' || $current_page == 'verify_application.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="officer_dashboard.php?view=pending" class="sidebar-link <?php echo (isset($_GET['view']) && $_GET['view'] == 'pending') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-check"></i> Verify Applications
        </a>
        <a href="officer_dashboard.php?view=approved" class="sidebar-link <?php echo (isset($_GET['view']) && $_GET['view'] == 'approved') ? 'active' : ''; ?>">
            <i class="fa-solid fa-signature"></i> Bus Pass Approval
        </a>
        <a href="officer_dashboard.php?view=renewals" class="sidebar-link <?php echo (isset($_GET['view']) && $_GET['view'] == 'renewals') ? 'active' : ''; ?>">
            <i class="fa-solid fa-arrows-rotate"></i> Renewal Requests
        </a>
        <a href="route_management.php" class="sidebar-link <?php echo ($current_page == 'route_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-route"></i> Route Management
        </a>
        <a href="reports.php" class="sidebar-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-pie"></i> Reports
        </a>
        <a href="notifications.php" class="sidebar-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bell"></i> Notifications
        </a>
        <a href="profile.php" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-gear"></i> Profile
        </a>

    <?php elseif ($role === 'student'): ?>
        <!-- Student Navigation -->
        <a href="student_dashboard.php" class="sidebar-link <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="apply.php" class="sidebar-link <?php echo ($current_page == 'apply.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-signature"></i> Apply Bus Pass
        </a>
        <a href="renew.php" class="sidebar-link <?php echo ($current_page == 'renew.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-arrows-rotate"></i> Renew Pass
        </a>
        <a href="student_dashboard.php#applications" class="sidebar-link">
            <i class="fa-solid fa-receipt"></i> My Applications
        </a>
        <a href="student_dashboard.php#downloads" class="sidebar-link">
            <i class="fa-solid fa-download"></i> Download Bus Pass
        </a>
        <a href="notifications.php" class="sidebar-link <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-bell"></i> Notifications
        </a>
        <a href="profile.php" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user-astronaut"></i> Profile
        </a>
    <?php endif; ?>
    
    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1rem 0;">
    
    <form action="logout.php" method="POST" style="width: 100%;">
        <button type="submit" class="sidebar-link" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; font-family: inherit;">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
        </button>
    </form>
</aside>
<main class="main-content">
