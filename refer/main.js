/**
 * CoreCount Fitness Planner
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Store sidebar state in cookie
            const isCollapsed = sidebar.classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;
        });
    }
    
    // Password visibility toggle
    const togglePassword = document.querySelectorAll('.toggle-password');
    
    if (togglePassword) {
        togglePassword.forEach(function(element) {
            element.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Workout timer functionality
    const timerDisplay = document.getElementById('timerDisplay');
    const startTimerBtn = document.getElementById('startTimer');
    const pauseTimerBtn = document.getElementById('pauseTimer');
    const resetTimerBtn = document.getElementById('resetTimer');
    const progressBar = document.querySelector('.progress-bar');
    
    if (timerDisplay && startTimerBtn && pauseTimerBtn && resetTimerBtn) {
        let timer;
        let seconds = 45; // Default exercise time
        let isResting = false;
        let isPaused = false;
        
        // Update timer display
        function updateTimerDisplay() {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            timerDisplay.textContent = `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
            
            // Update progress bar
            const totalTime = isResting ? 15 : 45; // Rest or exercise time
            const percentage = (seconds / totalTime) * 100;
            progressBar.style.width = `${percentage}%`;
        }
        
        // Start timer
        startTimerBtn.addEventListener('click', function() {
            if (isPaused) {
                isPaused = false;
            }
            
            if (!timer) {
                timer = setInterval(function() {
                    if (!isPaused) {
                        seconds--;
                        updateTimerDisplay();
                        
                        if (seconds === 0) {
                            clearInterval(timer);
                            timer = null;
                            
                            if (isResting) {
                                // Switch to exercise
                                isResting = false;
                                seconds = 45;
                                document.querySelector('.exercise-status').textContent = 'Exercise';
                            } else {
                                // Switch to rest
                                isResting = true;
                                seconds = 15;
                                document.querySelector('.exercise-status').textContent = 'Rest';
                            }
                            
                            updateTimerDisplay();
                            startTimerBtn.click(); // Auto-start next phase
                        }
                    }
                }, 1000);
            }
        });
        
        // Pause timer
        pauseTimerBtn.addEventListener('click', function() {
            isPaused = !isPaused;
            this.textContent = isPaused ? 'Resume' : 'Pause';
        });
        
        // Reset timer
        resetTimerBtn.addEventListener('click', function() {
            clearInterval(timer);
            timer = null;
            isResting = false;
            isPaused = false;
            seconds = 45;
            document.querySelector('.exercise-status').textContent = 'Exercise';
            pauseTimerBtn.textContent = 'Pause';
            updateTimerDisplay();
        });
        
        // Initialize timer display
        updateTimerDisplay();
    }
    
    // Drag and drop for schedule
    const draggableWorkouts = document.querySelectorAll('.draggable-workout');
    const dropZones = document.querySelectorAll('.calendar-day');
    
    if (draggableWorkouts.length > 0 && dropZones.length > 0) {
        draggableWorkouts.forEach(function(workout) {
            workout.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', workout.getAttribute('data-workout-id'));
                setTimeout(() => {
                    workout.classList.add('dragging');
                }, 0);
            });
            
            workout.addEventListener('dragend', function() {
                workout.classList.remove('dragging');
            });
        });
        
        dropZones.forEach(function(zone) {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                zone.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', function() {
                zone.classList.remove('dragover');
            });
            
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                zone.classList.remove('dragover');
                
                const workoutId = e.dataTransfer.getData('text/plain');
                const date = zone.getAttribute('data-date');
                
                // AJAX call to save the scheduled workout
                fetch('schedule_workout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `workout_id=${workoutId}&date=${date}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the UI
                        const workoutElement = document.querySelector(`[data-workout-id="${workoutId}"]`);
                        // More robust way to get the workout name
                        let workoutName = '';
                        const nameSpan = workoutElement.querySelector('.d-flex span');
                        if (nameSpan) {
                            workoutName = nameSpan.textContent.trim();
                        } else if (workoutElement.querySelector('span')) {
                            workoutName = workoutElement.querySelector('span').textContent.trim();
                        } else {
                            workoutName = workoutElement.textContent.trim();
                        }
                        
                        // Check if scheduled-workouts container exists, if not create it
                        let scheduledWorkoutsContainer = zone.querySelector('.scheduled-workouts');
                        if (!scheduledWorkoutsContainer) {
                            scheduledWorkoutsContainer = document.createElement('div');
                            scheduledWorkoutsContainer.className = 'scheduled-workouts';
                            zone.appendChild(scheduledWorkoutsContainer);
                            
                            // Add has-workouts class to the day number
                            const dayNumber = zone.querySelector('.day-number');
                            if (dayNumber) {
                                dayNumber.classList.add('has-workouts');
                            }
                        }
                        
                        // Create workout item with category class
                        const workoutItem = document.createElement('div');
                        // Get the category ID from the workout element's data attribute or from the AJAX response
                        let categoryId = data.category_id ? parseInt(data.category_id) : null;
                        
                        // If category ID is not in the AJAX response, try to get it from the workout element
                        if (!categoryId && workoutElement && workoutElement.hasAttribute('data-category-id')) {
                            categoryId = parseInt(workoutElement.getAttribute('data-category-id'));
                        }
                        
                        // If still no category ID, try to get it from the category name
                        if (!categoryId && workoutElement) {
                            const categoryText = workoutElement.querySelector('small.text-muted');
                            if (categoryText) {
                                // Make an educated guess based on category name
                                const categoryName = categoryText.textContent.trim().toLowerCase();
                                if (categoryName.includes('cardio')) categoryId = 1;
                                else if (categoryName.includes('strength')) categoryId = 2;
                                else if (categoryName.includes('flexibility')) categoryId = 3;
                                else if (categoryName.includes('core')) categoryId = 4;
                                else if (categoryName.includes('hiit')) categoryId = 5;
                            }
                        }
                        
                        // Default to category 1 if no category ID was found
                        if (!categoryId) categoryId = 1;
                        
                        workoutItem.className = `workout-item category-${categoryId}`;
                        workoutItem.innerHTML = `
                            <div class="workout-time">${data.time ? data.time.substring(0, 5) : '09:00'}</div>
                            <span class="workout-name">${workoutName}</span>
                            <div class="workout-actions">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="schedule_id" value="${data.schedule_id}">
                                    <button type="submit" name="delete_schedule" class="btn btn-sm btn-danger" title="Remove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        `;
                        
                        // Add the workout item to the scheduled-workouts container
                        scheduledWorkoutsContainer.appendChild(workoutItem);
                        
                        // Add event listener to remove button
                        const deleteButton = workoutItem.querySelector('button[name="delete_schedule"]');
                        if (deleteButton) {
                            deleteButton.addEventListener('click', function(e) {
                                e.preventDefault(); // Prevent form submission
                                const scheduleId = this.closest('form').querySelector('input[name="schedule_id"]').value;
                                removeScheduledWorkout(scheduleId, workoutItem);
                            });
                        }
                    } else {
                        alert('Failed to schedule workout: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while scheduling the workout.');
                });
            });
        });
        
        // Function to remove scheduled workout
        function removeScheduledWorkout(scheduleId, element) {
            fetch('remove_scheduled_workout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `schedule_id=${scheduleId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.remove();
                } else {
                    alert('Failed to remove workout: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the workout.');
            });
        }
    }
    
    // Add existing remove workout event listeners
    document.querySelectorAll('.remove-workout').forEach(function(button) {
        button.addEventListener('click', function() {
            const scheduleId = this.getAttribute('data-schedule-id');
            const workoutItem = this.closest('.scheduled-workout');
            removeScheduledWorkout(scheduleId, workoutItem);
        });
    });
});