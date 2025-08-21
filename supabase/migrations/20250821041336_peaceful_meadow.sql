-- L.P.S.T Bookings System - Complete Database Setup
-- Compatible with all hosting providers including Hostinger
-- Run this SQL file in your hosting panel's phpMyAdmin or database manager

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS lpst_bookings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE lpst_bookings;

-- Enhanced users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('OWNER', 'ADMIN') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced resources table with owner customization
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('room', 'hall') NOT NULL,
    identifier VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    custom_name VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_resource (type, identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced bookings table with guest information
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_mobile VARCHAR(15) NOT NULL,
    client_aadhar VARCHAR(20) NULL,
    client_license VARCHAR(20) NULL,
    receipt_number VARCHAR(100) NULL,
    payment_mode ENUM('ONLINE', 'OFFLINE') DEFAULT 'OFFLINE',
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    actual_check_in DATETIME NULL,
    actual_check_out DATETIME NULL,
    status ENUM('BOOKED', 'PENDING', 'COMPLETED', 'ADVANCED_BOOKED', 'PAID') DEFAULT 'BOOKED',
    booking_type ENUM('regular', 'advanced') DEFAULT 'regular',
    advance_date DATE NULL,
    advance_payment_mode ENUM('ONLINE', 'OFFLINE') NULL,
    admin_id INT NOT NULL,
    is_paid BOOLEAN DEFAULT FALSE,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_notes TEXT,
    duration_minutes INT DEFAULT 0,
    sms_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NULL,
    resource_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'UPI',
    payment_status ENUM('PENDING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    upi_transaction_id VARCHAR(100) NULL,
    payment_notes TEXT,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced settings table for SMS and Email configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS logs table
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    sms_type ENUM('BOOKING', 'CHECKOUT', 'CANCELLATION', 'ADVANCE') NOT NULL,
    status ENUM('SENT', 'FAILED', 'PENDING') DEFAULT 'PENDING',
    response_data TEXT,
    admin_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    email_type ENUM('EXPORT', 'REPORT', 'NOTIFICATION') NOT NULL,
    status ENUM('SENT', 'FAILED', 'PENDING') DEFAULT 'PENDING',
    response_data TEXT,
    admin_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking cancellations table for tracking
CREATE TABLE IF NOT EXISTS booking_cancellations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    resource_id INT NOT NULL,
    cancelled_by INT NOT NULL,
    cancellation_reason TEXT,
    original_client_name VARCHAR(255),
    original_client_mobile VARCHAR(15),
    original_advance_date DATE NULL,
    duration_at_cancellation INT DEFAULT 0,
    cancelled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default users with secure passwords (password: admin123)
INSERT INTO users (username, password, role) VALUES
('owner', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'OWNER'),
('admin1', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN'),
('admin2', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN'),
('admin3', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN'),
('admin4', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN'),
('admin5', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN'),
('admin6', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewdBxGkgaHqz3nO6', 'ADMIN')
ON DUPLICATE KEY UPDATE username=username;

-- Insert resources (26 rooms + 2 halls)
INSERT INTO resources (type, identifier, display_name) VALUES
('room', '1', 'ROOM NO 1'), ('room', '2', 'ROOM NO 2'), ('room', '3', 'ROOM NO 3'),
('room', '4', 'ROOM NO 4'), ('room', '5', 'ROOM NO 5'), ('room', '6', 'ROOM NO 6'),
('room', '7', 'ROOM NO 7'), ('room', '8', 'ROOM NO 8'), ('room', '9', 'ROOM NO 9'),
('room', '10', 'ROOM NO 10'), ('room', '11', 'ROOM NO 11'), ('room', '12', 'ROOM NO 12'),
('room', '13', 'ROOM NO 13'), ('room', '14', 'ROOM NO 14'), ('room', '15', 'ROOM NO 15'),
('room', '16', 'ROOM NO 16'), ('room', '17', 'ROOM NO 17'), ('room', '18', 'ROOM NO 18'),
('room', '19', 'ROOM NO 19'), ('room', '20', 'ROOM NO 20'), ('room', '21', 'ROOM NO 21'),
('room', '22', 'ROOM NO 22'), ('room', '23', 'ROOM NO 23'), ('room', '24', 'ROOM NO 24'),
('room', '25', 'ROOM NO 25'), ('room', '26', 'ROOM NO 26'),
('hall', 'SMALL_PARTY_HALL', 'SMALL PARTY HALL'),
('hall', 'BIG_PARTY_HALL', 'BIG PARTY HALL')
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);

-- Insert enhanced settings for SMS, Email, and system configuration
INSERT INTO settings (setting_key, setting_value) VALUES
('upi_id', 'owner@upi'),
('upi_name', 'L.P.S.T Bookings'),
('hotel_name', 'L.P.S.T Hotel'),
('sms_api_url', 'https://api.textlocal.in/send/'),
('sms_api_key', 'YOUR_SMS_API_KEY_HERE'),
('sms_sender_id', 'LPSTHT'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'your-email@gmail.com'),
('smtp_password', 'your-app-password'),
('smtp_encryption', 'tls'),
('owner_email', 'owner@lpsthotel.com'),
('system_timezone', 'Asia/Kolkata'),
('auto_refresh_interval', '30'),
('checkout_grace_hours', '24'),
('default_room_rate', '1000.00'),
('default_hall_rate', '5000.00')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_bookings_resource_status ON bookings(resource_id, status);
CREATE INDEX IF NOT EXISTS idx_bookings_advance_date ON bookings(advance_date);
CREATE INDEX IF NOT EXISTS idx_bookings_admin ON bookings(admin_id);
CREATE INDEX IF NOT EXISTS idx_bookings_mobile ON bookings(client_mobile);
CREATE INDEX IF NOT EXISTS idx_payments_resource ON payments(resource_id);
CREATE INDEX IF NOT EXISTS idx_resources_active ON resources(is_active);
CREATE INDEX IF NOT EXISTS idx_sms_logs_booking ON sms_logs(booking_id);
CREATE INDEX IF NOT EXISTS idx_email_logs_admin ON email_logs(admin_id);

-- Create views for reporting
CREATE OR REPLACE VIEW booking_summary AS
SELECT 
    b.id,
    b.client_name,
    b.client_mobile,
    b.client_aadhar,
    b.client_license,
    b.receipt_number,
    b.payment_mode,
    r.display_name as resource_name,
    r.custom_name as resource_custom_name,
    r.type as resource_type,
    b.check_in,
    b.check_out,
    b.status,
    b.booking_type,
    b.advance_date,
    b.advance_payment_mode,
    b.total_amount,
    b.is_paid,
    u.username as admin_name,
    b.created_at
FROM bookings b
JOIN resources r ON b.resource_id = r.id
JOIN users u ON b.admin_id = u.id;

-- Create view for admin activity tracking
CREATE OR REPLACE VIEW admin_activity AS
SELECT 
    u.id as admin_id,
    u.username as admin_name,
    COUNT(b.id) as total_bookings,
    COUNT(CASE WHEN b.status = 'BOOKED' THEN 1 END) as active_bookings,
    COUNT(CASE WHEN b.status = 'COMPLETED' THEN 1 END) as completed_bookings,
    COUNT(CASE WHEN b.booking_type = 'advanced' THEN 1 END) as advance_bookings,
    SUM(CASE WHEN b.is_paid = 1 THEN b.total_amount ELSE 0 END) as total_revenue,
    MAX(b.created_at) as last_booking_date
FROM users u
LEFT JOIN bookings b ON u.id = b.admin_id
WHERE u.role = 'ADMIN'
GROUP BY u.id, u.username;