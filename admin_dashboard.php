<?php
/**
 * Admin Dashboard
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Function to require admin login
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = "You must be logged in as an administrator to access this page";
        redirect(SITE_URL . '/admin.php');
    }
}

// Require admin login for this page
requireAdmin();

// Get active section from URL parameter
$section = isset($_GET['section']) ? $_GET['section'] : 'users';

// Handle user deletion if requested
if ($section === 'users' && isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    
    // Delete user
    $delete_sql = "DELETE FROM users WHERE user_id = :user_id";
    $delete_stmt = $db->prepare($delete_sql);
    $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete user";
    }
    
    // Redirect to remove the action from URL
    redirect(SITE_URL . '/admin_dashboard.php?section=users');
}

// Handle message reply if submitted
if ($section === 'messages' && isset($_POST['reply_message']) && is_numeric($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $reply = trim($_POST['reply']);
    $user_email = trim($_POST['user_email']);
    
    if (!empty($reply) && !empty($user_email)) {
        // Update message with reply
        $update_sql = "UPDATE contact_messages SET admin_reply = :reply, replied_at = NOW() WHERE message_id = :message_id";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->bindParam(':reply', $reply, PDO::PARAM_STR);
        $update_stmt->bindParam(':message_id', $message_id, PDO::PARAM_INT);
        
        if ($update_stmt->execute()) {
            // Send email to user
            $subject = "Reply to your CoreCount message";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . EMAIL_NAME . ' <' . EMAIL_FROM . '>' . "\r\n";
            
            $email_message = "<html><body>";
            $email_message .= "<h2>Reply from CoreCount Admin</h2>";
            $email_message .= "<p>Thank you for contacting us. Here is our response to your inquiry:</p>";
            $email_message .= "<p><strong>Reply:</strong> {$reply}</p>";
            $email_message .= "<p>If you have any further questions, please don't hesitate to contact us again.</p>";
            $email_message .= "<p>Best regards,<br>CoreCount Team</p>";
            $email_message .= "</body></html>";
            
            mail($user_email, $subject, $email_message, $headers);
            
            $_SESSION['success'] = "Reply sent successfully";
        } else {
            $_SESSION['error'] = "Failed to send reply";
        }
        
        // Redirect to remove the form submission
        redirect(SITE_URL . '/admin_dashboard.php?section=messages');
    } else {
        $_SESSION['error'] = "Reply cannot be empty";
    }
}

// Handle admin password change
if ($section === 'manage' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $admin_id = $_SESSION['admin_id'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long";
    } else {
        // Verify current password
        $check_sql = "SELECT password FROM admins WHERE admin_id = :admin_id";
        $check_stmt = $db->prepare($check_sql);
        $check_stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $check_stmt->execute();
        
        if ($row = $check_stmt->fetch()) {
            if (password_verify($current_password, $row['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE admins SET password = :password WHERE admin_id = :admin_id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $update_stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
                
                if ($update_stmt->execute()) {
                    $_SESSION['success'] = "Password changed successfully";
                } else {
                    $_SESSION['error'] = "Failed to change password";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect";
            }
        } else {
            $_SESSION['error'] = "Admin account not found";
        }
    }
    
    // Redirect to remove the form submission
    redirect(SITE_URL . '/admin_dashboard.php?section=manage');
}

// Handle new admin creation
if ($section === 'manage' && isset($_POST['create_admin'])) {
    $new_username = trim($_POST['new_username']);
    $new_admin_password = trim($_POST['new_admin_password']);
    $new_admin_email = trim($_POST['new_admin_email']);
    
    // Validate input
    if (empty($new_username) || empty($new_admin_password)) {
        $_SESSION['error'] = "Username and password are required";
    } elseif (strlen($new_admin_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long";
    } else {
        // Check if username already exists
        $check_sql = "SELECT COUNT(*) as count FROM admins WHERE username = :username";
        $check_stmt = $db->prepare($check_sql);
        $check_stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
        $check_stmt->execute();
        $result = $check_stmt->fetch();
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Username already exists";
        } else {
            // Create new admin
            $hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO admins (username, password, email) VALUES (:username, :password, :email)";
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
            $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $insert_stmt->bindParam(':email', $new_admin_email, PDO::PARAM_STR);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "New admin created successfully";
            } else {
                $_SESSION['error'] = "Failed to create new admin";
            }
        }
    }
    
    // Redirect to remove the form submission
    redirect(SITE_URL . '/admin_dashboard.php?section=manage');
}

// Set page title
$page_title = "Admin Dashboard - CoreCount";

// Set page title
$page_title = "Admin Dashboard - CoreCount";
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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
<?php
?>

<main class="admin-main">
    <!-- Admin Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 admin-navbar">
        <div class="container-fluid">
            <div class="navbar-brand">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo6.png" alt="CoreCount Logo" height="40" class="d-inline-block align-top">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'users' ? 'active' : ''; ?>" href="?section=users">
                            <i class="fas fa-users me-1"></i> User Records
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'messages' ? 'active' : ''; ?>" href="?section=messages">
                            <i class="fas fa-envelope me-1"></i> Contact Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'progress' ? 'active' : ''; ?>" href="?section=progress">
                            <i class="fas fa-chart-line me-1"></i> User Progress
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'manage' ? 'active' : ''; ?>" href="?section=manage">
                            <i class="fas fa-cog me-1"></i> Admin Manage
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-light">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-3">
        <?php echo flash('success', 'alert alert-success'); ?>
        <?php echo flash('error', 'alert alert-danger'); ?>
        
        <!-- Content Area -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <?php if ($section === 'progress'): ?>
                        <!-- User Progress Section -->
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Progress - Workout Types</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-8 offset-md-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Frequently Used Workout Types</h5>
                                            <canvas id="workoutTypeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <h5>Recent Workout Completions</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Workout</th>
                                                    <th>Type</th>
                                                    <th>Duration</th>
                                                    <th>Calories</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Get recent workout completions with workout name and category
                                                $progress_query = "SELECT p.*, u.username, w.name, c.name as category_name 
                                                                  FROM user_progress p
                                                                  JOIN users u ON p.user_id = u.user_id
                                                                  JOIN workouts w ON p.workout_id = w.workout_id
                                                                  JOIN workout_categories c ON w.category_id = c.category_id
                                                                  ORDER BY p.completion_date DESC LIMIT 10";
                                                $progress_stmt = $db->prepare($progress_query);
                                                $progress_stmt->execute();
                                                $progress_data = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                if (count($progress_data) > 0):
                                                    foreach ($progress_data as $progress):
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($progress['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($progress['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($progress['category_name']); ?></td>
                                                    <td><?php echo $progress['duration']; ?> min</td>
                                                    <td><?php echo $progress['calories_burned']; ?> cal</td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($progress['completion_date'])); ?></td>
                                                </tr>
                                                <?php
                                                    endforeach;
                                                else:
                                                ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No workout progress data found</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <?php
                            // Get workout category statistics for chart
                            $workout_types_query = "SELECT c.name as category_name, COUNT(*) as count 
                                                   FROM user_progress p
                                                   JOIN workouts w ON p.workout_id = w.workout_id
                                                   JOIN workout_categories c ON w.category_id = c.category_id
                                                   GROUP BY c.name
                                                   ORDER BY count DESC";
                            $workout_types_stmt = $db->prepare($workout_types_query);
                            $workout_types_stmt->execute();
                            $workout_types_data = $workout_types_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Prepare data for chart
                            $chart_labels = [];
                            $chart_data = [];
                            $chart_colors = [
                                'rgba(255, 99, 132, 0.7)',   // Cardio - Red
                                'rgba(54, 162, 235, 0.7)',  // HIIT - Blue
                                'rgba(255, 206, 86, 0.7)',  // Strength - Yellow
                                'rgba(75, 192, 192, 0.7)',  // Flexibility - Teal
                                'rgba(153, 102, 255, 0.7)', // Core - Purple
                                'rgba(255, 159, 64, 0.7)',  // Orange (if needed)
                                'rgba(199, 199, 199, 0.7)',  // Gray (if needed)
                                'rgba(83, 102, 255, 0.7)',   // Blue-purple (if needed)
                                'rgba(40, 159, 64, 0.7)',    // Green (if needed)
                                'rgba(210, 199, 199, 0.7)'   // Light gray (if needed)
                            ];
                            
                            foreach ($workout_types_data as $type) {
                                $chart_labels[] = $type['category_name'];
                                $chart_data[] = $type['count'];
                            }
                            ?>
                            
                            <!-- Chart.js Script -->
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const ctx = document.getElementById('workoutTypeChart').getContext('2d');
                                    const workoutTypeChart = new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: <?php echo json_encode($chart_labels); ?>,
                                            datasets: [{
                                                label: 'Workout Type Usage',
                                                data: <?php echo json_encode($chart_data); ?>,
                                                backgroundColor: <?php echo json_encode($chart_colors); ?>,
                                                borderColor: <?php echo json_encode($chart_colors); ?>,
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            plugins: {
                                                legend: {
                                                    position: 'top',
                                                },
                                                title: {
                                                    display: true,
                                                    text: 'Workout Types Frequency'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Number of Workouts'
                                                    }
                                                },
                                                x: {
                                                    title: {
                                                        display: true,
                                                        text: 'Workout Category'
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    
                    <?php elseif ($section === 'users'): ?>
                        <!-- User Records -->
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Records</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all users
                                        $users_query = "SELECT user_id, username, email, created_at FROM users ORDER BY created_at DESC";
                                        $users_stmt = $db->prepare($users_query);
                                        $users_stmt->execute();
                                        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (count($users) > 0):
                                            foreach ($users as $user):
                                        ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="?section=users&delete_user=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No users found</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    
                    <?php elseif ($section === 'messages'): ?>
                        <!-- Contact Messages -->
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Contact Messages</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Check if contact_messages table has admin_reply column
                            $check_column = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'admin_reply'")->rowCount();
                            if ($check_column == 0) {
                                // Add admin_reply and replied_at columns
                                $db->exec("ALTER TABLE contact_messages ADD COLUMN admin_reply TEXT AFTER message, ADD COLUMN replied_at DATETIME DEFAULT NULL AFTER admin_reply");
                            }
                            
                            // Fetch all messages
                            $messages_query = "SELECT * FROM contact_messages ORDER BY submitted_at DESC";
                            $messages_stmt = $db->prepare($messages_query);
                            $messages_stmt->execute();
                            $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($messages) > 0):
                                foreach ($messages as $message):
                            ?>
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                    <span class="badge bg-<?php echo isset($message['replied_at']) ? 'success' : 'warning'; ?>">
                                        <?php echo isset($message['replied_at']) ? 'Replied' : 'Pending'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>From:</strong> <?php echo htmlspecialchars($message['name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</p>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <p><strong>Type:</strong> <?php echo htmlspecialchars($message['message_type']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="message-content mb-3">
                                        <h6>Message:</h6>
                                        <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    </div>
                                    
                                    <?php if (isset($message['admin_reply']) && !empty($message['admin_reply'])): ?>
                                    <div class="admin-reply mb-3 bg-light p-3 rounded">
                                        <h6>Your Reply:</h6>
                                        <p><?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?></p>
                                        <small class="text-muted">Replied on: <?php echo date('M d, Y H:i', strtotime($message['replied_at'])); ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="reply-form">
                                        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm<?php echo $message['message_id']; ?>">
                                            <?php echo isset($message['admin_reply']) ? 'Send New Reply' : 'Reply'; ?>
                                        </button>
                                        
                                        <div class="collapse mt-3" id="replyForm<?php echo $message['message_id']; ?>">
                                            <form method="post" action="?section=messages">
                                                <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                                                <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($message['email']); ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="reply<?php echo $message['message_id']; ?>" class="form-label">Your Reply</label>
                                                    <textarea class="form-control" id="reply<?php echo $message['message_id']; ?>" name="reply" rows="4" required></textarea>
                                                </div>
                                                
                                                <button type="submit" name="reply_message" class="btn btn-success">Send Reply</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-muted">
                                    Submitted on: <?php echo date('M d, Y H:i', strtotime($message['submitted_at'])); ?>
                                </div>
                            </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <div class="alert alert-info">No messages found</div>
                            <?php endif; ?>
                        </div>
                    
                    <?php elseif ($section === 'progress'): ?>
                        <!-- User Progress -->
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Progress</h5>
                        </div>
                        <div class="card-body">
                            <!-- Exercise Usage Chart -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">Exercise Usage Frequency</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container" style="position: relative; height:350px; width:100%">
                                                <canvas id="exerciseUsageChart"></canvas>
                                            </div>
                                            <?php
                                            // Fetch exercise usage data
                                            $exercise_usage_query = "SELECT e.name as exercise_name, COUNT(*) as usage_count 
                                                FROM user_progress p 
                                                JOIN workouts w ON p.workout_id = w.workout_id 
                                                JOIN workout_exercises we ON w.workout_id = we.workout_id 
                                                JOIN exercises e ON we.exercise_id = e.exercise_id 
                                                GROUP BY e.exercise_id 
                                                ORDER BY usage_count DESC 
                                                LIMIT 10";
                                            $exercise_usage_stmt = $db->prepare($exercise_usage_query);
                                            $exercise_usage_stmt->execute();
                                            $exercise_usage_data = $exercise_usage_stmt->fetchAll(PDO::FETCH_ASSOC);
                                            
                                            // Prepare data for chart
                                            $exercise_names = [];
                                            $exercise_counts = [];
                                            $chart_colors = [];
                                            
                                            // Generate random colors for chart
                                            function generateRandomColor() {
                                                $r = rand(100, 200);
                                                $g = rand(100, 200);
                                                $b = rand(100, 200);
                                                return "rgba($r, $g, $b, 0.7)";
                                            }
                                            
                                            foreach ($exercise_usage_data as $data) {
                                                $exercise_names[] = $data['exercise_name'];
                                                $exercise_counts[] = $data['usage_count'];
                                                $chart_colors[] = generateRandomColor();
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Records Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Workout</th>
                                            <th>Duration</th>
                                            <th>Calories</th>
                                            <th>Completion Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch all progress records with user and workout details
                                        $progress_query = "SELECT p.*, u.username, w.name as workout_name 
                                                         FROM user_progress p 
                                                         JOIN users u ON p.user_id = u.user_id 
                                                         JOIN workouts w ON p.workout_id = w.workout_id 
                                                         ORDER BY p.completion_date DESC";
                                        $progress_stmt = $db->prepare($progress_query);
                                        $progress_stmt->execute();
                                        $progress_records = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (count($progress_records) > 0):
                                            foreach ($progress_records as $record):
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['username']); ?></td>
                                            <td><?php echo htmlspecialchars($record['workout_name']); ?></td>
                                            <td><?php echo $record['duration']; ?> minutes</td>
                                            <td><?php echo $record['calories_burned']; ?> calories</td>
                                            <td><?php echo date('M d, Y H:i', strtotime($record['completion_date'])); ?></td>
                                        </tr>
                                        <?php
                                            endforeach;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No progress records found</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    
                    <?php elseif ($section === 'manage'): ?>
                        <!-- Admin Management -->
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Admin Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Change Password -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">Change Password</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="?section=manage">
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                </div>
                                                
                                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Create New Admin -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">Create New Admin</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="?section=manage">
                                                <div class="mb-3">
                                                    <label for="new_username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="new_username" name="new_username" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_admin_password" class="form-label">Password</label>
                                                    <input type="password" class="form-control" id="new_admin_password" name="new_admin_password" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_admin_email" class="form-label">Email (Optional)</label>
                                                    <input type="email" class="form-control" id="new_admin_email" name="new_admin_email">
                                                </div>
                                                
                                                <button type="submit" name="create_admin" class="btn btn-success">Create Admin</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Admin List -->
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">Admin List</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Username</th>
                                                            <th>Email</th>
                                                            <th>Created</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Fetch all admins
                                                        $admins_query = "SELECT admin_id, username, email, created_at FROM admins ORDER BY created_at DESC";
                                                        $admins_stmt = $db->prepare($admins_query);
                                                        $admins_stmt->execute();
                                                        $admins = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);
                                                        
                                                        if (count($admins) > 0):
                                                            foreach ($admins as $admin):
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $admin['admin_id']; ?></td>
                                                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                            <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                                        </tr>
                                                        <?php
                                                            endforeach;
                                                        else:
                                                        ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center">No admin accounts found</td>
                                                        </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

<script>
    // Initialize any admin-specific JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
        // Add confirmation for delete actions
        const deleteButtons = document.querySelectorAll('[data-confirm]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm(this.getAttribute('data-confirm'))) {
                    e.preventDefault();
                }
            });
        });
        
        // Initialize Exercise Usage Chart if we're on the progress section
        if (document.getElementById('exerciseUsageChart')) {
            const ctx = document.getElementById('exerciseUsageChart').getContext('2d');
            
            // Get data from PHP
            const exerciseNames = <?php echo json_encode($exercise_names ?? []); ?>;
            const exerciseCounts = <?php echo json_encode($exercise_counts ?? []); ?>;
            const chartColors = <?php echo json_encode($chart_colors ?? []); ?>;
            
            // Create the chart
            const exerciseUsageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: exerciseNames,
                    datasets: [{
                        label: 'Usage Frequency',
                        data: exerciseCounts,
                        backgroundColor: chartColors,
                        borderColor: chartColors.map(color => color.replace('0.7', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Used ${context.raw} times`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Usage Count'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Exercise Name'
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
    });
</script>
</body>
</html>