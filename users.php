<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$users = get_all_users();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editing = null;
if ($editId !== null) {
    foreach ($users as $user) {
        if ((int)$user['id'] === $editId) {
            $editing = $user;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' && !empty($_POST['id'])) {
        $users = array_values(array_filter($users, function ($user) {
            return (int)$user['id'] !== (int)$_POST['id'];
        }));
        delete_user((int)$_POST['id']);
        header('Location: users.php');
        exit;
    }

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : time();
    $data = [
        'id' => $id,
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => trim($_POST['role'] ?? 'Viewer'),
        'status' => trim($_POST['status'] ?? 'Active'),
    ];

    save_user($id, $data['name'], $data['email'], $data['role'], $data['status']);
    header('Location: users.php');
    exit;
}

page_header('User Management', 'User Management');
?>
<h2>User Management</h2>
<p>Manage administrators, staff, and reviewers for the portal.</p>
<form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?php echo isset($editing['id']) ? (int)$editing['id'] : ''; ?>">
    <label>
        Full Name
        <input type="text" name="name" required value="<?php echo isset($editing['name']) ? htmlspecialchars($editing['name']) : ''; ?>">
    </label>
    <label>
        Email
        <input type="email" name="email" required value="<?php echo isset($editing['email']) ? htmlspecialchars($editing['email']) : ''; ?>">
    </label>
    <label>
        Role
        <select name="role">
            <option value="Admin" <?php echo (isset($editing['role']) && $editing['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="Staff" <?php echo (isset($editing['role']) && $editing['role'] === 'Staff') ? 'selected' : ''; ?>>Staff</option>
            <option value="Viewer" <?php echo (isset($editing['role']) && $editing['role'] === 'Viewer') ? 'selected' : ''; ?>>Viewer</option>
        </select>
    </label>
    <label>
        Status
        <select name="status">
            <option value="Active" <?php echo (isset($editing['status']) && $editing['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo (isset($editing['status']) && $editing['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </label>
    <button type="submit">Save User</button>
</form>
<div class="panel">
    <h3>Existing Users</h3>
    <table>
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['status']); ?></td>
                    <td>
                        <a href="users.php?edit=<?php echo (int)$user['id']; ?>">Edit</a>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>
