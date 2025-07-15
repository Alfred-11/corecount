<?php
/**
 * Home Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Fetch motivational quotes
$quotes_query = "SELECT * FROM motivational_quotes ORDER BY RAND() LIMIT 5";
$quotes_stmt = $db->prepare($quotes_query);
$quotes_stmt->execute();
$quotes = $quotes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fitness articles section has been removed as the table is no longer needed
$articles = []; // Empty array to prevent errors in the template

// Page title
$page_title = "Home - CoreCount Fitness Planner";

// Include header
include_once 'includes/header.php';
?>

<!-- Video Hero Section -->
<section class="video-hero-section">
    <div class="video-background">
        <video autoplay muted loop playsinline id="hero-video">
            <source src="video and image/workoutvideo.webm" type="video/webm">
            <!-- Fallback image if video doesn't load -->
            <img src="video and image/group-of-people-stand-facing-a-fitness-instructor.jpg" alt="Fitness Class" class="img-fluid">
        </video>
        <div class="video-overlay"></div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto hero-text text-center">
                <h1 class="animate-fade-in">Transform Your Body, Transform Your Life</h1>
                <p class="animate-fade-in-delay">CoreCount helps you track, plan, and achieve your fitness goals with personalized workouts and progress tracking.</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="hero-buttons animate-fade-in-delay-2">
                        <a href="signup.php" class="btn btn-primary btn-lg pulse-btn">Get Started</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                    </div>
                <?php else: ?>
                    <div class="hero-buttons animate-fade-in-delay-2">
                        <a href="categories.php" class="btn btn-primary btn-lg pulse-btn">Start Workout</a>
                        <a href="profile.php" class="btn btn-outline-light btn-lg">View Profile</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Motivational Quotes Carousel -->
<section class="quotes-section">
    <div class="container">
        <h2 class="section-title">Daily Motivation</h2>
        <div id="quotesCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($quotes as $index => $quote): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="quote-card">
                            <blockquote>
                                <p>"<?php echo htmlspecialchars($quote['quote_text']); ?>"</p>
                                <footer>- <?php echo htmlspecialchars($quote['author']); ?></footer>
                            </blockquote>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#quotesCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#quotesCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

<!-- Fitness Articles -->
<section class="articles-section">
    <div class="container">
        <h2 class="section-title">Fitness Articles & Tips</h2>
        <div class="row">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-4 mb-4">
                        <div class="article-card">
                            <?php if (!empty($article['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image_path']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="img-fluid">
                            <?php else: ?>
                                <img src="assets/images/article-placeholder.jpg" alt="Article Image" class="img-fluid">
                            <?php endif; ?>
                            <div class="article-content">
                                <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                                <p class="article-meta">By <?php echo htmlspecialchars($article['author']); ?> | <?php echo date('F j, Y', strtotime($article['published_at'])); ?></p>
                                <p class="article-excerpt"><?php echo substr(strip_tags($article['content']), 0, 150); ?>...</p>
                                <a href="article.php?id=<?php echo $article['article_id']; ?>" class="btn btn-outline-primary">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Article Section -->
<section class="article-feature-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="featured-article-card animate-on-scroll">
                    <h2 class="article-title">The Importance of Fitness: How Workouts Improve Physical and Mental Well-Being</h2>
                    
                    <div class="article-meta">
                        <span><i class="fas fa-user"></i> CoreCount Team</span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?></span>
                    </div>
                    
                    <div class="article-image-container">
                        <img src="video and image/edgar-chaparro-sHfo3WOgGTU-unsplash.jpg" alt="Fitness Training" class="img-fluid rounded">
                    </div>
                    
                    <div class="article-content">
                        <h3>Introduction</h3>
                        <p>In today's fast-paced world, maintaining fitness has become more crucial than ever. A sedentary lifestyle, poor diet, and high-stress levels have led to an increase in lifestyle-related diseases such as obesity, diabetes, and cardiovascular disorders. However, engaging in regular workouts and maintaining physical fitness can drastically improve overall well-being.</p>
                        
                        <p>In this article, we will explore:</p>
                        <ul class="article-list">
                            <li>The importance of fitness</li>
                            <li>The benefits of workouts</li>
                            <li>The mental health advantages of staying active</li>
                            <li>Workout recommendations with links</li>
                        </ul>
                        
                        <h3>Why Fitness is Important?</h3>
                        <p>Fitness is more than just looking good—it's about feeling good, maintaining health, and preventing diseases. Here's why fitness should be a priority in your life:</p>
                        
                        <div class="fitness-benefit-card">
                            <div class="benefit-icon"><i class="fas fa-heartbeat"></i></div>
                            <div class="benefit-content">
                                <h4>1. Prevents Lifestyle Diseases</h4>
                                <ul>
                                    <li>Reduces the risk of heart disease, high blood pressure, type 2 diabetes, and obesity.</li>
                                    <li>Improves insulin sensitivity, lowering the risk of diabetes.</li>
                                    <li>Strengthens the immune system, reducing the chances of falling sick.</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="fitness-benefit-card">
                            <div class="benefit-icon"><i class="fas fa-heart"></i></div>
                            <div class="benefit-content">
                                <h4>2. Improves Heart Health</h4>
                                <ul>
                                    <li>Aerobic exercises like running, swimming, and cycling improve cardiovascular endurance.</li>
                                    <li>Strength training helps regulate blood pressure and lowers cholesterol.</li>
                                    <li>Engaging in at least 150 minutes of moderate-intensity exercise per week reduces the risk of heart-related illnesses.</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4 mb-4">
                            <a href="categories.php" class="btn btn-primary btn-lg">Explore Our Workout Categories</a>
                        </div>
                        
                        <div class="row mt-5">
                            <div class="col-md-6">
                                <div class="article-image-side">
                                    <img src="video and image/victor-freitas-vqDAUejnwKw-unsplash.jpg" alt="Strength Training" class="img-fluid rounded">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h3>Mental Health Benefits of Exercise</h3>
                                <p>Physical fitness is closely tied to mental well-being. Here's how exercise helps mental health:</p>
                                
                                <div class="mental-health-benefit">
                                    <h4><i class="fas fa-brain"></i> Reduces Stress and Anxiety</h4>
                                    <p>Workouts release endorphins, the body's natural stress relievers. Activities like yoga, running, and strength training lower cortisol levels (the stress hormone).</p>
                                </div>
                                
                                <div class="mental-health-benefit">
                                    <h4><i class="fas fa-smile"></i> Boosts Mood and Combats Depression</h4>
                                    <p>Exercise triggers the release of dopamine and serotonin, chemicals that elevate mood. Regular workouts have been proven as effective as antidepressant medications in reducing depression.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title">Why Choose CoreCount?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3>Personalized Workouts</h3>
                    <p>Discover workouts tailored to your fitness level and goals.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Track Your Progress</h3>
                    <p>Monitor your improvements with detailed statistics and charts.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Schedule Workouts</h3>
                    <p>Plan your fitness routine with our easy-to-use scheduler.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Intentionally removed YouTube video section as requested -->

<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Start Your Fitness Journey?</h2>
            <p>Join CoreCount today and transform your workout experience.</p>
            <?php if (!isLoggedIn()): ?>
                <a href="signup.php" class="btn btn-primary btn-lg">Sign Up Now</a>
            <?php else: ?>
                <a href="categories.php" class="btn btn-primary btn-lg">Start Working Out</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>