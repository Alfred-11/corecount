<?php
/**
 * Signup Page
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
$username = $email = $password = $confirm_password = '';
$username_error = $email_error = $password_error = $confirm_password_error = '';

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_error = 'Please enter a username';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST['username']))) {
        $username_error = 'Username can only contain letters, numbers, and underscores';
    } else {
        // Check if username exists
        $sql = "SELECT user_id FROM users WHERE username = :username";
        
        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(':username', $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST['username']);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $username_error = 'This username is already taken';
                } else {
                    $username = trim($_POST['username']);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
    
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_error = 'Please enter an email address';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Please enter a valid email address';
    } else {
        // Check if email exists
        $sql = "SELECT user_id FROM users WHERE email = :email";
        
        if ($stmt = $db->prepare($sql)) {
            $stmt->bindParam(':email', $param_email, PDO::PARAM_STR);
            $param_email = trim($_POST['email']);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $email_error = 'This email is already registered';
                } else {
                    $email = trim($_POST['email']);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
    
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
    
    // Check input errors before inserting into database
    if (empty($username_error) && empty($email_error) && empty($password_error) && empty($confirm_password_error)) {
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(':username', $param_username, PDO::PARAM_STR);
            $stmt->bindParam(':email', $param_email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $param_password, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Get the user ID of the newly created user
                $user_id = $db->lastInsertId();
                
                // Check if profile exists before creating one
                $check_profile_sql = "SELECT user_id FROM user_profiles WHERE user_id = :user_id";
                $check_profile_stmt = $db->prepare($check_profile_sql);
                $check_profile_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $check_profile_stmt->execute();
                
                // Only create profile if it doesn't exist
                if ($check_profile_stmt->rowCount() == 0) {
                    $profile_sql = "INSERT INTO user_profiles (user_id) VALUES (:user_id)";
                    $profile_stmt = $db->prepare($profile_sql);
                    $profile_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $profile_stmt->execute();
                }
                
                // Set success message
                $_SESSION['success'] = "Account created successfully! You can now log in.";
                
                // Redirect to login page
                redirect(SITE_URL . '/login.php');
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Page title
$page_title = "Sign Up - CoreCount Fitness Planner";
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
                            <h2>Create an Account</h2>
                        </div>
                        <div class="auth-body">
                            <?php
                            echo flash('success');
                            echo flash('error', 'alert alert-danger');
                            ?>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group mb-3">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_error)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                    <span class="invalid-feedback"><?php echo $username_error; ?></span>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_error)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                    <span class="invalid-feedback"><?php echo $email_error; ?></span>
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
                                    <label for="confirm_password">Confirm Password</label>
                                    <div class="password-toggle">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_error)) ? 'is-invalid' : ''; ?>">
                                        <i class="toggle-password fas fa-eye"></i>
                                        <span class="invalid-feedback"><?php echo $confirm_password_error; ?></span>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <button type="submit" class="btn btn-primary btn-block w-100">Sign Up</button>
                                </div>
                            </form>
                            
                            <div class="auth-footer">
                                <p>Already have an account? <a href="login.php">Login here</a></p>
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