<?php
/**
 * Header Template
 * CoreCount Fitness Planner
 */

// Ensure config is loaded
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : SITE_NAME . ' - Fitness Planner'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <?php if (basename($_SERVER['PHP_SELF']) === 'schedule.php'): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/schedule.css?v=<?php echo time(); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/icon-colors.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="wrapper">
        <nav id="sidebar" class="<?php echo (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true') || (isset($force_sidebar_collapse) && $force_sidebar_collapse === true) ? 'collapsed' : ''; ?>">
            <div class="sidebar-header">
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <img src="<?php echo SITE_URL; ?>/assets/images/logo6.png" alt="Fitness Planner Logo">
                    </a>
                </div>
            </div>

            <ul class="list-unstyled components">
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/categories.php">
                        <i class="fas fa-dumbbell"></i> Workouts
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'progress.php' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/progress.php">
                            <i class="fas fa-chart-line"></i> Progress
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/schedule.php">
                            <i class="fas fa-calendar-alt"></i> Schedule
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_messages.php' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/user_messages.php">
                            <i class="fas fa-envelope"></i> My Messages
                        </a>
                    </li>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin_dashboard.php">
                            <i class="fas fa-cog"></i> Admin Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
                    <a href="<?php echo SITE_URL; ?>/contact.php">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto">
                            <?php if (isLoggedIn()): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-user-circle"></i> 
                                        <?php 
                                        echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
                                        ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">Profile</a></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/change-password.php">Change Password</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Login</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Flash Messages -->
            <div class="container mt-3">
                <?php
                echo flash('success');
                echo flash('error', 'alert alert-danger');
                echo flash('warning', 'alert alert-warning');
                echo flash('info', 'alert alert-info');
                ?>
            </div>

            <!-- Main Content -->
            <main>