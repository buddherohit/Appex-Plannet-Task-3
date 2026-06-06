<?php
/**
 * Smart User Management System - Navbar Component
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_image = $_SESSION['user_image'] ?? null;
$profile_pic_path = 'assets/images/default-avatar.svg';

// If uploads exists, check it
if (!empty($user_image)) {
    if (file_exists(__DIR__ . '/../uploads/' . $user_image)) {
        $profile_pic_path = 'uploads/' . $user_image;
    }
}

// Generate notification count mock or fetch log alerts
$notif_count = 3; 
?>
<nav class="app-navbar glass-panel m-3 shadow-sm rounded-4">
    <div class="navbar-left">
        <button id="sidebar-toggle" class="sidebar-toggler me-2 d-lg-none" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-3"></i>
        </button>
        <div class="d-none d-md-flex align-items-center">
            <span class="fw-semibold text-secondary">Welcome back, <strong class="text-primary"><?php echo escape_html($_SESSION['user_name'] ?? 'Guest'); ?></strong></span>
        </div>
    </div>
    
    <div class="navbar-right">
        <!-- Theme Toggle -->
        <button id="theme-toggle" class="theme-toggle" title="Toggle Theme" aria-label="Toggle Theme">
            <i class="bi bi-moon-fill"></i>
        </button>
        
        <!-- Notifications Dropdown -->
        <div class="dropdown">
            <button class="theme-toggle position-relative" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <i class="bi bi-bell-fill"></i>
                <span class="position-absolute top-1 start-70 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                    <?php echo $notif_count; ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end glass-panel shadow border-0 p-2 rounded-3" aria-labelledby="notifDropdown" style="width: 280px;">
                <li class="p-2 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Notifications</h6>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 p-2 mt-1" href="profile.php">
                        <small class="d-block fw-semibold">Profile verification</small>
                        <small class="text-muted">Your profile is up to date.</small>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 p-2" href="dashboard.php">
                        <small class="d-block fw-semibold">System Alert</small>
                        <small class="text-muted">New security patch applied successfully.</small>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item rounded-2 p-2" href="profile.php">
                        <small class="d-block fw-semibold">Avatar uploaded</small>
                        <small class="text-muted">Manage your photo inside settings.</small>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle gap-2 text-primary" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?php echo $profile_pic_path; ?>" alt="Avatar" class="navbar-avatar rounded-circle">
                <span class="d-none d-sm-inline fw-semibold text-secondary"><?php echo escape_html($_SESSION['user_name'] ?? 'User'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end glass-panel shadow border-0 p-2 rounded-3 mt-2" aria-labelledby="profileDropdown">
                <li><span class="dropdown-header text-muted fw-bold">Manage Account</span></li>
                <li>
                    <a class="dropdown-item rounded-2" href="profile.php">
                        <i class="bi bi-person me-2"></i> My Profile
                    </a>
                </li>
                <?php if (get_user_role() === 'admin'): ?>
                <li>
                    <a class="dropdown-item rounded-2" href="users.php">
                        <i class="bi bi-people me-2"></i> Manage Users
                    </a>
                </li>
                <?php endif; ?>
                <li><hr class="dropdown-divider border-light"></li>
                <li>
                    <a class="dropdown-item text-danger rounded-2" href="auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Log Out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
