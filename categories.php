<?php
/**
 * Workout Categories Page
 * CoreCount Fitness Planner
 * 
 * This page displays all workout categories and allows users to browse workouts by category
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if a specific category is requested
$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch all categories
$categories_query = "SELECT * FROM workout_categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch workouts based on category filter
if ($category_id > 0) {
    // Fetch workouts for a specific category
    $workouts_query = "SELECT w.*, c.name as category_name 
                      FROM workouts w 
                      JOIN workout_categories c ON w.category_id = c.category_id 
                      WHERE w.category_id = :category_id 
                      ORDER BY w.name";
    $workouts_stmt = $db->prepare($workouts_query);
    $workouts_stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $workouts_stmt->execute();
    
    // Get the selected category name
    $selected_category = null;
    foreach ($categories as $category) {
        if ($category['category_id'] == $category_id) {
            $selected_category = $category;
            break;
        }
    }
} else {
    // Fetch all workouts
    $workouts_query = "SELECT w.*, c.name as category_name 
                      FROM workouts w 
                      JOIN workout_categories c ON w.category_id = c.category_id 
                      ORDER BY c.name, w.name";
    $workouts_stmt = $db->prepare($workouts_query);
    $workouts_stmt->execute();
}

$workouts = $workouts_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group workouts by category for display
$workouts_by_category = [];
foreach ($workouts as $workout) {
    $cat_id = $workout['category_id'];
    if (!isset($workouts_by_category[$cat_id])) {
        $workouts_by_category[$cat_id] = [
            'category_name' => $workout['category_name'],
            'workouts' => []
        ];
    }
    $workouts_by_category[$cat_id]['workouts'][] = $workout;
}

// Set page title
if ($category_id > 0 && $selected_category) {
    $page_title = htmlspecialchars($selected_category['name']) . " Workouts - CoreCount";
} else {
    $page_title = "Workout Categories - CoreCount";
}

// Include header
include 'includes/header.php';
?>

<!-- Include Workout Cards CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/category-cards.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive-enhancements.css?v=<?php echo time(); ?>">


<main>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <?php if ($category_id > 0 && $selected_category): ?>
                    <h1><?php echo htmlspecialchars($selected_category['name']); ?> Workouts</h1>
                    <?php if (!empty($selected_category['description'])): ?>
                        <p class="lead"><?php echo htmlspecialchars($selected_category['description']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <h1>Workout Categories</h1>
                    <p class="lead">Browse our collection of workouts by category</p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-end">
                <?php if ($category_id > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/categories.php" class="btn btn-outline-primary">
                        <i class="fas fa-th"></i> View All Categories
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Filter Buttons -->
        <div class="category-filter mb-4">
            <a href="<?php echo SITE_URL; ?>/categories.php" class="btn <?php echo $category_id == 0 ? 'btn-primary' : 'btn-outline-primary'; ?>">
                All Categories
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo SITE_URL; ?>/categories.php?id=<?php echo $category['category_id']; ?>" 
                   class="btn <?php echo $category_id == $category['category_id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($category_id == 0): ?>
            <!-- Display all categories in cards -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <?php foreach ($categories as $category): ?>
                    <div class="col">
                        <div class="category-card h-100">
                            <div class="category-content p-4">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <?php if (!empty($category['description'])): ?>
                                    <p><?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/categories.php?id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-primary mt-2">View Workouts</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Display workouts -->
        <?php if (count($workouts) > 0): ?>
            <?php if ($category_id == 0): ?>
                <!-- Group workouts by category when showing all -->
                <?php foreach ($workouts_by_category as $cat_id => $category_data): ?>
                    <div class="category-section mb-5">
                        <h2 class="category-title mb-4"><?php echo htmlspecialchars($category_data['category_name']); ?> Workouts</h2>
                        <div class="workout-grid">
                            <?php foreach ($category_data['workouts'] as $workout): ?>
                                <div>
                                    <div class="workout-card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($workout['name']); ?></h5>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-<?php 
                                                    echo $workout['difficulty_level'] === 'beginner' ? 'success' :
                                                        ($workout['difficulty_level'] === 'intermediate' ? 'warning' : 'danger');
                                                ?>"><?php echo ucfirst($workout['difficulty_level']); ?></span>
                                                <small class="text-muted"><?php echo $workout['duration']; ?> min</small>
                                            </div>
                                            <p class="card-text"><?php echo htmlspecialchars(substr($workout['description'], 0, 100)); ?>...</p>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0">
                                            <a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $workout['workout_id']; ?>" class="btn btn-primary w-100">View Workout</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Show workouts for selected category -->
                <div class="category-section mb-5">
                    <h2 class="category-title mb-4"><?php echo htmlspecialchars($selected_category['name']); ?> Workouts</h2>
                    <div class="workout-grid">
                        <?php foreach ($workouts as $workout): ?>
                            <div>
                                <div class="workout-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($workout['name']); ?></h5>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-<?php 
                                                echo $workout['difficulty_level'] === 'beginner' ? 'success' :
                                                    ($workout['difficulty_level'] === 'intermediate' ? 'warning' : 'danger');
                                            ?>"><?php echo ucfirst($workout['difficulty_level']); ?></span>
                                            <small class="text-muted"><?php echo $workout['duration']; ?> min</small>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($workout['description'], 0, 100)); ?>...</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $workout['workout_id']; ?>" class="btn btn-primary mt-2">View Workout</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No workouts found for this category.
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>