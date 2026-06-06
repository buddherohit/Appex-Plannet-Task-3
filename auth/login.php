<?php
/**
 * Smart User Management System - Login Page
 */

require_once __DIR__ . '/../config/database.php';
start_secure_session();

// If user is already logged in, redirect them to dashboard
if (is_logged_in()) {
    header("Location: ../dashboard.php");
    exit();
}

// Check if there is an automatic Remember Me login
if (check_remember_me()) {
    header("Location: ../dashboard.php");
    exit();
}

$error_msg = "";
$success_msg = "";

// Check for redirect message
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success_msg = "Account created successfully! Please login.";
}

// Handle Login Form Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = sanitize_input($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Security token mismatch. Please try again.";
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = sanitize_input($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $error_msg = "Email and Password are required.";
        } else {
            $conn = get_db_connection();
            $sql = "SELECT id, full_name, email, password, role, profile_image FROM users WHERE email = ?";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($row = mysqli_fetch_assoc($result)) {
                        // Verify Password
                        if (password_verify($password, $row['password'])) {
                            // Password is correct, start session
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['user_name'] = $row['full_name'];
                            $_SESSION['user_email'] = $row['email'];
                            $_SESSION['user_role'] = $row['role'];
                            $_SESSION['user_image'] = $row['profile_image'];
                            
                            // Prevent Session Fixation
                            session_regenerate_id(true);
                            
                            // Log user login activity
                            log_activity($row['id'], "User logged in successfully.");
                            
                            // Set Remember Me Cookie if checked
                            if ($remember) {
                                // Create a secure signature using user's password hash and a salt key
                                $signature = md5($row['password'] . 'smart_salt_key');
                                $cookie_val = base64_encode($row['id'] . ':' . $row['email'] . ':' . $signature);
                                
                                // Set cookie for 30 days
                                setcookie(
                                    'remember_user',
                                    $cookie_val,
                                    time() + (30 * 24 * 60 * 60),
                                    '/',
                                    '',
                                    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                                    true // HttpOnly
                                );
                            }
                            
                            // Redirect based on role or to dashboard
                            header("Location: ../dashboard.php");
                            exit();
                        } else {
                            $error_msg = "Invalid email or password.";
                        }
                    } else {
                        $error_msg = "Invalid email or password.";
                    }
                } else {
                    $error_msg = "Database query failure. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Generate CSRF Token
$csrf_token = generate_csrf_token();

// Include layouts
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card glass-panel fade-in-up shadow">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">SmartUMS</h2>
            <p class="text-secondary small">Access user management terminal</p>
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
        
        <form action="login.php" method="POST" class="needs-validation-custom" novalidate>
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <!-- Email -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control form-control-custom border-start-0" 
                           placeholder="name@example.com" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
                <label class="form-label form-label-custom">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" id="login-password" class="form-control form-control-custom border-start-0" 
                           placeholder="••••••••" required style="border-radius: 0 10px 10px 0;">
                    <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="login-password" style="border-radius: 0 10px 10px 0;">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            
            <!-- Remember Me & Forgot Password -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember-me" class="form-check-input" style="cursor: pointer;">
                    <label class="form-check-label text-secondary small" for="remember-me" style="cursor: pointer; user-select: none;">Remember Me</label>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary-custom w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Log In
            </button>
        </form>
        
        <div class="text-center mt-3">
            <p class="text-secondary small mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold text-primary">Register here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
