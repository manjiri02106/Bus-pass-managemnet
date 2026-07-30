<?php
/**
 * includes/dash_layout.php — Shared top navbar for role dashboards.
 * Sets $dashTitle and $dashRoleLabel before including.
 */
$dashTitle     = $dashTitle ?? 'Dashboard';
$dashRoleLabel = VALID_ROLES[current_role()] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($dashTitle) ?> · <?= e(APP_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth.css">
  <style>body.auth-body{display:block;padding:0;background:#f8fafc;}</style>
</head>
<body class="auth-body">
<nav class="dash-navbar sticky-top">
  <div class="container d-flex align-items-center justify-content-between py-2">
    <a href="<?= e(BASE_URL) ?>/index.php" class="d-flex align-items-center text-decoration-none text-dark fw-bold">
      <span class="brand-badge me-2" style="width:38px;height:38px;font-size:1rem;border-radius:12px;">
        <i class="bi bi-bus-front-fill"></i></span>
      <?= e(APP_NAME) ?>
    </a>
    <div class="d-flex align-items-center gap-2">
      <span class="badge rounded-pill text-bg-light border d-none d-sm-inline">
        <i class="bi bi-person-circle me-1"></i><?= e($dashRoleLabel) ?>
      </span>
      <a href="<?= e(BASE_URL) ?>/change_password.php" class="btn btn-soft btn-sm">
        <i class="bi bi-key me-1"></i>Change Password
      </a>
      <a href="<?= e(BASE_URL) ?>/logout.php" class="btn btn-sm btn-outline-danger rounded-pill">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
      </a>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?= render_flash() ?>
