<?php
/**
 * Smart User Management System - Delete User Action Handler (Admin Only)
 */

require_once __DIR__ . '/config/database.php';
start_secure_session();

// 1. Authenticate and authorize admin
if (!is_logged_in() || get_user_role() !== 'admin') {
    header("Location: auth/login.php");
    exit();
}

$conn = get_db_connection();

// 2. Enforce POST request and check CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit();
}

$csrf_token = sanitize_input($_POST['csrf_token'] ?? '');
if (!validate_csrf_token($csrf_token)) {
    header("Location: users.php?err=csrf");
    exit();
}

// 3. Verify user id
$target_user_id = intval($_POST['user_id'] ?? 0);
$current_admin_id = intval($_SESSION['user_id']);

if ($target_user_id <= 0) {
    header("Location: users.php?err=notfound");
    exit();
}

// Prevent self-deletion
if ($target_user_id === $current_admin_id) {
    header("Location: users.php?err=selfdelete");
    exit();
}

// 4. Fetch details to delete associated profile image file
$profile_image = null;
$email = '';
$fetch_sql = "SELECT email, profile_image FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $fetch_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $profile_image = $row['profile_image'];
            $email = $row['email'];
        }
    }
    mysqli_stmt_close($stmt);
}

if (empty($email)) {
    header("Location: users.php?err=notfound");
    exit();
}

// 5. Delete User Account
$delete_sql = "DELETE FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $delete_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Delete image file if it exists
        if (!empty($profile_image)) {
            $image_path = __DIR__ . '/uploads/' . $profile_image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Log deletion activity
        log_activity($current_admin_id, "Admin deleted user account: $email (ID: $target_user_id).");
        
        header("Location: users.php?msg=deleted");
        exit();
    } else {
        header("Location: users.php?err=db");
        exit();
    }
    mysqli_stmt_close($stmt);
} else {
    header("Location: users.php?err=db");
    exit();
}
?>
