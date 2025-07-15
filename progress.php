<?php
/**
 * User Progress Page
 * CoreCount Fitness Planner
 */

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';

// Ensure user is logged in
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Determine date range based on period
$end_date = date('Y-m-d');
switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'all':
        $start_date = '1970-01-01'; // All time
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-1 month'));
        $period = 'month';
}

// Build query conditions
$conditions = "WHERE up.user_id = :user_id AND up.completion_date BETWEEN :start_date AND :end_date";
$params = [
    ':user_id' => $user_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date . ' 23:59:59'
];

if ($category > 0) {
    $conditions .= " AND w.category_id = :category_id";
    $params[':category_id'] = $category;
}

// Fetch user's workout progress
$progress_query = "SELECT up.*, w.name as workout_name, w.difficulty_level, 
                         c.name as category_name, c.category_id
                  FROM user_progress up
                  JOIN workouts w ON up.workout_id = w.workout_id
                  JOIN workout_categories c ON w.category_id = c.category_id
                  $conditions
                  ORDER BY up.completion_date DESC";

$progress_stmt = $db->prepare($progress_query);
foreach ($params as $key => $value) {
    $progress_stmt->bindValue($key, $value);
}
$progress_stmt->execute();
$progress_records = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_workouts = count($progress_records);
$total_duration = 0;
$total_calories = 0;
$workouts_by_category = [];

foreach ($progress_records as $record) {
    $total_duration += $record['duration'];
    $total_calories += $record['calories_burned'];
    
    $category_id = $record['category_id'];
    if (!isset($workouts_by_category[$category_id])) {
        $workouts_by_category[$category_id] = [
            'name' => $record['category_name'],
            'count' => 0,
            'duration' => 0,
            'calories' => 0
        ];
    }
    
    $workouts_by_category[$category_id]['count']++;
    $workouts_by_category[$category_id]['duration'] += $record['duration'];
    $workouts_by_category[$category_id]['calories'] += $record['calories_burned'];
}

// Prepare data for charts
// 1. Workout frequency over time (for line chart)
$workouts_by_date = [];
$dates_array = [];
$counts_array = [];

// Group workouts by date
foreach ($progress_records as $record) {
    $date = date('Y-m-d', strtotime($record['completion_date']));
    if (!isset($workouts_by_date[$date])) {
        $workouts_by_date[$date] = 0;
    }
    $workouts_by_date[$date]++;
}

// Sort by date
ksort($workouts_by_date);

// Format for chart
foreach ($workouts_by_date as $date => $count) {
    $dates_array[] = date('M d', strtotime($date));
    $counts_array[] = $count;
}

// 2. Prepare category data for pie chart
$category_names = [];
$category_counts = [];
$category_colors = [];

// Define a color palette for categories
$color_palette = [
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
    '#FF9F40', '#8AC249', '#EA526F', '#7B68EE', '#20B2AA'
];

$color_index = 0;
foreach ($workouts_by_category as $cat_id => $cat_data) {
    $category_names[] = $cat_data['name'];
    $category_counts[] = $cat_data['count'];
    $category_colors[] = $color_palette[$color_index % count($color_palette)];
    $color_index++;
}

// Fetch all workout categories for filter dropdown
$categories_query = "SELECT * FROM workout_categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$page_title = "My Progress - CoreCount";

// Include header
include_once 'includes/header.php';
?>
<!-- Include Progress Page Specific CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/progress.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/category-cards.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive-enhancements.css?v=<?php echo time(); ?>">

<main class="container py-4">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3">My Workout Progress</h1>
            <p class="lead">Track your fitness journey and see how far you've come.</p>
        </div>
    </div>
    
    <!-- Filters and Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="stats-card">
                <div class="card-body">
                    <h5 class="card-title">Quick Stats</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-fire"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?php echo $total_calories; ?></h4>
                                    <p>Total Calories Burned</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?php echo $total_duration; ?> min</h4>
                                    <p>Total Workout Time</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?php echo $total_workouts; ?></h4>
                                    <p>Workouts Completed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="stat-info">
                                    <h4><?php echo count($workouts_by_date); ?></h4>
                                    <p>Active Days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="filter-card">
                <div class="card-body">
                    <h5 class="card-title">Filter Results</h5>
                    <form action="" method="GET" class="mt-2">
                        <div class="mb-3">
                            <label for="period" class="form-label">Time Period</label>
                            <select name="period" id="period" class="form-select">
                                <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Last Week</option>
                                <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Last Month</option>
                                <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Last Year</option>
                                <option value="all" <?php echo $period == 'all' ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Workout Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Workout Progress Charts -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4 mb-md-0">
            <div class="chart-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Workout Frequency</h5>
                    <?php if (empty($dates_array)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No workout data available for the selected period.</p>
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="workoutFrequencyChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Workout Categories Distribution</h5>
                    <?php if (empty($category_names)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No category data available for the selected period.</p>
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="workoutCategoriesChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Summary -->
    <div class="row mb-4 section-with-gradient">
        <div class="col-12">
            <div class="chart-card">
                <div class="card-body">
                    <h5 class="card-title">Progress Summary</h5>
                    <div class="row text-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-4 rounded progress-summary-card">
                                <h2 class="mb-0"><?php echo $total_workouts; ?></h2>
                                <p class="mb-0">Workouts Completed</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-4 rounded progress-summary-card">
                                <h2 class="mb-0"><?php echo $total_duration; ?> min</h2>
                                <p class="mb-0">Total Workout Time</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 rounded progress-summary-card">
                                <h2 class="mb-0"><?php echo $total_calories; ?></h2>
                                <p class="mb-0">Calories Burned</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <?php if (!empty($workouts_by_category)): ?>
    <div class="row mb-4 section-with-gradient">
        <div class="col-12">
            <div class="chart-card">
                <div class="card-body">
                    <h5 class="card-title">Workout Categories</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Workouts</th>
                                    <th>Duration (min)</th>
                                    <th>Calories</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workouts_by_category as $cat_id => $cat_data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat_data['name']); ?></td>
                                    <td><?php echo $cat_data['count']; ?></td>
                                    <td><?php echo $cat_data['duration']; ?></td>
                                    <td><?php echo $cat_data['calories']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Workout History -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <div class="card-body">
                    <h5 class="card-title">Workout History</h5>
                    
                    <?php if (empty($progress_records)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No workout records found for the selected period. Try changing your filters or complete some workouts to see your progress here.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Workout</th>
                                    <th>Category</th>
                                    <th>Duration</th>
                                    <th>Calories</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progress_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['completion_date'])); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/workout.php?id=<?php echo $record['workout_id']; ?>">
                                            <?php echo htmlspecialchars($record['workout_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['category_name']); ?></td>
                                    <td><?php echo $record['duration']; ?> min</td>
                                    <td><?php echo $record['calories_burned']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Include Chart.js library and custom chart styles -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/charts.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize charts if data is available
    <?php if (!empty($dates_array)): ?>
    // Workout Frequency Chart (Line Chart)
    var frequencyCtx = document.getElementById('workoutFrequencyChart').getContext('2d');
    var frequencyChart = new Chart(frequencyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates_array); ?>,
            datasets: [{
                label: 'Workouts Completed',
                data: <?php echo json_encode($counts_array); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Workout Frequency Over Time'
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            layout: {
                padding: 10
            },
            animation: {
                duration: 500
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($category_names)): ?>
    // Workout Categories Chart (Pie Chart)
    var categoriesCtx = document.getElementById('workoutCategoriesChart').getContext('2d');
    var categoriesChart = new Chart(categoriesCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($category_names); ?>,
            datasets: [{
                data: <?php echo json_encode($category_counts); ?>,
                backgroundColor: <?php echo json_encode($category_colors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Workout Distribution by Category'
                },
                legend: {
                    display: true,
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' workouts (' + percentage + '%)';
                        }
                    }
                }
            },
            layout: {
                padding: 10
            },
            animation: {
                duration: 500
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include_once 'includes/footer.php'; ?>