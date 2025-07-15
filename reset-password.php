<?php
/**
 * Reset Password Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

// Define variables and set to empty values
$token = $email = $password = $confirm_password = '';
$token_error = $password_error = $confirm_password_error = '';
$token_verified = false;

// Check if token is provided in URL and email is in session
if (isset($_GET['token']) && !empty($_GET['token']) && isset($_SESSION['reset_email'])) {
    $token = trim($_GET['token']);
    $email = $_SESSION['reset_email'];
    
    // Verify token with additional security checks
    $sql = "SELECT user_id, email, reset_token, reset_token_expiry FROM users WHERE reset_token = :token AND email = :email AND reset_token IS NOT NULL AND reset_token_expiry IS NOT NULL";
    
    if ($stmt = $db->prepare($sql)) {
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check if token is expired
                $expiry = strtotime($row['reset_token_expiry']);
                $now = time();
                
                if ($expiry > $now) {
                    // Token is valid
                    $token_verified = true;
                    $user_id = $row['user_id'];
                } else {
                    // Token is expired - invalidate it in the database
                    $invalidate_sql = "UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE user_id = :user_id";
                    $invalidate_stmt = $db->prepare($invalidate_sql);
                    $invalidate_stmt->bindParam(':user_id', $row['user_id'], PDO::PARAM_INT);
                    $invalidate_stmt->execute();
                    
                    // Token is expired
                    $token_error = 'Password reset link has expired. Please request a new one.';
                }
            } else {
                // Token not found
                $token_error = 'Invalid password reset link. Please request a new one.';
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
} else {
    // No token provided or no email in session
    $token_error = 'Invalid password reset link. Please request a new one.';
}

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_verified) {
    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_error = 'Please enter a password';
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_error = 'Password must have at least 6 characters';
    } else {
        $password = trim($_POST['password']);
    }
    
    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_error = 'Please confirm password';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_error) && ($password != $confirm_password)) {
            $confirm_password_error = 'Passwords did not match';
        }
    }
    
    // Check input errors before updating the database
    if (empty($password_error) && empty($confirm_password_error)) {
        // Prepare an update statement
        $sql = "UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = :user_id";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(':password', $param_password, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Password updated successfully
                // Ensure session is started before setting session variable
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['success'] = "Your password has been reset successfully. You can now log in with your new password.";
                redirect(SITE_URL . '/login.php');
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Page title
$page_title = "Reset Password - CoreCount Fitness Planner";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/icon-colors.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-container">
        <!-- Background Video -->
        <div class="video-background">
            <video autoplay loop muted playsinline id="auth-bg-video">
                <source src="<?php echo SITE_URL; ?>/video and image/signorloginbgvideo.webm" type="video/webm">
            </video>
            <div class="video-overlay"></div>
        </div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h2>Reset Password</h2>
                        </div>
                        <div class="auth-body">
                            <?php
                            if (!empty($token_error)) {
                                echo '<div class="alert alert-danger">' . $token_error . '</div>';
                                echo '<div class="text-center mt-3"><a href="forgot-password.php" class="btn btn-primary">Request New Reset Link</a></div>';
                            } elseif ($token_verified) {
                            ?>
                            <p class="mb-4">Please enter your new password below.</p>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $token; ?>" method="post">
                                <div class="form-group mb-3">
                                    <label for="password">New Password</label>
                                    <div class="password-toggle">
                                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_error)) ? 'is-invalid' : ''; ?>">
                                        <i class="toggle-password fas fa-eye"></i>
                                        <span class="invalid-feedback"><?php echo $password_error; ?></span>
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
                                    <button type="submit" class="btn btn-primary btn-block w-100">Reset Password</button>
                                </div>
                            </form>
                            <?php } ?>
                            
                            <div class="auth-footer">
                                <p>Remember your password? <a href="login.php">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
