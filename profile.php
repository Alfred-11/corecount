<?php
/**
 * User Profile Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Ensure user is logged in
requireLogin();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Define variables and set to empty values
$first_name = $last_name = $age = $gender = $weight = $height = '';
$first_name_error = $last_name_error = $age_error = $gender_error = $weight_error = $height_error = '';
$success_message = $error_message = '';

// Fetch user profile data
$sql = "SELECT u.username, u.email, p.* FROM users u 
        LEFT JOIN user_profiles p ON u.user_id = p.user_id 
        WHERE u.user_id = :user_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// If profile doesn't exist, create an empty one
if (!$user_data || !isset($user_data['profile_id']) || !$user_data['profile_id']) {
    // Check if a profile already exists for this user
    $check_sql = "SELECT COUNT(*) as profile_count FROM user_profiles WHERE user_id = :user_id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $profile_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['profile_count'];
    
    // Only create a new profile if one doesn't exist
    if ($profile_count == 0) {
        $create_profile_sql = "INSERT INTO user_profiles (user_id) VALUES (:user_id)";
        $create_profile_stmt = $db->prepare($create_profile_sql);
        $create_profile_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $create_profile_stmt->execute();
    }
    
    // Fetch the profile data
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate first name
    $first_name = trim($_POST['first_name']);
    
    // Validate last name
    $last_name = trim($_POST['last_name']);
    
    // Validate age
    if (!empty(trim($_POST['age'])) && (!is_numeric(trim($_POST['age'])) || trim($_POST['age']) < 1)) {
        $age_error = 'Please enter a valid age';
    } else {
        $age = !empty(trim($_POST['age'])) ? trim($_POST['age']) : null;
    }
    
    // Validate gender
    $gender = !empty($_POST['gender']) ? trim($_POST['gender']) : null;
    
    // Validate weight
    if (!empty(trim($_POST['weight'])) && (!is_numeric(trim($_POST['weight'])) || trim($_POST['weight']) < 1)) {
        $weight_error = 'Please enter a valid weight';
    } else {
        $weight = !empty(trim($_POST['weight'])) ? trim($_POST['weight']) : null;
    }
    
    // Validate height
    if (!empty(trim($_POST['height'])) && (!is_numeric(trim($_POST['height'])) || trim($_POST['height']) < 1)) {
        $height_error = 'Please enter a valid height';
    } else {
        $height = !empty(trim($_POST['height'])) ? trim($_POST['height']) : null;
    }
    
    // Check input errors before updating the database
    if (empty($first_name_error) && empty($last_name_error) && empty($age_error) && 
        empty($gender_error) && empty($weight_error) && empty($height_error)) {
        
        // Prepare an update statement
        $update_sql = "UPDATE user_profiles SET 
                      first_name = :first_name, 
                      last_name = :last_name, 
                      age = :age, 
                      gender = :gender, 
                      weight = :weight, 
                      height = :height 
                      WHERE user_id = :user_id";
        
        if ($update_stmt = $db->prepare($update_sql)) {
            // Bind variables to the prepared statement as parameters
            $update_stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $update_stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $update_stmt->bindParam(':age', $age, PDO::PARAM_INT);
            $update_stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
            $update_stmt->bindParam(':weight', $weight, PDO::PARAM_STR);
            $update_stmt->bindParam(':height', $height, PDO::PARAM_STR);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            // Attempt to execute the prepared statement
            if ($update_stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                
                // Refresh user data with the latest information
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = 'Something went wrong. Please try again later.';
            }
        }
    }
}

// Calculate BMI if weight and height are available
$bmi = null;
$bmi_category = '';
// Make sure we have valid user data and both weight and height are set
if (isset($user_data) && is_array($user_data) && 
    isset($user_data['weight']) && isset($user_data['height']) && 
    !empty($user_data['weight']) && !empty($user_data['height'])) {
    // BMI = weight(kg) / (height(m))²
    $height_in_meters = $user_data['height'] / 100; // Convert cm to m
    $bmi = round($user_data['weight'] / ($height_in_meters * $height_in_meters), 1);
    
    // Determine BMI category
    if ($bmi < 18.5) {
        $bmi_category = 'Underweight';
    } elseif ($bmi >= 18.5 && $bmi < 25) {
        $bmi_category = 'Normal weight';
    } elseif ($bmi >= 25 && $bmi < 30) {
        $bmi_category = 'Overweight';
    } else {
        $bmi_category = 'Obesity';
    }
}

// Fetch workout statistics
$stats_sql = "SELECT 
              COUNT(*) as total_workouts,
              SUM(duration) as total_duration,
              SUM(calories_burned) as total_calories
              FROM user_progress 
              WHERE user_id = :user_id";
$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Page title
$page_title = "My Profile - CoreCount Fitness Planner";

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="video and image/avatar.png" alt="Profile Avatar">
            </div>
            <h2 class="profile-name"><?php echo htmlspecialchars($user_data['username'] ?? ''); ?></h2>
            <p class="profile-email"><?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
        </div>
        
        <div class="profile-body">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="profile-section">
                        <h3 class="profile-section-title">Personal Information</h3>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control <?php echo (!empty($first_name_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>">
                                    <span class="invalid-feedback"><?php echo $first_name_error; ?></span>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control <?php echo (!empty($last_name_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>">
                                    <span class="invalid-feedback"><?php echo $last_name_error; ?></span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="age">Age</label>
                                    <input type="number" name="age" id="age" class="form-control <?php echo (!empty($age_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user_data['age'] ?? ''); ?>">
                                    <span class="invalid-feedback"><?php echo $age_error; ?></span>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="gender">Gender</label>
                                    <select name="gender" id="gender" class="form-control <?php echo (!empty($gender_error)) ? 'is-invalid' : ''; ?>">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo (isset($user_data['gender']) && $user_data['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <span class="invalid-feedback"><?php echo $gender_error; ?></span>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="weight">Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" id="weight" class="form-control <?php echo (!empty($weight_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user_data['weight'] ?? ''); ?>">
                                    <span class="invalid-feedback"><?php echo $weight_error; ?></span>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="height">Height (cm)</label>
                                    <input type="number" name="height" id="height" class="form-control <?php echo (!empty($height_error)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($user_data['height'] ?? ''); ?>">
                                    <span class="invalid-feedback"><?php echo $height_error; ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="change-password.php" class="btn btn-secondary">Change Password</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="profile-section">
                        <h3 class="profile-section-title">Fitness Metrics</h3>
                        
                        <div class="profile-metrics">
                            <?php if ($bmi): ?>
                                <div class="profile-metric">
                                    <div class="profile-metric-value"><?php echo $bmi; ?></div>
                                    <div class="profile-metric-label">BMI (<?php echo $bmi_category; ?>)</div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="profile-metric">
                                <div class="profile-metric-value"><?php echo isset($stats['total_workouts']) && $stats['total_workouts'] ? $stats['total_workouts'] : 0; ?></div>
                                <div class="profile-metric-label">Workouts Completed</div>
                            </div>
                            
                            <div class="profile-metric">
                                <div class="profile-metric-value"><?php echo isset($stats['total_duration']) && $stats['total_duration'] ? round($stats['total_duration'] / 60) : 0; ?></div>
                                <div class="profile-metric-label">Total Hours</div>
                            </div>
                            
                            <div class="profile-metric">
                                <div class="profile-metric-value"><?php echo isset($stats['total_calories']) && $stats['total_calories'] ? $stats['total_calories'] : 0; ?></div>
                                <div class="profile-metric-label">Calories Burned</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-section">
                        <h3 class="profile-section-title">Account Actions</h3>
                        <div class="d-grid gap-2">
                            <a href="progress.php" class="btn btn-outline-primary">View Progress</a>
                            <a href="schedule.php" class="btn btn-outline-primary">Manage Schedule</a>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>