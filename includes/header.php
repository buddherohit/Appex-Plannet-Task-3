<?php
/**
 * Smart User Management System - Main Layout Header
 */

// Include database config and start session
require_once __DIR__ . '/../config/database.php';
start_secure_session();

// Get the current page file name for active states in sidebar
$current_page = basename($_SERVER['PHP_SELF']);

// Redirect to login if accessing protected page and not logged in
$public_pages = ['login.php', 'register.php'];
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Determine if we are inside the 'auth' folder
$is_auth_folder = ($current_dir === 'auth');

if (!$is_auth_folder && !in_array($current_page, $public_pages)) {
    // If not logged in and not on login/register, force login
    if (!is_logged_in()) {
        // Try remember me first
        if (!check_remember_me()) {
            header("Location: auth/login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart User Management System</title>
    
    <!-- Google Fonts (Outfit) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <?php if ($is_auth_folder): ?>
        <link rel="stylesheet" href="../assets/css/style.css">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/style.css">
    <?php endif; ?>
    
    <!-- Local Session Theme Override script to prevent page flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
<?php if (!$is_auth_folder && is_logged_in()): ?>
<div class="app-container">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>
    
    <div class="app-main">
        <!-- Navbar -->
        <?php include_once __DIR__ . '/navbar.php'; ?>
        
        <!-- Content Container -->
        <div class="content-wrapper">
<?php endif; ?>
