-- CoreCount Fitness Planner Database Setup

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS corecount;

-- Use the database
USE corecount;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User profiles table
CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,  -- Added UNIQUE constraint to prevent duplicate profiles
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    age INT,
    gender ENUM('male', 'female', 'other'),
    weight DECIMAL(5,2),  -- in kg
    height DECIMAL(5,2),  -- in cm
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Workout categories table
CREATE TABLE IF NOT EXISTS workout_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,  -- Added UNIQUE constraint to prevent duplicate categories
    description TEXT,
    -- image_path column has been removed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workouts table
CREATE TABLE IF NOT EXISTS workouts (
    workout_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced'),
    duration INT,  -- in minutes
    calories_burned INT,  -- estimated calories
    -- image_path column has been removed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES workout_categories(category_id) ON DELETE CASCADE
);

-- Exercises table
CREATE TABLE IF NOT EXISTS exercises (
    exercise_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    form_guidance TEXT,
    tips TEXT,
    duration INT DEFAULT 45,  -- in seconds
    rest_period INT DEFAULT 15,  -- in seconds
    -- image_path column has been removed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Workout exercises (junction table)
CREATE TABLE IF NOT EXISTS workout_exercises (
    workout_id INT NOT NULL,
    exercise_id INT NOT NULL,
    exercise_order INT NOT NULL,  -- order of exercises in workout
    PRIMARY KEY (workout_id, exercise_id),
    FOREIGN KEY (workout_id) REFERENCES workouts(workout_id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE
);

-- User workout progress table
CREATE TABLE IF NOT EXISTS user_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_id INT NOT NULL,
    completion_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    duration INT,  -- actual duration in minutes
    calories_burned INT,
    -- notes column has been removed
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(workout_id) ON DELETE CASCADE
);

-- Workout schedules table
CREATE TABLE IF NOT EXISTS workout_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    workout_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    -- notification column has been removed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (workout_id) REFERENCES workouts(workout_id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    message_type VARCHAR(50),
    -- isread column has been removed
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fitness articles table has been completely removed as it is no longer needed
-- Previously contained: article_id, title, content, author, publication_date, image_path, etc.

-- Motivational quotes table
CREATE TABLE IF NOT EXISTS motivational_quotes (
    quote_id INT AUTO_INCREMENT PRIMARY KEY,
    quote_text TEXT NOT NULL,
    author VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clear existing data to prevent duplicates
TRUNCATE TABLE workout_exercises;
TRUNCATE TABLE workouts;
TRUNCATE TABLE workout_categories;
TRUNCATE TABLE exercises;
TRUNCATE TABLE motivational_quotes;

-- Insert default workout categories
INSERT INTO workout_categories (name, description) VALUES
('Cardio', 'Exercises that raise your heart rate and improve cardiovascular health'),
('Strength', 'Exercises focused on building muscle strength and endurance'),
('Flexibility', 'Exercises that improve range of motion and prevent injury'),
('Core', 'Exercises targeting the abdominal and lower back muscles'),
('HIIT', 'High-Intensity Interval Training for maximum calorie burn');

-- Insert sample workouts
INSERT INTO workouts (category_id, name, description, difficulty_level, duration, calories_burned) VALUES
-- Cardio workouts
(1, 'Beginner Running Program', 'A gentle introduction to running designed for beginners. Alternates between walking and jogging intervals to build endurance gradually.', 'beginner', 30, 250),
(1, 'Interval Sprint Training', 'High-intensity interval training with sprints to maximize calorie burn and improve cardiovascular fitness.', 'advanced', 25, 400),
(1, 'Steady State Cardio', 'Maintain a consistent pace for an extended period to build aerobic endurance and improve heart health.', 'intermediate', 45, 350),
(1, '30-Minute Treadmill Intervals', 'Alternating between walking, jogging, and running on a treadmill to improve cardiovascular endurance and burn calories.', 'intermediate', 30, 300),
(1, 'Jump Rope Cardio Blast', 'High-intensity jump rope intervals that improve coordination, agility, and cardiovascular fitness.', 'intermediate', 20, 250),

-- Strength workouts
(2, 'Full Body Strength Circuit', 'A comprehensive workout targeting all major muscle groups through a series of compound exercises.', 'intermediate', 40, 320),
(2, 'Upper Body Focus', 'Concentrate on developing strength in your chest, back, shoulders, and arms with this targeted routine.', 'intermediate', 35, 280),
(2, 'Lower Body Power', 'Build strength and power in your legs, glutes, and core with this challenging lower body workout.', 'advanced', 35, 300),
(2, 'Bodyweight Strength Circuit', 'No-equipment strength workout using only bodyweight exercises like push-ups, squats, and lunges.', 'beginner', 30, 250),
(2, 'Dumbbell Total Body', 'Comprehensive strength workout using dumbbells to target all major muscle groups.', 'intermediate', 45, 320),

-- Flexibility workouts
(3, 'Beginner Yoga Flow', 'A gentle introduction to yoga poses and breathing techniques to improve flexibility and reduce stress.', 'beginner', 30, 150),
(3, 'Dynamic Stretching Routine', 'Active stretches that mimic sport-specific movements to prepare your body for activity and improve range of motion.', 'beginner', 20, 120),
(3, 'Advanced Flexibility Training', 'Deep stretching techniques to significantly improve flexibility and joint mobility for those with an established practice.', 'advanced', 40, 200),
(3, 'Power Yoga Flow', 'Dynamic yoga sequence that builds strength, flexibility, and balance through flowing movements.', 'intermediate', 45, 250),
(3, 'Full Body Stretch Routine', 'Comprehensive stretching session targeting all major muscle groups to improve overall flexibility.', 'beginner', 25, 120),

-- Core workouts
(4, 'Core Fundamentals', 'Master the basics of core training with this foundational workout focusing on proper form and technique.', 'beginner', 20, 150),
(4, 'Ab Circuit Challenge', 'A fast-paced circuit of core exercises designed to target all areas of your abdominals and obliques.', 'intermediate', 25, 200),
(4, 'Pilates Core Workout', 'Strengthen your deep core muscles with controlled movements inspired by Pilates techniques.', 'intermediate', 30, 180),
(4, 'Six-Pack Abs Circuit', 'Targeted abdominal workout designed to strengthen and define all areas of the core.', 'intermediate', 20, 180),
(4, 'Plank Challenge', 'Series of plank variations to build core stability, endurance, and strength.', 'intermediate', 15, 120),

-- HIIT workouts
(5, 'Beginner HIIT', 'An introduction to high-intensity interval training with modified exercises and longer rest periods.', 'beginner', 20, 200),
(5, 'Tabata Inferno', 'Ultra-intense 20-second work intervals with 10-second rest periods to maximize calorie burn and improve conditioning.', 'advanced', 25, 400),
(5, 'Total Body HIIT', 'A comprehensive high-intensity workout that challenges every muscle group for complete fitness development.', 'intermediate', 30, 350),
(5, 'Bodyweight HIIT Blast', 'Equipment-free high-intensity interval training using only bodyweight exercises.', 'intermediate', 25, 300),
(5, 'Dumbbell HIIT Circuit', 'Full-body high-intensity workout using dumbbells to add resistance to classic HIIT movements.', 'intermediate', 30, 320);

-- Insert sample exercises
INSERT INTO exercises (name, description, form_guidance, tips, duration, rest_period) VALUES
-- Cardio exercises
('High Knees', 'Run in place while bringing your knees up to hip level', 'Keep your core engaged and maintain an upright posture', 'Focus on speed and height of knees', 30, 15),
('Jumping Jacks', 'Jump while spreading legs and raising arms', 'Land softly with knees slightly bent', 'Maintain a consistent rhythm', 45, 15),
('Mountain Climbers', 'Alternate bringing knees to chest in plank position', 'Keep hips level and core tight', 'Focus on speed while maintaining form', 40, 20),
('Burpees', 'Full body exercise combining squat, plank, push-up and jump', 'Keep your back flat during the plank portion', 'Modify by removing the push-up if needed', 45, 30),
('Skater Jumps', 'Lateral jumps from one foot to the other', 'Land softly with knee slightly bent', 'Swing arms for momentum and balance', 40, 20),

-- Strength exercises
('Push-ups', 'Upper body exercise targeting chest, shoulders, and triceps', 'Keep body in straight line from head to heels', 'Modify on knees if needed', 45, 30),
('Squats', 'Lower body exercise targeting quadriceps, hamstrings, and glutes', 'Keep weight in heels and knees tracking over toes', 'Go as low as comfortable while maintaining form', 50, 25),
('Lunges', 'Single leg exercise working quadriceps, hamstrings, and glutes', 'Keep front knee over ankle, not beyond toes', 'Step far enough forward to create 90-degree angles', 45, 20),
('Dumbbell Rows', 'Back exercise using dumbbells', 'Keep back flat and core engaged', 'Pull elbow straight back, squeezing shoulder blade', 40, 20),
('Plank', 'Core stabilization exercise', 'Maintain straight line from head to heels', 'Engage core and glutes throughout', 60, 30),

-- Flexibility exercises
('Downward Dog', 'Yoga pose stretching shoulders, hamstrings, and calves', 'Form an inverted V-shape with body', 'Pedal feet to deepen the stretch', 60, 15),
('Cobra Stretch', 'Back extension stretching chest and abdominals', 'Keep shoulders down away from ears', 'Only lift as high as comfortable', 45, 15),
('Butterfly Stretch', 'Inner thigh and groin stretch', 'Sit tall with soles of feet together', 'Gently press knees toward floor', 60, 10),
('Standing Hamstring Stretch', 'Stretch for back of legs', 'Keep back straight and hinge at hips', 'Only go as far as comfortable', 45, 10),
('Hip Flexor Stretch', 'Stretch for front of hips', 'Keep front knee over ankle', 'Tuck pelvis to intensify stretch', 50, 10),

-- Core exercises
('Crunches', 'Basic abdominal exercise', 'Keep lower back pressed into floor', 'Focus on quality over quantity', 45, 15),
('Russian Twists', 'Oblique exercise with rotation', 'Keep chest up and back straight', 'Control the movement rather than rushing', 40, 20),
('Leg Raises', 'Lower abdominal exercise', 'Keep lower back pressed into floor', 'Lower legs only as far as you can maintain form', 45, 25),
('Bicycle Crunches', 'Targets rectus abdominis and obliques', 'Keep elbows wide and neck relaxed', 'Focus on the rotation and extension', 50, 20),
('Side Plank', 'Lateral core stabilization', 'Stack shoulders, hips, and feet', 'Modify by dropping bottom knee to floor if needed', 30, 15),

-- HIIT exercises
('Squat Jumps', 'Explosive lower body exercise', 'Land softly with knees bent', 'Drive arms up for momentum', 30, 20),
('Push-up to Side Plank', 'Combination movement for upper body and core', 'Rotate from push-up to side plank with control', 'Modify push-up on knees if needed', 45, 25),
('Kettlebell Swings', 'Hip-hinge movement with kettlebell', 'Drive movement from hips, not arms', 'Keep back flat throughout', 40, 20),
('Box Jumps', 'Explosive lower body plyometric', 'Land softly with knees bent', 'Step down rather than jumping down', 30, 30),
('Battle Rope Slams', 'Upper body and core power exercise', 'Keep knees slightly bent and core engaged', 'Use full arm range of motion', 30, 20);

-- Connect workouts with exercises (sample connections)
INSERT INTO workout_exercises (workout_id, exercise_id, exercise_order) VALUES
-- Beginner Running Program exercises
(1, 1, 1),  -- High Knees
(1, 2, 2),  -- Jumping Jacks
(1, 12, 3), -- Cobra Stretch

-- Interval Sprint Training exercises
(2, 3, 1),  -- Mountain Climbers
(2, 4, 2),  -- Burpees
(2, 5, 3),  -- Skater Jumps
(2, 21, 4), -- Squat Jumps

-- Steady State Cardio exercises
(3, 1, 1),  -- High Knees
(3, 2, 2),  -- Jumping Jacks
(3, 3, 3),  -- Mountain Climbers

-- 30-Minute Treadmill Intervals exercises
(4, 1, 1),  -- High Knees
(4, 3, 2),  -- Mountain Climbers
(4, 15, 3), -- Hip Flexor Stretch

-- Jump Rope Cardio Blast exercises
(5, 2, 1),  -- Jumping Jacks
(5, 4, 2),  -- Burpees
(5, 5, 3),  -- Skater Jumps

-- Full Body Strength Circuit exercises
(6, 6, 1),  -- Push-ups
(6, 7, 2),  -- Squats
(6, 8, 3),  -- Lunges
(6, 9, 4),  -- Dumbbell Rows
(6, 10, 5), -- Plank

-- Upper Body Focus exercises
(7, 6, 1),  -- Push-ups
(7, 9, 2),  -- Dumbbell Rows
(7, 22, 3), -- Push-up to Side Plank

-- Lower Body Power exercises
(8, 7, 1),  -- Squats
(8, 8, 2),  -- Lunges
(8, 21, 3), -- Squat Jumps
(8, 24, 4), -- Box Jumps

-- Bodyweight Strength Circuit exercises
(9, 6, 1),  -- Push-ups
(9, 7, 2),  -- Squats
(9, 8, 3),  -- Lunges
(9, 10, 4), -- Plank

-- Dumbbell Total Body exercises
(10, 6, 1),  -- Push-ups
(10, 7, 2),  -- Squats
(10, 9, 3),  -- Dumbbell Rows
(10, 23, 4), -- Kettlebell Swings

-- Beginner Yoga Flow exercises
(11, 11, 1), -- Downward Dog
(11, 12, 2), -- Cobra Stretch
(11, 13, 3), -- Butterfly Stretch

-- Dynamic Stretching Routine exercises
(12, 14, 1), -- Standing Hamstring Stretch
(12, 15, 2), -- Hip Flexor Stretch
(12, 11, 3), -- Downward Dog

-- Advanced Flexibility Training exercises
(13, 11, 1), -- Downward Dog
(13, 12, 2), -- Cobra Stretch
(13, 13, 3), -- Butterfly Stretch
(13, 14, 4), -- Standing Hamstring Stretch
(13, 15, 5), -- Hip Flexor Stretch

-- Power Yoga Flow exercises
(14, 11, 1), -- Downward Dog
(14, 12, 2), -- Cobra Stretch
(14, 10, 3), -- Plank

-- Full Body Stretch Routine exercises
(15, 13, 1), -- Butterfly Stretch
(15, 14, 2), -- Standing Hamstring Stretch
(15, 15, 3), -- Hip Flexor Stretch

-- Core Fundamentals exercises
(16, 10, 1), -- Plank
(16, 16, 2), -- Crunches
(16, 18, 3), -- Leg Raises
(16, 20, 4), -- Side Plank

-- Ab Circuit Challenge exercises
(17, 16, 1), -- Crunches
(17, 17, 2), -- Russian Twists
(17, 18, 3), -- Leg Raises
(17, 19, 4), -- Bicycle Crunches

-- Pilates Core Workout exercises
(18, 10, 1), -- Plank
(18, 18, 2), -- Leg Raises
(18, 19, 3), -- Bicycle Crunches
(18, 20, 4), -- Side Plank

-- Six-Pack Abs Circuit exercises
(19, 16, 1), -- Crunches
(19, 17, 2), -- Russian Twists
(19, 18, 3), -- Leg Raises
(19, 19, 4), -- Bicycle Crunches

-- Plank Challenge exercises
(20, 10, 1), -- Plank
(20, 20, 2), -- Side Plank
(20, 22, 3), -- Push-up to Side Plank

-- Beginner HIIT exercises
(21, 1, 1),  -- High Knees
(21, 2, 2),  -- Jumping Jacks
(21, 7, 3),  -- Squats
(21, 3, 4),  -- Mountain Climbers

-- Tabata Inferno exercises
(22, 4, 1),  -- Burpees
(22, 21, 2), -- Squat Jumps
(22, 3, 3),  -- Mountain Climbers
(22, 22, 4), -- Push-up to Side Plank

-- Total Body HIIT exercises
(23, 4, 1),  -- Burpees
(23, 7, 2),  -- Squats
(23, 6, 3),  -- Push-ups
(23, 21, 4), -- Squat Jumps
(23, 3, 5),  -- Mountain Climbers

-- Bodyweight HIIT Blast exercises
(24, 1, 1),  -- High Knees
(24, 4, 2),  -- Burpees
(24, 7, 3),  -- Squats
(24, 6, 4),  -- Push-ups

-- Dumbbell HIIT Circuit exercises
(25, 7, 1),  -- Squats
(25, 9, 2),  -- Dumbbell Rows
(25, 23, 3), -- Kettlebell Swings
(25, 3, 4);  -- Mountain Climbers

-- Insert sample motivational quotes
INSERT INTO motivational_quotes (quote_text, author) VALUES
('The only bad workout is the one that didn\'t happen.', 'Unknown'),
('Fitness is not about being better than someone else. It\'s about being better than you used to be.', 'Unknown'),
('The hard days are the best because that\'s when champions are made.', 'Gabby Douglas'),
('Take care of your body. It\'s the only place you have to live.', 'Jim Rohn'),
('The only way to define your limits is by going beyond them.', 'Arthur C. Clarke'),
('Your body can stand almost anything. It\'s your mind that you have to convince.', 'Unknown'),
('The difference between try and triumph is just a little umph!', 'Marvin Phillips'),
('Strength does not come from physical capacity. It comes from an indomitable will.', 'Mahatma Gandhi'),
('The clock is ticking. Are you becoming the person you want to be?', 'Greg Plitt'),
('You don\'t have to be extreme, just consistent.', 'Unknown');