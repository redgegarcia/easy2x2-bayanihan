<?php
require_once 'db_config.php';

// Drop old tables (to remove UNIQUE constraint on leader_username)
mysqli_query($conn, "DROP TABLE IF EXISTS cycles");
mysqli_query($conn, "DROP TABLE IF EXISTS matrix_boards");
mysqli_query($conn, "DROP TABLE IF EXISTS users");

// Users table
mysqli_query($conn, "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    sponsor VARCHAR(50) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Matrix boards table – now multiple boards per user (active + completed)
mysqli_query($conn, "CREATE TABLE matrix_boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leader_username VARCHAR(50) NOT NULL,
    slot1 VARCHAR(50) DEFAULT NULL,
    slot2 VARCHAR(50) DEFAULT NULL,
    slot3 VARCHAR(50) DEFAULT NULL,
    slot4 VARCHAR(50) DEFAULT NULL,
    slot5 VARCHAR(50) DEFAULT NULL,
    slot6 VARCHAR(50) DEFAULT NULL,
    status ENUM('ACTIVE','COMPLETED') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(leader_username, status)
)");

// Cycles table (payout history)
mysqli_query($conn, "CREATE TABLE cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    completed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reward_amount INT NOT NULL
)");

// Create admin user (password: admin123)
$hash = password_hash('admin123', PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT INTO users (username, password, sponsor) VALUES ('admin', '$hash', '')");

// Create initial ACTIVE board for admin
mysqli_query($conn, "INSERT INTO matrix_boards (leader_username, status) VALUES ('admin', 'ACTIVE')");

echo "✅ All tables recreated with cycling support.<br>";
echo "Login: <strong>admin</strong> / <strong>admin123</strong><br>";
echo "<a href='login.php'>Go to Login</a>";
?>