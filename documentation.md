# CoreCount Fitness Application Documentation

## Overview
This documentation provides a comprehensive guide to the PHP files in the CoreCount fitness application and instructions for adding new workouts and exercises to the database.

## PHP Files and Their Functions

### Configuration Files
- **config/config.php**: Contains application-wide configuration settings, constants, and utility functions.
- **config/database.php**: Manages database connection using PDO. Contains the Database class with connection parameters and methods.

### Authentication & User Management
- **signup.php**: Handles new user registration with form validation.
- **login.php**: Manages user authentication and session creation.
- **logout.php**: Terminates user sessions and redirects to the login page.
- **forgot-password.php**: Allows users to request password reset links.
- **reset-password.php**: Processes password reset requests using tokens.
- **change-password.php**: Lets authenticated users change their passwords.
- **profile.php**: Displays and updates user profile information.

### Admin Functions
- **admin_login.php**: Separate login interface for administrators.
- **admin_dashboard.php**: Control panel for site administrators.
- **admin.php**: Additional administrative functions and settings.

### Workout Management
- **categories.php**: Displays workout categories available to users.
- **workout.php**: Shows details for a specific workout including exercises, difficulty, and duration.
- **workout_timer.php**: Provides a timer interface for users during workout sessions.
- **enhanced_workouts.php**: Script to populate the database with comprehensive workout data.
- **insert_workouts.php**: Script to add initial sample workout data to the database.

### Progress Tracking
- **progress.php**: Displays user workout history and achievement statistics.
- **save_workout_progress.php**: Records completed workout data to the user's history.

### Scheduling
- **schedule.php**: Interface for viewing and managing workout schedules.
- **schedule_workout.php**: Adds workouts to a user's personal schedule.
- **remove_scheduled_workout.php**: Removes workouts from a user's schedule.

### Miscellaneous
- **index.php**: The main landing page of the application.
- **contact.php**: Contact form and information.
- **user_messages.php**: Manages user notifications and system messages.
- **fix_database.php**: Utility script for database maintenance and repairs.

### Include Files
- **includes/header.php**: Common header template included across pages.
- **includes/footer.php**: Common footer template included across pages.

## Database Structure

The application uses a MySQL database with the following key tables:

1. **users**: Stores user account information
2. **user_profiles**: Contains detailed user profile data
3. **workout_categories**: Lists workout categories (Cardio, Strength, etc.)
4. **workouts**: Stores individual workout programs
5. **exercises**: Contains exercise details and instructions
6. **workout_exercises**: Junction table connecting workouts to exercises
7. **motivational_quotes**: Stores motivational content

## How to Add New Workouts

### Method 1: Using the Admin Interface
If you have administrator access, you can add workouts through the admin dashboard.

### Method 2: Direct Database Insertion

To add a new workout directly to the database:

1. Connect to your MySQL database using phpMyAdmin or another SQL client
2. Insert a new record into the `workouts` table using the following SQL structure:

```sql
INSERT INTO workouts (category_id, name, description, difficulty_level, duration, calories_burned) VALUES
(category_id_number, 'Workout Name', 'Detailed workout description', 'difficulty_level', duration_in_minutes, estimated_calories);
```

Example:
```sql
INSERT INTO workouts (category_id, name, description, difficulty_level, duration, calories_burned) VALUES
(2, 'Advanced Kettlebell Circuit', 'High-intensity kettlebell workout targeting all major muscle groups', 'advanced', 45, 450);
```

**Note**: The `category_id` must match an existing category in the `workout_categories` table:
- 1: Cardio
- 2: Strength
- 3: Flexibility
- 4: Core
- 5: HIIT

## How to Add Exercises to a Workout

### Step 1: Add the Exercise

First, add the exercise to the `exercises` table if it doesn't already exist:

```sql
INSERT INTO exercises (name, description, form_guidance, tips, duration, rest_period) VALUES
('Exercise Name', 'Exercise description', 'Form guidance instructions', 'Helpful tips', duration_in_seconds, rest_period_in_seconds);
```

Example:
```sql
INSERT INTO exercises (name, description, form_guidance, tips, duration, rest_period) VALUES
('Kettlebell Snatch', 'Explosive full-body exercise using a kettlebell', 'Keep your back straight and core engaged', 'Start with a lighter weight to master form', 40, 20);
```

### Step 2: Connect Exercise to Workout

After adding the exercise, connect it to a specific workout using the `workout_exercises` junction table:

```sql
INSERT INTO workout_exercises (workout_id, exercise_id, exercise_order) VALUES
(workout_id_number, exercise_id_number, order_in_sequence);
```

Example:
```sql
-- Assuming the workout_id for 'Advanced Kettlebell Circuit' is 25 and the exercise_id for 'Kettlebell Snatch' is 30
INSERT INTO workout_exercises (workout_id, exercise_id, exercise_order) VALUES
(25, 30, 1);  -- This makes Kettlebell Snatch the first exercise in the sequence
```

### Finding IDs

To find the correct workout_id and exercise_id values:

```sql
-- Find workout_id
SELECT workout_id, name FROM workouts WHERE name LIKE '%Kettlebell%';

-- Find exercise_id
SELECT exercise_id, name FROM exercises WHERE name LIKE '%Snatch%';
```

## Best Practices

1. Always provide detailed descriptions for workouts and exercises
2. Include proper form guidance to prevent injuries
3. Set appropriate difficulty levels to help users choose suitable workouts
4. Maintain consistent naming conventions
5. Ensure exercise durations and rest periods are realistic

## Troubleshooting

If you encounter issues when adding workouts or exercises:

1. Verify that all required fields are included in your SQL statements
2. Check that foreign key references (category_id, workout_id, exercise_id) exist in their respective tables
3. Ensure unique constraints are not violated (duplicate names, etc.)
4. Use the fix_database.php utility if database integrity issues occur

For additional assistance, contact the system administrator.