<?php
/**
 * User Messages Page
 * CoreCount Fitness Planner
 * 
 * This page displays contact messages and admin replies for logged-in users
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Require user to be logged in
requireLogin();

// Get current user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username, email FROM users WHERE user_id = :user_id";
$user_stmt = $db->prepare($user_query);
$user_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Check if contact_messages table has admin_reply column
$check_column = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'admin_reply'")->rowCount();
if ($check_column == 0) {
    // Add admin_reply and replied_at columns
    $db->exec("ALTER TABLE contact_messages ADD COLUMN admin_reply TEXT AFTER message, ADD COLUMN replied_at DATETIME DEFAULT NULL AFTER admin_reply");
}

// Process new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $message_type = trim($_POST['message_type']);
    
    // Validate input
    $error = '';
    
    if (empty($subject)) {
        $error = "Subject is required";
    } elseif (empty($message)) {
        $error = "Message is required";
    } elseif (empty($message_type)) {
        $error = "Message type is required";
    }
    
    if (empty($error)) {
        // Insert new message
        $insert_sql = "INSERT INTO contact_messages (name, email, subject, message, message_type) 
                      VALUES (:name, :email, :subject, :message, :message_type)";
        $insert_stmt = $db->prepare($insert_sql);
        $insert_stmt->bindParam(':name', $user['username'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
        $insert_stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $insert_stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $insert_stmt->bindParam(':message_type', $message_type, PDO::PARAM_STR);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Your message has been sent successfully!";
            redirect(SITE_URL . '/user_messages.php');
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Fetch user's messages
$messages_query = "SELECT * FROM contact_messages WHERE email = :email ORDER BY submitted_at DESC";
$messages_stmt = $db->prepare($messages_query);
$messages_stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
$messages_stmt->execute();
$messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "My Messages - CoreCount";

// Include header
include 'includes/header.php';
?>

<main>
    <div class="container py-4">
        <div class="row">
            <div class="col-12 mb-4">
                <h1>My Messages</h1>
                <p class="lead">View your message history and admin responses</p>
                
                <?php echo flash('success', 'alert alert-success'); ?>
                <?php echo flash('error', 'alert alert-danger'); ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Send Us a Message</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message_type" class="form-label">Message Type</label>
                                <select class="form-select" id="message_type" name="message_type" required>
                                    <option value="">Select a message type</option>
                                    <option value="suggestion">Suggestion</option>
                                    <option value="report">Report an Issue</option>
                                    <option value="question">Question</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="submit_message" class="btn btn-primary">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Message History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($messages) > 0): ?>
                            <div class="accordion" id="messageAccordion">
                                <?php foreach ($messages as $index => $message): ?>
                                    <div class="accordion-item mb-3 border">
                                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <span><?php echo htmlspecialchars($message['subject']); ?></span>
                                                    <span class="badge bg-<?php echo isset($message['replied_at']) ? 'success' : 'warning'; ?> ms-2">
                                                        <?php echo isset($message['replied_at']) ? 'Replied' : 'Pending'; ?>
                                                    </span>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#messageAccordion">
                                            <div class="accordion-body">
                                                <div class="message-meta mb-3">
                                                    <small class="text-muted">Sent on: <?php echo date('M d, Y H:i', strtotime($message['submitted_at'])); ?></small>
                                                    <small class="text-muted ms-3">Type: <?php echo htmlspecialchars($message['message_type']); ?></small>
                                                </div>
                                                
                                                <div class="message-content mb-3">
                                                    <h6>Your Message:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                </div>
                                                
                                                <?php if (isset($message['admin_reply']) && !empty($message['admin_reply'])): ?>
                                                    <div class="admin-reply bg-light p-3 rounded">
                                                        <h6>Admin Reply:</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?></p>
                                                        <small class="text-muted">Replied on: <?php echo date('M d, Y H:i', strtotime($message['replied_at'])); ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i> Waiting for admin response
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p>You haven't sent any messages yet. Use the form to send a message to our team.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>