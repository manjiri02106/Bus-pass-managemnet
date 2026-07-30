<?php
/**
 * dashboards/student_dashboard.php — Student landing after login.
 * Replace the placeholder cards with your bus-pass features (apply, track, renew…).
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/auth_functions.php';

require_role(['student']);

$dashTitle = 'Student Dashboard';
require __DIR__ . '/../includes/dash_layout.php';
?>

<div class="dash-hero mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <p class="mb-1 opacity-75">Welcome back,</p>
      <h2 class="fw-bold mb-1"><?= e($_SESSION['user_name']) ?> 👋</h2>
      <p class="mb-0 opacity-75">Manage your bus pass applications and travel history.</p>
    </div>
    <a href="#" class="btn btn-light btn-gradient rounded-pill px-4">
      <i class="bi bi-plus-circle me-1"></i>Apply for Bus Pass
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-ticket-perforated"></i></div>
      <div class="fw-bold fs-4">Active</div><small class="text-muted">Current Pass</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-hourglass-split"></i></div>
      <div class="fw-bold fs-4">0</div><small class="text-muted">Pending Requests</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-geo-alt"></i></div>
      <div class="fw-bold fs-4">—</div><small class="text-muted">Assigned Route</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card p-3 h-100">
      <div class="stat-icon mb-2"><i class="bi bi-calendar-check"></i></div>
      <div class="fw-bold fs-4">—</div><small class="text-muted">Pass Expiry</small>
    </div>
  </div>
</div>

<div class="stat-card p-4">
  <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions</h5>
  <div class="d-flex flex-wrap gap-2">
    <a href="#" class="btn btn-soft"><i class="bi bi-file-earmark-plus me-1"></i>New Application</a>
    <a href="#" class="btn btn-soft"><i class="bi bi-clock-history me-1"></i>Application History</a>
    <a href="<?= e(BASE_URL) ?>/change_password.php" class="btn btn-soft"><i class="bi bi-key me-1"></i>Change Password</a>
  </div>
  <hr>
  <p class="text-muted small mb-0">
    <i class="bi bi-info-circle me-1"></i>
    This dashboard is a starter — hook your bus-pass modules (apply / track / renew / payments) into the
    quick actions above. Your session expires automatically after 20 minutes of inactivity.
  </p>
</div>

<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
