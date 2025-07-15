-- Admin SQL File
-- CoreCount Fitness Planner

-- Admins table for admin login
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user if not exists with properly hashed password
-- The password is 'admin123' hashed with PASSWORD_DEFAULT
INSERT INTO admins (username, password, email)
SELECT 'admin', '$2y$10$uqEKQmn9JOr7BUcWwxwDnODVYgvFN2BmMzuJfnJvFj5UYP/EjZmPK', 'admin@corecount.com'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');
-- Default password is 'admin123' (properly hashed)

-- Contact messages table for storing contact form data
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    admin_reply TEXT,
    replied_at DATETIME DEFAULT NULL,
    message_type VARCHAR(50),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User progress table for analyzing frequent exercise types
CREATE TABLE IF NOT EXISTS user_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_id INT NOT NULL,
    completion_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    duration INT,  -- actual duration in minutes
    calories_burned INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(workout_id) ON DELETE CASCADE
);

-- Users table (reference to existing users table)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);