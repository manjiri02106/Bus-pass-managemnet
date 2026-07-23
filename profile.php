<?php
/**
 * Student Profile - View and Edit
 * Bus Pass Management System
 */
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$student = getCurrentStudent();
$student_id = (int)$_SESSION['student_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $dob = sanitize($_POST['dob']);
    $gender = sanitize($_POST['gender']);
    $address = sanitize($_POST['address']);
    $college_name = sanitize($_POST['college_name']);
    $course = sanitize($_POST['course']);
    $year_of_study = (int)$_POST['year_of_study'];

    if (empty($full_name) || empty($phone) || empty($college_name)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Handle profile picture upload
        $profile_pic = $student['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed)) {
                $profile_pic = 'student_' . $student_id . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], UPLOAD_PATH . $profile_pic);
                // Delete old picture if not default
                if ($student['profile_pic'] !== 'default.png' && file_exists(UPLOAD_PATH . $student['profile_pic'])) {
                    unlink(UPLOAD_PATH . $student['profile_pic']);
                }
            } else {
                $error = 'Profile picture must be JPG, PNG or GIF.';
            }
        }

        if (empty($error)) {
            $q = "UPDATE students SET full_name=?, phone=?, dob=?, gender=?, address=?, college_name=?, course=?, year_of_study=?, profile_pic=? WHERE id=?";
            $s = mysqli_prepare($conn, $q);
            mysqli_stmt_bind_param($s, 'sssssssssi', $full_name, $phone, $dob, $gender, $address, $college_name, $course, $year_of_study, $profile_pic, $student_id);
            
            if (mysqli_stmt_execute($s)) {
                $_SESSION['student_name'] = $full_name;
                $success = 'Profile updated successfully!';
                $student = getCurrentStudent(); // Refresh
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4">
        <?php displayFlashMessage(); ?>
        
        <div class="fade-in">
            <h4 class="mb-4"><i class="bi bi-person-circle text-primary me-2"></i>My Profile</h4>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <div class="mb-3">
                                <img src="<?php echo BASE_URL; ?>/uploads/<?php echo $student['profile_pic'] ?: 'default.png'; ?>" 
                                     alt="Profile" class="rounded-circle img-thumbnail" 
                                     style="width:150px;height:150px;object-fit:cover;"
                                     id="profilePreview"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&size=150&background=0d6efd&color=fff'">
                            </div>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                            <p class="text-muted mb-1"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($student['email']); ?></p>
                            <p class="text-muted mb-0"><i class="bi bi-card-text me-1"></i><?php echo htmlspecialchars($student['college_id_number']); ?></p>
                            <hr>
                            <p class="small text-muted mb-0">Member since: <?php echo date('d M Y', strtotime($student['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($student['email']); ?>" readonly disabled>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" name="dob" class="form-control" 
                                               value="<?php echo $student['dob']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="">Select</option>
                                            <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">College Name <span class="text-danger">*</span></label>
                                        <input type="text" name="college_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($student['college_name']); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Course</label>
                                        <input type="text" name="course" class="form-control" 
                                               value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Year of Study</label>
                                        <select name="year_of_study" class="form-select">
                                            <option value="">Select</option>
                                            <?php for ($y = 1; $y <= 4; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($student['year_of_study'] ?? 0) == $y ? 'selected' : ''; ?>>
                                                <?php echo $y; ?><?php echo $y === 1 ? 'st' : ($y === 2 ? 'nd' : ($y === 3 ? 'rd' : 'th')); ?> Year
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" name="profile_pic" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                                        <small class="text-muted">Leave empty to keep current picture</small>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
