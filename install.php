<?php
require_once 'db_config.php';

// Drop old tables (para fresh start)
mysqli_query($conn, "DROP TABLE IF EXISTS otps");
mysqli_query($conn, "DROP TABLE IF EXISTS cycles");
mysqli_query($conn, "DROP TABLE IF EXISTS matrix_boards");
mysqli_query($conn, "DROP TABLE IF EXISTS users");

// ==================== USERS TABLE ====================
mysqli_query($conn, "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    sponsor VARCHAR(50) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==================== MATRIX BOARDS TABLE ====================
mysqli_query($conn, "CREATE TABLE matrix_boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leader_username VARCHAR(50) NOT NULL,
    slot1 VARCHAR(50) DEFAULT NULL,
    slot2 VARCHAR(50) DEFAULT NULL,
    status ENUM('ACTIVE','COMPLETED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(leader_username, status)
)");

// ==================== CYCLES TABLE (payout history) ====================
mysqli_query($conn, "CREATE TABLE cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    completed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_amount INT NOT NULL
)");

// ==================== OTPS TABLE (for OTP login) ====================
mysqli_query($conn, "CREATE TABLE otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==================== PRODUCTS TABLE (for registration codes) ====================
mysqli_query($conn, "CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==================== CODES TABLE (registration codes) ====================
mysqli_query($conn, "CREATE TABLE codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    product_id INT NOT NULL,
    is_used TINYINT DEFAULT 0,
    used_by VARCHAR(50) DEFAULT NULL,
    used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

// ==================== CREATE ADMIN USER ====================
// Admin email: admin@easy2x2.com
mysqli_query($conn, "INSERT INTO users (username, email, sponsor) VALUES ('admin', 'admin@easy2x2.com', '')");

// Create initial ACTIVE board for admin
mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('admin', 'ACTIVE')");

// ==================== DEFAULT PRODUCT ====================
mysqli_query($conn, "INSERT INTO products (name, price, description) VALUES ('Easy 2x2 Bayanihan Membership', 2500.00, 'One-time membership with product')");

// ==================== SAMPLE CODES (for testing) ====================
mysqli_query($conn, "INSERT INTO codes (code, product_id, is_used) VALUES
('EASY2X2-ABC123', 1, 0),
('EASY2X2-DEF456', 1, 0),
('EASY2X2-GHI789', 1, 0),
('EASY2X2-JKL012', 1, 0),
('EASY2X2-MNO345', 1, 0),
('EASY2X2-PQR678', 1, 0),
('EASY2X2-STU901', 1, 0),
('EASY2X2-VWX234', 1, 0),
('EASY2X2-YZA567', 1, 0),
('EASY2X2-BCD890', 1, 0)");

echo "✅ All tables created successfully!<br>";
echo "✅ Admin user created: admin@easy2x2.com (OTP login via email)<br>";
echo "✅ 10 sample registration codes added<br>";
echo "<hr>";
echo "<a href='login.php'>Go to Login</a>";
?>
