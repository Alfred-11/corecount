<?php
/**
 * Change Password Page
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
$current_password = $new_password = $confirm_password = '';
$current_password_error = $new_password_error = $confirm_password_error = '';
$success_message = $error_message = '';

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate current password
    if (empty(trim($_POST['current_password']))) {
        $current_password_error = 'Please enter your current password';
    } else {
        $current_password = trim($_POST['current_password']);
    }
    
    // Validate new password
    if (empty(trim($_POST['new_password']))) {
        $new_password_error = 'Please enter a new password';
    } elseif (strlen(trim($_POST['new_password'])) < 6) {
        $new_password_error = 'Password must have at least 6 characters';
    } else {
        $new_password = trim($_POST['new_password']);
    }
    
    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_error = 'Please confirm password';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($new_password_error) && ($new_password != $confirm_password)) {
            $confirm_password_error = 'Passwords did not match';
        }
    }
    
    // Check input errors before updating the database
    if (empty($current_password_error) && empty($new_password_error) && empty($confirm_password_error)) {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $hashed_password = $row['password'];
            
            if (password_verify($current_password, $hashed_password)) {
                // Current password is correct, update with new password
                $update_sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->bindParam(':password', $param_password, PDO::PARAM_STR);
                $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                // Hash the new password
                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($update_stmt->execute()) {
                    $success_message = 'Password changed successfully!';
                    // Clear form fields after successful update
                    $current_password = $new_password = $confirm_password = '';
                } else {
                    $error_message = 'Something went wrong. Please try again later.';
                }
            } else {
                $current_password_error = 'Current password is incorrect';
            }
        } else {
            $error_message = 'Something went wrong. Please try again later.';
        }
    }
}

// Page title
$page_title = "Change Password - CoreCount Fitness Planner";

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Change Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="current_password">Current Password</label>
                            <div class="password-toggle">
                                <input type="password" name="current_password" id="current_password" class="form-control <?php echo (!empty($current_password_error)) ? 'is-invalid' : ''; ?>">
                                <i class="toggle-password fas fa-eye"></i>
                                <span class="invalid-feedback"><?php echo $current_password_error; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <div class="password-toggle">
                                <input type="password" name="new_password" id="new_password" class="form-control <?php echo (!empty($new_password_error)) ? 'is-invalid' : ''; ?>">
                                <i class="toggle-password fas fa-eye"></i>
                                <span class="invalid-feedback"><?php echo $new_password_error; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-toggle">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_error)) ? 'is-invalid' : ''; ?>">
                                <i class="toggle-password fas fa-eye"></i>
                                <span class="invalid-feedback"><?php echo $confirm_password_error; ?></span>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                            <a href="profile.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>