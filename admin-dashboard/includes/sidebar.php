<?php
// admin-dashboard/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['user_name'] ?? 'Admin';
$user_role = $_SESSION['user_role'] ?? 'System Administrator';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-icon">S</div>
        Staradmin
    </div>

    <div class="sidebar-profile">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=ffffff&color=303af6"
            alt="profile">
        <div class="details">
            <span class="name"><?= htmlspecialchars($user_name) ?></span>
            <span class="role"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user_role))) ?></span>
        </div>
    </div>

    <div class="sidebar-menu-title">Main Menu</div>

    <div class="sidebar-nav">
        <a href="index.php" class="nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            Dashboard
        </a>
        <a href="users.php" class="nav-item <?= ($current_page == 'users.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            User Management
        </a>
        <a href="students.php" class="nav-item <?= ($current_page == 'students.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            Student Management
        </a>
        <a href="route_management.php"
            class="nav-item <?= ($current_page == 'route_management.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            Route Management
        </a>
        <a href="reports.php" class="nav-item <?= ($current_page == 'reports.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            Reports
        </a>
        <a href="settings.php" class="nav-item <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <div class="circle-icon"></div>
            System Settings
        </a>
    </div>
</aside>

<div style="flex: 1; display: flex; flex-direction: column;">
    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="navbar-left">
            <div class="navbar-help">Help : +91 80105 49855</div>
            <div class="navbar-search">
                <input type="text" placeholder="Search Here">
            </div>
        </div>

        <div class="navbar-profile">
            <div class="navbar-icons">
                <?php
                // Fetch unread notifications count
                require_once __DIR__ . '/../../Transport-Officer-Module/db.php';
                $notif_stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
                $unread_count = $notif_stmt->fetchColumn();
                ?>
                <a href="notifications.php" style="color: inherit; text-decoration: none;">
                    <i class="fa-regular fa-bell">
                        <?php if ($unread_count > 0): ?>
                            <span class="badge-dot"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </i>
                </a>
                <i class="fa-regular fa-envelope"><span class="badge-dot green">3</span></i>
                <form action="../auth/logout.php" method="POST" style="display:inline; margin:0;">
                    <button type="submit" style="background:none; border:none; color:inherit; cursor:pointer;">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </form>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=303af6&color=fff"
                alt="profile" class="user-img">
        </div>
    </nav>

    <main class="main-content">