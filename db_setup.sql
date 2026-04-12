-- Lab 5: SQL Injection Attack and Defense
-- Database Setup Script
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS lab5;
USE lab5;

-- Vulnerable app users table (plaintext passwords)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

-- Clear existing data
TRUNCATE TABLE users;

-- Insert test users (plaintext passwords for vulnerable app)
INSERT INTO users (username, password) VALUES ('user1', 'pass1');
INSERT INTO users (username, password) VALUES ('admin', 'admin123');

-- Secure app users table (hashed passwords)
CREATE TABLE IF NOT EXISTS secure_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Clear existing data
TRUNCATE TABLE secure_users;

-- Hashed passwords (bcrypt) for secure_users
-- user1 / pass1
-- admin / admin123
INSERT INTO secure_users (username, password) VALUES ('user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
INSERT INTO secure_users (username, password) VALUES ('admin', '$2y$10$lCdEqGXJZP4.5sGG8SfRmedK/RFPl/PKWoaEjK9w0m.pMaWpd3wXC');

-- NOTE: If the above hashes don't work, run secure_app/setup.php to regenerate hashes
