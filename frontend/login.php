<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMPML Bus Pass Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-page {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl) var(--spacing-lg);
            background: linear-gradient(135deg, var(--bg-secondary) 0%, #FFFFFF 100%);
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .login-card-page {
            background: var(--bg-primary);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
        }
        .login-card-page .login-card-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        .login-card-page .login-card-header i {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: var(--spacing-md);
        }
        .login-card-page .login-card-header h2 {
            font-size: 24px;
            margin-bottom: var(--spacing-sm);
        }
        .login-card-page .login-card-header p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 0;
        }
        .login-card-page .form-group {
            margin-bottom: var(--spacing-lg);
        }
        .login-card-page .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .login-card-page .form-control {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        .login-card-page .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 76, 219, 0.1);
        }
        .login-card-page .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .login-card-page .input-icon {
            position: absolute;
            left: var(--spacing-md);
            color: var(--text-secondary);
            font-size: 14px;
            pointer-events: none;
        }
        .login-card-page .input-wrapper .form-control {
            padding-left: 40px;
        }
        .login-card-page .btn-block {
            width: 100%;
            justify-content: center;
        }
        .login-card-page .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            font-size: 13px;
        }
        .login-card-page .form-footer a {
            color: var(--primary);
            font-weight: 600;
        }
        .login-card-page .signup-text {
            text-align: center;
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: var(--spacing-lg);
        }
        .login-card-page .signup-text a {
            color: var(--primary);
            font-weight: 600;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-secondary);
            font-size: 13px;
            margin-bottom: var(--spacing-lg);
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="logo-wrapper">
                    <i class="fas fa-graduation-cap logo-icon"></i>
                </div>
                <div class="brand-text">
                    <h1 class="college-name">PMPML</h1>
                    <p class="college-tagline">Nurturing Excellence, Inspiring Innovation</p>
                </div>
            </div>

            <nav class="nav-menu">
                <a href="index.php#home" class="nav-link">Home</a>
                <a href="index.php#about" class="nav-link">About</a>
                <a href="index.php#routes" class="nav-link">Bus Routes</a>
                <a href="index.php#notices" class="nav-link">Notices</a>
                <a href="index.php#faq" class="nav-link">FAQ</a>
                <a href="index.php#contact" class="nav-link">Contact</a>
            </nav>

            <a href="index.php" class="btn btn-outline btn-login">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Login Page Content -->
    <section class="login-page">
        <div class="login-wrapper">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <div class="login-card-page">
                <div class="login-card-header">
                    <i class="fas fa-user-circle"></i>
                    <h2>Login to Your Account</h2>
                    <p>Welcome back! Please enter your credentials.</p>
                </div>

                <form id="loginForm" method="post" action="">
                    <div class="form-group">
                        <label>Select Role</label>
                        <select class="form-control" required>
                            <option value="">Choose a role</option>
                            <option value="student">Student</option>
                            <option value="parent">Parent</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Username or Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" class="form-control" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="form-footer">
                        <label><input type="checkbox"> Remember me</label>
                        <a href="#">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>

                    <p class="signup-text">
                        Don't have an account? <a href="#">Sign up here</a>
                    </p>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <div class="footer-brand">
                    <i class="fas fa-graduation-cap footer-logo"></i>
                    <h4>PMPML - BUS PASS MANAGEMENT</h4>
                </div>
                <p class="footer-description">
                    A comprehensive student transportation management system for managing student bus passes efficiently.
                </p>
            </div>

            <div class="footer-column">
                <h5>Important Links</h5>
                <ul>
                    <li><a href="index.php#home">Home</a></li>
                    <li><a href="index.php#about">About Us</a></li>
                    <li><a href="index.php#routes">Bus Routes</a></li>
                    <li><a href="index.php#faq">FAQ</a></li>
                    <li><a href="index.php#contact">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h5>Stay Connected</h5>
                <div class="social-links">
                    <a href="#" class="social-icon facebook" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon instagram" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-icon youtube" title="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="social-icon twitter" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 PMPML Bus Pass Management. All rights reserved.</p>
            <div class="footer-meta">
                <span>Version 1.0.0</span>
                <span>Last Updated: 20 May 2026</span>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>

