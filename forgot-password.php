<?php
/**
 * Forgot Password Page
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
$email = $email_error = '';
$success = false;

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_error = 'Please enter your email address';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Please enter a valid email address';
    } else {
        $email = trim($_POST['email']);
    }
    
    // Check input errors before processing
    if (empty($email_error)) {
        // Check if email exists in database
        $sql = "SELECT user_id, username FROM users WHERE email = :email";
        
        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $user_id = $row['user_id'];
                    $username = $row['username'];
                    
                    // Generate a secure token for password reset
                    $token = bin2hex(random_bytes(32)); // Increased from 16 to 32 bytes for better security
                    
                    // Set token expiry (1 hour from now)
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Update user with reset token
                    $update_sql = "UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE user_id = :user_id";
                    
                    if ($update_stmt = $db->prepare($update_sql)) {
                        $update_stmt->bindParam(':token', $token, PDO::PARAM_STR);
                        $update_stmt->bindParam(':expiry', $expiry, PDO::PARAM_STR);
                        $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        
                        if ($update_stmt->execute()) {
                            // Store email in session for the reset page
                            $_SESSION['reset_email'] = $email;
                            
                            // Set success message
                            $success = true;
                            
                            // Redirect directly to reset password page
                            redirect(SITE_URL . '/reset-password.php?token=' . $token);
                        } else {
                            $email_error = 'Error updating reset token. Please try again later.';
                        }
                    } else {
                        $email_error = 'Error updating reset token. Please try again later.';
                    }
                } else {
                    // Email not found - show a message but keep it somewhat vague for security
                    $email_error = "We couldn't find an account with that email address. Please check and try again.";
                    // Add a small delay to prevent timing attacks that could reveal if an email exists
                    sleep(1);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    
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
                            <h2>Forgot Password</h2>
                        </div>
                        <div class="auth-body">
            
            <p>Enter your email address below and we'll send you a link to reset your password.</p>
            
            <?php if(!empty($email_error)): ?>
                <div class="alert alert-danger">
                    <?php echo $email_error; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_error)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </div>

                     
            </form>
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
                        