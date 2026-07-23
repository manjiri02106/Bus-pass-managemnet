-- ============================================================
-- Bus Pass Management System - Database Schema
-- Compatible with MySQL 8.x
-- ============================================================

CREATE DATABASE IF NOT EXISTS bus_pass_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bus_pass_system;

-- ============================================================
-- TABLE: admin_users
-- ============================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,  -- bcrypt hash
    role        ENUM('super_admin','admin','verifier') NOT NULL DEFAULT 'verifier',
    is_active   TINYINT(1)    NOT NULL DEFAULT 1,
    last_login  DATETIME      NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bus_routes
-- ============================================================
CREATE TABLE IF NOT EXISTS bus_routes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    route_number    VARCHAR(20)   NOT NULL UNIQUE,
    route_name      VARCHAR(200)  NOT NULL,
    source          VARCHAR(100)  NOT NULL,
    destination     VARCHAR(100)  NOT NULL,
    stops           TEXT          NOT NULL,   -- JSON array of stop names
    distance_km     DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
    fare_monthly    DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    fare_quarterly  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    fare_annual     DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: applicants
-- ============================================================
CREATE TABLE IF NOT EXISTS applicants (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    phone           VARCHAR(15)   NOT NULL,
    dob             DATE          NOT NULL,
    gender          ENUM('male','female','other') NOT NULL,
    address         TEXT          NOT NULL,
    city            VARCHAR(100)  NOT NULL,
    pincode         VARCHAR(10)   NOT NULL,
    category        ENUM('general','student','senior_citizen','differently_abled','employee') NOT NULL DEFAULT 'general',
    id_proof_type   VARCHAR(50)   NOT NULL,
    id_proof_number VARCHAR(50)   NOT NULL,
    password        VARCHAR(255)  NOT NULL,
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: applications
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_number  VARCHAR(20)   NOT NULL UNIQUE,
    applicant_id        INT UNSIGNED  NOT NULL,
    route_id            INT UNSIGNED  NOT NULL,
    boarding_stop       VARCHAR(100)  NOT NULL,
    alighting_stop      VARCHAR(100)  NOT NULL,
    pass_type           ENUM('monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
    pass_start_date     DATE          NULL,
    pass_end_date       DATE          NULL,
    amount_paid         DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
    payment_ref         VARCHAR(50)   NULL,
    status              ENUM(
                            'pending',
                            'under_review',
                            'docs_verified',
                            'route_validated',
                            'approved',
                            'rejected',
                            'correction_requested'
                        ) NOT NULL DEFAULT 'pending',
    pass_number         VARCHAR(30)   NULL UNIQUE,
    admin_remarks       TEXT          NULL,
    rejection_reason    TEXT          NULL,
    reviewed_by         INT UNSIGNED  NULL,
    reviewed_at         DATETIME      NULL,
    approved_at         DATETIME      NULL,
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id)  ON DELETE CASCADE,
    FOREIGN KEY (route_id)     REFERENCES bus_routes(id)  ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by)  REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status        (status),
    INDEX idx_applicant_id  (applicant_id),
    INDEX idx_route_id      (route_id),
    INDEX idx_created_at    (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: documents
-- ============================================================
CREATE TABLE IF NOT EXISTS documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED  NOT NULL,
    doc_type        ENUM(
                        'applicant_photo',
                        'id_proof',
                        'address_proof',
                        'fee_receipt',
                        'category_certificate',
                        'other'
                    ) NOT NULL,
    doc_label       VARCHAR(100)  NOT NULL,
    file_name       VARCHAR(255)  NOT NULL,
    file_path       VARCHAR(500)  NOT NULL,
    file_size       INT UNSIGNED  NOT NULL DEFAULT 0,
    mime_type       VARCHAR(100)  NOT NULL,
    status          ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    admin_notes     TEXT          NULL,
    verified_by     INT UNSIGNED  NULL,
    verified_at     DATETIME      NULL,
    uploaded_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by)    REFERENCES admin_users(id)  ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_doc_type       (doc_type),
    INDEX idx_status         (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: correction_requests
-- ============================================================
CREATE TABLE IF NOT EXISTS correction_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED  NOT NULL,
    requested_by    INT UNSIGNED  NOT NULL,
    fields_to_fix   TEXT          NOT NULL,  -- JSON array of field names
    message         TEXT          NOT NULL,
    status          ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    resolved_at     DATETIME      NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by)   REFERENCES admin_users(id)  ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_status         (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: application_logs (Audit Trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS application_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED  NOT NULL,
    action          VARCHAR(100)  NOT NULL,
    old_status      VARCHAR(50)   NULL,
    new_status      VARCHAR(50)   NULL,
    performed_by    INT UNSIGNED  NULL,
    notes           TEXT          NULL,
    ip_address      VARCHAR(45)   NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by)   REFERENCES admin_users(id)  ON DELETE SET NULL,
    INDEX idx_application_id (application_id),
    INDEX idx_created_at     (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Admin Users
-- Passwords are bcrypt hashes of "Admin@123"
-- ============================================================
INSERT INTO admin_users (name, email, password, role) VALUES
('Super Admin',    'superadmin@buspass.gov',  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie', 'super_admin'),
('Ravi Kumar',     'ravi.kumar@buspass.gov',  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie', 'admin'),
('Priya Sharma',   'priya.sharma@buspass.gov','$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie', 'verifier');

-- ============================================================
-- SEED DATA: Bus Routes
-- ============================================================
INSERT INTO bus_routes (route_number, route_name, source, destination, stops, distance_km, fare_monthly, fare_quarterly, fare_annual) VALUES
('RT-001', 'City Centre to Airport',        'City Centre',    'Airport',        '["City Centre","MG Road","Silk Board","Electronic City","Airport"]',            28.5, 850.00,  2400.00,  9000.00),
('RT-002', 'Old Town to New Bus Stand',     'Old Town',       'New Bus Stand',  '["Old Town","Market Square","Town Hall","Central Park","New Bus Stand"]',       12.0, 450.00,  1250.00,  4500.00),
('RT-003', 'University Road to IT Hub',     'University',     'IT Hub',         '["University","Science Block","Main Gate","Ring Road","IT Hub"]',               18.3, 600.00,  1700.00,  6200.00),
('RT-004', 'North Zone to South Terminal',  'North Zone',     'South Terminal', '["North Zone","Railway Station","City Centre","Hospital Junction","South Terminal"]', 22.0, 700.00, 1950.00, 7200.00),
('RT-005', 'West Colony to East Mall',      'West Colony',    'East Mall',      '["West Colony","Residential Area","Main Market","School Road","East Mall"]',    15.7, 500.00,  1400.00,  5100.00),
('RT-006', 'Industrial Zone Express',       'Industrial Zone','City Centre',    '["Industrial Zone","Factory Gate","Bypass Road","Ring Road","City Centre"]',    30.1, 900.00,  2550.00,  9500.00),
('RT-007', 'Suburban Local — North',        'Sector 1',       'Sector 15',      '["Sector 1","Sector 3","Sector 5","Sector 9","Sector 12","Sector 15"]',         10.5, 350.00,  980.00,   3600.00);

-- ============================================================
-- SEED DATA: Applicants
-- ============================================================
INSERT INTO applicants (name, email, phone, dob, gender, address, city, pincode, category, id_proof_type, id_proof_number, password) VALUES
('Arjun Mehta',       'arjun.mehta@email.com',       '9876543210', '1998-05-12', 'male',   '45, Rose Garden, Sector 4',    'Bangalore', '560001', 'student',           'Aadhaar Card', 'XXXX-XXXX-1234', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Sneha Patel',        'sneha.patel@email.com',        '9845123456', '1995-08-22', 'female', '12, Lakeview Apartments',      'Bangalore', '560002', 'general',           'Voter ID',     'VID-ABC-98765',  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Rajesh Nair',        'rajesh.nair@email.com',        '9123456789', '1952-02-14', 'male',   '88, Old Quarters, MG Road',    'Bangalore', '560003', 'senior_citizen',    'PAN Card',     'ABCDE1234F',     '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Divya Krishnan',     'divya.krishnan@email.com',     '9988776655', '2001-11-30', 'female', '7, Green Valley, IT Park Road','Bangalore', '560037', 'employee',          'Aadhaar Card', 'XXXX-XXXX-5678', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Mohammed Faiz',      'faiz.khan@email.com',          '9012345678', '1990-07-04', 'male',   '33, Hilltop Complex, Sector 7','Bangalore', '560068', 'differently_abled', 'Aadhaar Card', 'XXXX-XXXX-9012', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Lakshmi Venkat',     'lakshmi.v@email.com',          '9871234560', '1985-03-19', 'female', '56, Sunrise Towers, Anna Nagar','Bangalore','560040', 'general',           'Passport',     'P1234567',       '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Kiran Desai',        'kiran.desai@email.com',        '9765432109', '2003-09-15', 'male',   '21, Student Housing, Univ Road','Bangalore','560045', 'student',           'Aadhaar Card', 'XXXX-XXXX-3456', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie'),
('Anita Joshi',        'anita.joshi@email.com',        '9654321098', '1967-12-01', 'female', '9, Peaceful Colony, West Zone','Bangalore', '560022', 'senior_citizen',    'Voter ID',     'VID-XYZ-11223', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiQFHQ2YOSfxIQbR3xKZtv6eDpie');

-- ============================================================
-- SEED DATA: Applications
-- ============================================================
INSERT INTO applications (application_number, applicant_id, route_id, boarding_stop, alighting_stop, pass_type, amount_paid, payment_ref, status, admin_remarks) VALUES
('APP-2024-0001', 1, 3, 'University',     'IT Hub',         'monthly',    600.00,  'PAY-REF-001', 'pending',            NULL),
('APP-2024-0002', 2, 1, 'City Centre',    'Electronic City','quarterly',  2400.00, 'PAY-REF-002', 'under_review',       'Documents submitted, under review'),
('APP-2024-0003', 3, 4, 'Railway Station','City Centre',    'annual',     7200.00, 'PAY-REF-003', 'docs_verified',      'All documents verified successfully'),
('APP-2024-0004', 4, 3, 'Ring Road',      'IT Hub',         'monthly',    600.00,  'PAY-REF-004', 'route_validated',    'Route and docs verified'),
('APP-2024-0005', 5, 2, 'Old Town',       'Market Square',  'quarterly',  1250.00, 'PAY-REF-005', 'approved',           'Verified and approved'),
('APP-2024-0006', 6, 6, 'Industrial Zone','Ring Road',      'monthly',    900.00,  'PAY-REF-006', 'rejected',           NULL),
('APP-2024-0007', 7, 7, 'Sector 1',       'Sector 9',       'annual',     3600.00, 'PAY-REF-007', 'correction_requested', 'Category certificate missing'),
('APP-2024-0008', 8, 5, 'West Colony',    'Main Market',    'quarterly',  1400.00, 'PAY-REF-008', 'pending',            NULL);

-- Update approved application
UPDATE applications SET
    pass_number  = 'PASS-BLR-2024-005',
    approved_at  = '2024-07-15 14:30:00',
    reviewed_by  = 2,
    reviewed_at  = '2024-07-15 14:30:00',
    pass_start_date = '2024-07-15',
    pass_end_date   = '2024-10-14'
WHERE id = 5;

-- Update rejected application
UPDATE applications SET
    rejection_reason = 'Address proof document is not legible. The uploaded file is blurred and unacceptable.',
    reviewed_by = 1,
    reviewed_at = '2024-07-16 10:00:00'
WHERE id = 6;

-- ============================================================
-- SEED DATA: Documents
-- ============================================================
INSERT INTO documents (application_id, doc_type, doc_label, file_name, file_path, file_size, mime_type, status, admin_notes, verified_by, verified_at) VALUES
-- APP-2024-0001 (pending)
(1, 'applicant_photo',    'Applicant Photograph',    'photo_001.jpg',       'uploads/documents/photo_001.jpg',       102400,  'image/jpeg',       'pending', NULL, NULL, NULL),
(1, 'id_proof',           'Aadhaar Card',             'aadhaar_001.pdf',     'uploads/documents/aadhaar_001.pdf',     204800,  'application/pdf',  'pending', NULL, NULL, NULL),
(1, 'address_proof',      'Utility Bill',             'address_001.jpg',     'uploads/documents/address_001.jpg',     153600,  'image/jpeg',       'pending', NULL, NULL, NULL),
(1, 'fee_receipt',        'Payment Receipt',          'receipt_001.pdf',     'uploads/documents/receipt_001.pdf',     98304,   'application/pdf',  'pending', NULL, NULL, NULL),
(1, 'category_certificate','College ID Card',         'college_id_001.jpg',  'uploads/documents/college_id_001.jpg',  87040,   'image/jpeg',       'pending', NULL, NULL, NULL),

-- APP-2024-0002 (under_review)
(2, 'applicant_photo',    'Applicant Photograph',    'photo_002.jpg',       'uploads/documents/photo_002.jpg',       115200,  'image/jpeg',       'verified', 'Clear photograph', 2, '2024-07-10 09:00:00'),
(2, 'id_proof',           'Voter ID Card',            'voter_002.jpg',       'uploads/documents/voter_002.jpg',       204800,  'image/jpeg',       'verified', 'Valid Voter ID',    2, '2024-07-10 09:15:00'),
(2, 'address_proof',      'Bank Statement',           'bank_stmt_002.pdf',   'uploads/documents/bank_stmt_002.pdf',   307200,  'application/pdf',  'pending',  NULL,              NULL, NULL),
(2, 'fee_receipt',        'Payment Receipt',          'receipt_002.pdf',     'uploads/documents/receipt_002.pdf',     98304,   'application/pdf',  'pending',  NULL,              NULL, NULL),

-- APP-2024-0003 (docs_verified)
(3, 'applicant_photo',    'Applicant Photograph',    'photo_003.jpg',       'uploads/documents/photo_003.jpg',       120832,  'image/jpeg',       'verified', 'Clear',            2, '2024-07-11 10:00:00'),
(3, 'id_proof',           'PAN Card',                 'pan_003.jpg',         'uploads/documents/pan_003.jpg',         87040,   'image/jpeg',       'verified', 'Valid PAN',        2, '2024-07-11 10:10:00'),
(3, 'address_proof',      'Aadhaar Card',             'aadhaar_addr_003.pdf','uploads/documents/aadhaar_addr_003.pdf',204800,  'application/pdf',  'verified', 'Valid address',    2, '2024-07-11 10:20:00'),
(3, 'fee_receipt',        'Payment Receipt',          'receipt_003.pdf',     'uploads/documents/receipt_003.pdf',     98304,   'application/pdf',  'verified', 'Verified',         2, '2024-07-11 10:25:00'),
(3, 'category_certificate','Senior Citizen Certificate','sr_cert_003.pdf',  'uploads/documents/sr_cert_003.pdf',     153600,  'application/pdf',  'verified', 'Authentic cert',   2, '2024-07-11 10:30:00'),

-- APP-2024-0004 (route_validated) — all docs verified
(4, 'applicant_photo',    'Applicant Photograph',    'photo_004.jpg',       'uploads/documents/photo_004.jpg',       102400,  'image/jpeg',       'verified', 'OK', 2, '2024-07-12 11:00:00'),
(4, 'id_proof',           'Aadhaar Card',             'aadhaar_004.pdf',     'uploads/documents/aadhaar_004.pdf',     204800,  'application/pdf',  'verified', 'OK', 2, '2024-07-12 11:05:00'),
(4, 'address_proof',      'Electricity Bill',         'bill_004.jpg',        'uploads/documents/bill_004.jpg',        153600,  'image/jpeg',       'verified', 'OK', 2, '2024-07-12 11:10:00'),
(4, 'fee_receipt',        'Payment Receipt',          'receipt_004.pdf',     'uploads/documents/receipt_004.pdf',     98304,   'application/pdf',  'verified', 'OK', 2, '2024-07-12 11:15:00'),
(4, 'category_certificate','Employee ID',             'emp_id_004.jpg',      'uploads/documents/emp_id_004.jpg',      87040,   'image/jpeg',       'verified', 'OK', 2, '2024-07-12 11:20:00'),

-- APP-2024-0005 (approved)
(5, 'applicant_photo',    'Applicant Photograph',    'photo_005.jpg',       'uploads/documents/photo_005.jpg',       99328,   'image/jpeg',       'verified', 'OK', 2, '2024-07-14 09:00:00'),
(5, 'id_proof',           'Aadhaar Card',             'aadhaar_005.pdf',     'uploads/documents/aadhaar_005.pdf',     204800,  'application/pdf',  'verified', 'OK', 2, '2024-07-14 09:10:00'),
(5, 'address_proof',      'Rental Agreement',         'rental_005.pdf',      'uploads/documents/rental_005.pdf',      307200,  'application/pdf',  'verified', 'OK', 2, '2024-07-14 09:20:00'),
(5, 'fee_receipt',        'Payment Receipt',          'receipt_005.pdf',     'uploads/documents/receipt_005.pdf',     98304,   'application/pdf',  'verified', 'OK', 2, '2024-07-14 09:30:00'),
(5, 'category_certificate','Disability Certificate', 'dis_cert_005.pdf',    'uploads/documents/dis_cert_005.pdf',    204800,  'application/pdf',  'verified', 'Govt certified', 2, '2024-07-14 09:40:00'),

-- APP-2024-0006 (rejected)
(6, 'applicant_photo',    'Applicant Photograph',    'photo_006.jpg',       'uploads/documents/photo_006.jpg',       102400,  'image/jpeg',       'verified', 'OK',              2, '2024-07-15 10:00:00'),
(6, 'id_proof',           'Passport',                 'passport_006.jpg',    'uploads/documents/passport_006.jpg',    204800,  'image/jpeg',       'verified', 'OK',              2, '2024-07-15 10:10:00'),
(6, 'address_proof',      'Electricity Bill',         'bill_006.jpg',        'uploads/documents/bill_006.jpg',        30720,   'image/jpeg',       'rejected', 'Image is blurred and unreadable. Please resubmit.', 1, '2024-07-16 09:50:00'),
(6, 'fee_receipt',        'Payment Receipt',          'receipt_006.pdf',     'uploads/documents/receipt_006.pdf',     98304,   'application/pdf',  'verified', 'OK',              2, '2024-07-15 10:20:00'),

-- APP-2024-0007 (correction_requested)
(7, 'applicant_photo',    'Applicant Photograph',    'photo_007.jpg',       'uploads/documents/photo_007.jpg',       102400,  'image/jpeg',       'verified', 'OK', 3, '2024-07-17 11:00:00'),
(7, 'id_proof',           'Aadhaar Card',             'aadhaar_007.pdf',     'uploads/documents/aadhaar_007.pdf',     204800,  'application/pdf',  'verified', 'OK', 3, '2024-07-17 11:10:00'),
(7, 'address_proof',      'Utility Bill',             'bill_007.jpg',        'uploads/documents/bill_007.jpg',        153600,  'image/jpeg',       'verified', 'OK', 3, '2024-07-17 11:20:00'),
(7, 'fee_receipt',        'Payment Receipt',          'receipt_007.pdf',     'uploads/documents/receipt_007.pdf',     98304,   'application/pdf',  'verified', 'OK', 3, '2024-07-17 11:25:00'),
(7, 'category_certificate','Student ID',              'student_007.jpg',     'uploads/documents/student_007.jpg',     51200,   'image/jpeg',       'rejected', 'Student ID appears expired. Please submit a valid current semester ID.', 1, '2024-07-17 11:35:00'),

-- APP-2024-0008 (pending)
(8, 'applicant_photo',    'Applicant Photograph',    'photo_008.jpg',       'uploads/documents/photo_008.jpg',       102400,  'image/jpeg',       'pending', NULL, NULL, NULL),
(8, 'id_proof',           'Voter ID',                 'voter_008.jpg',       'uploads/documents/voter_008.jpg',       87040,   'image/jpeg',       'pending', NULL, NULL, NULL),
(8, 'address_proof',      'Gas Bill',                 'gas_008.jpg',         'uploads/documents/gas_008.jpg',         71680,   'image/jpeg',       'pending', NULL, NULL, NULL),
(8, 'fee_receipt',        'Payment Receipt',          'receipt_008.pdf',     'uploads/documents/receipt_008.pdf',     98304,   'application/pdf',  'pending', NULL, NULL, NULL);

-- ============================================================
-- SEED DATA: Correction Requests
-- ============================================================
INSERT INTO correction_requests (application_id, requested_by, fields_to_fix, message, status) VALUES
(7, 1,
 '["category_certificate","boarding_stop"]',
 'Dear Applicant, your Student ID card appears to be expired. Please upload a valid ID for the current academic year (2024-25). Additionally, please verify your boarding stop — "Sector 1" is not listed as a stop on Route RT-007. Please correct this before resubmission.',
 'open');

-- ============================================================
-- SEED DATA: Application Logs
-- ============================================================
INSERT INTO application_logs (application_id, action, old_status, new_status, performed_by, notes, ip_address) VALUES
(1, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.10'),
(2, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.11'),
(2, 'Review Started',           'pending',         'under_review',         2,    'Admin started review process',                           '10.0.0.1'),
(3, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.12'),
(3, 'Review Started',           'pending',         'under_review',         2,    'Admin started review process',                           '10.0.0.1'),
(3, 'Documents Verified',       'under_review',    'docs_verified',        2,    'All 5 documents verified successfully',                  '10.0.0.1'),
(4, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.13'),
(4, 'Review Started',           'pending',         'under_review',         2,    'Admin started review process',                           '10.0.0.2'),
(4, 'Documents Verified',       'under_review',    'docs_verified',        2,    'All documents verified',                                 '10.0.0.2'),
(4, 'Route Validated',          'docs_verified',   'route_validated',      2,    'Route RT-003 validated. Boarding and alighting stops confirmed.', '10.0.0.2'),
(5, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.14'),
(5, 'Review Started',           'pending',         'under_review',         2,    'Admin started review process',                           '10.0.0.1'),
(5, 'Documents Verified',       'under_review',    'docs_verified',        2,    'All documents verified',                                 '10.0.0.1'),
(5, 'Route Validated',          'docs_verified',   'route_validated',      2,    'Route RT-002 validated',                                 '10.0.0.1'),
(5, 'Application Approved',     'route_validated', 'approved',             2,    'Pass No: PASS-BLR-2024-005 issued',                      '10.0.0.1'),
(6, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.15'),
(6, 'Review Started',           'pending',         'under_review',         1,    'Admin started review process',                           '10.0.0.3'),
(6, 'Document Rejected',        'under_review',    'under_review',         1,    'Address proof rejected: image unreadable',               '10.0.0.3'),
(6, 'Application Rejected',     'under_review',    'rejected',             1,    'Rejected: address proof not acceptable',                 '10.0.0.3'),
(7, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.16'),
(7, 'Review Started',           'pending',         'under_review',         3,    'Verifier started review process',                        '10.0.0.4'),
(7, 'Correction Requested',     'under_review',    'correction_requested', 1,    'Category certificate invalid + boarding stop mismatch',  '10.0.0.3'),
(8, 'Application Submitted',    NULL,              'pending',              NULL, 'New application submitted by applicant',                 '192.168.1.17');

-- ============================================================
-- STORED PROCEDURE: Generate pass number
-- ============================================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_generate_pass_number(
    IN  p_application_id    INT UNSIGNED,
    IN  p_route_number      VARCHAR(20),
    OUT p_pass_number       VARCHAR(30)
)
BEGIN
    DECLARE v_year  CHAR(4);
    DECLARE v_seq   INT;

    SET v_year = YEAR(CURDATE());
    SELECT COUNT(*) + 1 INTO v_seq
    FROM applications
    WHERE pass_number IS NOT NULL
      AND YEAR(approved_at) = v_year;

    SET p_pass_number = CONCAT('PASS-', p_route_number, '-', v_year, '-', LPAD(v_seq, 4, '0'));
END //

DELIMITER ;
