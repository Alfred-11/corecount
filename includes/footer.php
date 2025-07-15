</main>
        </div> <!-- End of content -->
    </div> <!-- End of wrapper -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>CoreCount</h5>
                    <p>Your ultimate fitness planner for tracking workouts, monitoring progress, and achieving your fitness goals.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/categories.php">Workouts</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/progress.php">Progress</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/schedule.php">Schedule</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/profile.php">Profile</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/admin.php">Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt"></i> Udupi, Workout City</p>
                        <p><i class="fas fa-phone"></i> +91 9969110000</p>
                        <p><i class="fas fa-envelope"></i> info@corecount.com</p>
                    </address>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-center">
                    <hr>
                    <p class="copyright">© <?php echo date('Y'); ?> CoreCount. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>