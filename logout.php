<?php
/**
 * Logout Page
 * CoreCount Fitness Planner
 */

// Include configuration file
require_once 'config/config.php';

// Check if admin is logged in
$is_admin = isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Store redirect target before clearing session
$redirect_target = $is_admin ? SITE_URL . '/admin_login.php' : SITE_URL . '/login.php';

// Check if the user is logged in (either regular user or admin)
if (isLoggedIn() || $is_admin) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the flash message
    session_start();
    
    // Set success message
    $_SESSION['success'] = "You have been successfully logged out";
}

// Redirect to appropriate login page
redirect($redirect_target);
?>