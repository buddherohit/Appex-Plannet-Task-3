<?php
/**
 * Smart User Management System - User Registration Page
 */

require_once __DIR__ . '/../config/database.php';
start_secure_session();

// If user is already logged in, redirect them to dashboard
if (is_logged_in()) {
    header("Location: ../dashboard.php");
    exit();
}

$error_msg = "";
$success_msg = "";

// Handle Registration Form Post
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
        $confirm_password = sanitize_input($_POST['confirm_password'] ?? '');
        
        // Server-Side Validations
        if (empty($full_name) || empty($email) || empty($mobile) || empty($password) || empty($confirm_password)) {
            $error_msg = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error_msg = "Password must be at least 6 characters long.";
        } elseif (!preg_match("/^[+]?[0-9]{10,15}$/", preg_replace("/[\s-]/", "", $mobile))) {
            $error_msg = "Please enter a valid mobile number (10-15 digits).";
        } else {
            // Check for duplicate email in database using prepared statements
            $conn = get_db_connection();
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
            
            // If no errors, proceed with registration
            if (empty($error_msg)) {
                // Hash Password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $role = 'user'; // Default role is standard user
                
                $insert_sql = "INSERT INTO users (full_name, email, mobile, password, role) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $mobile, $hashed_password, $role);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $new_user_id = mysqli_insert_id($conn);
                        
                        // Log registration activity
                        log_activity($new_user_id, "User registered a new account.");
                        
                        $success_msg = "Registration successful! You can now log in.";
                        // Clear inputs for successful form
                        $full_name = $email = $mobile = "";
                    } else {
                        $error_msg = "Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Generate CSRF Token for Form
$csrf_token = generate_csrf_token();

// Include layouts
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card glass-panel fade-in-up shadow">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">SmartUMS</h2>
            <p class="text-secondary small">Create an account to get started</p>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-custom d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><?php echo escape_html($error_msg); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-custom d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <div><?php echo escape_html($success_msg); ?></div>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="POST" class="needs-validation-custom" novalidate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" name="full_name" class="form-control form-control-custom border-start-0" 
                           placeholder="John Doe" value="<?php echo escape_html($full_name ?? ''); ?>" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control form-control-custom border-start-0" 
                           placeholder="name@example.com" value="<?php echo escape_html($email ?? ''); ?>" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>
            
            <!-- Mobile -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Mobile Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-phone"></i>
                    </span>
                    <input type="tel" name="mobile" class="form-control form-control-custom border-start-0 validate-mobile" 
                           placeholder="+1234567890" value="<?php echo escape_html($mobile ?? ''); ?>" required style="border-radius: 0 10px 10px 0;">
                    <div class="invalid-feedback">Enter a valid mobile number (e.g., +1234567890).</div>
                </div>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" id="register-password" class="form-control form-control-custom border-start-0 validate-password" 
                           placeholder="••••••••" required style="border-radius: 0 10px 10px 0;">
                    <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="register-password" style="border-radius: 0 10px 10px 0;">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            
            <!-- Confirm Password -->
            <div class="mb-4">
                <label class="form-label form-label-custom">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-lock-check"></i>
                    </span>
                    <input type="password" name="confirm_password" id="register-confirm-password" class="form-control form-control-custom border-start-0 validate-confirm-password" 
                           placeholder="••••••••" required style="border-radius: 0 10px 10px 0;">
                    <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="register-confirm-password" style="border-radius: 0 10px 10px 0;">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                    <div class="invalid-feedback">Passwords do not match.</div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3">
                <i class="bi bi-person-plus-fill me-2"></i> Register Account
            </button>
        </form>
        
        <div class="text-center mt-3">
            <p class="text-secondary small mb-0">Already have an account? <a href="login.php" class="text-decoration-none fw-bold text-primary">Login here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
