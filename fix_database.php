<?php
/**
 * Database Fix Script
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Start with a clean HTML structure
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>CoreCount Database Fix</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container py-5'>
        <div class='card shadow'>
            <div class='card-header bg-primary text-white'>
                <h3>CoreCount Database Fix</h3>
            </div>
            <div class='card-body'>";

// Check if admin table exists
$admin_table_check = $db->query("SHOW TABLES LIKE 'admins'");
if ($admin_table_check->rowCount() == 0) {
    // Create admins table
    $create_admin_table = "CREATE TABLE IF NOT EXISTS admins (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($create_admin_table);
    
    echo "<div class='alert alert-info'>Admin table created successfully.</div>";
}

// Define admin credentials
$admin_username = 'admin';
$admin_password = 'admin123';
$admin_email = 'admin@corecount.com';
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin exists
$check_admin = "SELECT COUNT(*) FROM admins WHERE username = :username";
$stmt = $db->prepare($check_admin);
$stmt->bindParam(':username', $admin_username);
$stmt->execute();

$success = false;

if ($stmt->fetchColumn() > 0) {
    // Admin exists, update password
    $update_admin = "UPDATE admins SET password = :password WHERE username = :username";
    $stmt = $db->prepare($update_admin);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':username', $admin_username);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>
                <h4>Success!</h4>
                <p>The admin password has been updated successfully.</p>
                <p>You can now log in with:</p>
                <ul>
                    <li><strong>Username:</strong> {$admin_username}</li>
                    <li><strong>Password:</strong> {$admin_password}</li>
                </ul>
              </div>";
        $success = true;
    } else {
        echo "<div class='alert alert-danger'>
                <h4>Error!</h4>
                <p>There was a problem updating the admin password.</p>
                <p>Error: " . implode(", ", $stmt->errorInfo()) . "</p>
              </div>";
    }
} else {
    // Admin doesn't exist, create new admin
    $insert_admin = "INSERT INTO admins (username, password, email) VALUES (:username, :password, :email)";
    $stmt = $db->prepare($insert_admin);
    $stmt->bindParam(':username', $admin_username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':email', $admin_email);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>
                <h4>Success!</h4>
                <p>Default admin user created successfully.</p>
                <p>You can now log in with:</p>
                <ul>
                    <li><strong>Username:</strong> {$admin_username}</li>
                    <li><strong>Password:</strong> {$admin_password}</li>
                </ul>
              </div>";
        $success = true;
    } else {
        echo "<div class='alert alert-danger'>
                <h4>Error!</h4>
                <p>Error creating default admin user: " . implode(", ", $stmt->errorInfo()) . "</p>
              </div>";
    }
}

// Add login button if successful
if ($success) {
    echo "<div class='mt-4'>
            <a href='admin_login.php' class='btn btn-primary'>Go to Admin Login</a>
          </div>";
}

echo "</div>
        </div>
    </div>
</body>
</html>";
?>