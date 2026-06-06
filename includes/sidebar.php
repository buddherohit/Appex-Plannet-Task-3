<?php
/**
 * Smart User Management System - Sidebar Component
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = get_user_role();
?>
<aside id="app-sidebar" class="app-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="bi bi-shield-lock-fill text-indigo-400"></i>
            <span>SmartUMS</span>
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        
        <?php if ($role === 'admin'): ?>
        <li class="sidebar-item <?php echo ($current_page == 'users.php' || $current_page == 'add-user.php' || $current_page == 'edit-user.php') ? 'active' : ''; ?>">
            <a href="users.php">
                <i class="bi bi-people"></i>
                <span>User Management</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="sidebar-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <a href="profile.php">
                <i class="bi bi-person-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="profile.php#settings">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <div class="sidebar-item text-danger">
            <a href="auth/logout.php" class="text-danger">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>
