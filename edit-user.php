<?php
/**
 * Smart User Management System - Edit User Page (Admin Only)
 */

require_once __DIR__ . '/includes/header.php';

// Enforce admin access
require_admin();

$conn = get_db_connection();
$error_msg = "";
$success_msg = "";

// Get target user ID
$target_user_id = intval($_GET['id'] ?? 0);
if ($target_user_id <= 0) {
    header("Location: users.php?err=notfound");
    exit();
}

// Fetch user data
$target_user = null;
$sql = "SELECT id, full_name, email, mobile, role, profile_image FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) === 1) {
            $target_user = mysqli_fetch_assoc($result);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$target_user) {
    header("Location: users.php?err=notfound");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = sanitize_input($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Security token mismatch. Please try again.";
    } else {
        // Collect and Sanitize input data
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $mobile = sanitize_input($_POST['mobile'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');
        $password = sanitize_input($_POST['password'] ?? ''); // Optional
        
        // Server-Side Validations
        if (empty($full_name) || empty($email) || empty($mobile) || empty($role)) {
            $error_msg = "All fields except password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } elseif (!in_array($role, ['admin', 'user'])) {
            $error_msg = "Invalid role selected.";
        } elseif (!preg_match("/^[+]?[0-9]{10,15}$/", preg_replace("/[\s-]/", "", $mobile))) {
            $error_msg = "Please enter a valid mobile number (10-15 digits).";
        } elseif (!empty($password) && strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        } else {
            // Check for duplicate email (excluding current user)
            $email_check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
            if ($stmt = mysqli_prepare($conn, $email_check_sql)) {
                mysqli_stmt_bind_param($stmt, "si", $email, $target_user_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $error_msg = "Email address is already registered to another user.";
                    }
                }
                mysqli_stmt_close($stmt);
            }
            
            // If no errors, update user
            if (empty($error_msg)) {
                $update_sql = "";
                $stmt_params = [];
                
                if (!empty($password)) {
                    // Update including password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $update_sql = "UPDATE users SET full_name = ?, email = ?, mobile = ?, role = ?, password = ? WHERE id = ?";
                    
                    if ($stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($stmt, "sssssi", $full_name, $email, $mobile, $role, $hashed_password, $target_user_id);
                        $exec_ok = mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    // Update excluding password
                    $update_sql = "UPDATE users SET full_name = ?, email = ?, mobile = ?, role = ? WHERE id = ?";
                    if ($stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $mobile, $role, $target_user_id);
                        $exec_ok = mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                
                if ($exec_ok) {
                    // Log Administrative action
                    log_activity($_SESSION['user_id'], "Admin updated user details for: $email (ID: $target_user_id).");
                    
                    // If target user is current logged-in user, refresh their session name/email/role
                    if ($target_user_id === intval($_SESSION['user_id'])) {
                        $_SESSION['user_name'] = $full_name;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_role'] = $role;
                    }
                    
                    header("Location: users.php?msg=updated");
                    exit();
                } else {
                    $error_msg = "Database operation failure. Please try again.";
                }
            }
        }
    }
}

// Generate CSRF Token
$csrf_token = generate_csrf_token();
?>

<div class="row fade-in-up">
    <div class="col-12">
        <h1 class="page-title">Edit User Details</h1>
        <p class="page-subtitle">Update registration details for <?php echo escape_html($target_user['full_name']); ?></p>
    </div>
</div>

<div class="row fade-in-up">
    <div class="col-lg-8 mb-4">
        <div class="glass-panel p-4 shadow-sm">
            <h5 class="fw-bold mb-4">User Information</h5>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-custom d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?php echo escape_html($error_msg); ?></div>
                </div>
            <?php endif; ?>
            
            <form action="edit-user.php?id=<?php echo $target_user_id; ?>" method="POST" class="needs-validation-custom" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="row">
                    <!-- Full Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-custom" 
                               value="<?php echo escape_html($target_user['full_name']); ?>" required>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-custom" 
                               value="<?php echo escape_html($target_user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Mobile -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control form-control-custom validate-mobile" 
                               value="<?php echo escape_html($target_user['mobile']); ?>" required>
                        <div class="invalid-feedback">Enter a valid mobile number (10-15 digits).</div>
                    </div>
                    
                    <!-- Role -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Access Role</label>
                        <select name="role" class="form-select form-control-custom" required>
                            <option value="user" <?php echo ($target_user['role'] === 'user') ? 'selected' : ''; ?>>Standard User</option>
                            <option value="admin" <?php echo ($target_user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Password (Optional) -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label form-label-custom">New Password (Leave blank to keep current)</label>
                        <div class="input-group">
                            <input type="password" name="password" id="edit-user-password" class="form-control form-control-custom" 
                                   placeholder="••••••••">
                            <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="edit-user-password" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <small class="text-secondary d-block mt-1">Leave blank if you do not wish to reset the user's password.</small>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-circle me-1"></i> Update User
                    </button>
                    <a href="users.php" class="btn btn-outline-custom">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Visual Details Overlay -->
    <div class="col-lg-4 mb-4">
        <div class="glass-panel p-4 text-center">
            <h5 class="fw-bold mb-4 text-start">Current Profile Picture</h5>
            <?php
            $t_image = 'assets/images/default-avatar.svg';
            if (!empty($target_user['profile_image']) && file_exists(__DIR__ . '/uploads/' . $target_user['profile_image'])) {
                $t_image = 'uploads/' . $target_user['profile_image'];
            }
            ?>
            <div class="mb-3">
                <img src="<?php echo $t_image; ?>" alt="Profile Picture" class="rounded-circle border border-3 border-light shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
            </div>
            <h6 class="fw-bold mb-1"><?php echo escape_html($target_user['full_name']); ?></h6>
            <span class="badge bg-secondary-subtle text-secondary text-uppercase mb-2"><?php echo escape_html($target_user['role']); ?></span>
            
            <p class="text-secondary small mt-3">Profile picture uploads can be managed by the user from their profile panel.</p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
