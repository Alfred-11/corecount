<?php
/**
 * Workout Schedule Page
 * CoreCount Fitness Planner
 */

// Auto-hide sidebar when schedule page is loaded
if (!isset($_COOKIE['sidebar_collapsed']) || $_COOKIE['sidebar_collapsed'] !== 'true') {
    setcookie('sidebar_collapsed', 'true', time() + (86400 * 30), '/'); // Set for 30 days
}

// Add JavaScript to ensure sidebar is hidden when page loads
$force_sidebar_collapse = true;

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Ensure user is logged in
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Process form submissions
$message = '';
$message_type = '';

// Add new schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $workout_id = isset($_POST['workout_id']) ? intval($_POST['workout_id']) : 0;
    $scheduled_date = isset($_POST['scheduled_date']) ? $_POST['scheduled_date'] : '';
    $scheduled_time = isset($_POST['scheduled_time']) ? $_POST['scheduled_time'] : '';
    
    // Validate inputs
    if ($workout_id <= 0) {
        $message = 'Please select a valid workout.';
        $message_type = 'danger';
    } elseif (empty($scheduled_date)) {
        $message = 'Please select a date for your workout.';
        $message_type = 'danger';
    } elseif (empty($scheduled_time)) {
        $message = 'Please select a time for your workout.';
        $message_type = 'danger';
    } elseif ($scheduled_date < date('Y-m-d')) {
        $message = 'Cannot schedule workouts for past dates. Please select today or a future date.';
        $message_type = 'danger';
    } else {
        // Check if workout exists
        $workout_check = "SELECT workout_id FROM workouts WHERE workout_id = :workout_id";
        $workout_stmt = $db->prepare($workout_check);
        $workout_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
        $workout_stmt->execute();
        
        if ($workout_stmt->rowCount() > 0) {
            // Insert schedule
            $insert_sql = "INSERT INTO workout_schedules (user_id, workout_id, scheduled_date, scheduled_time) 
                          VALUES (:user_id, :workout_id, :scheduled_date, :scheduled_time)";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':scheduled_date', $scheduled_date, PDO::PARAM_STR);
            $insert_stmt->bindParam(':scheduled_time', $scheduled_time, PDO::PARAM_STR);
            
            if ($insert_stmt->execute()) {
                $message = 'Workout scheduled successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to schedule workout. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Selected workout does not exist.';
            $message_type = 'danger';
        }
    }
}

// Mark as completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_schedule'])) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    
    if ($schedule_id > 0) {
        // Get schedule details
        $schedule_query = "SELECT ws.*, w.name, w.duration, w.calories_burned 
                          FROM workout_schedules ws 
                          JOIN workouts w ON ws.workout_id = w.workout_id 
                          WHERE ws.schedule_id = :schedule_id AND ws.user_id = :user_id";
        $schedule_stmt = $db->prepare($schedule_query);
        $schedule_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $schedule_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $schedule_stmt->execute();
        
        if ($schedule_stmt->rowCount() > 0) {
            $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Begin transaction
            $db->beginTransaction();
            
            try {
                // Mark schedule as completed
                $update_sql = "UPDATE workout_schedules SET completed = 1 WHERE schedule_id = :schedule_id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
                $update_stmt->execute();
                
                // Add to progress
                $progress_sql = "INSERT INTO user_progress (user_id, workout_id, duration, calories_burned) 
                                VALUES (:user_id, :workout_id, :duration, :calories_burned)";
                $progress_stmt = $db->prepare($progress_sql);
                $progress_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $progress_stmt->bindParam(':workout_id', $schedule['workout_id'], PDO::PARAM_INT);
                $progress_stmt->bindParam(':duration', $schedule['duration'], PDO::PARAM_INT);
                $progress_stmt->bindParam(':calories_burned', $schedule['calories_burned'], PDO::PARAM_INT);
                $progress_stmt->execute();
                
                // Commit transaction
                $db->commit();
                
                $message = 'Workout marked as completed and added to your progress!';
                $message_type = 'success';
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollBack();
                $message = 'Failed to complete workout. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Invalid schedule or not authorized.';
            $message_type = 'danger';
        }
    }
}

// Delete schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    
    if ($schedule_id > 0) {
        $delete_sql = "DELETE FROM workout_schedules WHERE schedule_id = :schedule_id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_sql);
        $delete_stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute() && $delete_stmt->rowCount() > 0) {
            $message = 'Scheduled workout removed successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to remove scheduled workout or not authorized.';
            $message_type = 'danger';
        }
    }
}

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($month < 1 || $month > 12) {
    $month = intval(date('m'));
}

// Calculate previous and next month/year
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get all days in the month
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_month = date('N', strtotime("$year-$month-01"));

// Fetch user's scheduled workouts for this month
$start_date = "$year-$month-01";
$end_date = "$year-$month-$num_days";

$schedules_query = "SELECT ws.*, w.name as workout_name, w.difficulty_level, c.name as category_name, c.category_id 
                   FROM workout_schedules ws 
                   JOIN workouts w ON ws.workout_id = w.workout_id 
                   JOIN workout_categories c ON w.category_id = c.category_id 
                   WHERE ws.user_id = :user_id 
                   AND ws.scheduled_date BETWEEN :start_date AND :end_date 
                   ORDER BY ws.scheduled_date, ws.scheduled_time";

$schedules_stmt = $db->prepare($schedules_query);
$schedules_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$schedules_stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
$schedules_stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
$schedules_stmt->execute();
$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize schedules by date
$schedule_by_date = [];
foreach ($schedules as $schedule) {
    $date = $schedule['scheduled_date'];
    if (!isset($schedule_by_date[$date])) {
        $schedule_by_date[$date] = [];
    }
    $schedule_by_date[$date][] = $schedule;
}

// Fetch all workouts for dropdown
$workouts_query = "SELECT w.workout_id, w.name, w.difficulty_level, c.name as category_name, c.category_id 
                  FROM workouts w 
                  JOIN workout_categories c ON w.category_id = c.category_id 
                  ORDER BY c.name, w.name";
$workouts_stmt = $db->prepare($workouts_query);
$workouts_stmt->execute();
$workouts = $workouts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$page_title = "Workout Schedule - CoreCount";

// Include header
include_once 'includes/header.php';

// Add custom CSS for schedule updates
?>
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/schedule-updates.css">
<?php
?>

<style>
    /* Custom styles for the schedule page */
    .calendar-table th, .calendar-table td {
        text-align: center;
    }
    .workout-list {
        height: 400px;
        overflow-y: auto;
    }
    .card {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .calendar-day {
        min-height: 160px;
    }
    .container {
        max-width: 1400px;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Workout Schedule</h1>
        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="alert alert-info">
        <p><i class="fas fa-info-circle"></i> <strong>Tip:</strong> You can also drag workouts from the list on the right and drop them onto a date to schedule them quickly!</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Workout Schedule Layout -->
<div class="container-fluid">
<div class="row">

<!-- Available Workouts Section - Moved to left side -->
<div class="col-lg-4 mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Available Workouts</h5>
        </div>
        <div class="card-body p-3">
            <p class="card-text">Drag and drop workouts onto calendar dates to schedule them.</p>
            <div class="list-group workout-list">
                <?php if (count($workouts) > 0): ?>
                    <?php foreach ($workouts as $workout): ?>
                        <div class="list-group-item list-group-item-action draggable-workout" 
                             draggable="true" 
                             data-workout-id="<?php echo $workout['workout_id']; ?>"
                             data-category-id="<?php echo $workout['category_id']; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($workout['name']); ?></span>
                                <span class="badge bg-<?php 
                                    echo $workout['difficulty_level'] === 'beginner' ? 'success' : 
                                        ($workout['difficulty_level'] === 'intermediate' ? 'warning' : 'danger'); 
                                ?>"><?php echo ucfirst($workout['difficulty_level']); ?></span>
                            </div>
                            <small class="text-muted"><?php echo htmlspecialchars($workout['category_name']); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        <p class="mb-0">No workouts available. Please add workouts first.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Section - Moved to right side -->
<div class="col-lg-8 mb-4">
    <!-- Calendar Navigation -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <a href="<?php echo SITE_URL; ?>/schedule.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Previous Month
                </a>
                <h3 class="mb-0"><?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
                <a href="<?php echo SITE_URL; ?>/schedule.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-primary">
                    Next Month <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Calendar Table -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <table class="table table-bordered calendar-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                        <th>Sunday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Calendar generation
                    $day_counter = 1;
                    $first_day_offset = $first_day_of_month - 1; // Adjust for Monday start
                    
                    // Calculate number of weeks
                    $total_days = $first_day_offset + $num_days;
                    $total_weeks = ceil($total_days / 7);
                    
                    for ($week = 0; $week < $total_weeks; $week++) {
                        echo '<tr>';
                        
                        // Loop through days of the week
                        for ($i = 0; $i < 7; $i++) {
                            $current_day = $week * 7 + $i + 1 - $first_day_offset;
                            
                            if ($current_day < 1 || $current_day > $num_days) {
                                echo '<td class="text-muted"></td>';
                                continue;
                            }
                            
                            $current_date = sprintf('%04d-%02d-%02d', $year, $month, $current_day);
                            $is_today = ($current_date === date('Y-m-d'));
                            $has_workouts = isset($schedule_by_date[$current_date]);
                            
                            echo '<td class="calendar-day' . ($is_today ? ' today' : '') . '" data-date="' . $current_date . '">';
                            echo '<div class="day-number' . ($has_workouts ? ' has-workouts' : '') . '">' . $current_day . '</div>';
                            
                            if ($has_workouts) {
                                echo '<div class="scheduled-workouts">';
                                foreach ($schedule_by_date[$current_date] as $schedule) {
                                    $category_class = 'category-' . $schedule['category_id'];
                                    $completed_class = $schedule['completed'] ? ' completed' : '';
                                    
                                    echo '<div class="workout-item ' . $category_class . $completed_class . '">';
                                    echo '<div class="workout-time">' . date('g:i A', strtotime($schedule['scheduled_time'])) . '</div>';
                                    echo '<span class="workout-name">' . htmlspecialchars($schedule['workout_name']) . '</span>';
                                    echo '<div class="workout-actions d-flex justify-content-end gap-1">';
                                    
                                    echo '<a href="' . SITE_URL . '/workout_timer.php?id=' . $schedule['workout_id'] . '" class="btn btn-sm btn-success" title="Start Workout">';
                                    echo '<i class="fas fa-play"></i>';
                                    echo '</a>';
                                    
                                    if (!$schedule['completed']) {
                                        echo '<form method="post" class="d-inline">';
                                        echo '<input type="hidden" name="schedule_id" value="' . $schedule['schedule_id'] . '">';
                                        echo '<button type="submit" name="complete_schedule" class="btn btn-sm btn-primary" title="Mark as Completed">';
                                        echo '<i class="fas fa-check"></i>';
                                        echo '</button>';
                                        echo '</form>';
                                    } else {
                                        echo '<span class="badge bg-success">Completed</span>';
                                    }
                                    
                                    echo '<form method="post" class="d-inline ms-1">';
                                    echo '<input type="hidden" name="schedule_id" value="' . $schedule['schedule_id'] . '">';
                                    echo '<button type="submit" name="delete_schedule" class="btn btn-sm btn-danger" title="Remove">';
                                    echo '<i class="fas fa-times"></i>';
                                    echo '</button>';
                                    echo '</form>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            echo '</td>';
                            
                            $day_counter++;
                        }
                        
                        // Fill remaining cells in last week
                        while ($i < 7) {
                            echo '<td class="text-muted"></td>';
                            $i++;
                        }
                        
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- End of Calendar Section -->

</div>
</div>

<?php include 'includes/footer.php'; ?>