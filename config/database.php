<?php
/**
 * Smart User Management System - Database Configuration & Security Helpers
 */

// Database configuration
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'user_management_system');
define('DB_PORT', 3308);

// Establish Database Connection
function get_db_connection() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
        if (!$conn) {
            die("ERROR: Could not connect to database. " . mysqli_connect_error());
        }
        // Set charset to utf8mb4 for secure multi-byte support
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

// Log User Activity
function log_activity($userId, $action) {
    $conn = get_db_connection();
    $sql = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $userId, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    }
    return false;
}

// Input Sanitization
function sanitize_input($data) {
    if ($data === null) return '';
    return trim($data);
}

// HTML Output Escaping (XSS Protection)
function escape_html($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// CSRF Token Generation
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Session security helper - must be called on every page load
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session cookie parameters if HTTPS is active
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 0, // session-based
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role
function get_user_role() {
    return $_SESSION['user_role'] ?? '';
}

// Require login filter
function require_login() {
    start_secure_session();
    if (!is_logged_in()) {
        // Check for Remember Me cookie
        if (check_remember_me()) {
            return;
        }
        header("Location: auth/login.php");
        exit();
    }
}

// Require admin role filter
function require_admin() {
    require_login();
    if (get_user_role() !== 'admin') {
        header("Location: dashboard.php?error=unauthorized");
        exit();
    }
}

// Remember Me Cookie Check
function check_remember_me() {
    if (isset($_COOKIE['remember_user'])) {
        $token = $_COOKIE['remember_user'];
        // In a production app, we would match a secure selector/token in a DB table.
        // For standard demonstration, we will base64 decode and verify email and id safely.
        $decoded = base64_decode($token);
        if ($decoded) {
            $parts = explode(':', $decoded);
            if (count($parts) === 3) {
                list($userId, $email, $hash) = $parts;
                $conn = get_db_connection();
                $sql = "SELECT id, full_name, email, role, password, profile_image FROM users WHERE id = ? AND email = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "is", $userId, $email);
                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        if ($row = mysqli_fetch_assoc($result)) {
                            // Check secret hash signature
                            $expected_hash = md5($row['password'] . 'smart_salt_key');
                            if (hash_equals($expected_hash, $hash)) {
                                // Re-authenticate session
                                $_SESSION['user_id'] = $row['id'];
                                $_SESSION['user_name'] = $row['full_name'];
                                $_SESSION['user_email'] = $row['email'];
                                $_SESSION['user_role'] = $row['role'];
                                $_SESSION['user_image'] = $row['profile_image'];
                                
                                // Regenerate session ID to prevent fixation
                                session_regenerate_id(true);
                                log_activity($row['id'], "User logged in automatically via Remember Me.");
                                return true;
                            }
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
    return false;
}
?>
