<?php
// admin-dashboard/students.php
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
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
            $message = "Student profile deleted successfully.";
        } elseif ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $prn = trim($_POST['prn_number'] ?? '');
            $roll = trim($_POST['roll_number'] ?? '');
            $dept = trim($_POST['department'] ?? '');
            $cls = trim($_POST['class'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($prn) || empty($roll)) {
                throw new Exception("PRN and Roll Number are required.");
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE students SET prn_number = ?, roll_number = ?, department = ?, class = ?, mobile = ?, address = ? WHERE id = ?");
                $stmt->execute([$prn, $roll, $dept, $cls, $mobile, $address, $id]);
                $message = "Student profile updated successfully.";
            } else {
                throw new Exception("Adding new students directly via admin is currently unsupported.");
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Fetch students
$query = "
    SELECT s.*, u.full_name, u.email 
    FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.prn_number
";
$students = $pdo->query($query)->fetchAll();

// Editing state
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="page-title-section">
    <h2 class="page-title">Student Management</h2>
    <p class="page-subtitle">View and update student profiles and academic details.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert" style="margin: 20px;">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="float:right; border:none; background:transparent; font-size:1.2rem;">&times;</button>
    </div>
<?php endif; ?>

<?php if ($editing): ?>
<div class="detail-grid" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title">Edit Student: <?= htmlspecialchars($editing['full_name']) ?></h3>
        </div>
        <div style="padding: 20px;">
            <form method="post" action="students.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editing['id'] ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Linked Account</label>
                        <input type="text" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; background:#f5f5f5;" value="<?= htmlspecialchars($editing['email']) ?>" disabled>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">PRN Number <span style="color:red">*</span></label>
                        <input type="text" name="prn_number" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['prn_number']) ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Roll Number <span style="color:red">*</span></label>
                        <input type="text" name="roll_number" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['roll_number']) ?>" required>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Department</label>
                        <input type="text" name="department" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['department']) ?>">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Class</label>
                        <input type="text" name="class" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['class']) ?>">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Mobile</label>
                        <input type="text" name="mobile" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['mobile']) ?>">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">Address</label>
                        <input type="text" name="address" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" value="<?= htmlspecialchars($editing['address']) ?>">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="padding: 10px 20px;">Update Profile</button>
                    <a href="students.php" style="margin-left: 10px; color: #555; text-decoration: none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="detail-grid mt-4" style="grid-template-columns: 1fr;">
    <div class="table-container">
        <div class="table-header-row">
            <h3 class="table-title">Registered Students</h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>PRN</th>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Dept & Class</th>
                    <th>Mobile</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:20px; color:#666;">No students registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?= htmlspecialchars($student['prn_number']) ?></strong></td>
                            <td><?= htmlspecialchars($student['roll_number']) ?></td>
                            <td>
                                <div><strong><?= htmlspecialchars($student['full_name']) ?></strong></div>
                                <span style="font-size:0.8rem; color:#666;"><?= htmlspecialchars($student['email']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($student['department']) ?> - <?= htmlspecialchars($student['class']) ?></td>
                            <td><?= htmlspecialchars($student['mobile']) ?></td>
                            <td style="text-align: right;">
                                <a href="?edit=<?= $student['id'] ?>" style="color: var(--accent); margin-right: 10px; text-decoration: none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                <form method="post" action="students.php" style="display:inline;" onsubmit="return confirm('Delete this student profile?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                                    <button type="submit" style="background:none; border:none; color:red; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
