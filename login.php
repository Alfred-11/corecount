<?php
/**
 * Login Page
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
$username_email = $password = '';
$username_email_error = $password_error = $login_error = '';

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate username/email
    if (empty(trim($_POST['username_email']))) {
        $username_email_error = 'Please enter username or email';
    } else {
        $username_email = trim($_POST['username_email']);
    }
    
    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_error = 'Please enter your password';
    } else {
        $password = trim($_POST['password']);
    }
    
    // Check input errors before processing
    if (empty($username_email_error) && empty($password_error)) {
        // Prepare a select statement
        $sql = "SELECT user_id, username, email, password FROM users WHERE username = :username_email OR email = :username_email";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(':username_email', $username_email, PDO::PARAM_STR);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if username/email exists
                if ($stmt->rowCount() == 1) {
                    // Fetch result
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $user_id = $row['user_id'];
                    $username = $row['username'];
                    $email = $row['email'];
                    $hashed_password = $row['password'];
                    
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        
                        // Set success message
                        $_SESSION['success'] = "Welcome back, $username!";
                        
                        // Redirect user to home page
                        redirect(SITE_URL . '/index.php');
                    } else {
                        // Password is not valid
                        $login_error = 'Invalid username/email or password';
                    }
                } else {
                    // Username/email doesn't exist
                    $login_error = 'Invalid username/email or password';
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Page title
$page_title = "Login - CoreCount Fitness Planner";
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
                            <h2>Login to CoreCount</h2>
                        </div>
                        <div class="auth-body">
                            <?php
                            echo flash('success');
                            echo flash('error', 'alert alert-danger');
                            
                            if (!empty($login_error)) {
                                echo '<div class="alert alert-danger">' . $login_error . '</div>';
                            }
                            ?>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group mb-3">
                                    <label for="username_email">Username or Email</label>
                                    <input type="text" name="username_email" id="username_email" class="form-control <?php echo (!empty($username_email_error)) ? 'is-invalid' : ''; ?>" value="<?php echo $username_email; ?>">
                                    <span class="invalid-feedback"><?php echo $username_email_error; ?></span>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="password">Password</label>
                                    <div class="password-toggle">
                                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_error)) ? 'is-invalid' : ''; ?>">
                                        <i class="toggle-password fas fa-eye"></i>
                                        <span class="invalid-feedback"><?php echo $password_error; ?></span>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <button type="submit" class="btn btn-primary btn-block w-100">Login</button>
                                </div>
                            </form>
                            
                            <div class="auth-footer">
                                <p>Forgot your password? <a href="forgot-password.php">Reset it here</a></p>
                                <p>Don't have an account? <a href="signup.php">Sign up now</a></p>
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