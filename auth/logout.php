<?php
/**
 * Smart User Management System - Logout Action Handler
 */

require_once __DIR__ . '/../config/database.php';
start_secure_session();

if (is_logged_in()) {
    $userId = $_SESSION['user_id'];
    log_activity($userId, "User logged out.");
}

// 1. Unset all session variables
$_SESSION = array();

// 2. Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Clear Remember Me Cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie(
        'remember_user',
        '',
        time() - 3600,
        '/',
        '',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        true
    );
}

// 4. Destroy the session
session_destroy();

// 5. Redirect to login page
header("Location: login.php");
exit();
?>
