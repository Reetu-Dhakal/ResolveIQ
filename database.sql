-- =====================================
-- CARETRACK PRO DATABASE
-- Complaint Management System
-- =====================================

CREATE DATABASE IF NOT EXISTS caretrack;
USE caretrack;

-- =====================================
-- USERS
-- =====================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,

    email VARCHAR(120) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL,

    phone VARCHAR(20),

    profile_image VARCHAR(255) DEFAULT 'default.png',

    role ENUM('admin','user') DEFAULT 'user',

    status ENUM('active','blocked') DEFAULT 'active',

    remember_token VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================
-- CATEGORIES
-- =====================================

CREATE TABLE categories (

    id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

INSERT INTO categories(category_name) VALUES

('Technical'),

('Academic'),

('Finance'),

('Hostel'),

('Library'),

('Electricity'),

('Internet'),

('Administration'),

('Other');

-- =====================================
-- PRIORITIES
-- =====================================

CREATE TABLE priorities (

    id INT AUTO_INCREMENT PRIMARY KEY,

    priority_name VARCHAR(50)

);

INSERT INTO priorities(priority_name)

VALUES

('Low'),

('Medium'),

('High'),

('Critical');

-- =====================================
-- COMPLAINTS
-- =====================================

CREATE TABLE complaints (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    category_id INT,

    priority_id INT,

    title VARCHAR(200),

    description TEXT,

    image VARCHAR(255),

    status ENUM(

        'Pending',

        'In Progress',

        'Resolved',

        'Rejected'

    ) DEFAULT 'Pending',

    admin_note TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(user_id)

    REFERENCES users(id)

    ON DELETE CASCADE,

    FOREIGN KEY(category_id)

    REFERENCES categories(id),

    FOREIGN KEY(priority_id)

    REFERENCES priorities(id)

);

-- =====================================
-- CHAT / MESSAGES
-- =====================================

CREATE TABLE complaint_messages (

    id INT AUTO_INCREMENT PRIMARY KEY,

    complaint_id INT,

    sender_id INT,

    message TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(complaint_id)

    REFERENCES complaints(id)

    ON DELETE CASCADE,

    FOREIGN KEY(sender_id)

    REFERENCES users(id)

);

-- =====================================
-- FILE ATTACHMENTS
-- =====================================

CREATE TABLE attachments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    complaint_id INT,

    file_name VARCHAR(255),

    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(complaint_id)

    REFERENCES complaints(id)

    ON DELETE CASCADE

);

-- =====================================
-- NOTIFICATIONS
-- =====================================

CREATE TABLE notifications (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    title VARCHAR(150),

    message TEXT,

    is_read BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(user_id)

    REFERENCES users(id)

    ON DELETE CASCADE

);

-- =====================================
-- PASSWORD RESET
-- =====================================

CREATE TABLE password_resets (

    id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(120),

    token VARCHAR(255),

    expires_at DATETIME

);

-- =====================================
-- SETTINGS
-- =====================================

CREATE TABLE settings (

    id INT AUTO_INCREMENT PRIMARY KEY,

    site_name VARCHAR(100),

    admin_email VARCHAR(100),

    dark_mode BOOLEAN DEFAULT FALSE

);

INSERT INTO settings(

site_name,

admin_email

)

VALUES(

'CareTrack',

'admin@caretrack.com'

);

-- =====================================
-- ACTIVITY LOGS
-- =====================================

CREATE TABLE activity_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    activity TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(user_id)

    REFERENCES users(id)

);

-- =====================================
-- SAMPLE ADMIN ACCOUNT
-- =====================================

INSERT INTO users(

full_name,

email,

password,

role

)

VALUES(

'Administrator',

'admin@caretrack.com',

'$2y$10$1gY7r3q1XzQ1u9xj5wXbRu8q1L6c5yF2tQ9mA5gX2nP9rM7K8dN2G',

'admin'

);