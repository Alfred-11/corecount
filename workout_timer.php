<?php
/**
 * Workout Timer Page
 * CoreCount Fitness Planner
 * 
 * This page provides an interactive timer for users to follow along with their workout
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

// Fetch exercises for this workout with all details including form guidance and tips
$exercises_query = "SELECT e.*, we.exercise_order
                   FROM exercises e
                   JOIN workout_exercises we ON e.exercise_id = we.exercise_id
                   WHERE we.workout_id = :workout_id
                   ORDER BY we.exercise_order";
$exercises_stmt = $db->prepare($exercises_query);
$exercises_stmt->bindParam(':workout_id', $workout_id, PDO::PARAM_INT);
$exercises_stmt->execute();
$exercises = $exercises_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user is logged in to enable progress tracking
$user_logged_in = isLoggedIn();

// Set page title
$page_title = "Workout Timer: " . htmlspecialchars($workout['name']) . " - CoreCount";

// Include header
include 'includes/header.php';

// Add custom CSS for tutorial links
echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/tutorial-link.css?v=' . time() . '">';
?>

<main>
    <div class="container-fluid py-4" id="workout-timer-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php">Categories</a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/categories.php?id=<?php echo $workout['category_id']; ?>"><?php echo htmlspecialchars($workout['category_name']); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $workout_id; ?>"><?php echo htmlspecialchars($workout['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Workout Timer</li>
            </ol>
        </nav>

        <!-- Workout Timer Header -->
        <div class="workout-header mb-4 text-center">
            <h1>Workout Timer: <?php echo htmlspecialchars($workout['name']); ?></h1>
            <p class="lead">Follow along with the timer to complete your workout</p>
            
            <?php if (!empty($workout['image_path'])): ?>
                <div class="workout-image-container mb-4 text-center">
                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($workout['image_path']); ?>" alt="<?php echo htmlspecialchars($workout['name']); ?>" class="img-fluid rounded">
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Toggle Button (Initially Hidden) -->
        <div id="sidebar-toggle-container" class="d-none mb-3">
            <button id="toggle-sidebar-btn" class="btn btn-outline-primary">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="row" id="workout-content-row">
            <!-- Left Column: Timer and Progress -->
            <div class="col-lg-6 col-md-12 timer-column">
                <!-- Timer Card -->
                <div class="card mb-4 timer-card" id="sticky-timer">
                    <div class="card-body text-center">
                        <!-- Current Exercise Display -->
                        <div class="current-exercise mb-4">
                            <h3 id="current-exercise-name">Get Ready!</h3>
                            <p id="current-exercise-description" class="text-muted">Your workout will begin shortly</p>
                        </div>
                        
                        <!-- Timer Display -->
                        <div class="timer-display mb-4">
                            <div class="timer-circle">
                                <span id="timer-count">10</span>
                                <small id="timer-label">seconds</small>
                            </div>
                        </div>
                        
                        <!-- Timer Controls -->
                        <div class="timer-controls mb-4 d-flex flex-wrap justify-content-center">
                            <button id="start-timer-btn" class="btn btn-success btn-lg m-2">
                                <i class="fas fa-play"></i> Start Workout
                            </button>
                            <button id="pause-timer-btn" class="btn btn-warning btn-lg m-2 d-none">
                                <i class="fas fa-pause"></i> Pause
                            </button>
                            <button id="resume-timer-btn" class="btn btn-primary btn-lg m-2 d-none">
                                <i class="fas fa-play"></i> Resume
                            </button>
                            <button id="skip-btn" class="btn btn-secondary btn-lg m-2">
                                <i class="fas fa-forward"></i> Skip
                            </button>
                            <button id="restart-btn" class="btn btn-danger btn-lg m-2">
                                <i class="fas fa-redo"></i> Restart
                            </button>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="progress mb-3">
                            <div id="exercise-progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <!-- Workout Progress -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Workout Progress</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="progress-stat-icon bg-primary text-white me-2">
                                                <i class="fas fa-dumbbell"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Current Exercise</h6>
                                                <p class="mb-0 fs-5 fw-bold"><span id="current-exercise-num">0</span> of <span id="total-exercises"><?php echo count($exercises); ?></span></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="progress-stat-icon bg-success text-white me-2">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Workout Time</h6>
                                                <p class="mb-0 fs-5 fw-bold" id="workout-time">00:00</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress-wrapper">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Workout Completion</small>
                                        <small class="text-muted"><span id="progress-percentage">0</span>% Complete</small>
                                    </div>
                                    <div class="progress progress-lg">
                                        <div id="workout-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Workout Timer Complete Message -->
                        <div id="workout-complete-message" class="alert alert-success d-none">
                            <i class="fas fa-check-circle"></i> Workout Complete! Great job!
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Exercise Guide and Sequence -->
            <div class="col-lg-6 col-md-12 exercise-column">
                <!-- Exercise Guide Section (initially hidden) -->
                <div id="exercise-guide" class="exercise-guide mb-4 d-none">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Exercise Guide</h5>
                        </div>
                        <div class="card-body">
                            <!-- YouTube Tutorial Link -->
                            <div id="tutorial-link" class="tutorial-link-container mb-3">
                                <!-- Tutorial link will be inserted here via JavaScript -->
                            </div>
                            
                            <!-- Exercise Image/Video -->
                            <div id="exercise-media" class="text-center mb-3">
                                <!-- Image will be inserted here via JavaScript -->
                            </div>
                            
                            <!-- Form Guidance -->
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="fas fa-check-circle"></i> Proper Form</h6>
                                <p id="exercise-form-guidance">Form guidance will appear here</p>
                            </div>
                            
                            <!-- Tips -->
                            <div>
                                <h6 class="text-primary"><i class="fas fa-lightbulb"></i> Tips</h6>
                                <p id="exercise-tips">Tips will appear here</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exercise List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Exercise Sequence</h5>
                        <span class="badge bg-primary"><?php echo count($exercises); ?> exercises</span>
                    </div>
                    <div class="card-body">
                        <div class="exercise-list">
                            <?php foreach ($exercises as $index => $exercise): ?>
                                <div class="exercise-item" data-exercise-id="<?php echo $exercise['exercise_id']; ?>">
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
                                    <div class="exercise-details mt-2 collapse" id="exercise-details-<?php echo $exercise['exercise_id']; ?>">
                                        <?php if (!empty($exercise['image_path'])): ?>
                                            <div class="exercise-image mb-2">
                                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($exercise['image_path']); ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?>" class="img-fluid rounded" style="max-height: 150px; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($exercise['description'])): ?>
                                            <p class="exercise-description"><?php echo htmlspecialchars($exercise['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($exercise['form_guidance'])): ?>
                                            <div class="form-guidance mb-2">
                                                <h6 class="text-primary"><i class="fas fa-check-circle"></i> Proper Form</h6>
                                                <p><?php echo htmlspecialchars($exercise['form_guidance']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($exercise['tips'])): ?>
                                            <div class="exercise-tips">
                                                <h6 class="text-primary"><i class="fas fa-lightbulb"></i> Tips</h6>
                                                <p><?php echo htmlspecialchars($exercise['tips']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary mt-2 toggle-details" type="button" data-bs-toggle="collapse" data-bs-target="#exercise-details-<?php echo $exercise['exercise_id']; ?>" aria-expanded="false">
                                        <span class="show-text">Show Details</span>
                                        <span class="hide-text d-none">Hide Details</span>
                                    </button>
                                </div>
                                <?php if ($index < count($exercises) - 1): ?>
                                    <hr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get DOM elements
        const startBtn = document.getElementById('start-timer-btn');
        const pauseBtn = document.getElementById('pause-timer-btn');
        const resumeBtn = document.getElementById('resume-timer-btn');
        const skipBtn = document.getElementById('skip-btn');
        const restartBtn = document.getElementById('restart-btn');
        const timerCount = document.getElementById('timer-count');
        const timerLabel = document.getElementById('timer-label');
        const currentExerciseName = document.getElementById('current-exercise-name');
        const currentExerciseDesc = document.getElementById('current-exercise-description');
        const currentExerciseNum = document.getElementById('current-exercise-num');
        const totalExercises = document.getElementById('total-exercises');
        const workoutTime = document.getElementById('workout-time');
        const exerciseProgressBar = document.getElementById('exercise-progress-bar');
        const workoutProgressBar = document.getElementById('workout-progress-bar');
        
        // Voice clips for workout
        const getReadyAudio = new Audio('voice/get ready.mp3');
        const workoutStartedAudio = new Audio('voice/workout started.mp3');
        const takeRestAudio = new Audio('voice/take rest.mp3');
        const workoutCompletedAudio = new Audio('voice/workout completed.mp3');
        
        // Function to play voice clips
        function playVoiceClip(audioElement) {
            // Reset any currently playing audio
            getReadyAudio.pause();
            getReadyAudio.currentTime = 0;
            workoutStartedAudio.pause();
            workoutStartedAudio.currentTime = 0;
            takeRestAudio.pause();
            takeRestAudio.currentTime = 0;
            workoutCompletedAudio.pause();
            workoutCompletedAudio.currentTime = 0;
            
            // Play the requested audio
            audioElement.play().catch(error => {
                console.log('Audio playback prevented:', error);
            });
        }
        
        // User login status from PHP
        const isLoggedIn = <?php echo $user_logged_in ? 'true' : 'false'; ?>;
        
        // Exercise data from PHP
        const exercises = <?php echo json_encode($exercises); ?>;
        
        // Timer variables
        let timer;
        let currentTime = 10; // Countdown time
        let currentExerciseIndex = -1; // Start with countdown
        let isResting = false;
        let isPaused = false;
        let totalWorkoutTime = 0;
        let workoutTimer;
        
        // Initialize workout timer
        function startWorkoutTimer() {
            workoutTimer = setInterval(() => {
                if (!isPaused) {
                    totalWorkoutTime++;
                    updateWorkoutTime();
                }
            }, 1000);
        }
        
        // Update workout time display
        function updateWorkoutTime() {
            const minutes = Math.floor(totalWorkoutTime / 60);
            const seconds = totalWorkoutTime % 60;
            workoutTime.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Start the workout
        function startWorkout() {
            startBtn.classList.add('d-none');
            pauseBtn.classList.remove('d-none');
            
            // Hide sidebar for distraction-free workout view
            // Use the existing sidebar collapsed functionality
            const sidebar = document.getElementById('sidebar');
            if (!sidebar.classList.contains('collapsed')) {
                document.getElementById('sidebarCollapse').click();
            }
            
            // Show the sidebar toggle button
            document.getElementById('sidebar-toggle-container').classList.remove('d-none');
            
            // Play 'Get Ready' voice clip
            playVoiceClip(getReadyAudio);
            
            // Start the countdown
            startTimer();
            startWorkoutTimer();
        }
        
        // Start the timer
        function startTimer() {
            timer = setInterval(() => {
                if (!isPaused) {
                    currentTime--;
                    timerCount.textContent = currentTime;
                    
                    // Update progress bar
                    updateExerciseProgress();
                    
                    if (currentTime <= 0) {
                        clearInterval(timer);
                        
                        if (currentExerciseIndex === -1) {
                            // Countdown finished, start first exercise
                            currentExerciseIndex = 0;
                            startExercise();
                        } else if (isResting) {
                            // Rest period finished, move to next exercise
                            isResting = false;
                            currentExerciseIndex++;
                            
                            if (currentExerciseIndex < exercises.length) {
                                startExercise();
                            } else {
                                // Workout complete
                                completeWorkout();
                            }
                        } else {
                            // Exercise finished, start rest period if available
                            const currentExercise = exercises[currentExerciseIndex];
                            // Check if this is the last exercise - if so, don't start rest period
                            if (currentExercise.rest_period > 0 && currentExerciseIndex < exercises.length - 1) {
                                startRest();
                            } else {
                                // No rest period, move to next exercise
                                currentExerciseIndex++;
                                
                                if (currentExerciseIndex < exercises.length) {
                                    startExercise();
                                } else {
                                    // Workout complete
                                    completeWorkout();
                                }
                            }
                        }
                    }
                }
            }, 1000);
        }
        
        // Start an exercise
        function startExercise() {
            const exercise = exercises[currentExerciseIndex];
            currentExerciseName.textContent = exercise.name;
            currentExerciseDesc.textContent = exercise.description || 'Focus on proper form';
            currentExerciseNum.textContent = currentExerciseIndex + 1;
            
            currentTime = exercise.duration;
            timerCount.textContent = currentTime;
            timerLabel.textContent = 'seconds';
            
            // Update progress
            updateWorkoutProgress();
            
            // Highlight current exercise in the list
            highlightCurrentExercise();
            
            // Show exercise guide with form guidance, tips, and image
            showExerciseGuide(exercise);
            
            // Play 'Workout Started' voice clip
            playVoiceClip(workoutStartedAudio);
            
            // Start the timer
            startTimer();
        }
        
        // Start a rest period
        function startRest() {
            const exercise = exercises[currentExerciseIndex];
            isResting = true;
            currentExerciseName.textContent = 'Rest';
            currentExerciseDesc.textContent = `Get ready for ${currentExerciseIndex + 1 < exercises.length ? exercises[currentExerciseIndex + 1].name : 'the end'}`;
            
            currentTime = exercise.rest_period;
            timerCount.textContent = currentTime;
            timerLabel.textContent = 'rest';
            
            // Keep showing the current exercise guide during rest
            // This allows users to review the next exercise during rest
            if (currentExerciseIndex + 1 < exercises.length) {
                showExerciseGuide(exercises[currentExerciseIndex + 1]);
            }
            
            // Play 'Take Rest' voice clip
            playVoiceClip(takeRestAudio);
            
            // Start the timer
            startTimer();
        }
        
        // Complete the workout
        function completeWorkout() {
            clearInterval(timer);
            clearInterval(workoutTimer);
            
            currentExerciseName.textContent = 'Workout Complete!';
            currentExerciseDesc.textContent = 'Great job! You have completed the workout.';
            timerCount.textContent = '✓';
            timerLabel.textContent = 'done';
            
            pauseBtn.classList.add('d-none');
            resumeBtn.classList.add('d-none');
            
            // Option to restore sidebar when workout is complete
            // Uncomment the line below if you want the sidebar to return after workout completion
            // document.body.classList.remove('workout-mode');
            
            // Play 'Workout Completed' voice clip
            playVoiceClip(workoutCompletedAudio);
            
            // Update progress bars to 100%
            exerciseProgressBar.style.width = '100%';
            exerciseProgressBar.setAttribute('aria-valuenow', 100);
            
            workoutProgressBar.style.width = '100%';
            workoutProgressBar.setAttribute('aria-valuenow', 100);
            workoutProgressBar.className = 'progress-bar progress-bar-striped bg-success';
            
            // Update progress percentage text
            document.getElementById('progress-percentage').textContent = '100';
            
            // Show workout complete message
            document.getElementById('workout-complete-message').classList.remove('d-none');
            
            // Hide exercise guide
            document.getElementById('exercise-guide').classList.add('d-none');
            
            // Save workout progress to database
            if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
                // Calculate calories burned based on workout duration and intensity
                const workoutDurationMinutes = Math.ceil(totalWorkoutTime / 60);
                // Calculate calories burned based on duration and workout difficulty
                let intensityFactor = 1;
                switch ('<?php echo $workout['difficulty_level']; ?>') {
                    case 'beginner':
                        intensityFactor = 0.8;
                        break;
                    case 'intermediate':
                        intensityFactor = 1;
                        break;
                    case 'advanced':
                        intensityFactor = 1.2;
                        break;
                    default:
                        intensityFactor = 1;
                }
                
                // Calculate calories burned with a more dynamic formula
                const baseCalories = <?php echo $workout['calories_burned']; ?>;
                const actualCaloriesBurned = Math.round(baseCalories * (workoutDurationMinutes / <?php echo $workout['duration']; ?>) * intensityFactor);
                
                // Display calories burned in the workout complete message
                document.getElementById('workout-complete-message').innerHTML = 
                    `<i class="fas fa-check-circle"></i> Workout Complete! Great job! <br>
                     <span class="mt-2 d-block">You burned approximately <strong>${actualCaloriesBurned}</strong> calories!</span>`;
                
                // Send AJAX request to save progress
                fetch('save_workout_progress.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `workout_id=${<?php echo $workout_id; ?>}&duration=${workoutDurationMinutes}&calories_burned=${actualCaloriesBurned}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Workout progress saved successfully');
                    } else {
                        console.error('Failed to save workout progress');
                    }
                })
                .catch(error => {
                    console.error('Error saving workout progress:', error);
                });
            }
            
            // Stay on the timer page (don't redirect)
            return false;
        }
        
        // Update exercise progress bar
        function updateExerciseProgress() {
            if (currentExerciseIndex === -1) {
                // Countdown progress
                const countdownTotal = 10;
                const progressPercentage = ((countdownTotal - currentTime) / countdownTotal) * 100;
                exerciseProgressBar.style.width = `${progressPercentage}%`;
            } else if (isResting) {
                // Rest progress
                const restTotal = exercises[currentExerciseIndex].rest_period;
                const progressPercentage = ((restTotal - currentTime) / restTotal) * 100;
                exerciseProgressBar.style.width = `${progressPercentage}%`;
            } else {
                // Exercise progress
                const exerciseTotal = exercises[currentExerciseIndex].duration;
                const progressPercentage = ((exerciseTotal - currentTime) / exerciseTotal) * 100;
                exerciseProgressBar.style.width = `${progressPercentage}%`;
            }
        }
        
        // Update workout progress bar
        function updateWorkoutProgress() {
            const progressPercentage = ((currentExerciseIndex + 1) / exercises.length) * 100;
            const roundedPercentage = Math.round(progressPercentage);
            
            // Update progress bar width
            workoutProgressBar.style.width = `${progressPercentage}%`;
            workoutProgressBar.setAttribute('aria-valuenow', roundedPercentage);
            
            // Update percentage text
            document.getElementById('progress-percentage').textContent = roundedPercentage;
            
            // Update progress bar color based on completion percentage
            if (progressPercentage < 25) {
                workoutProgressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-info';
            } else if (progressPercentage < 50) {
                workoutProgressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
            } else if (progressPercentage < 75) {
                workoutProgressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
            } else {
                workoutProgressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
            }
        }
        
        // Highlight current exercise in the list and move it to the top
        function highlightCurrentExercise() {
            // Remove highlight from all exercises
            document.querySelectorAll('.exercise-item').forEach(item => {
                item.classList.remove('active-exercise');
            });
            
            // Add highlight to current exercise
            if (currentExerciseIndex >= 0 && currentExerciseIndex < exercises.length) {
                const exerciseItems = document.querySelectorAll('.exercise-item');
                if (exerciseItems[currentExerciseIndex]) {
                    exerciseItems[currentExerciseIndex].classList.add('active-exercise');
                    
                    // Scroll the exercise into view without affecting the timer position
                    // Use a slight delay to ensure the exercise guide is updated first
                    setTimeout(() => {
                        exerciseItems[currentExerciseIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                    
                    // Move current exercise to the top of the list
                    moveCurrentExerciseToTop();
                }
            }
        }
        
        // Move current exercise to the top of the exercise list
        function moveCurrentExerciseToTop() {
            if (currentExerciseIndex > 0 && currentExerciseIndex < exercises.length) {
                const exerciseList = document.querySelector('.exercise-list');
                const exerciseItems = document.querySelectorAll('.exercise-item');
                
                // Get the current exercise element
                const currentExercise = exerciseItems[currentExerciseIndex];
                
                // Get the separator after the current exercise (if it exists)
                let separator = currentExercise.nextElementSibling;
                if (separator && separator.tagName === 'HR') {
                    // Move the current exercise to the top of the list
                    exerciseList.insertBefore(currentExercise, exerciseList.firstChild);
                    
                    // Move the separator to follow the current exercise
                    exerciseList.insertBefore(separator, exerciseList.childNodes[1]);
                } else {
                    // Just move the current exercise to the top if there's no separator
                    exerciseList.insertBefore(currentExercise, exerciseList.firstChild);
                }
            }
        }
        
        // Skip to next exercise
        function skipToNext() {
            clearInterval(timer);
            
            if (currentExerciseIndex === -1) {
                // Skip countdown
                currentExerciseIndex = 0;
                startExercise();
            } else if (isResting) {
                // Skip rest
                isResting = false;
                currentExerciseIndex++;
                
                if (currentExerciseIndex < exercises.length) {
                    startExercise();
                } else {
                    completeWorkout();
                }
            } else {
                // Skip exercise
                const currentExercise = exercises[currentExerciseIndex];
                // Check if this is the last exercise - if so, don't start rest period
                if (currentExercise.rest_period > 0 && currentExerciseIndex < exercises.length - 1) {
                    startRest();
                } else {
                    currentExerciseIndex++;
                    
                    if (currentExerciseIndex < exercises.length) {
                        startExercise();
                    } else {
                        completeWorkout();
                    }
                }
            }
            
            // Prevent default navigation
            return false;
        }
        
        // Restart the workout
        function restartWorkout() {
            clearInterval(timer);
            clearInterval(workoutTimer);
            
            // Reset variables
            currentTime = 10;
            currentExerciseIndex = -1;
            isResting = false;
            isPaused = false;
            totalWorkoutTime = 0;
            
            // Ensure we're still in workout mode (sidebar hidden)
            document.body.classList.add('workout-mode');
            
            // Reset UI
            timerCount.textContent = currentTime;
            timerLabel.textContent = 'seconds';
            currentExerciseName.textContent = 'Get Ready!';
            currentExerciseDesc.textContent = 'Your workout will begin shortly';
            currentExerciseNum.textContent = '0';
            workoutTime.textContent = '00:00';
            exerciseProgressBar.style.width = '0%';
            exerciseProgressBar.setAttribute('aria-valuenow', 0);
            workoutProgressBar.style.width = '0%';
            workoutProgressBar.setAttribute('aria-valuenow', 0);
            workoutProgressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
            document.getElementById('progress-percentage').textContent = '0';
            
            // Reset buttons
            startBtn.classList.remove('d-none');
            pauseBtn.classList.add('d-none');
            resumeBtn.classList.add('d-none');
            
            // Hide exercise guide
            document.getElementById('exercise-guide').classList.add('d-none');
            
            // Hide workout complete message if visible
            document.getElementById('workout-complete-message').classList.add('d-none');
            
            // Remove highlight from all exercises
            document.querySelectorAll('.exercise-item').forEach(item => {
                item.classList.remove('active-exercise');
            });
            
            // Stay on the timer page
            return false;
        }
        
        // Pause the workout
        function pauseWorkout() {
            isPaused = true;
            pauseBtn.classList.add('d-none');
            resumeBtn.classList.remove('d-none');
        }
        
        // Resume the workout
        function resumeWorkout() {
            isPaused = false;
            resumeBtn.classList.add('d-none');
            pauseBtn.classList.remove('d-none');
        }
        
        // Function to get YouTube tutorial link based on workout category
        function getYouTubeTutorialLink(categoryId, exerciseName) {
            // Map of category IDs to appropriate YouTube tutorial links
            const tutorialLinks = {
                // Cardio workouts (category_id: 1)
                1: {
                    default: 'https://www.youtube.com/watch?v=PvEnWsPrL4w', // Cardio workout for beginners
                    keywords: {
                        'running': 'https://www.youtube.com/watch?v=kpS5riDAZQc',
                        'jogging': 'https://www.youtube.com/watch?v=kpS5riDAZQc',
                        'cycling': 'https://www.youtube.com/watch?v=ewC5ggHvv50',
                        'jumping': 'https://www.youtube.com/watch?v=1b98WrRrmUs'
                    }
                },
                // Strength workouts (category_id: 2)
                2: {
                    default: 'https://www.youtube.com/watch?v=U0bhE67HuDY', // Strength training basics
                    keywords: {
                        'push': 'https://www.youtube.com/watch?v=IODxDxX7oi4',
                        'pull': 'https://www.youtube.com/watch?v=vT4GlCF3dIQ',
                        'squat': 'https://www.youtube.com/watch?v=YaXPRqUwItQ',
                        'deadlift': 'https://www.youtube.com/watch?v=hCDzSR6bW10'
                    }
                },
                // Flexibility workouts (category_id: 3)
                3: {
                    default: 'https://www.youtube.com/watch?v=qULTwquOuT4', // Full body stretching
                    keywords: {
                        'yoga': 'https://www.youtube.com/watch?v=v7AYKMP6rOE',
                        'stretch': 'https://www.youtube.com/watch?v=sTANio_2E0Q',
                        'mobility': 'https://www.youtube.com/watch?v=TSIbzfcnv_8'
                    }
                },
                // Core workouts (category_id: 4)
                4: {
                    default: 'https://www.youtube.com/watch?v=Ehy8G39d_PM', // Core strengthening
                    keywords: {
                        'abs': 'https://www.youtube.com/watch?v=DHD1-2P94DI',
                        'plank': 'https://www.youtube.com/watch?v=pSHjTRCQxIw',
                        'crunch': 'https://www.youtube.com/watch?v=4hmQA3snTyk'
                    }
                },
                // HIIT workouts (category_id: 5)
                5: {
                    default: 'https://www.youtube.com/watch?v=ml6cT4AZdqI', // HIIT workout tutorial
                    keywords: {
                        'interval': 'https://www.youtube.com/watch?v=Mvo2snJGhtM',
                        'tabata': 'https://www.youtube.com/watch?v=XIeCMhNWFQQ',
                        'circuit': 'https://www.youtube.com/watch?v=CBWQGb4LyAM'
                    }
                }
            };
            
            // Get the category tutorial links
            const categoryLinks = tutorialLinks[categoryId] || tutorialLinks[1]; // Default to cardio if category not found
            
            // Check if exercise name contains any keywords
            if (exerciseName) {
                const lowerName = exerciseName.toLowerCase();
                for (const [keyword, link] of Object.entries(categoryLinks.keywords)) {
                    if (lowerName.includes(keyword.toLowerCase())) {
                        return link;
                    }
                }
            }
            
            // Return default link for the category if no keyword match
            return categoryLinks.default;
        }
        
        // Function to show exercise guide with form guidance, tips, and image
        function showExerciseGuide(exercise) {
            const exerciseGuide = document.getElementById('exercise-guide');
            const tutorialLinkContainer = document.getElementById('tutorial-link');
            const exerciseMedia = document.getElementById('exercise-media');
            const formGuidance = document.getElementById('exercise-form-guidance');
            const tips = document.getElementById('exercise-tips');
            
            // Show the exercise guide section
            exerciseGuide.classList.remove('d-none');
            
            // Set form guidance and tips
            formGuidance.textContent = exercise.form_guidance || 'Maintain proper form throughout the exercise.';
            tips.textContent = exercise.tips || 'Focus on controlled movements and proper breathing.';
            
            // Add YouTube tutorial link based on workout category
            const categoryId = <?php echo $workout['category_id']; ?>;
            const tutorialLink = getYouTubeTutorialLink(categoryId, exercise.name);
            
            tutorialLinkContainer.innerHTML = `
                <div class="tutorial-link-header">
                    <i class="fab fa-youtube"></i>
                    <h6>Learn how to perform this exercise correctly</h6>
                </div>
                <a href="${tutorialLink}" target="_blank" class="tutorial-link-btn">
                    <i class="fas fa-play-circle"></i> Watch Tutorial
                </a>
            `;
            
            // Set exercise image if available
            exerciseMedia.innerHTML = '';
            if (exercise.image_path) {
                const img = document.createElement('img');
                img.src = '<?php echo SITE_URL; ?>/' + exercise.image_path;
                img.alt = exercise.name;
                img.className = 'img-fluid rounded mb-2';
                exerciseMedia.appendChild(img);
            } else {
                // Default image or message if no image is available
                exerciseMedia.innerHTML = '<div class="alert alert-info">Focus on following the form guidance below</div>';
            }
        }
        
        // Event listeners
        startBtn.addEventListener('click', startWorkout);
        pauseBtn.addEventListener('click', pauseWorkout);
        resumeBtn.addEventListener('click', resumeWorkout);
        skipBtn.addEventListener('click', function(e) {
            e.preventDefault();
            skipToNext();
            return false;
        });
        restartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            restartWorkout();
            return false;
        });
        
        // Sidebar toggle functionality
        const toggleSidebarBtn = document.getElementById('toggle-sidebar-btn');
        if (toggleSidebarBtn) {
            toggleSidebarBtn.addEventListener('click', function() {
                // Use the existing sidebarCollapse button to toggle the sidebar
                const sidebarCollapseBtn = document.getElementById('sidebarCollapse');
                if (sidebarCollapseBtn) {
                    sidebarCollapseBtn.click();
                }
            });
        }
        
        // Toggle exercise details buttons
        document.querySelectorAll('.toggle-details').forEach(button => {
            button.addEventListener('click', function() {
                const showText = this.querySelector('.show-text');
                const hideText = this.querySelector('.hide-text');
                
                // Check if the target collapse is expanded after the click
                const targetId = this.getAttribute('data-bs-target');
                const isExpanded = document.querySelector(targetId).classList.contains('show');
                
                if (isExpanded) {
                    showText.classList.add('d-none');
                    hideText.classList.remove('d-none');
                } else {
                    showText.classList.remove('d-none');
                    hideText.classList.add('d-none');
                }
            });
        });}
    );
</script>

<style>
    /* Timer Styles */
    .timer-card {
        background-color: var(--e-global-color-3266181);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Sticky Timer */
    #sticky-timer {
        position: sticky;
        top: 20px;
        z-index: 100;
    }
    
    /* Sidebar toggle button */
    #sidebar-toggle-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1000;
    }
    
    /* Distraction-free mode styles - use existing sidebar collapsed functionality */
    #sidebar.collapsed ~ #content #sidebar-toggle-container {
        display: block !important;
    }
    
    /* Hide the toggle button when sidebar is visible */
    #sidebar:not(.collapsed) ~ #content #sidebar-toggle-container {
        display: none !important;
    }
    
    /* Hide the main sidebar toggle when in workout timer page */
    .navbar #sidebarCollapse {
        display: none;
    }
    
    /* Workout layout styles */
    #workout-timer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding-top: 20px;
    }
    
    #workout-content-row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .timer-column, .exercise-column {
        padding: 0 15px;
    }
    
    .timer-circle {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        background-color: var(--e-global-color-primary);
        color: var(--e-global-color-3266181);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    #timer-count {
        font-size: 4rem;
        font-weight: 700;
        line-height: 1;
    }
    
    #timer-label {
        font-size: 1rem;
        text-transform: uppercase;
        opacity: 0.8;
    }
    
    .timer-controls button {
        margin: 0 5px 10px;
    }
    
    .exercise-item {
        padding: 15px;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }
    
    .active-exercise {
        background-color: var(--e-global-color-6f12657);
        border-left: 4px solid var(--e-global-color-secondary);
    }
    
    /* Progress Bar Styles */
    .progress {
        height: 10px;
        border-radius: 5px;
        background-color: var(--e-global-color-31495be);
        overflow: hidden;
    }
    
    .progress-lg {
        height: 15px;
    }
    
    #exercise-progress-bar {
        background-color: var(--e-global-color-accent);
        transition: width 0.5s ease;
    }
    
    #workout-progress-bar {
        transition: width 0.5s ease, background-color 0.5s ease;
    }
    
    .progress-wrapper {
        margin-top: 10px;
    }
    
    .progress-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    /* Exercise Guide Styles */
    .exercise-guide {
        transition: all 0.3s ease;
    }
    
    #exercise-media img {
        max-height: 250px;
        object-fit: contain;
        border: 1px solid #ddd;
    }
    
    .exercise-guide h6 {
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .exercise-guide p {
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    /* Responsive styles */
    @media (max-width: 991.98px) {
        .timer-column, .exercise-column {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .exercise-column {
            margin-top: 20px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>