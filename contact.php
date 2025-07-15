<?php
/**
 * Contact Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Define variables and set to empty values
$name = $email = $subject = $message = $message_type = '';
$name_error = $email_error = $subject_error = $message_error = $message_type_error = '';
$success_message = $error_message = '';

// Process form data when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate name
    if (empty(trim($_POST['name']))) {
        $name_error = 'Please enter your name';
    } else {
        $name = trim($_POST['name']);
    }
    
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_error = 'Please enter your email address';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Please enter a valid email address';
    } else {
        $email = trim($_POST['email']);
    }
    
    // Validate subject
    if (empty(trim($_POST['subject']))) {
        $subject_error = 'Please enter a subject';
    } else {
        $subject = trim($_POST['subject']);
    }
    
    // Validate message
    if (empty(trim($_POST['message']))) {
        $message_error = 'Please enter your message';
    } else {
        $message = trim($_POST['message']);
    }
    
    // Validate message type
    if (empty(trim($_POST['message_type']))) {
        $message_type_error = 'Please select a message type';
    } else {
        $message_type = trim($_POST['message_type']);
    }
    
    // Check input errors before inserting into database
    if (empty($name_error) && empty($email_error) && empty($subject_error) && empty($message_error) && empty($message_type_error)) {
        // Prepare an insert statement
        $sql = "INSERT INTO contact_messages (name, email, subject, message, message_type) VALUES (:name, :email, :subject, :message, :message_type)";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':message_type', $message_type, PDO::PARAM_STR);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Send notification email to admin
                $admin_email = EMAIL_FROM;
                $admin_subject = 'New Contact Form Submission - CoreCount';
                
                $admin_message = "<html><body>";
                $admin_message .= "<h2>New Contact Form Submission</h2>";
                $admin_message .= "<p><strong>Name:</strong> {$name}</p>";
                $admin_message .= "<p><strong>Email:</strong> {$email}</p>";
                $admin_message .= "<p><strong>Subject:</strong> {$subject}</p>";
                $admin_message .= "<p><strong>Message Type:</strong> {$message_type}</p>";
                $admin_message .= "<p><strong>Message:</strong></p>";
                $admin_message .= "<p>{$message}</p>";
                $admin_message .= "</body></html>";
                
                // Email headers
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . EMAIL_NAME . ' <' . EMAIL_FROM . '>' . "\r\n";
                
                // Try to send email to admin
                $mail_sent = @mail($admin_email, $admin_subject, $admin_message, $headers);
                
                // Success message - even if mail fails, the contact was saved to database
                $success_message = 'Your message has been sent successfully! We will get back to you soon.';
                
                // Clear form fields
                $name = $email = $subject = $message = $message_type = '';
            } else {
                $error_message = 'Something went wrong. Please try again later.';
            }
        }
    }
}

// Page title
$page_title = "Contact Us - CoreCount Fitness Planner";

// Include header
include_once 'includes/header.php';
?>

<!-- Include Responsive Enhancements CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive-enhancements.css?v=<?php echo time(); ?>">


<div class="container mt-4">
    <h1 class="text-center mb-4">Contact Us</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Location and Contact Info -->
        <div class="col-md-12 mb-4">
            <div class="alert alert-info">
                <p><i class="fas fa-info-circle me-2"></i> To send us a message, please visit the <a href="<?php echo SITE_URL; ?>/user_messages.php">My Messages</a> section.</p>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="contact-info h-100 card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Contact Information</h4>
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Our Location</h4>
                        <p>Udupi , Workout City</p>
                    </div>
                </div>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Phone Number</h4>
                        <p>+91 9969110000</p>
                    </div>
                </div>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Email Address</h4>
                        <p>info@corecount.com</p>
                    </div>
                </div>
                
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-info-content">
                        <h4>Business Hours</h4>
                        <p>Monday - Friday: 9:00 AM - 5:00 PM</p>
                    </div>
                </div>
                
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4 class="mb-3">Frequently Asked Questions</h4>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Why CoreCount?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>CoreCount offers a comprehensive fitness planning solution with workout tracking, progress monitoring, and personalized scheduling. Our platform is designed to help you achieve your fitness goals efficiently and effectively.</p>
                                    <p>We combine science-backed workout methodologies with user-friendly technology to create a seamless fitness experience. Whether you're a beginner or an advanced athlete, CoreCount adapts to your needs and helps you stay consistent with your fitness journey.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How to schedule workouts?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>To schedule a workout, follow these simple steps:</p>
                                    <ol>
                                        <li>Navigate to the <strong>Schedule</strong> section in the main menu</li>
                                        <li>Browse through available workouts or use filters to find specific ones</li>
                                        <li>Click on your preferred workout to view details</li>
                                        <li>Select a date and time that works for you</li>
                                        <li>Click "Schedule Workout" to add it to your calendar</li>
                                    </ol>
                                    <p>You can view and manage all your scheduled workouts in the calendar view. Need to reschedule? Simply click on the workout in your calendar and select "Reschedule" or "Remove" as needed.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    What are categories and how does progress work?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p><strong>Workout Categories:</strong></p>
                                    <p>Categories organize workouts by type, making it easier to find exercises that target specific fitness goals:</p>
                                    <ul>
                                        <li><strong>Cardio:</strong> Improves heart health and burns calories (running, cycling, HIIT)</li>
                                        <li><strong>Strength:</strong> Builds muscle and increases power (weightlifting, resistance training)</li>
                                        <li><strong>Flexibility:</strong> Enhances range of motion (yoga, stretching)</li>
                                        <li><strong>Core:</strong> Strengthens abdominal and back muscles</li>
                                        <li><strong>Full Body:</strong> Comprehensive workouts targeting multiple muscle groups</li>
                                    </ul>
                                    <p><strong>Progress Tracking:</strong></p>
                                    <p>Our progress system monitors several key metrics:</p>
                                    <ul>
                                        <li>Completed workouts (total and by category)</li>
                                        <li>Calories burned during each session</li>
                                        <li>Workout duration and frequency</li>
                                        <li>Performance improvements over time</li>
                                    </ul>
                                    <p>View detailed progress reports with visual charts and statistics in the Progress section. Filter by time period or category to analyze specific aspects of your fitness journey.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    How is BMI calculated?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>BMI (Body Mass Index) is calculated using the standard formula:</p>
                                    <p class="text-center fw-bold">BMI = weight (kg) / [height (m)]²</p>
                                    <p>CoreCount automatically calculates your BMI based on the height and weight information in your profile. The result falls into one of these categories:</p>
                                    <ul>
                                        <li><strong>Below 18.5:</strong> Underweight</li>
                                        <li><strong>18.5-24.9:</strong> Normal weight</li>
                                        <li><strong>25.0-29.9:</strong> Overweight</li>
                                        <li><strong>30.0 and above:</strong> Obesity</li>
                                    </ul>
                                    <p>While BMI provides a useful general assessment, it doesn't account for factors like muscle mass, bone density, or body composition. We recommend using it as one of several metrics to track your fitness progress.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Can I customize my workout plan?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="faqFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Yes! CoreCount offers several ways to customize your workout experience:</p>
                                    <ul>
                                        <li><strong>Personalized Scheduling:</strong> Create a workout calendar that fits your lifestyle</li>
                                        <li><strong>Difficulty Levels:</strong> Choose workouts based on your fitness level (beginner, intermediate, advanced)</li>
                                        <li><strong>Category Focus:</strong> Emphasize specific workout types based on your goals</li>
                                        <li><strong>Duration Options:</strong> Select workouts that fit your available time</li>
                                    </ul>
                                    <p>Your profile information also helps us recommend workouts that align with your fitness goals and preferences. As you complete more workouts, our system learns your patterns and can suggest more tailored options.</p>
                                </div>
                            </div>
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