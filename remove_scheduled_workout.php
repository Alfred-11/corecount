<?php
/**
 * AJAX Handler for Removing Scheduled Workouts
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to manage workouts.'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if required parameters are provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = intval($_POST['schedule_id']);
    
    if ($schedule_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid schedule ID.'
        ]);
        exit;
    }
    
    // Delete the scheduled workout
    $delete_sql = "DELETE FROM workout_schedules WHERE schedule_id = :schedule_id AND user_id = :user_id";
    $delete_stmt = $db->prepare($delete_sql);
    $delete_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($delete_stmt->execute() && $delete_stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Scheduled workout removed successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove scheduled workout or not authorized.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Missing required parameters.'
    ]);
}
?>