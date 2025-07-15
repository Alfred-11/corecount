<?php
/**
 * Insert Workouts Script
 * CoreCount Fitness Planner
 * 
 * This script populates the workouts table with sample workout data
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if workouts already exist
$check_query = "SELECT COUNT(*) as count FROM workouts";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute();
$result = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    echo "<p>Workouts already exist in the database. No new workouts were added.</p>";
    echo "<p><a href='categories.php'>View Workout Categories</a></p>";
    exit;
}

// Sample workouts data
$workouts = [
    // Cardio workouts (category_id: 1)
    [
        'category_id' => 1,
        'name' => 'Beginner Running Program',
        'description' => 'A gentle introduction to running designed for beginners. Alternates between walking and jogging intervals to build endurance gradually.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 250
    ],
    [
        'category_id' => 1,
        'name' => 'Interval Sprint Training',
        'description' => 'High-intensity interval training with sprints to maximize calorie burn and improve cardiovascular fitness.',
        'difficulty_level' => 'advanced',
        'duration' => 25,
        'calories_burned' => 400
    ],
    [
        'category_id' => 1,
        'name' => 'Steady State Cardio',
        'description' => 'Maintain a consistent pace for an extended period to build aerobic endurance and improve heart health.',
        'difficulty_level' => 'intermediate',
        'duration' => 45,
        'calories_burned' => 350
    ],
    
    // Strength workouts (category_id: 2)
    [
        'category_id' => 2,
        'name' => 'Full Body Strength Circuit',
        'description' => 'A comprehensive workout targeting all major muscle groups through a series of compound exercises.',
        'difficulty_level' => 'intermediate',
        'duration' => 40,
        'calories_burned' => 320
    ],
    [
        'category_id' => 2,
        'name' => 'Upper Body Focus',
        'description' => 'Concentrate on developing strength in your chest, back, shoulders, and arms with this targeted routine.',
        'difficulty_level' => 'intermediate',
        'duration' => 35,
        'calories_burned' => 280
    ],
    [
        'category_id' => 2,
        'name' => 'Lower Body Power',
        'description' => 'Build strength and power in your legs, glutes, and core with this challenging lower body workout.',
        'difficulty_level' => 'advanced',
        'duration' => 35,
        'calories_burned' => 300
    ],
    
    // Flexibility workouts (category_id: 3)
    [
        'category_id' => 3,
        'name' => 'Beginner Yoga Flow',
        'description' => 'A gentle introduction to yoga poses and breathing techniques to improve flexibility and reduce stress.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 150
    ],
    [
        'category_id' => 3,
        'name' => 'Dynamic Stretching Routine',
        'description' => 'Active stretches that mimic sport-specific movements to prepare your body for activity and improve range of motion.',
        'difficulty_level' => 'beginner',
        'duration' => 20,
        'calories_burned' => 120
    ],
    [
        'category_id' => 3,
        'name' => 'Advanced Flexibility Training',
        'description' => 'Deep stretching techniques to significantly improve flexibility and joint mobility for those with an established practice.',
        'difficulty_level' => 'advanced',
        'duration' => 40,
        'calories_burned' => 200
    ],
    
    // Core workouts (category_id: 4)
    [
        'category_id' => 4,
        'name' => 'Core Fundamentals',
        'description' => 'Master the basics of core training with this foundational workout focusing on proper form and technique.',
        'difficulty_level' => 'beginner',
        'duration' => 20,
        'calories_burned' => 150
    ],
    [
        'category_id' => 4,
        'name' => 'Ab Circuit Challenge',
        'description' => 'A fast-paced circuit of core exercises designed to target all areas of your abdominals and obliques.',
        'difficulty_level' => 'intermediate',
        'duration' => 25,
        'calories_burned' => 200
    ],
    [
        'category_id' => 4,
        'name' => 'Pilates Core Workout',
        'description' => 'Strengthen your deep core muscles with controlled movements inspired by Pilates techniques.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 180
    ],
    
    // HIIT workouts (category_id: 5)
    [
        'category_id' => 5,
        'name' => 'Beginner HIIT',
        'description' => 'An introduction to high-intensity interval training with modified exercises and longer rest periods.',
        'difficulty_level' => 'beginner',
        'duration' => 20,
        'calories_burned' => 200
    ],
    [
        'category_id' => 5,
        'name' => 'Tabata Inferno',
        'description' => 'Ultra-intense 20-second work intervals with 10-second rest periods to maximize calorie burn and improve conditioning.',
        'difficulty_level' => 'advanced',
        'duration' => 25,
        'calories_burned' => 400
    ],
    [
        'category_id' => 5,
        'name' => 'Total Body HIIT',
        'description' => 'A comprehensive high-intensity workout that challenges every muscle group for complete fitness development.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 350
    ]
];

// Insert workouts
$insert_query = "INSERT INTO workouts (category_id, name, description, difficulty_level, duration, calories_burned) 
               VALUES (:category_id, :name, :description, :difficulty_level, :duration, :calories_burned)";
$insert_stmt = $db->prepare($insert_query);

$success_count = 0;

try {
    // Begin transaction
    $db->beginTransaction();
    
    foreach ($workouts as $workout) {
        $insert_stmt->bindParam(':category_id', $workout['category_id'], PDO::PARAM_INT);
        $insert_stmt->bindParam(':name', $workout['name'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':description', $workout['description'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':difficulty_level', $workout['difficulty_level'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':duration', $workout['duration'], PDO::PARAM_INT);
        $insert_stmt->bindParam(':calories_burned', $workout['calories_burned'], PDO::PARAM_INT);
        
        if ($insert_stmt->execute()) {
            $success_count++;
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo "<p>Successfully added $success_count workouts to the database.</p>";
    echo "<p><a href='categories.php'>View Workout Categories</a></p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>