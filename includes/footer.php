<?php
/**
 * Smart User Management System - Main Layout Footer
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_auth_folder = (basename(dirname($_SERVER['PHP_SELF'])) === 'auth');
?>
<?php if (!$is_auth_folder && is_logged_in()): ?>
        </div> <!-- Close content-wrapper -->
    </div> <!-- Close app-main -->
</div> <!-- Close app-container -->
<?php endif; ?>

    <!-- Bootstrap 5 Bundle JS CDN with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Custom JS Asset -->
    <?php if ($is_auth_folder): ?>
        <script src="../assets/js/main.js"></script>
    <?php else: ?>
        <script src="assets/js/main.js"></script>
    <?php endif; ?>
</body>
</html>
