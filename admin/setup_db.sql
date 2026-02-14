-- ============================================
-- Valorol Admin Panel - Database Setup
-- ============================================
-- Run this SQL in phpMyAdmin on your Hostinger panel.
-- It creates the admin_users table and a default admin account.
--
-- Default login:
--   Username: admin
--   Password: admin123
--
-- IMPORTANT: Change the password immediately after first login
--            using the "Change Password" feature in the dashboard.
-- ============================================

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin user (password: admin123)
-- The hash below is bcrypt for 'admin123'
INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$10$8KzQxL3G5b0VJfN7wZpYXOYC7KxLm9Q6kVJb0R8Zy8HdW.CYT6v5e')
ON DUPLICATE KEY UPDATE username = username;
