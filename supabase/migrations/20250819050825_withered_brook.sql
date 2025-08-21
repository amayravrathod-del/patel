/*
# Enhanced L.P.S.T Bookings System

1. Enhanced Tables
   - Updated bookings table with better status management
   - Added duration tracking and payment confirmation
   - Enhanced resources table for owner customization
   - Improved payment system with UPI integration

2. New Features
   - Real-time duration tracking
   - Advanced booking management
   - Owner customization options
   - Enhanced payment workflow

3. Status Flow
   - VACANT → BOOKED → PAID → VACANT
   - Advanced bookings with date-based activation
   - Automatic timer management
*/

-- Enhanced users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('OWNER', 'ADMIN') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

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
);

-- Enhanced bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    actual_check_in DATETIME NULL,
    actual_check_out DATETIME NULL,
    status ENUM('BOOKED', 'PENDING', 'COMPLETED', 'ADVANCED_BOOKED', 'PAID') DEFAULT 'BOOKED',
    booking_type ENUM('regular', 'advanced') DEFAULT 'regular',
    advance_date DATE NULL,
    admin_id INT NOT NULL,
    is_paid BOOLEAN DEFAULT FALSE,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_notes TEXT,
    duration_minutes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

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
);

-- Enhanced settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Insert default users with secure passwords
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

-- Insert enhanced settings
INSERT INTO settings (setting_key, setting_value) VALUES
('upi_id', 'owner@upi'),
('upi_name', 'L.P.S.T Bookings'),
('qr_image', ''),
('system_timezone', 'UTC'),
('auto_refresh_interval', '30'),
('checkout_grace_hours', '24'),
('default_room_rate', '1000.00'),
('default_hall_rate', '5000.00')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_bookings_resource_status ON bookings(resource_id, status);
CREATE INDEX IF NOT EXISTS idx_bookings_advance_date ON bookings(advance_date);
CREATE INDEX IF NOT EXISTS idx_payments_resource ON payments(resource_id);
CREATE INDEX IF NOT EXISTS idx_resources_active ON resources(is_active);