<?php
/**
 * Individual Workout Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if workout ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(SITE_URL . '/categories.php');
}

$workout_id = intval($_GET['id']);

// Fetch workout details
$workout_query = "SELECT w.*, c.name as category_name, c.category_id 
                 FROM workouts w
                 JOIN workout_categories c ON w.category_id = c.category_id
                 WHERE w.workout_id = :workout_id";
$workout_stmt = $db->prepare($workout_query);
$workout_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
$workout_stmt->execute();

if ($workout_stmt->rowCount() == 0) {
    // Workout not found
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

// Page title
$page_title = htmlspecialchars($workout['name']) . " - CoreCount";

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php">Categories</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php?id=<?php echo $workout['category_id']; ?>"><?php echo htmlspecialchars($workout['category_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($workout['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Workout Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($workout['name']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($workout['description']); ?></p>
            
            <div class="workout-meta d-flex flex-wrap gap-3 mb-3">
                <div class="workout-meta-item">
                    <i class="fas fa-dumbbell"></i> 
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
                    <i class="fas fa-th-list"></i> <?php echo count($exercises); ?> exercises
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <a href="<?php echo SITE_URL; ?>/workout_timer.php?id=<?php echo $workout_id; ?>" class="btn btn-success">
                    <i class="fas fa-play-circle"></i> Start Workout
                </a>
                <?php if ($user_logged_in): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#completeWorkoutModal">
                        <i class="fas fa-check-circle"></i> Complete Workout
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#scheduleWorkoutModal">
                        <i class="far fa-calendar-plus"></i> Schedule Workout
                    </button>
                <?php endif; ?>
            </div>
            <?php if (!$user_logged_in): ?>
                <div class="alert alert-info mt-3">
                    <p><a href="<?php echo SITE_URL; ?>/login.php">Login</a> or <a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a> to track your progress and schedule workouts.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($workout['image_path'])): ?>
        <div class="col-md-4">
            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($workout['image_path']); ?>" alt="<?php echo htmlspecialchars($workout['name']); ?>" class="img-fluid rounded">
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Exercise List -->
    <h2 class="mb-3">Exercises</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <?php if (count($exercises) > 0): ?>
                <div class="accordion" id="exerciseAccordion">
                    <?php foreach ($exercises as $index => $exercise): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $exercise['exercise_id']; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $exercise['exercise_id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $exercise['exercise_id']; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <span><strong><?php echo $index + 1; ?>.</strong> <?php echo htmlspecialchars($exercise['name']); ?></span>
                                        <span class="badge bg-secondary"><?php echo $exercise['duration']; ?>s</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $exercise['exercise_id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $exercise['exercise_id']; ?>" data-bs-parent="#exerciseAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <?php if (!empty($exercise['image_path'])): ?>
                                        <div class="col-md-4 mb-3">
                                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($exercise['image_path']); ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?>" class="img-fluid rounded">
                                        </div>
                                        <?php endif; ?>
                                        <div class="col-md-<?php echo !empty($exercise['image_path']) ? '8' : '12'; ?>">
                                            <p><?php echo htmlspecialchars($exercise['description']); ?></p>
                                            
                                            <?php if (!empty($exercise['form_guidance'])): ?>
                                            <h6>Proper Form:</h6>
                                            <p><?php echo htmlspecialchars($exercise['form_guidance']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($exercise['tips'])): ?>
                                            <h6>Tips:</h6>
                                            <p><?php echo htmlspecialchars($exercise['tips']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between">
                                                <span><i class="far fa-clock"></i> <?php echo $exercise['duration']; ?> seconds</span>
                                                <span><i class="fas fa-bed"></i> <?php echo $exercise['rest_period']; ?> seconds rest</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No exercises found for this workout.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">Workout Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Exercises
                            <span class="badge bg-primary rounded-pill"><?php echo count($exercises); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Workout Duration
                            <span><?php echo $workout['duration']; ?> minutes</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Estimated Calories
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
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#completeWorkoutModal">
                        <i class="fas fa-check-circle"></i> Complete Workout
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Complete Workout Modal -->
<?php if ($user_logged_in): ?>
<div class="modal fade" id="completeWorkoutModal" tabindex="-1" aria-labelledby="completeWorkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $workout_id; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeWorkoutModalLabel">Complete Workout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $workout['duration']; ?>" min="1" max="300">
                        <small class="form-text text-muted">How long did your workout take?</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="calories_burned" class="form-label">Calories Burned</label>
                        <input type="number" class="form-control" id="calories_burned" name="calories_burned" value="<?php echo $workout['calories_burned']; ?>" min="1" max="2000">
                        <small class="form-text text-muted">Estimate how many calories you burned</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="How did it go? Any challenges or achievements?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="complete_workout" class="btn btn-primary">Save Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>