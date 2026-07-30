<?php
/**
 * Admin Page Header
 * Bus Pass Management System
 *
 * @param string $page_title   Title shown in <title> and page header
 * @param string $active_page  Key to mark the active sidebar item
 */

// Variables $page_title and $active_page must be set before including this file.
$page_title  ??= 'Dashboard';
$active_page ??= 'dashboard';

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bus Pass Management System — Admin Portal">
    <meta name="robots" content="noindex, nofollow">

    <title><?= e($page_title) ?> — Bus Pass Admin</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="<?= e(str_contains($_SERVER['PHP_SELF'], '/admin/') ? '../' : '') ?>assets/css/admin.css" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- ── Main wrapper ─────────────────────────────────────────── -->
<div class="main-content" id="mainContent">

    <!-- Top navbar -->
    <nav class="topnav d-flex align-items-center justify-content-between px-4 py-2">
        <button class="btn btn-link sidebar-toggle p-0 me-3" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="d-flex align-items-center gap-2 ms-auto">
            <!-- Time display -->
            <span class="text-muted small me-2 d-none d-md-inline" id="liveClock"></span>

            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link position-relative p-1" id="notifDropdown"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          id="notifBadge" style="font-size:.6rem;">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg" id="notifList"
                    style="min-width:320px; max-height:400px; overflow-y:auto;">
                    <li class="dropdown-header fw-semibold px-3 py-2 border-bottom">Notifications</li>
                    <li class="dropdown-item-text text-muted small px-3 py-2">Loading…</li>
                </ul>
            </div>

            <!-- Admin profile dropdown -->
            <div class="dropdown ms-2">
                <button class="btn btn-link d-flex align-items-center gap-2 text-decoration-none p-1"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline fw-semibold small">
                        <?= e($_SESSION['admin_name'] ?? 'Admin') ?>
                    </span>
                    <i class="bi bi-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header"><?= e($_SESSION['admin_email'] ?? '') ?></h6></li>
                    <li><span class="dropdown-item-text small text-muted text-capitalize">
                        Role: <?= e(str_replace('_', ' ', $_SESSION['admin_role'] ?? '')) ?>
                    </span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(str_contains($_SERVER['PHP_SELF'], '/admin/') ? '../' : '') ?>logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- /Top navbar -->

    <!-- Page content area -->
    <div class="page-content px-4 py-3">

        <!-- Page heading -->
        <div class="page-header mb-4">
            <h1 class="page-title"><?= e($page_title) ?></h1>
        </div>

        <!-- Flash message -->
        <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type'])) ?>
                    alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'error' ? 'x-circle-fill' : 'info-circle-fill') ?>"></i>
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
