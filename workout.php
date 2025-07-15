<?php
/**
 * Individual Workout Page
 * CoreCount Fitness Planner
 * 
 * This page displays details for a specific workout and allows users to start or complete it
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if workout ID is provided
if (!isset($_GET['id'])) {
    // Redirect to categories page if no workout ID
    redirect(SITE_URL . '/categories.php');
}

$workout_id = intval($_GET['id']);

// Fetch workout details including category information
$workout_query = "SELECT w.*, c.name as category_name, c.category_id
                 FROM workouts w
                 JOIN workout_categories c ON w.category_id = c.category_id
                 WHERE w.workout_id = :workout_id";
$workout_stmt = $db->prepare($workout_query);
$workout_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
$workout_stmt->execute();

if ($workout_stmt->rowCount() == 0) {
    // Workout not found
    $_SESSION['error'] = "Workout not found.";
    redirect(SITE_URL . '/categories.php');
}

$workout = $workout_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch exercises for this workout
$exercises_query = "SELECT e.*, we.exercise_order
                   FROM exercises e
                   JOIN workout_exercises we ON e.exercise_id = we.exercise_id
                   WHERE we.workout_id = :workout_id
                   ORDER BY we.exercise_order";
$exercises_stmt = $db->prepare($exercises_query);
$exercises_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
$exercises_stmt->execute();
$exercises = $exercises_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in to show progress tracking
$user_logged_in = isLoggedIn();

// Handle workout completion form submission
if ($user_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_workout'])) {
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : $workout['duration'];
    $calories = isset($_POST['calories_burned']) ? intval($_POST['calories_burned']) : $workout['calories_burned'];
    // Notes field removed as it's no longer stored in the database
    
    // Insert progress record
    $progress_sql = "INSERT INTO user_progress (user_id, workout_id, duration, calories_burned)
                    VALUES (:user_id, :workout_id, :duration, :calories_burned)";
    $progress_stmt = $db->prepare($progress_sql);
    $progress_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $progress_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
    $progress_stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
    $progress_stmt->bindParam(':calories_burned', $calories, PDO::PARAM_INT);
    // Note: notes field has been removed from the database
    
    if ($progress_stmt->execute()) {
        $_SESSION['success'] = "Workout completed! Your progress has been recorded.";
        redirect(SITE_URL . '/progress.php');
    } else {
        $error = "Failed to record workout progress. Please try again.";
    }
}

// Set page title
$page_title = htmlspecialchars($workout['name']) . " - CoreCount";

// Include header
include 'includes/header.php';
?>

<main>
    <div class="container py-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php">Categories</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php?id=<?php echo $workout['category_id']; ?>"><?php echo htmlspecialchars($workout['category_name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($workout['name']); ?></li>
            </ol>
        </nav>

        <!-- Workout Header -->
        <div class="workout-header mb-4">
            <h1><?php echo htmlspecialchars($workout['name']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($workout['description']); ?></p>
            
            <div class="workout-meta d-flex flex-wrap gap-3 mb-3">
                <div class="workout-meta-item">
                    <span class="badge bg-<?php 
                        echo $workout['difficulty_level'] === 'beginner' ? 'success' :
                            ($workout['difficulty_level'] === 'intermediate' ? 'warning' : 'danger');
                    ?>">
                        <?php echo ucfirst(htmlspecialchars($workout['difficulty_level'])); ?>
                    </span>
                </div>
                <div class="workout-meta-item">
                    <i class="far fa-clock"></i> <?php echo htmlspecialchars($workout['duration']); ?> minutes
                </div>
                <div class="workout-meta-item">
                    <i class="fas fa-fire"></i> ~<?php echo htmlspecialchars($workout['calories_burned']); ?> calories
                </div>
                <div class="workout-meta-item">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($workout['category_name']); ?>
                </div>
            </div>
            
            <?php if ($user_logged_in): ?>
                <div class="workout-actions d-flex flex-wrap gap-2">
                    <a href="<?php echo SITE_URL; ?>/workout_timer.php?id=<?php echo $workout_id; ?>" class="btn btn-success">
                        <i class="fas fa-play-circle"></i> Start Workout
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p><a href="<?php echo SITE_URL; ?>/login.php">Login</a> or <a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a> to track your progress and schedule workouts.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-lg-8">


                <!-- Exercises Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Exercises</h5>
                        <span class="badge bg-primary"><?php echo count($exercises); ?> exercises</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($exercises) > 0): ?>
                            <div class="exercise-list">
                                <?php foreach ($exercises as $index => $exercise): ?>
                                    <div class="exercise-item">
                                        <div class="exercise-header d-flex justify-content-between align-items-center">
                                            <h5 class="exercise-title mb-0">
                                                <span class="exercise-number"><?php echo $index + 1; ?>.</span>
                                                <?php echo htmlspecialchars($exercise['name']); ?>
                                            </h5>
                                            <div class="exercise-duration">
                                                <span class="badge bg-secondary"><?php echo $exercise['duration']; ?>s</span>
                                                <?php if ($exercise['rest_period'] > 0): ?>
                                                    <span class="badge bg-light text-dark">Rest: <?php echo $exercise['rest_period']; ?>s</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="exercise-details mt-2">
                                            <?php if (!empty($exercise['description'])): ?>
                                                <p><?php echo htmlspecialchars($exercise['description']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($exercise['form_guidance'])): ?>
                                                <div class="exercise-guidance mt-2">
                                                    <strong>Form Guidance:</strong>
                                                    <p><?php echo htmlspecialchars($exercise['form_guidance']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($exercise['tips'])): ?>
                                                <div class="exercise-tips mt-2">
                                                    <strong>Tips:</strong>
                                                    <p><?php echo htmlspecialchars($exercise['tips']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($index < count($exercises) - 1): ?>
                                        <hr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>No exercises found for this workout.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Workout Summary Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Workout Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Category
                                <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($workout['category_name']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Workout Duration
                                <span><?php echo $workout['duration']; ?> minutes</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Calories Burned
                                <span><?php echo $workout['calories_burned']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Difficulty
                                <span class="badge bg-<?php 
                                    echo $workout['difficulty_level'] === 'beginner' ? 'success' :
                                        ($workout['difficulty_level'] === 'intermediate' ? 'warning' : 'danger');
                                ?>">
                                    <?php echo ucfirst($workout['difficulty_level']); ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <?php if ($user_logged_in): ?>
                        <div class="card-footer">
                            <form method="post" action="<?php echo SITE_URL; ?>/save_workout_progress.php" id="direct-complete-form">
                                <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
                                <input type="hidden" name="duration" value="<?php echo $workout['duration']; ?>">
                                <input type="hidden" name="calories_burned" value="<?php echo $workout['calories_burned']; ?>">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-check-circle"></i> Complete Workout
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Related Workouts from Same Category -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Similar Workouts</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Fetch related workouts from the same category
                        $related_query = "SELECT workout_id, name, difficulty_level 
                                         FROM workouts 
                                         WHERE category_id = :category_id 
                                         AND workout_id != :workout_id 
                                         LIMIT 5";
                        $related_stmt = $db->prepare($related_query);
                        $related_stmt->bindParam(':category_id', $workout['category_id'], PDO::PARAM_INT);
                        $related_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
                        $related_stmt->execute();
                        $related_workouts = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($related_workouts) > 0):
                        ?>
                            <div class="list-group">
                                <?php foreach ($related_workouts as $related): ?>
                                    <a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $related['workout_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                        <span class="badge bg-<?php 
                                            echo $related['difficulty_level'] === 'beginner' ? 'success' :
                                                ($related['difficulty_level'] === 'intermediate' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($related['difficulty_level']); ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mb-0">No similar workouts found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Complete Workout Modal removed as requested by user -->

<!-- Schedule Workout Modal -->
<div class="modal fade" id="scheduleWorkoutModal" tabindex="-1" aria-labelledby="scheduleWorkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo SITE_URL; ?>/schedule.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleWorkoutModalLabel">Schedule Workout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="workout_id" value="<?php echo $workout_id; ?>">
                    
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="scheduled_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>