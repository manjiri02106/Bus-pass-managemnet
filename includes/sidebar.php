<?php
/**
 * Admin Sidebar Navigation
 * Bus Pass Management System
 */

$base = str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') ? '../' : '';
?>

<aside class="sidebar" id="adminSidebar">
    <!-- Brand -->
    <div class="sidebar-brand px-4 py-3 d-flex align-items-center gap-3">
        <div class="brand-icon">
            <i class="bi bi-bus-front-fill fs-4"></i>
        </div>
        <div class="brand-text">
            <div class="fw-bold lh-1">BusPass</div>
            <div class="text-uppercase opacity-50" style="font-size:.65rem; letter-spacing:.1em;">Admin Portal</div>
        </div>
    </div>

    <hr class="sidebar-divider">

    <!-- Navigation -->
    <ul class="sidebar-nav list-unstyled px-2 mb-0">

        <!-- Dashboard -->
        <li class="sidebar-item">
            <a href="<?= $base ?>admin/index.php"
               class="sidebar-link <?= ($active_page === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Applications -->
        <li class="sidebar-section-header mt-3 mb-1 px-3 text-uppercase opacity-50"
            style="font-size:.65rem; letter-spacing:.1em;">Applications</li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/applications.php"
               class="sidebar-link <?= ($active_page === 'applications') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>All Applications</span>
                <span class="sidebar-badge" id="pendingCountBadge">…</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/document_verification.php"
               class="sidebar-link <?= ($active_page === 'document_verification') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-check"></i>
                <span>Document Verification</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/route_validation.php"
               class="sidebar-link <?= ($active_page === 'route_validation') ? 'active' : '' ?>">
                <i class="bi bi-map"></i>
                <span>Route Validation</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/approve_reject.php"
               class="sidebar-link <?= ($active_page === 'approve_reject') ? 'active' : '' ?>">
                <i class="bi bi-check2-square"></i>
                <span>Approve / Reject</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/request_corrections.php"
               class="sidebar-link <?= ($active_page === 'request_corrections') ? 'active' : '' ?>">
                <i class="bi bi-pencil-square"></i>
                <span>Request Corrections</span>
            </a>
        </li>

        <!-- System -->
        <li class="sidebar-section-header mt-3 mb-1 px-3 text-uppercase opacity-50"
            style="font-size:.65rem; letter-spacing:.1em;">System</li>

        <li class="sidebar-item">
            <a href="<?= $base ?>admin/route_validation.php?view=routes"
               class="sidebar-link <?= ($active_page === 'routes') ? 'active' : '' ?>">
                <i class="bi bi-signpost-2"></i>
                <span>Manage Routes</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="<?= $base ?>logout.php" class="sidebar-link text-danger-light">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar footer -->
    <div class="sidebar-footer px-4 py-3 mt-auto">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle avatar-sm">
                <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="lh-sm">
                <div class="fw-semibold small"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="text-capitalize opacity-60" style="font-size:.7rem;">
                    <?= e(str_replace('_', ' ', $_SESSION['admin_role'] ?? '')) ?>
                </div>
            </div>
        </div>
    </div>
</aside>
<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
