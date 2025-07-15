<?php
/**
 * AJAX Handler for Workout Scheduling
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
        'message' => 'You must be logged in to schedule workouts.'
    ]);
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if required parameters are provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['workout_id']) && isset($_POST['date'])) {
    $workout_id = intval($_POST['workout_id']);
    $scheduled_date = $_POST['date'];
    $scheduled_time = isset($_POST['time']) ? $_POST['time'] : '09:00:00'; // Default to 9 AM if not specified

    // Validate inputs
    if ($workout_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid workout selected.'
        ]);
        exit;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduled_date)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Please use YYYY-MM-DD.'
        ]);
        exit;
    }
    
    // Prevent scheduling workouts for past dates
    $current_date = date('Y-m-d');
    if ($scheduled_date < $current_date) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot schedule workouts for past dates. Please select today or a future date.'
        ]);
        exit;
    }

    // Check if workout exists
    $workout_check = "SELECT workout_id FROM workouts WHERE workout_id = :workout_id";
    $workout_stmt = $db->prepare($workout_check);
    $workout_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
    $workout_stmt->execute();

    if ($workout_stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Selected workout does not exist.'
        ]);
        exit;
    }

    // Insert schedule
    $insert_sql = "INSERT INTO workout_schedules (user_id, workout_id, scheduled_date, scheduled_time)
                  VALUES (:user_id, :workout_id, :scheduled_date, :scheduled_time)";
    $insert_stmt = $db->prepare($insert_sql);
    $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $insert_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
    $insert_stmt->bindParam(':scheduled_date', $scheduled_date, PDO::PARAM_STR);
    $insert_stmt->bindParam(':scheduled_time', $scheduled_time, PDO::PARAM_STR);

    try {
        if ($insert_stmt->execute()) {
            // Get the inserted schedule ID
            $schedule_id = $db->lastInsertId();

            // Log successful insertion for debugging
            error_log("Workout scheduled successfully. Schedule ID: {$schedule_id}, User ID: {$user_id}, Workout ID: {$workout_id}");

            // Get workout details including category
            $workout_query = "SELECT w.name, w.category_id FROM workouts w WHERE w.workout_id = :workout_id";
            $workout_stmt = $db->prepare($workout_query);
            $workout_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
            $workout_stmt->execute();
            $workout = $workout_stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'message' => 'Workout scheduled successfully!',
                'schedule_id' => $schedule_id,
                'workout_name' => $workout['name'],
                'category_id' => $workout['category_id'],
                'date' => $scheduled_date,
                'time' => $scheduled_time
            ]);
        } else {
            // Log the error information
            $errorInfo = $insert_stmt->errorInfo();
            error_log("Failed to execute workout schedule insertion. Error: " . json_encode($errorInfo));

            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $errorInfo[2]
            ]);
        }
    } catch (PDOException $e) {
        // Log the exception
        error_log("Exception during workout scheduling: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_schedule'])) {
    // Handle removing a scheduled workout
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;

    if ($schedule_id > 0) {
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
            'message' => 'Invalid schedule ID.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Missing required parameters.'
    ]);
}
?>