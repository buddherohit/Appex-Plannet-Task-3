<?php
/**
 * Smart User Management System - User Profile Page
 */

require_once __DIR__ . '/includes/header.php';

// Enforce login
if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit();
}

$conn = get_db_connection();
$user_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// 1. Fetch current user data from database (ensuring fresh state)
$user = null;
$sql = "SELECT id, full_name, email, mobile, role, profile_image, created_at FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
        }
    }
    mysqli_stmt_close($stmt);
}

if (!$user) {
    // If user deleted or invalid, log out
    header("Location: auth/logout.php");
    exit();
}

// 2. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF
    $csrf_token = sanitize_input($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Security token mismatch. Please try again.";
    } else {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $mobile = sanitize_input($_POST['mobile'] ?? '');
        $password = sanitize_input($_POST['password'] ?? '');
        
        // Validation
        if (empty($full_name) || empty($mobile)) {
            $error_msg = "Name and mobile number are required.";
        } elseif (!preg_match("/^[+]?[0-9]{10,15}$/", preg_replace("/[\s-]/", "", $mobile))) {
            $error_msg = "Please enter a valid mobile number (10-15 digits).";
        } elseif (!empty($password) && strlen($password) < 6) {
            $error_msg = "New password must be at least 6 characters long.";
        } else {
            // Update db
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $update_sql = "UPDATE users SET full_name = ?, mobile = ?, password = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($stmt, "sssi", $full_name, $mobile, $hashed_password, $user_id);
                    $exec_ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            } else {
                $update_sql = "UPDATE users SET full_name = ?, mobile = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($stmt, "ssi", $full_name, $mobile, $user_id);
                    $exec_ok = mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
            
            if ($exec_ok) {
                $success_msg = "Profile details updated successfully!";
                log_activity($user_id, "User updated profile contact details.");
                
                // Refresh local session variables
                $_SESSION['user_name'] = $full_name;
                
                // Refresh local user variables
                $user['full_name'] = $full_name;
                $user['mobile'] = $mobile;
            } else {
                $error_msg = "Database update failure. Please try again.";
            }
        }
    }
}

// 3. Handle Profile Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    // Validate CSRF
    $csrf_token = sanitize_input($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($csrf_token)) {
        $error_msg = "Security token mismatch. Please try again.";
    } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['profile_image'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // File extension validation
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
        
        // 2MB check
        $maxSize = 2 * 1024 * 1024;
        
        // Validate by getimagesize to verify actual image content
        $image_info = @getimagesize($fileTmpPath);
        
        if ($image_info === false) {
            $error_msg = "Uploaded file is not a valid image.";
        } elseif (!in_array($fileExtension, $allowedExtensions)) {
            $error_msg = "Invalid extension. Only JPG, JPEG, and PNG are allowed.";
        } elseif (!in_array($fileType, $allowedMimes)) {
            $error_msg = "Invalid file type format.";
        } elseif ($fileSize > $maxSize) {
            $error_msg = "File exceeds maximum upload size (2MB).";
        } else {
            // Check if uploads folder exists, if not create it
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique name
            $newFileName = uniqid('avatar_', true) . '.' . $fileExtension;
            $dest_path = $upload_dir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Delete previous avatar file if exists
                if (!empty($user['profile_image'])) {
                    $old_file = $upload_dir . $user['profile_image'];
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
                
                // Update database
                $update_image_sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_image_sql)) {
                    mysqli_stmt_bind_param($stmt, "si", $newFileName, $user_id);
                    if (mysqli_stmt_execute($stmt)) {
                        $success_msg = "Profile image uploaded successfully!";
                        log_activity($user_id, "User updated profile picture.");
                        
                        // Update session and variables
                        $_SESSION['user_image'] = $newFileName;
                        $user['profile_image'] = $newFileName;
                    } else {
                        $error_msg = "Failed to update profile picture in database.";
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $error_msg = "Error moving uploaded file on server. Check permissions.";
            }
        }
    } else {
        $error_msg = "Please select a valid image file to upload.";
    }
}

// Generate CSRF Token
$csrf_token = generate_csrf_token();
?>

<div class="row fade-in-up">
    <div class="col-12">
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your personal credentials, contact info, and picture settings</p>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger alert-custom alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo escape_html($error_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success alert-custom alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo escape_html($success_msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row fade-in-up">
    <!-- Left Column: Avatar & Overview Card -->
    <div class="col-lg-4 mb-4">
        <div class="glass-panel p-4 text-center h-100">
            <h5 class="fw-bold mb-4 text-start">Profile Overview</h5>
            
            <?php
            $avatar_path = 'assets/images/default-avatar.svg';
            if (!empty($user['profile_image']) && file_exists(__DIR__ . '/uploads/' . $user['profile_image'])) {
                $avatar_path = 'uploads/' . $user['profile_image'];
            }
            ?>
            
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="avatar-wrapper mb-3">
                    <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="avatar-image">
                    <label for="profile_image_file" class="avatar-upload-label">
                        <i class="bi bi-camera-fill"></i>
                    </label>
                    <input type="file" name="profile_image" id="profile_image_file" class="d-none validate-file-upload" 
                           accept="image/png, image/jpeg, image/jpg" onchange="this.form.submit()">
                </div>
                
                <!-- Explicit upload button triggered automatically, or manual option -->
                <input type="hidden" name="upload_avatar" value="1">
            </form>
            
            <h4 class="fw-bold mb-1"><?php echo escape_html($user['full_name']); ?></h4>
            <p class="text-secondary mb-3"><?php echo escape_html($user['email']); ?></p>
            
            <span class="badge rounded-pill bg-primary px-3 py-2 text-uppercase mb-4">
                <i class="bi bi-shield-check me-1"></i> <?php echo escape_html($user['role']); ?>
            </span>
            
            <div class="border-top border-light pt-3 text-start">
                <div class="mb-2">
                    <span class="text-secondary small d-block">Mobile Number</span>
                    <strong class="text-primary"><?php echo escape_html($user['mobile']); ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-secondary small d-block">Account Status</span>
                    <strong class="text-success"><i class="bi bi-check-circle-fill"></i> Active</strong>
                </div>
                <div>
                    <span class="text-secondary small d-block">Registered Date</span>
                    <strong class="text-secondary small"><?php echo date('F d, Y H:i', strtotime($user['created_at'])); ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Settings Form -->
    <div class="col-lg-8 mb-4" id="settings">
        <div class="glass-panel p-4 h-100 shadow-sm">
            <h5 class="fw-bold mb-4">Account Settings</h5>
            
            <form action="profile.php" method="POST" class="needs-validation-custom" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="row">
                    <!-- Full Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Full Name</label>
                        <input type="text" name="full_name" class="form-control form-control-custom" 
                               value="<?php echo escape_html($user['full_name']); ?>" required>
                    </div>
                    
                    <!-- Mobile Number -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control form-control-custom validate-mobile" 
                               value="<?php echo escape_html($user['mobile']); ?>" required>
                        <div class="invalid-feedback">Enter a valid mobile number (10-15 digits).</div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Email (Locked) -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Email Address <span class="text-muted small">(Read Only)</span></label>
                        <input type="email" class="form-control form-control-custom text-muted" 
                               value="<?php echo escape_html($user['email']); ?>" readonly style="background-color: rgba(0,0,0,0.03);">
                    </div>
                    
                    <!-- Role (Locked) -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label form-label-custom">Access System Role <span class="text-muted small">(Read Only)</span></label>
                        <input type="text" class="form-control form-control-custom text-muted text-uppercase" 
                               value="<?php echo escape_html($user['role']); ?>" readonly style="background-color: rgba(0,0,0,0.03);">
                    </div>
                </div>
                
                <div class="row">
                    <!-- Password (Optional) -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label form-label-custom">Change Password (Leave blank to keep current)</label>
                        <div class="input-group">
                            <input type="password" name="password" id="profile-password" class="form-control form-control-custom" 
                                   placeholder="••••••••">
                            <button class="btn btn-outline-custom border-start-0 toggle-password-btn" type="button" data-target="profile-password" style="border-top-right-radius: 10px; border-bottom-right-radius: 10px;">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <small class="text-secondary d-block mt-1">If you want to modify your password, please write a new one here (min. 6 characters).</small>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-save2 me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
