-- ============================================================
--  BUS PASS MANAGEMENT SYSTEM — Authentication Module
--  MySQL schema (import via phpMyAdmin: http://localhost/phpmyadmin)
--  Run this ONCE before first use.
-- ============================================================

CREATE DATABASE IF NOT EXISTS buspass_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE buspass_db;

-- ------------------------------------------------------------
--  USERS  (students, transport officers, administrators)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(80)  NOT NULL,
    username    VARCHAR(40)  NOT NULL UNIQUE,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,                 -- bcrypt hash (password_hash)
    role        ENUM('student','officer','admin') NOT NULL DEFAULT 'student',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  PASSWORD RESET TOKENS  (single-use, expiring)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(120) NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,                 -- hashed token (never store raw)
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resets_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  LOGIN ATTEMPTS  (simple brute-force throttling)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier  VARCHAR(190) NOT NULL,                 -- username+ip
    attempts    INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME    NULL,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_identifier (identifier)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  SEED ACCOUNTS  (change these passwords immediately in prod)
--  password for all three:  Admin@123
-- ------------------------------------------------------------
INSERT INTO users (full_name, username, email, password, role) VALUES
('System Administrator','admin','admin@buspass.local',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdQMLsfTuQy0dTVa6','admin'),
('Transport Officer','officer','officer@buspass.local',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdQMLsfTuQy0dTVa6','officer'),
('Demo Student','student','student@buspass.local',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdQMLsfTuQy0dTVa6','student');
