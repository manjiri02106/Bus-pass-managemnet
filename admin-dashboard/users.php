<?php
// admin-dashboard/users.php
require_once __DIR__ . '/../auth/config/session.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../Transport-Officer-Module/db.php';

$message = '';
$messageType = 'success';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)$_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            $message = "User deleted successfully.";
        } elseif ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = trim($_POST['role'] ?? 'student');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($email) || empty($full_name)) {
                throw new Exception("All fields are required.");
            }

            if ($id > 0) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $full_name, $role, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $full_name, $role, $id]);
                }
                $message = "User updated successfully.";
            } else {
                // Insert
                if (empty($password)) {
                    throw new Exception("Password is required for new users.");
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $full_name, $role, $hash]);
                $message = "User created successfully.";
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle search
$search = trim($_GET['search'] ?? '');
$where = "1=1";
$params = [];

if ($search !== '') {
    $where = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

// Fetch users
$stmt = $pdo->prepare("SELECT * FROM users WHERE $where ORDER BY role, full_name");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Editing state
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">User Management</h2>
    <p class="page-subtitle">Manage system access, roles, and profiles.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert" style="margin: 20px;">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="float:right; border:none; background:transparent; font-size:1.2rem;">&times;</button>
    </div>
<?php endif; ?>

<div class="detail-grid" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title"><?= $editing ? 'Edit User' : 'Add New User' ?></h3>
        </div>
        <div style="padding: 20px;">
            <form method="post" action="users.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Username <span style="color:red">*</span></label>
                        <input type="text" name="username" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['username'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Full Name <span style="color:red">*</span></label>
                        <input type="text" name="full_name" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Email <span style="color:red">*</span></label>
                        <input type="email" name="email" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Role</label>
                        <select name="role" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                            <option value="student" <?= ($editing && $editing['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                            <option value="officer" <?= ($editing && $editing['role'] === 'officer') ? 'selected' : '' ?>>Transport Officer</option>
                            <option value="admin" <?= ($editing && $editing['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Password <?= $editing ? '<span style="color:#888; font-size:0.8rem;">(Leave blank to keep)</span>' : '<span style="color:red">*</span>' ?></label>
                        <input type="password" name="password" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" <?= $editing ? '' : 'required' ?>>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="padding: 10px 20px;"><?= $editing ? 'Update User' : 'Create User' ?></button>
                    <?php if ($editing): ?>
                        <a href="users.php" style="margin-left: 10px; color: #555; text-decoration: none;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="detail-grid mt-4" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="table-title">System Users</h3>
            <form method="get" action="users.php" style="display: flex; gap: 10px;">
                <input type="text" name="search" style="padding:5px; border:1px solid #ccc; border-radius:4px;" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary" style="padding: 5px 15px;">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" style="color: red; text-decoration: none; align-self: center;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-rejected" style="background:#dc3545;">Admin</span>
                            <?php elseif ($user['role'] === 'officer'): ?>
                                <span class="badge" style="background:#0d6efd; color:white;">Officer</span>
                            <?php else: ?>
                                <span class="badge badge-pending" style="background:#6c757d;">Student</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="?edit=<?= $user['id'] ?>" style="color: var(--accent); margin-right: 10px; text-decoration: none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="post" action="users.php" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:red; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
