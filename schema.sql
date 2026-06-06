-- Create Database
CREATE DATABASE IF NOT EXISTS user_management_system;
USE user_management_system;

-- Drop Tables if they exist (for easy re-import)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Create Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Activity Logs Table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Database with default Admin and User
-- Admin credentials: admin@smartums.com / admin123
-- User credentials: user@smartums.com / user123
INSERT INTO users (full_name, email, mobile, password, role, profile_image) VALUES
('System Administrator', 'admin@smartums.com', '+1234567890', '$2y$10$VyXGwndxHq9u8Wcfy4Hb2u7IMtZ0/M/lrv2vG3hFYyxHYl4XHxXFW', 'admin', NULL),
('Regular User', 'user@smartums.com', '+1987654321', '$2y$10$kNo937GBoo.gJLQqFkN8xu04Np4R.cAWFVnag/PzklSY15Liy/EbG', 'user', NULL);

-- Seed some initial activity logs
INSERT INTO activity_logs (user_id, action) VALUES
(1, 'Database initialized and admin account seeded.'),
(2, 'Regular user account seeded.');
