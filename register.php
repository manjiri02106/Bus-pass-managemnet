<?php
/**
 * Student Registration Page
 * Bus Pass Management System
 */
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $college_name = sanitize($_POST['college_name']);
    $college_id_number = sanitize($_POST['college_id_number']);
    $course = sanitize($_POST['course']);
    $year_of_study = (int)$_POST['year_of_study'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($college_name) || empty($college_id_number)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $check = "SELECT id FROM students WHERE email = ? OR college_id_number = ?";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, 'ss', $email, $college_id_number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'An account with this email or College ID already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO students (full_name, email, password, phone, college_name, college_id_number, course, year_of_study) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'sssssssi', $full_name, $email, $hashed_password, $phone, $college_name, $college_id_number, $course, $year_of_study);
            
            if (mysqli_stmt_execute($stmt)) {
                $student_id = mysqli_insert_id($conn);
                
                // Create welcome notification
                $notif_query = "INSERT INTO notifications (student_id, title, message, type) VALUES (?, 'Welcome!', 'Welcome to Bus Pass Management System! Please complete your profile and apply for a bus pass.', 'success')";
                $notif_stmt = mysqli_prepare($conn, $notif_query);
                mysqli_stmt_bind_param($notif_stmt, 'i', $student_id);
                mysqli_stmt_execute($notif_stmt);
                
                // Auto login
                $_SESSION['student_id'] = $student_id;
                $_SESSION['student_name'] = $full_name;
                
                redirect('/dashboard.php', 'Registration successful! Welcome to ' . APP_NAME, 'success');
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card card fade-in" style="max-width: 600px;">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="auth-logo">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h3 class="fw-bold">Create Account</h3>
                    <p class="text-muted">Register as a student to apply for Bus Pass</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="full_name" class="form-control" 
                                       placeholder="John Doe" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="john@college.edu" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" name="phone" class="form-control" 
                                       placeholder="9876543210" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">College ID Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="college_id_number" class="form-control" 
                                       placeholder="CS2021001" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">College Name <span class="text-danger">*</span></label>
                            <input type="text" name="college_name" class="form-control" 
                                   placeholder="University of Example" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Course</label>
                            <input type="text" name="course" class="form-control" 
                                   placeholder="B.Sc. Computer Science">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Year</label>
                            <select name="year_of_study" class="form-select">
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" 
                                       id="password" placeholder="Min 6 characters" required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" data-target="#password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" class="form-control" 
                                       id="confirm_password" placeholder="Re-enter password" required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" data-target="#confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">
                        Already have an account? 
                        <a href="login.php" class="fw-bold text-decoration-none">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>

