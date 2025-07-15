<?php
/**
 * Workout Timer Page
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

// Check if user is logged in
$user_logged_in = isLoggedIn();

// Page title
$page_title = "Workout Timer: " . htmlspecialchars($workout['name']) . " - CoreCount";

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
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $workout_id; ?>"><?php echo htmlspecialchars($workout['name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Workout Timer</li>
        </ol>
    </nav>
    
    <!-- Workout Timer Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Workout Timer: <?php echo htmlspecialchars($workout['name']); ?></h1>
            <p class="lead">Follow along with the timer for each exercise and rest period.</p>
        </div>
        <?php if (!empty($workout['image_path'])): ?>
        <div class="col-md-4">
            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($workout['image_path']); ?>" alt="<?php echo htmlspecialchars($workout['name']); ?>" class="img-fluid rounded">
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Timer Section -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h2 id="current-exercise-name" class="mb-3">Get Ready!</h2>
                    
                    <div id="exercise-image-container" class="mb-3 d-none">
                        <img id="exercise-image" src="" alt="Exercise" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    
                    <div id="timer-display" class="display-1 mb-3">00:10</div>
                    
                    <div id="timer-progress" class="progress mb-4" style="height: 20px;">
                        <div id="timer-progress-bar" class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <div id="exercise-info" class="mb-4 d-none">
                        <p id="exercise-description"></p>
                        <div id="exercise-form" class="mb-2 d-none">
                            <h5>Proper Form:</h5>
                            <p id="exercise-form-text"></p>
                        </div>
                        <div id="exercise-tips" class="mb-2 d-none">
                            <h5>Tips:</h5>
                            <p id="exercise-tips-text"></p>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button id="prev-btn" class="btn btn-secondary" disabled>
                            <i class="fas fa-step-backward"></i> Previous
                        </button>
                        <button id="pause-btn" class="btn btn-primary">
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <button id="next-btn" class="btn btn-secondary">
                            <i class="fas fa-step-forward"></i> Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header">
                    <h5 class="mb-0">Workout Progress</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Current Exercise:</span>
                        <span id="current-exercise-count">0</span>/<span id="total-exercises"><?php echo count($exercises); ?></span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div id="workout-progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <div class="list-group exercise-list">
                        <?php foreach ($exercises as $index => $exercise): ?>
                            <div id="exercise-item-<?php echo $index; ?>" class="list-group-item list-group-item-action" data-exercise-id="<?php echo $exercise['exercise_id']; ?>" data-duration="<?php echo $exercise['duration']; ?>" data-rest="<?php echo $exercise['rest_period']; ?>" data-name="<?php echo htmlspecialchars($exercise['name']); ?>" data-description="<?php echo htmlspecialchars($exercise['description']); ?>" data-form="<?php echo htmlspecialchars($exercise['form_guidance']); ?>" data-tips="<?php echo htmlspecialchars($exercise['tips']); ?>" data-image="<?php echo !empty($exercise['image_path']) ? SITE_URL . '/' . $exercise['image_path'] : ''; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <span><strong><?php echo $index + 1; ?>.</strong> <?php echo htmlspecialchars($exercise['name']); ?></span>
                                    <span class="badge bg-secondary"><?php echo $exercise['duration']; ?>s</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($user_logged_in): ?>
                <div class="card-footer">
                    <button id="complete-workout-btn" type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#completeWorkoutModal">
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
            <form method="post" action="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $workout_id; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeWorkoutModalLabel">Complete Workout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all exercises from the list
        const exerciseItems = document.querySelectorAll('.exercise-list .list-group-item');
        const exercises = Array.from(exerciseItems).map(item => ({
            id: item.dataset.exerciseId,
            name: item.dataset.name,
            duration: parseInt(item.dataset.duration),
            rest: parseInt(item.dataset.rest),
            description: item.dataset.description,
            form: item.dataset.form,
            tips: item.dataset.tips,
            image: item.dataset.image
        }));
        
        // Timer variables
        let currentExerciseIndex = -1;
        let isResting = false;
        let timeRemaining = 10; // Initial countdown
        let timerInterval;
        let isPaused = false;
        
        // DOM elements
        const timerDisplay = document.getElementById('timer-display');
        const timerProgressBar = document.getElementById('timer-progress-bar');
        const currentExerciseName = document.getElementById('current-exercise-name');
        const currentExerciseCount = document.getElementById('current-exercise-count');
        const totalExercises = document.getElementById('total-exercises');
        const workoutProgressBar = document.getElementById('workout-progress-bar');
        const exerciseDescription = document.getElementById('exercise-description');
        const exerciseForm = document.getElementById('exercise-form');
        const exerciseFormText = document.getElementById('exercise-form-text');
        const exerciseTips = document.getElementById('exercise-tips');
        const exerciseTipsText = document.getElementById('exercise-tips-text');
        const exerciseImageContainer = document.getElementById('exercise-image-container');
        const exerciseImage = document.getElementById('exercise-image');
        const exerciseInfo = document.getElementById('exercise-info');
        
        // Control buttons
        const prevBtn = document.getElementById('prev-btn');
        const pauseBtn = document.getElementById('pause-btn');
        const nextBtn = document.getElementById('next-btn');
        
        // Set total exercises
        totalExercises.textContent = exercises.length;
        
        // Start the timer
        startTimer();
        
        // Timer function
        function startTimer() {
            clearInterval(timerInterval);
            
            timerInterval = setInterval(() => {
                if (isPaused) return;
                
                timeRemaining--;
                
                // Update timer display
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Update progress bar
                let totalTime;
                if (currentExerciseIndex < 0) {
                    totalTime = 10; // Initial countdown
                } else if (isResting) {
                    totalTime = exercises[currentExerciseIndex].rest;
                } else {
                    totalTime = exercises[currentExerciseIndex].duration;
                }
                
                const progressPercentage = (timeRemaining / totalTime) * 100;
                timerProgressBar.style.width = `${progressPercentage}%`;
                
                // Check if timer is complete
                if (timeRemaining <= 0) {
                    if (currentExerciseIndex < 0) {
                        // Start first exercise
                        nextExercise();
                    } else if (isResting) {
                        // Rest period is over, move to next exercise
                        isResting = false;
                        nextExercise();
                    } else {
                        // Exercise is complete, start rest period if not the last exercise
                        if (currentExerciseIndex < exercises.length - 1) {
                            startRestPeriod();
                        } else {
                            // Workout complete
                            clearInterval(timerInterval);
                            currentExerciseName.textContent = 'Workout Complete!';
                            timerDisplay.textContent = '00:00';
                            timerProgressBar.style.width = '100%';
                            timerProgressBar.classList.remove('bg-warning');
                            timerProgressBar.classList.add('bg-success');
                            nextBtn.disabled = true;
                        }
                    }
                }
            }, 1000);
        }
        
        // Start rest period
        function startRestPeriod() {
            isResting = true;
            timeRemaining = exercises[currentExerciseIndex].rest;
            currentExerciseName.textContent = 'Rest';
            timerProgressBar.classList.remove('bg-primary');
            timerProgressBar.classList.add('bg-warning');
            exerciseInfo.classList.add('d-none');
            exerciseImageContainer.classList.add('d-none');
        }
        
        // Move to next exercise
        function nextExercise() {
            currentExerciseIndex++;
            
            if (currentExerciseIndex >= exercises.length) {
                currentExerciseIndex = exercises.length - 1;
                return;
            }
            
            // Update exercise info
            const exercise = exercises[currentExerciseIndex];
            timeRemaining = exercise.duration;
            currentExerciseName.textContent = exercise.name;
            currentExerciseCount.textContent = currentExerciseIndex + 1;
            
            // Update progress bar
            timerProgressBar.classList.remove('bg-warning');
            timerProgressBar.classList.add('bg-primary');
            
            // Update workout progress
            const progressPercentage = ((currentExerciseIndex + 1) / exercises.length) * 100;
            workoutProgressBar.style.width = `${progressPercentage}%`;
            
            // Update exercise details
            exerciseDescription.textContent = exercise.description;
            exerciseInfo.classList.remove('d-none');
            
            if (exercise.form && exercise.form !== 'null') {
                exerciseFormText.textContent = exercise.form;
                exerciseForm.classList.remove('d-none');
            } else {
                exerciseForm.classList.add('d-none');
            }
            
            if (exercise.tips && exercise.tips !== 'null') {
                exerciseTipsText.textContent = exercise.tips;
                exerciseTips.classList.remove('d-none');
            } else {
                exerciseTips.classList.add('d-none');
            }
            
            // Update image if available
            if (exercise.image && exercise.image !== 'null' && exercise.image !== '') {
                exerciseImage.src = exercise.image;
                exerciseImageContainer.classList.remove('d-none');
            } else {
                exerciseImageContainer.classList.add('d-none');
            }
            
            // Highlight current exercise in list
            exerciseItems.forEach(item => item.classList.remove('active'));
            document.getElementById(`exercise-item-${currentExerciseIndex}`).classList.add('active');
            
            // Update button states
            prevBtn.disabled = currentExerciseIndex === 0;
            nextBtn.disabled = currentExerciseIndex === exercises.length - 1 && !isResting;
        }
        
        // Move to previous exercise
        function prevExercise() {
            if (currentExerciseIndex <= 0) return;
            
            currentExerciseIndex--;
            isResting = false;
            
            // Update exercise info
            const exercise = exercises[currentExerciseIndex];
            timeRemaining = exercise.duration;
            currentExerciseName.textContent = exercise.name;
            currentExerciseCount.textContent = currentExerciseIndex + 1;
            
            // Update progress bar
            timerProgressBar.classList.remove('bg-warning');
            timerProgressBar.classList.add('bg-primary');
            
            // Update workout progress
            const progressPercentage = ((currentExerciseIndex + 1) / exercises.length) * 100;
            workoutProgressBar.style.width = `${progressPercentage}%`;
            
            // Update exercise details
            exerciseDescription.textContent = exercise.description;
            exerciseInfo.classList.remove('d-none');
            
            if (exercise.form && exercise.form !== 'null') {
                exerciseFormText.textContent = exercise.form;
                exerciseForm.classList.remove('d-none');
            } else {
                exerciseForm.classList.add('d-none');
            }
            
            if (exercise.tips && exercise.tips !== 'null') {
                exerciseTipsText.textContent = exercise.tips;
                exerciseTips.classList.remove('d-none');
            } else {
                exerciseTips.classList.add('d-none');
            }
            
            // Update image if available
            if (exercise.image && exercise.image !== 'null' && exercise.image !== '') {
                exerciseImage.src = exercise.image;
                exerciseImageContainer.classList.remove('d-none');
            } else {
                exerciseImageContainer.classList.add('d-none');
            }
            
            // Highlight current exercise in list
            exerciseItems.forEach(item => item.classList.remove('active'));
            document.getElementById(`exercise-item-${currentExerciseIndex}`).classList.add('active');
            
            // Update button states
            prevBtn.disabled = currentExerciseIndex === 0;
            nextBtn.disabled = false;
        }
        
        // Button event listeners
        prevBtn.addEventListener('click', function() {
            prevExercise();
        });
        
        pauseBtn.addEventListener('click', function() {
            isPaused = !isPaused;
            if (isPaused) {
                pauseBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
            } else {
                pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
            }
        });
        
        nextBtn.addEventListener('click', function() {
            if (isResting) {
                isResting = false;
                nextExercise();
            } else if (currentExerciseIndex < exercises.length - 1) {
                startRestPeriod();
            }
        });
        
        // Exercise list item click event
        exerciseItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                currentExerciseIndex = index - 1;
                isResting = false;
                nextExercise();
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>