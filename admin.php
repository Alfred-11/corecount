<?php
// Include config file
require_once 'config/config.php';

// Check if the user is already logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // Redirect to admin dashboard
    redirect(SITE_URL . '/admin_dashboard.php');
    exit;
} else {
    // Redirect to login page
    redirect(SITE_URL . '/admin_login.php');
    exit; // Make sure to exit after redirect
}
?>