/**
 * CoreCount Fitness Planner
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle scroll animations
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    
    function checkScroll() {
        animateElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight * 0.85) {
                element.classList.add('visible');
            }
        });
    }
    
    // Check elements on initial load
    checkScroll();
    
    // Check elements on scroll
    window.addEventListener('scroll', checkScroll);
    
    // Ensure video autoplay works on mobile for hero video
    const heroVideo = document.getElementById('hero-video');
    if (heroVideo) {
        heroVideo.play().catch(error => {
            console.log('Autoplay prevented:', error);
            // Add a play button for mobile devices where autoplay is restricted
            const videoBackground = document.querySelector('.video-background');
            const playButton = document.createElement('button');
            playButton.classList.add('video-play-btn');
            playButton.innerHTML = '<i class="fas fa-play"></i>';
            videoBackground.appendChild(playButton);
            
            playButton.addEventListener('click', () => {
                heroVideo.play();
                playButton.style.display = 'none';
            });
        });
    }
    
    // Ensure video autoplay works on mobile for auth pages
    const authBgVideo = document.getElementById('auth-bg-video');
    if (authBgVideo) {
        authBgVideo.play().catch(error => {
            console.log('Auth video autoplay prevented:', error);
            // Add a play button for mobile devices where autoplay is restricted
            const videoBackground = authBgVideo.closest('.video-background');
            const playButton = document.createElement('button');
            playButton.classList.add('video-play-btn');
            playButton.innerHTML = '<i class="fas fa-play"></i>';
            videoBackground.appendChild(playButton);
            
            playButton.addEventListener('click', () => {
                authBgVideo.play();
                playButton.style.display = 'none';
            });
        });
    }
    // Sidebar toggle functionality
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    // Initialize body class based on sidebar state on page load
    if (sidebar && sidebar.classList.contains('collapsed')) {
        document.body.classList.add('sidebar-collapsed');
        // Initialize hamburger icon state based on sidebar state
        const hamburgerIcon = document.querySelector('#sidebarCollapse i');
        if (hamburgerIcon) {
            hamburgerIcon.classList.remove('fa-bars');
            hamburgerIcon.classList.add('fa-times');
        }
    }
    
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            // Always get the sidebar element when the button is clicked
            // This ensures we have the most up-to-date reference
            const sidebarElement = document.getElementById('sidebar');
            
            if (sidebarElement) {
                // Toggle the collapsed class
                sidebarElement.classList.toggle('collapsed');
                
                // Toggle sidebar-collapsed class on body for footer adjustment
                const isCollapsed = sidebarElement.classList.contains('collapsed');
                if (isCollapsed) {
                    document.body.classList.add('sidebar-collapsed');
                    // Toggle hamburger icon to 'times' icon when sidebar is collapsed
                    const hamburgerIcon = this.querySelector('i');
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-bars');
                        hamburgerIcon.classList.add('fa-times');
                    }
                } else {
                    document.body.classList.remove('sidebar-collapsed');
                    // Toggle 'times' icon back to hamburger icon when sidebar is expanded
                    const hamburgerIcon = this.querySelector('i');
                    if (hamburgerIcon) {
                        hamburgerIcon.classList.remove('fa-times');
                        hamburgerIcon.classList.add('fa-bars');
                    }
                }
                
                // Store sidebar state in cookie
                document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;
            } else {
                // Fallback if sidebar element isn't found
                console.log('Sidebar element not found, trying alternative approach');
                // Try to find the sidebar using a different approach
                const sidebarAlt = document.querySelector('#sidebar');
                if (sidebarAlt) {
                    sidebarAlt.classList.toggle('collapsed');
                    
                    // Toggle sidebar-collapsed class on body for footer adjustment
                    const isCollapsed = sidebarAlt.classList.contains('collapsed');
                    if (isCollapsed) {
                        document.body.classList.add('sidebar-collapsed');
                        // Toggle hamburger icon to 'times' icon when sidebar is collapsed
                        const hamburgerIcon = this.querySelector('i');
                        if (hamburgerIcon) {
                            hamburgerIcon.classList.remove('fa-bars');
                            hamburgerIcon.classList.add('fa-times');
                        }
                    } else {
                        document.body.classList.remove('sidebar-collapsed');
                        // Toggle 'times' icon back to hamburger icon when sidebar is expanded
                        const hamburgerIcon = this.querySelector('i');
                        if (hamburgerIcon) {
                            hamburgerIcon.classList.remove('fa-times');
                            hamburgerIcon.classList.add('fa-bars');
                        }
                    }
                    
                    // Store sidebar state in cookie
                    document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;
                }
            }
        });
    }
    
    // Password visibility toggle with enhanced animation
    const togglePassword = document.querySelectorAll('.toggle-password');
    
    if (togglePassword) {
        togglePassword.forEach(function(element) {
            element.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Add a subtle animation to the input field
                passwordInput.classList.add('pulse-animation');
                setTimeout(() => {
                    passwordInput.classList.remove('pulse-animation');
                }, 500);
                
                // Toggle the icon with smooth transition
                if (type === 'text') {
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        });
    }
    
    // Add animation for auth forms
    const authForm = document.querySelector('.auth-body form');
    const formInputs = document.querySelectorAll('.auth-body form .form-control');
    
    if (authForm) {
        // Add staggered animation to form elements
        formInputs.forEach((input, index) => {
            input.style.opacity = '0';
            input.style.transform = 'translateY(20px)';
            input.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            input.style.transitionDelay = `${0.1 + (index * 0.1)}s`;
            
            setTimeout(() => {
                input.style.opacity = '1';
                input.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Add form validation visual feedback
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('input-focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('input-focused');
                
                // Add validation visual feedback
                if (this.value.trim() !== '') {
                    this.classList.add('is-filled');
                } else {
                    this.classList.remove('is-filled');
                }
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
                
                // Prevent scheduling workouts for past dates
                const currentDate = new Date();
                currentDate.setHours(0, 0, 0, 0);
                const selectedDate = new Date(date);
                selectedDate.setHours(0, 0, 0, 0);
                
                if (selectedDate < currentDate) {
                    alert('Cannot schedule workouts for past dates. Please select today or a future date.');
                    return;
                }
                
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