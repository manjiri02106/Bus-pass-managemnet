<?php
/**
 * dashboards/admin_dashboard.php — Administrator landing after login.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_functions.php';

require_role(['admin']);

$dashTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/dash_layout.php';
?>

<div class="dash-hero mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <p class="mb-1 opacity-75">Administrator Console</p>
      <h2 class="fw-bold mb-1"><?= e($_SESSION['user_name']) ?> 🛡️</h2>
      <p class="mb-0 opacity-75">Full control over users, officers, routes and system settings.</p>
    </div>
    <a href="#" class="btn btn-light btn-gradient rounded-pill px-4">
      <i class="bi bi-person-plus me-1"></i>Add Officer
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-mortarboard"></i></div>
      <div class="fw-bold fs-4">1</div><small class="text-muted">Students</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-person-badge"></i></div>
      <div class="fw-bold fs-4">1</div><small class="text-muted">Officers</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-diagram-3"></i></div>
      <div class="fw-bold fs-4">—</div><small class="text-muted">Routes</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-graph-up-arrow"></i></div>
      <div class="fw-bold fs-4">—</div><small class="text-muted">Passes Issued</small>
    </div>
  </div>
</div>

<div class="stat-card p-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Admin Actions</h5>
  <div class="d-flex flex-wrap gap-2">
    <a href="#" class="btn btn-soft"><i class="bi bi-people-fill me-1"></i>Manage Users</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-person-badge me-1"></i>Manage Officers</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-signpost-split me-1"></i>Manage Routes</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-gear me-1"></i>System Settings</a>
    <a href="<?= e(BASE_URL) ?>/change_password.php" class="btn btn-soft"><i class="bi bi-key me-1"></i>Change Password</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
