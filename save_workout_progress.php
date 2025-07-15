<?php
/**
 * AJAX Handler for Saving Workout Progress
 * CoreCount Fitness Planner
 * 
 * This script receives workout completion data via AJAX and saves it to the user_progress table
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Check if it's an AJAX request
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Set content type to JSON for AJAX requests
if ($is_ajax) {
    header('Content-Type: application/json');
}

// Check if user is logged in
if (!isLoggedIn()) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
    } else {
        $_SESSION['error'] = 'You must be logged in to record workout progress.';
        redirect(SITE_URL . '/login.php');
    }
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    } else {
        $_SESSION['error'] = 'Invalid request method.';
        redirect(SITE_URL . '/categories.php');
    }
    exit;
}

// Get POST data
$workout_id = isset($_POST['workout_id']) ? intval($_POST['workout_id']) : 0;
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
$calories_burned = isset($_POST['calories_burned']) ? intval($_POST['calories_burned']) : 0;

// Validate data
if ($workout_id <= 0) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid workout ID']);
    } else {
        $_SESSION['error'] = 'Invalid workout ID.';
        redirect(SITE_URL . '/categories.php');
    }
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Insert progress record
$progress_sql = "INSERT INTO user_progress (user_id, workout_id, duration, calories_burned)
                VALUES (:user_id, :workout_id, :duration, :calories_burned)";
$progress_stmt = $db->prepare($progress_sql);
$progress_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$progress_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
$progress_stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
$progress_stmt->bindParam(':calories_burned', $calories_burned, PDO::PARAM_INT);

// Execute query and return result
if ($progress_stmt->execute()) {
    if ($is_ajax) {
        echo json_encode([
            'success' => true, 
            'message' => 'Workout progress saved successfully',
            'progress_id' => $db->lastInsertId()
        ]);
    } else {
        // For direct form submission, set success message and redirect to progress page
        $_SESSION['success'] = 'Workout completed! Your progress has been recorded.';
        redirect(SITE_URL . '/progress.php');
    }
} else {
    if ($is_ajax) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save workout progress'
        ]);
    } else {
        $_SESSION['error'] = 'Failed to record workout progress. Please try again.';
        redirect(SITE_URL . '/workout.php?id=' . $workout_id);
    }
}