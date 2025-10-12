-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS elearning;
USE elearning;

-- Create courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    duration INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create enrollments table
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    course_id INT,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Insert sample courses
INSERT IGNORE INTO courses (title, instructor, duration, price) VALUES
('JavaScript Fundamentals', 'John Doe', 10, 49.99),
('PHP for Beginners', 'Jane Smith', 8, 39.99),
('Node.js API Development', 'Mike Johnson', 12, 59.99),
('Database Design', 'Sarah Wilson', 6, 44.99),
('MySQL Mastery', 'Alex Brown', 8, 54.99);

-- Insert sample users
INSERT IGNORE INTO users (username, email) VALUES
('student1', 'student1@example.com'),
('student2', 'student2@example.com'),
('admin', 'admin@elearning.com');

-- Insert sample enrollments
INSERT IGNORE INTO enrollments (user_id, course_id) VALUES
(1, 1),  -- student1 enrolled in JavaScript
(1, 3),  -- student1 enrolled in Node.js
(2, 2),  -- student2 enrolled in PHP
(2, 4);  -- student2 enrolled in Database Design