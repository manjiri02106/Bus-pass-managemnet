<?php
/**
 * dashboards/officer_dashboard.php — Transport Officer landing after login.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_functions.php';

require_role(['officer']);

$dashTitle = 'Officer Dashboard';
require __DIR__ . '/../includes/dash_layout.php';
?>

<div class="dash-hero mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <p class="mb-1 opacity-75">Transport Officer Portal</p>
      <h2 class="fw-bold mb-1"><?= e($_SESSION['user_name']) ?> 🚍</h2>
      <p class="mb-0 opacity-75">Review applications, verify documents and approve bus passes.</p>
    </div>
    <a href="#" class="btn btn-light btn-gradient rounded-pill px-4">
      <i class="bi bi-clipboard-check me-1"></i>Review Queue
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-inbox"></i></div>
      <div class="fw-bold fs-4">0</div><small class="text-muted">New Applications</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-search"></i></div>
      <div class="fw-bold fs-4">0</div><small class="text-muted">Under Review</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-check2-all"></i></div>
      <div class="fw-bold fs-4">0</div><small class="text-muted">Approved Today</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-bus-front"></i></div>
      <div class="fw-bold fs-4">—</div><small class="text-muted">Active Routes</small>
    </div>
  </div>
</div>

<div class="stat-card p-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions</h5>
  <div class="d-flex flex-wrap gap-2">
    <a href="#" class="btn btn-soft"><i class="bi bi-check-square me-1"></i>Approve / Reject</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-people me-1"></i>Pass Holders</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-map me-1"></i>Route Management</a>
    <a href="<?= e(BASE_URL) ?>/change_password.php" class="btn btn-soft"><i class="bi bi-key me-1"></i>Change Password</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
