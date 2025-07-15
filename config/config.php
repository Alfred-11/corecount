<?php
/**
 * Global Configuration File
 * CoreCount Fitness Planner
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'CoreCount');
define('SITE_URL', 'http://localhost/corecount');

// Email configuration for password reset and notifications
define('EMAIL_FROM', 'noreply@corecount.com');
define('EMAIL_NAME', 'CoreCount Fitness');

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Helper function to redirect
 * @param string $location - URL to redirect to
 */
function redirect($location) {
    header("Location: {$location}");
    exit;
}

/**
 * Helper function to sanitize user input
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Helper function to check if user is logged in
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper function to ensure user is logged in, redirect if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "You must be logged in to access this page";
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Helper function to display flash messages
 * @param string $name - Session variable name
 * @param string $class - CSS class for styling
 * @return string - HTML for flash message
 */
function flash($name = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($_SESSION[$name])) {
            $message = $_SESSION[$name];
            unset($_SESSION[$name]);
            return "<div class='{$class}'>{$message}</div>";
        }
    }
    return '';
}
?>