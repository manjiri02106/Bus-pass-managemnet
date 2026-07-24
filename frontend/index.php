<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Bus Pass Management System - ZEAL College of Engineering</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
                <a href="#home" class="nav-link active">Home</a>
                <a href="#about" class="nav-link">About</a>
                <a href="#routes" class="nav-link">Bus Routes</a>
                <a href="#notices" class="nav-link">Notices</a>
                <a href="#faq" class="nav-link">FAQ</a>
                <a href="#contact" class="nav-link">Contact</a>
            </nav>

            <a href="login.php" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h2 class="hero-title">
                    STUDENT<br>
                    <span class="highlight">BUS PASS</span><br>
                    MANAGEMENT SYSTEM
                </h2>
                <p class="hero-subtitle">Fast • Secure • Paperless</p>
                <p class="hero-description">
                    A smart and secure platform to apply, manage, renew, and track your college transportation bus pass online. Making student transportation easier and more transparent.
                </p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <button class="btn btn-success btn-lg">
                        <i class="fas fa-file-alt"></i> Apply Now
                    </button>
                    <button class="btn btn-outline btn-lg">
                        <i class="fas fa-bus"></i> View Bus Routes
                    </button>
                </div>
            </div>

            <div class="hero-image">
                <img src="hero-bus.png" alt="Pune city with bus transportation" class="hero-illustration-image">
                
                <!-- Login Card removed - redirects to login.php -->
            </div>
        </div>
    </section>

    <!-- Why Use Our Portal Section -->
    <section class="features" id="about">
        <div class="container">
            <div class="section-header">
                <h2>Why Use Our Portal?</h2>
                <p>Experience the future of student transportation management</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon online-app">
                        <i class="fas fa-clipboard"></i>
                    </div>
                    <h3>Online Application</h3>
                    <p>Apply for a new bus pass online in just a few simple steps</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon upload-docs">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Upload Documents</h3>
                    <p>Easily upload required documents securely online</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon track-status">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Track Status</h3>
                    <p>Track your application status in real-time from your dashboard</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon easy-renewal">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Easy Renewal</h3>
                    <p>Renew your bus pass online before it expires</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon notifications">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Notifications</h3>
                    <p>Get instant updates and reminders on your application</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon mobile-friendly">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile Friendly</h3>
                    <p>Access the portal anytime, anywhere on any device</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Information Cards Section -->
    <section class="info-section">
        <div class="container">
            <div class="info-cards-wrapper">
                <!-- Bus Route Information Card -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-route"></i>
                        <h3>Bus Route Information</h3>
                        <a href="#" class="view-all">View All Routes →</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="routes-table">
                            <thead>
                                <tr>
                                    <th>Route No.</th>
                                    <th>Source</th>
                                    <th>Destination</th>
                                    <th>Distance</th>
                                    <th>Bus No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="route-badge">R-01</span></td>
                                    <td>City Center</td>
                                    <td>College Campus</td>
                                    <td>12 km</td>
                                    <td>MH12 AB1234</td>
                                </tr>
                                <tr>
                                    <td><span class="route-badge">R-02</span></td>
                                    <td>New Market</td>
                                    <td>College Campus</td>
                                    <td>10 km</td>
                                    <td>MH12 AB1678</td>
                                </tr>
                                <tr>
                                    <td><span class="route-badge">R-03</span></td>
                                    <td>Railway Station</td>
                                    <td>College Campus</td>
                                    <td>14 km</td>
                                    <td>MH12 AB9012</td>
                                </tr>
                                <tr>
                                    <td><span class="route-badge">R-04</span></td>
                                    <td>Ganesh Nagar</td>
                                    <td>College Campus</td>
                                    <td>11 km</td>
                                    <td>MH12 AB3456</td>
                                </tr>
                                <tr>
                                    <td><span class="route-badge">R-05</span></td>
                                    <td>Sai Chowk</td>
                                    <td>College Campus</td>
                                    <td>9 km</td>
                                    <td>MH12 AB7890</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Important Notices Card -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-bell"></i>
                        <h3>Important Notices</h3>
                        <a href="#" class="view-all">View All →</a>
                    </div>
                    <div class="notices-list">
                        <div class="notice-item notice-new">
                            <span class="notice-badge new">New</span>
                            <div class="notice-content">
                                <p>New bus pass applications are now open for the academic year 2026-27</p>
                                <span class="notice-date">20 May 2026</span>
                            </div>
                        </div>
                        <div class="notice-item notice-info">
                            <span class="notice-badge info">Info</span>
                            <div class="notice-content">
                                <p>Carry your digital bus pass while traveling</p>
                                <span class="notice-date">18 May 2026</span>
                            </div>
                        </div>
                        <div class="notice-item notice-alert">
                            <span class="notice-badge alert">Alert</span>
                            <div class="notice-content">
                                <p>Renew your pass before the expiry date to avoid interruption</p>
                                <span class="notice-date">15 May 2026</span>
                            </div>
                        </div>
                        <div class="notice-item notice-update">
                            <span class="notice-badge update">Update</span>
                            <div class="notice-content">
                                <p>New routes added for the academic year 2026-27</p>
                                <span class="notice-date">10 May 2026</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Card -->
                <div class="info-card">
                    <div class="card-header">
                        <i class="fas fa-question-circle"></i>
                        <h3>Frequently Asked Questions</h3>
                        <a href="#" class="view-all">View All →</a>
                    </div>
                    <div class="faq-accordion">
                        <div class="accordion-item">
                            <button class="accordion-header">
                                <span>How do I apply for a bus pass?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <p>You can apply for a bus pass through our online portal. Go to the "Apply Now" section, fill in your details, upload required documents, and submit your application. Our team will verify and approve it within 2-3 business days.</p>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <button class="accordion-header">
                                <span>What documents are required?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <p>You need to provide: Valid college ID, Proof of residence (address), Government-issued ID, and Recent photograph (passport size).</p>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <button class="accordion-header">
                                <span>How can I check my application status?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <p>Log in to your account and go to the "Track Status" section. You'll see real-time updates on your application status.</p>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <button class="accordion-header">
                                <span>Can I renew my bus pass online?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <p>Yes! You can renew your bus pass online 30 days before expiry. Simply log in and click the "Renew Pass" button in your dashboard.</p>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <button class="accordion-header">
                                <span>How do I download my bus pass?</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <p>Once approved, your digital bus pass will be available in your dashboard. Click "Download" to save it as a PDF file to your device.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Support Section -->
    <section class="support-section">
        <div class="container">
            <div class="support-grid">
                <!-- Contact Information -->
                <div class="support-card">
                    <div class="support-icon contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Contact Information</h3>
                    <div class="support-content">
                        <div class="contact-item">
                            <i class="fas fa-building"></i>
                            <div>
                                <p class="label">College Transport Department</p>
                                <p>Zeal College of Engineering</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <p class="label">Phone</p>
                                <p>+91 12345 67890</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <p class="label">Email</p>
                                <p>transport@zealedu.in</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <p class="label">Address</p>
                                <p>Narhe, Pune – 411041</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Desk -->
                <div class="support-card">
                    <div class="support-icon helpdesk-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Help Desk</h3>
                    <div class="support-content">
                        <p class="support-text">We are here to help you!</p>
                        <div class="working-hours">
                            <p><strong>Working Hours</strong></p>
                            <p>Monday–Saturday</p>
                            <p>9:00 AM – 6:00 PM</p>
                        </div>
                        <div class="support-illustration">
                            <i class="fas fa-comments"></i>
                        </div>
                        <button class="btn btn-primary btn-block">
                            <i class="fas fa-ticket-alt"></i> Raise a Ticket
                        </button>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="support-card">
                    <div class="support-icon quicklinks-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Quick Links</h3>
                    <div class="support-content quicklinks-list">
                        <a href="#">
                            <i class="fas fa-book"></i> User Manual
                        </a>
                        <a href="#">
                            <i class="fas fa-file-contract"></i> Terms & Conditions
                        </a>
                        <a href="#">
                            <i class="fas fa-shield-alt"></i> Privacy Policy
                        </a>
                        <a href="#">
                            <i class="fas fa-undo"></i> Refund Policy
                        </a>
                        <a href="#">
                            <i class="fas fa-support"></i> Support Center
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <div class="footer-brand">
                    <i class="fas fa-graduation-cap footer-logo"></i>
                    <h4>ZEAL COLLEGE OF ENGINEERING</h4>
                </div>
                <p class="footer-description">
                    A comprehensive student transportation management system for managing student bus passes efficiently.
                </p>
            </div>

            <div class="footer-column">
                <h5>Important Links</h5>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#routes">Bus Routes</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
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
            <p>&copy; 2026 Zeal College of Engineering. All rights reserved.</p>
            <div class="footer-meta">
                <span>Version 1.0.0</span>
                <span>Last Updated: 20 May 2026</span>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>

