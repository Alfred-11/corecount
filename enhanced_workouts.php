<?php
/**
 * Enhanced Workouts Script
 * CoreCount Fitness Planner
 * 
 * This script populates the workouts table with a comprehensive list of workout data
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if enhanced workouts already exist
$check_query = "SELECT COUNT(*) as count FROM workouts";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute();
$result = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Only add workouts if there are less than 15 workouts (the original set)
if ($result['count'] >= 15 && $result['count'] < 50) {
    echo "<p>Enhanced workouts already exist in the database. No new workouts were added.</p>";
    echo "<p><a href='categories.php'>View Workout Categories</a></p>";
    exit;
}

// Sample enhanced workouts data
$workouts = [
    // Cardio workouts (category_id: 1)
    [
        'category_id' => 1,
        'name' => '30-Minute Treadmill Intervals',
        'description' => 'Alternating between walking, jogging, and running on a treadmill to improve cardiovascular endurance and burn calories.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 300
    ],
    [
        'category_id' => 1,
        'name' => 'Stair Climber Challenge',
        'description' => 'Intense stair climbing workout that targets your lower body while providing excellent cardiovascular benefits.',
        'difficulty_level' => 'advanced',
        'duration' => 25,
        'calories_burned' => 280
    ],
    [
        'category_id' => 1,
        'name' => 'Elliptical Cross Training',
        'description' => 'Low-impact cardio workout on an elliptical machine with varying resistance levels to engage multiple muscle groups.',
        'difficulty_level' => 'beginner',
        'duration' => 35,
        'calories_burned' => 320
    ],
    [
        'category_id' => 1,
        'name' => 'Cycling Endurance Ride',
        'description' => 'Steady-state cycling workout designed to build aerobic endurance and leg strength.',
        'difficulty_level' => 'intermediate',
        'duration' => 45,
        'calories_burned' => 400
    ],
    [
        'category_id' => 1,
        'name' => 'Jump Rope Cardio Blast',
        'description' => 'High-intensity jump rope intervals that improve coordination, agility, and cardiovascular fitness.',
        'difficulty_level' => 'intermediate',
        'duration' => 20,
        'calories_burned' => 250
    ],
    [
        'category_id' => 1,
        'name' => 'Swimming Laps',
        'description' => 'Full-body, low-impact cardio workout that alternates between different swimming strokes.',
        'difficulty_level' => 'intermediate',
        'duration' => 40,
        'calories_burned' => 350
    ],
    [
        'category_id' => 1,
        'name' => 'Power Walking',
        'description' => 'Brisk walking with arm movements and proper posture to elevate heart rate and burn calories.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 200
    ],
    
    // Strength workouts (category_id: 2)
    [
        'category_id' => 2,
        'name' => 'Push-Pull-Legs Split',
        'description' => 'Classic strength training split that targets pushing muscles (chest, shoulders, triceps), pulling muscles (back, biceps), and legs on separate days.',
        'difficulty_level' => 'intermediate',
        'duration' => 50,
        'calories_burned' => 350
    ],
    [
        'category_id' => 2,
        'name' => 'Bodyweight Strength Circuit',
        'description' => 'No-equipment strength workout using only bodyweight exercises like push-ups, squats, and lunges.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 250
    ],
    [
        'category_id' => 2,
        'name' => 'Dumbbell Total Body',
        'description' => 'Comprehensive strength workout using dumbbells to target all major muscle groups.',
        'difficulty_level' => 'intermediate',
        'duration' => 45,
        'calories_burned' => 320
    ],
    [
        'category_id' => 2,
        'name' => 'Kettlebell Complex',
        'description' => 'Dynamic strength and conditioning workout using kettlebells for functional movement patterns.',
        'difficulty_level' => 'advanced',
        'duration' => 35,
        'calories_burned' => 300
    ],
    [
        'category_id' => 2,
        'name' => 'Barbell Strength Focus',
        'description' => 'Heavy compound lifting with barbells to build maximum strength and muscle mass.',
        'difficulty_level' => 'advanced',
        'duration' => 60,
        'calories_burned' => 400
    ],
    [
        'category_id' => 2,
        'name' => 'Resistance Band Workout',
        'description' => 'Full-body strength training using resistance bands for constant tension throughout movements.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 220
    ],
    [
        'category_id' => 2,
        'name' => 'Functional Fitness Circuit',
        'description' => 'Strength exercises that mimic everyday movements to improve overall functionality and prevent injuries.',
        'difficulty_level' => 'intermediate',
        'duration' => 40,
        'calories_burned' => 300
    ],
    
    // Flexibility workouts (category_id: 3)
    [
        'category_id' => 3,
        'name' => 'Power Yoga Flow',
        'description' => 'Dynamic yoga sequence that builds strength, flexibility, and balance through flowing movements.',
        'difficulty_level' => 'intermediate',
        'duration' => 45,
        'calories_burned' => 250
    ],
    [
        'category_id' => 3,
        'name' => 'Full Body Stretch Routine',
        'description' => 'Comprehensive stretching session targeting all major muscle groups to improve overall flexibility.',
        'difficulty_level' => 'beginner',
        'duration' => 25,
        'calories_burned' => 120
    ],
    [
        'category_id' => 3,
        'name' => 'Pilates Mat Class',
        'description' => 'Core-focused workout that improves flexibility, posture, and body awareness through controlled movements.',
        'difficulty_level' => 'intermediate',
        'duration' => 40,
        'calories_burned' => 200
    ],
    [
        'category_id' => 3,
        'name' => 'Yin Yoga',
        'description' => 'Slow-paced yoga style where poses are held for longer periods to target deep connective tissues.',
        'difficulty_level' => 'beginner',
        'duration' => 50,
        'calories_burned' => 150
    ],
    [
        'category_id' => 3,
        'name' => 'Active Recovery Mobility',
        'description' => 'Gentle movement patterns designed to increase joint mobility and muscle recovery between intense workouts.',
        'difficulty_level' => 'beginner',
        'duration' => 30,
        'calories_burned' => 130
    ],
    [
        'category_id' => 3,
        'name' => 'Ballet-Inspired Stretch',
        'description' => 'Graceful stretching routine inspired by ballet movements to improve flexibility and posture.',
        'difficulty_level' => 'intermediate',
        'duration' => 35,
        'calories_burned' => 180
    ],
    [
        'category_id' => 3,
        'name' => 'Foam Rolling Session',
        'description' => 'Self-myofascial release technique using a foam roller to reduce muscle tension and improve flexibility.',
        'difficulty_level' => 'beginner',
        'duration' => 20,
        'calories_burned' => 100
    ],
    
    // Core workouts (category_id: 4)
    [
        'category_id' => 4,
        'name' => 'Six-Pack Abs Circuit',
        'description' => 'Targeted abdominal workout designed to strengthen and define all areas of the core.',
        'difficulty_level' => 'intermediate',
        'duration' => 20,
        'calories_burned' => 180
    ],
    [
        'category_id' => 4,
        'name' => 'Plank Challenge',
        'description' => 'Series of plank variations to build core stability, endurance, and strength.',
        'difficulty_level' => 'intermediate',
        'duration' => 15,
        'calories_burned' => 120
    ],
    [
        'category_id' => 4,
        'name' => 'Medicine Ball Core',
        'description' => 'Dynamic core exercises using a medicine ball to engage the entire midsection.',
        'difficulty_level' => 'advanced',
        'duration' => 25,
        'calories_burned' => 220
    ],
    [
        'category_id' => 4,
        'name' => 'Stability Ball Workout',
        'description' => 'Core-strengthening exercises performed on an unstable surface to increase difficulty and engagement.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 200
    ],
    [
        'category_id' => 4,
        'name' => 'Lower Ab Focus',
        'description' => 'Targeted workout for the often-neglected lower abdominal region.',
        'difficulty_level' => 'intermediate',
        'duration' => 20,
        'calories_burned' => 170
    ],
    [
        'category_id' => 4,
        'name' => 'Oblique Sculptor',
        'description' => 'Side-focused core workout to strengthen and define the oblique muscles.',
        'difficulty_level' => 'intermediate',
        'duration' => 20,
        'calories_burned' => 180
    ],
    [
        'category_id' => 4,
        'name' => 'Core and Back Strength',
        'description' => 'Balanced workout targeting both the abdominal and back muscles for complete core development.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 220
    ],
    
    // HIIT workouts (category_id: 5)
    [
        'category_id' => 5,
        'name' => 'Bodyweight HIIT Blast',
        'description' => 'Equipment-free high-intensity interval training using only bodyweight exercises.',
        'difficulty_level' => 'intermediate',
        'duration' => 25,
        'calories_burned' => 300
    ],
    [
        'category_id' => 5,
        'name' => 'EMOM Challenge',
        'description' => 'Every Minute On the Minute workout format where you perform specific exercises at the start of each minute.',
        'difficulty_level' => 'advanced',
        'duration' => 30,
        'calories_burned' => 350
    ],
    [
        'category_id' => 5,
        'name' => 'AMRAP Session',
        'description' => 'As Many Rounds As Possible format where you complete as many circuits as you can in a set time period.',
        'difficulty_level' => 'advanced',
        'duration' => 20,
        'calories_burned' => 280
    ],
    [
        'category_id' => 5,
        'name' => 'Kettlebell HIIT',
        'description' => 'High-intensity interval training using kettlebells for added resistance and metabolic demand.',
        'difficulty_level' => 'advanced',
        'duration' => 30,
        'calories_burned' => 350
    ],
    [
        'category_id' => 5,
        'name' => 'Battle Rope Intervals',
        'description' => 'Intense upper body and core workout using battle ropes in an interval format.',
        'difficulty_level' => 'advanced',
        'duration' => 20,
        'calories_burned' => 300
    ],
    [
        'category_id' => 5,
        'name' => 'Dumbbell HIIT Circuit',
        'description' => 'Full-body high-intensity workout using dumbbells to add resistance to classic HIIT movements.',
        'difficulty_level' => 'intermediate',
        'duration' => 30,
        'calories_burned' => 320
    ],
    [
        'category_id' => 5,
        'name' => 'Cardio and Strength HIIT',
        'description' => 'Balanced HIIT workout alternating between cardio bursts and strength exercises.',
        'difficulty_level' => 'intermediate',
        'duration' => 35,
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
    
    echo "<p>Successfully added $success_count enhanced workouts to the database.</p>";
    echo "<p><a href='categories.php'>View Workout Categories</a></p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>