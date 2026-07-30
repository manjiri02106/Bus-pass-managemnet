<?php
// profile.php - Transport Officer Profile Management
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';

// Enforce access for Officer or Admin
check_auth(['officer', 'admin']);

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        if (empty($full_name)) {
            throw new Exception("Full Name cannot be empty.");
        }

        // Fetch current user details
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Update password if requested
        $update_password_sql = "";
        $params = [$full_name];

        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception("Current password is required to set a new password.");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Incorrect current password.");
            }
            
            $update_password_sql = ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $params[] = $user_id;
        $pdo->prepare("UPDATE users SET full_name = ? $update_password_sql WHERE id = ?")->execute($params);
        
        // Update session
        $_SESSION['full_name'] = $full_name;
        
        $message = "Profile updated successfully.";
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT username, email, full_name, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch();

include __DIR__ . '/header.php';
include __DIR__ . '/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">My Profile</h2>
    <p class="page-subtitle">Manage your personal information and account security.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="white-card shadow-sm h-100" style="padding: 40px 20px;">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <div class="mb-4">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; font-size: 48px; background: linear-gradient(135deg, var(--star-blue), #b66dff); box-shadow: 0px 10px 20px rgba(48, 58, 246, 0.2);">
                        <?= strtoupper(substr($userData['full_name'], 0, 1)) ?>
                    </div>
                </div>
                <h4 class="mb-1 fw-bold" style="color: var(--text-dark);"><?= htmlspecialchars($userData['full_name']) ?></h4>
                <p style="color: var(--star-blue); font-weight: 500;" class="mb-4"><?= htmlspecialchars(ucfirst($userData['role'])) ?></p>
                
                <div class="text-start mt-2" style="background: var(--bg-light); border-radius: 8px; padding: 20px;">
                    <div class="mb-3">
                        <small style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 3px;">Username</small>
                        <span style="color: var(--text-dark); font-weight: 500;"><i class="bi bi-person me-2" style="color: var(--star-blue);"></i><?= htmlspecialchars($userData['username']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 3px;">Email Address</small>
                        <span style="color: var(--text-dark); font-weight: 500;"><i class="bi bi-envelope me-2" style="color: var(--star-blue);"></i><?= htmlspecialchars($userData['email']) ?></span>
                    </div>
                    <div>
                        <small style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 3px;">Member Since</small>
                        <span style="color: var(--text-dark); font-weight: 500;"><i class="bi bi-calendar me-2" style="color: var(--star-blue);"></i><?= date('F j, Y', strtotime($userData['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="white-card shadow-sm h-100">
            <div class="mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                <h4 class="mb-0 fw-bold" style="color: var(--text-dark);">Update Profile Details</h4>
            </div>
            <div class="card-body px-0 py-2">
                <form method="post" action="profile.php">
                    <h6 class="mb-3 fw-bold" style="color: var(--star-blue);">Personal Information</h6>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($userData['full_name']) ?>" required>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3 mt-5 fw-bold" style="color: var(--star-blue);">Change Password</h6>
                    <p class="small mb-4" style="color: var(--text-muted);">Leave password fields blank if you do not wish to change your password.</p>
                    
                    <div class="row g-4 mb-5">
                        <div class="col-md-12">
                            <label class="form-label fw-medium" style="color: var(--text-dark);">Current Password</label>
                            <input type="password" class="form-control" name="current_password" placeholder="Enter current password to verify" style="padding: 10px 15px; border: 1px solid var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="color: var(--text-dark);">New Password</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Enter new password" style="padding: 10px 15px; border: 1px solid var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium" style="color: var(--text-dark);">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password" style="padding: 10px 15px; border: 1px solid var(--border-color);">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end pt-3" style="border-top: 1px solid var(--border-color);">
                        <button type="submit" class="btn btn-primary px-5 py-2" style="background: var(--star-blue); border: none; font-weight: 500; border-radius: 4px;">
                            <i class="bi bi-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
