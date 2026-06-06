<?php
/**
 * Smart User Management System - Create User Page (Admin Only)
 */

require_once __DIR__ . '/includes/header.php';

// Enforce admin access
require_admin();

$conn = get_db_connection();
$error_msg = "";

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
        $password = sanitize_input($_POST['password'] ?? '');
        $role = sanitize_input($_POST['role'] ?? 'user');
        
        // Server-Side Validations
        if (empty($full_name) || empty($email) || empty($mobile) || empty($password) || empty($role)) {
            $error_msg = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        } elseif (!in_array($role, ['admin', 'user'])) {
            $error_msg = "Invalid role selected.";
        } elseif (!preg_match("/^[+]?[0-9]{10,15}$/", preg_replace("/[\s-]/", "", $mobile))) {
            $error_msg = "Please enter a valid mobile number (10-15 digits).";
        } else {
            // Check for duplicate email
            $email_check_sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $email_check_sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $error_msg = "Email address is already registered.";
                    }
                } else {
                    $error_msg = "Database query failure. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
            
            // If no errors, proceed with user insertion
            if (empty($error_msg)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $insert_sql = "INSERT INTO users (full_name, email, mobile, password, role) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $mobile, $hashed_password, $role);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $new_user_id = mysqli_insert_id($conn);
                        
                        // Log administrative activity
                        log_activity($_SESSION['user_id'], "Admin created a new user account: $email (ID: $new_user_id).");
                        
                        // Log new user registration activity
                        log_activity($new_user_id, "User account initialized by Administrator.");
                        
                        header("Location: users.php?msg=added");
                        exit();
                    } else {
                        $error_msg = "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
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
        <h1 class="page-title">Add New User</h1>
        <p class="page-subtitle">Register a new user account in the system database</p>
    </div>
</div>

<div class="row fade-in-up">
    <div class="col-lg-8 mb-4">
        <div class="glass-panel p-4 shadow-sm">
            <h5 class="fw-bold mb-4">Account Information</h5>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-custom d-flex align-items-center gap-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?php echo escape_html($error_msg); ?></div>
                </div>
            <?php endif; ?>
            
            <form action="add-user.php" method="POST" class="needs-validation-custom" novalidate>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="row">
                    <!-- Full Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-custom" 
                               placeholder="John Doe" value="<?php echo escape_html($full_name ?? ''); ?>" required>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-custom" 
                               placeholder="name@example.com" value="<?php echo escape_html($email ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Mobile -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control form-control-custom validate-mobile" 
                               placeholder="+1234567890" value="<?php echo escape_html($mobile ?? ''); ?>" required>
                        <div class="invalid-feedback">Enter a valid mobile number (10-15 digits).</div>
                    </div>
                    
                    <!-- Role -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Access Role</label>
                        <select name="role" class="form-select form-control-custom" required>
                            <option value="user" <?php echo (isset($role) && $role === 'user') ? 'selected' : ''; ?>>Standard User</option>
                            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Password -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label form-label-custom">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="add-user-password" class="form-control form-control-custom" 
                                   placeholder="••••••••" required>
                            <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="add-user-password" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <small class="text-secondary d-block mt-1">Must be at least 6 characters long.</small>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-circle me-1"></i> Save User
                    </button>
                    <a href="users.php" class="btn btn-outline-custom">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
