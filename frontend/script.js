/* ============================================
   STUDENT BUS PASS MANAGEMENT SYSTEM
   JavaScript - Interactive Elements
   ============================================ */

// ============================================
// INITIALIZE WHEN DOM IS READY
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    initializeAccordions();
    initializeMobileMenu();
    initializePasswordToggle();
    initializeNavigation();
    initializeFormInteractions();
    observeAnimations();
});

// ============================================
// MOBILE MENU TOGGLE
// ============================================

function initializeMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    if (!menuToggle) return;

    menuToggle.addEventListener('click', () => {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close menu when a link is clicked
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
}

// ============================================
// ACCORDION FUNCTIONALITY
// ============================================

function initializeAccordions() {
    const accordionHeaders = document.querySelectorAll('.accordion-header');

    accordionHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const accordionItem = header.parentElement;
            const isActive = accordionItem.classList.contains('active');

            // Close all other accordions
            document.querySelectorAll('.accordion-item').forEach(item => {
                item.classList.remove('active');
            });

            // Toggle current accordion
            if (!isActive) {
                accordionItem.classList.add('active');
            }
        });

        // Keyboard support for accordion
        header.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                header.click();
            }
        });
    });
}

// ============================================
// PASSWORD VISIBILITY TOGGLE
// ============================================

function initializePasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const input = button.previousElementSibling;
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            button.classList.toggle('fa-eye');
            button.classList.toggle('fa-eye-slash');

            // Update aria-label for accessibility
            const label = isPassword ? 'Hide password' : 'Show password';
            button.setAttribute('aria-label', label);
        });
    });
}

// ============================================
// NAVIGATION - ACTIVE STATE & SMOOTH SCROLL
// ============================================

function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('section');

    // Handle nav link clicks
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();

            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));

            // Add active class to clicked link
            link.classList.add('active');

            // Smooth scroll to section
            const targetId = link.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Update active nav link on scroll
    window.addEventListener('scroll', () => {
        let currentSection = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            const sectionHeight = section.clientHeight;

            if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                currentSection = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').substring(1) === currentSection) {
                link.classList.add('active');
            }
        });
    });
}

// ============================================
// FORM INTERACTIONS
// ============================================

function initializeFormInteractions() {
    const formControls = document.querySelectorAll('.form-control');

    // Add focus/blur animations
    formControls.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });

        // Add input animation effect
        input.addEventListener('input', () => {
            if (input.value.trim() !== '') {
                input.classList.add('has-value');
            } else {
                input.classList.remove('has-value');
            }
        });
    });
}

// ============================================
// SCROLL ANIMATION - OBSERVE ELEMENTS
// ============================================

function observeAnimations() {
    const options = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
                observer.unobserve(entry.target);
            }
        });
    }, options);

    // Observe feature cards and support cards
    document.querySelectorAll('.feature-card, .support-card, .info-card').forEach(card => {
        observer.observe(card);
    });
}

// ============================================
// BUTTON INTERACTIONS
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.btn');

    buttons.forEach(button => {
        // Login buttons now redirect to login.php (handled via anchor tags)

        // Apply Now button
        if (button.textContent.includes('Apply Now')) {
            button.addEventListener('click', () => {
                showNotification('Redirecting to application form...');
                // In a real application, this would navigate to the form
                // window.location.href = '/apply';
            });
        }

        // View Bus Routes button
        if (button.textContent.includes('View Bus Routes')) {
            button.addEventListener('click', () => {
                document.querySelector('#routes').scrollIntoView({ behavior: 'smooth' });
            });
        }

        // Raise a Ticket button
        if (button.textContent.includes('Raise a Ticket')) {
            button.addEventListener('click', () => {
                showNotification('Support ticket form will open...');
            });
        }
    });
});

// ============================================
// LOGIN MODAL
// ============================================

function createLoginModal() {
    const modal = document.createElement('div');
    modal.className = 'login-modal';
    modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Login to Your Account</h2>
            <form class="login-form" id="loginForm">
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
                    <input type="text" class="form-control" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <div class="form-footer">
                    <label><input type="checkbox"> Remember me</label>
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
                <p class="signup-text">Don't have an account? <a href="#">Sign up here</a></p>
            </form>
        </div>
    `;

    // Close modal
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');

    closeBtn.addEventListener('click', () => closeModal(modal));
    overlay.addEventListener('click', () => closeModal(modal));

    // Form submission
    modal.querySelector('#loginForm').addEventListener('submit', (e) => {
        e.preventDefault();
        showNotification('Login successful! Redirecting...');
        setTimeout(() => closeModal(modal), 1500);
    });

    return modal;
}

function closeModal(modal) {
    modal.classList.remove('active');
    setTimeout(() => modal.remove(), 300);
}

// ============================================
// NOTIFICATIONS
// ============================================

function showNotification(message, type = 'success', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 100);

    // Remove notification
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// ============================================
// SMOOTH SCROLL POLYFILL
// ============================================

if (!('scrollBehavior' in document.documentElement.style)) {
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;

        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (!target) return;

        target.scrollIntoView({ behavior: 'smooth' });
    });
}

// ============================================
// TABLE SORTING
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    const tables = document.querySelectorAll('.routes-table');

    tables.forEach(table => {
        const headers = table.querySelectorAll('th');

        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                sortTable(table, index);
            });
        });
    });
});

function sortTable(table, columnIndex) {
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const isAsc = table.getAttribute('data-sort-asc-' + columnIndex) !== 'true';

    rows.sort((a, b) => {
        const aValue = a.querySelectorAll('td')[columnIndex].textContent.trim();
        const bValue = b.querySelectorAll('td')[columnIndex].textContent.trim();

        // Try numeric comparison first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAsc ? aNum - bNum : bNum - aNum;
        }

        // Fall back to string comparison
        return isAsc ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
    });

    // Re-append sorted rows
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));

    // Update sort state
    table.setAttribute('data-sort-asc-' + columnIndex, isAsc);
}

// ============================================
// LAZY LOADING IMAGES
// ============================================

if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ============================================
// FORM VALIDATION
// ============================================

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Add real-time validation to forms
document.addEventListener('DOMContentLoaded', () => {
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const phoneInputs = document.querySelectorAll('input[type="tel"]');

    emailInputs.forEach(input => {
        input.addEventListener('blur', () => {
            if (input.value && !validateEmail(input.value)) {
                input.classList.add('error');
                showFieldError(input, 'Please enter a valid email address');
            } else {
                input.classList.remove('error');
                removeFieldError(input);
            }
        });
    });

    phoneInputs.forEach(input => {
        input.addEventListener('blur', () => {
            if (input.value && !validatePhone(input.value)) {
                input.classList.add('error');
                showFieldError(input, 'Please enter a valid phone number');
            } else {
                input.classList.remove('error');
                removeFieldError(input);
            }
        });
    });
});

function showFieldError(input, message) {
    let errorElement = input.parentElement.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('span');
        errorElement.className = 'field-error';
        input.parentElement.appendChild(errorElement);
    }
    errorElement.textContent = message;
}

function removeFieldError(input) {
    const errorElement = input.parentElement.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// ============================================
// KEYBOARD NAVIGATION
// ============================================

// Trap focus in modals
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.querySelector('.login-modal.active');
        if (modal) {
            closeModal(modal);
        }
    }
});

// ============================================
// FEATURE COUNTER ANIMATION
// ============================================

function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    const counter = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = target;
            clearInterval(counter);
        } else {
            element.textContent = Math.floor(start);
        }
    }, 16);
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Debounce function for scroll events
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function for performance
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.bottom >= 0
    );
}

// ============================================
// PAGE LOAD PERFORMANCE
// ============================================

// Log page performance metrics
window.addEventListener('load', () => {
    if (window.performance && window.performance.timing) {
        const perf = window.performance.timing;
        const pageLoadTime = perf.loadEventEnd - perf.navigationStart;
        console.log('Page load time: ' + pageLoadTime + 'ms');
    }
});

// ============================================
// SERVICE WORKER REGISTRATION (Optional)
// ============================================

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Uncomment to enable service worker
        // navigator.serviceWorker.register('/sw.js').then(reg => {
        //     console.log('Service Worker registered');
        // }).catch(err => {
        //     console.log('Service Worker registration failed');
        // });
    });
}

// ============================================
// EXPORT FUNCTIONS (if using modules)
// ============================================

window.busPassApp = {
    showNotification,
    animateCounter,
    validateEmail,
    validatePhone,
    debounce,
    throttle,
    isInViewport
};

console.log('Bus Pass Management System initialized successfully!');
