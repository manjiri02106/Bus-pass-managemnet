<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$students = get_all_students();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editing = null;
if ($editId !== null) {
    foreach ($students as $student) {
        if ((int)$student['id'] === $editId) {
            $editing = $student;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete' && !empty($_POST['id'])) {
        $students = array_values(array_filter($students, function ($student) {
            return (int)$student['id'] !== (int)$_POST['id'];
        }));
        delete_student((int)$_POST['id']);
        header('Location: students.php');
        exit;
    }

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : time();
    $data = [
        'id' => $id,
        'student_id' => trim($_POST['student_id'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'course' => trim($_POST['course'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'status' => trim($_POST['status'] ?? 'Pending'),
    ];

    save_student($id, $data['student_id'], $data['name'], $data['department'], $data['course'], $data['phone'], $data['status']);
    header('Location: students.php');
    exit;
}

page_header('Student Management', 'Student Management');
?>
<h2>Student Management</h2>
<p>Create, update, and remove student records for bus pass eligibility.</p>
<form method="post" class="form-grid">
    <input type="hidden" name="id" value="<?php echo isset($editing['id']) ? (int)$editing['id'] : ''; ?>">
    <label>
        Student ID
        <input type="text" name="student_id" required value="<?php echo isset($editing['student_id']) ? htmlspecialchars($editing['student_id']) : ''; ?>">
    </label>
    <label>
        Full Name
        <input type="text" name="name" required value="<?php echo isset($editing['name']) ? htmlspecialchars($editing['name']) : ''; ?>">
    </label>
    <label>
        Department
        <input type="text" name="department" value="<?php echo isset($editing['department']) ? htmlspecialchars($editing['department']) : ''; ?>">
    </label>
    <label>
        Course
        <input type="text" name="course" value="<?php echo isset($editing['course']) ? htmlspecialchars($editing['course']) : ''; ?>">
    </label>
    <label>
        Phone Number
        <input type="text" name="phone" value="<?php echo isset($editing['phone']) ? htmlspecialchars($editing['phone']) : ''; ?>">
    </label>
    <label>
        Status
        <select name="status">
            <option value="Pending" <?php echo (isset($editing['status']) && $editing['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="Approved" <?php echo (isset($editing['status']) && $editing['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="Rejected" <?php echo (isset($editing['status']) && $editing['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
        </select>
    </label>
    <button type="submit">Save Student</button>
</form>
<div class="panel">
    <h3>Student Records</h3>
    <table>
        <thead>
            <tr><th>Student ID</th><th>Name</th><th>Department</th><th>Course</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                    <td><?php echo htmlspecialchars($student['status']); ?></td>
                    <td>
                        <a href="students.php?edit=<?php echo (int)$student['id']; ?>">Edit</a>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>
