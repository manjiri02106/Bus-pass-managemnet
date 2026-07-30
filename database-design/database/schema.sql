-- Bus Pass Management System MySQL Schema
-- Created for local setup and deployment

CREATE DATABASE IF NOT EXISTS `bus_pass_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bus_pass_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table (Pass Types, e.g., Student, Senior, General)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) UNIQUE NOT NULL,
    `discount_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Routes Table
CREATE TABLE IF NOT EXISTS `routes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `route_code` VARCHAR(50) UNIQUE NOT NULL,
    `source` VARCHAR(100) NOT NULL,
    `destination` VARCHAR(100) NOT NULL,
    `standard_price` DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Passes Table
CREATE TABLE IF NOT EXISTS `passes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pass_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `route_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `qr_code_data` VARCHAR(255) UNIQUE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pass_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `transaction_id` VARCHAR(100) UNIQUE NOT NULL,
    `status` ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pass_id`) REFERENCES `passes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- Insert Seed Data
-- ========================================================

-- Insert Seed Users
-- Admin Pass: admin123
-- User Pass: user123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `profile_pic`) VALUES
(1, 'System Admin', 'admin@buspass.com', '$2y$10$842pOen3ku5qOoO3kvvWIuimPRU7Cr6IUfQV1PAJTZs3jvzhTIasC', 'admin', NULL),
(2, 'Jane Doe', 'jane@gmail.com', '$2y$10$F44Ws.47f4U0hmbu.jb5wu4LnykyZAVo9kGvPNBlemqZApAIuHcLa', 'user', NULL),
(3, 'John Smith', 'john@gmail.com', '$2y$10$F44Ws.47f4U0hmbu.jb5wu4LnykyZAVo9kGvPNBlemqZApAIuHcLa', 'user', NULL);

-- Insert Seed Categories
INSERT INTO `categories` (`id`, `name`, `discount_percentage`, `description`) VALUES
(1, 'General Citizen', 0.00, 'Standard bus pass with normal pricing.'),
(2, 'Student Special', 50.00, '50% discount for school and college students with valid student ID.'),
(3, 'Senior Citizen', 30.00, '30% discount for citizens aged 60 and above.'),
(4, 'Physically Challenged', 60.00, '60% discount for specially-abled passengers with certificate.');

-- Insert Seed Routes
INSERT INTO `routes` (`id`, `route_code`, `source`, `destination`, `standard_price`) VALUES
(1, 'RT-101', 'Downtown Hub', 'Airport Terminal 2', 150.00),
(2, 'RT-102', 'University Campus', 'Westside Station', 80.00),
(3, 'RT-103', 'Metro Center', 'IT Park Phase 1', 120.00),
(4, 'RT-104', 'Central Market', 'South Suburb Gate', 60.00),
(5, 'RT-105', 'East Harbor', 'Central Market', 90.00);

-- Insert Seed Passes
INSERT INTO `passes` (`id`, `pass_number`, `user_id`, `category_id`, `route_id`, `start_date`, `end_date`, `price`, `status`, `qr_code_data`) VALUES
(1, 'BP-20260721-0001', 2, 2, 2, '2026-07-01', '2026-08-01', 40.00, 'approved', 'VAL-BP-20260721-0001'),
(2, 'BP-20260721-0002', 3, 3, 1, '2026-07-20', '2026-10-20', 315.00, 'pending', 'VAL-BP-20260721-0002');

-- Insert Seed Payments
INSERT INTO `payments` (`id`, `pass_id`, `amount`, `transaction_id`, `status`, `payment_date`) VALUES
(1, 1, 40.00, 'TXN987654321', 'completed', '2026-07-01 10:00:00'),
(2, 2, 315.00, 'TXN123456789', 'pending', '2026-07-20 15:30:00');
