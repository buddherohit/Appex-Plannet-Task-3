<?php
/**
 * Smart User Management System - Users Management Page (Admin Only)
 */

require_once __DIR__ . '/includes/header.php';

// Enforce admin access
require_admin();

$conn = get_db_connection();
$user_id = $_SESSION['user_id'];

// 1. Sorting Config
$allowed_sort = ['full_name', 'created_at', 'role'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sort) ? $_GET['sort'] : 'created_at';
$order = ($_GET['order'] ?? '') === 'asc' ? 'asc' : 'desc';
$next_order = $order === 'asc' ? 'desc' : 'asc';

// 2. Search Config
$search = sanitize_input($_GET['search'] ?? '');
$search_param = "%$search%";

// 3. Pagination Config
$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// 4. Count total items for pagination
$total_records = 0;
if (!empty($search)) {
    $count_sql = "SELECT COUNT(*) as total FROM users WHERE full_name LIKE ? OR email LIKE ?";
    if ($stmt = mysqli_prepare($conn, $count_sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $total_records = $row['total'];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $count_sql = "SELECT COUNT(*) as total FROM users";
    if ($result = mysqli_query($conn, $count_sql)) {
        $row = mysqli_fetch_assoc($result);
        $total_records = $row['total'];
    }
}

$total_pages = ceil($total_records / $limit);

// 5. Fetch Users List
$users = [];
if (!empty($search)) {
    $sql = "SELECT id, full_name, email, mobile, role, profile_image, created_at 
            FROM users 
            WHERE full_name LIKE ? OR email LIKE ? 
            ORDER BY $sort $order 
            LIMIT ? OFFSET ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssii", $search_param, $search_param, $limit, $offset);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $sql = "SELECT id, full_name, email, mobile, role, profile_image, created_at 
            FROM users 
            ORDER BY $sort $order 
            LIMIT ? OFFSET ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Generate CSRF Token for delete action
$csrf_token = generate_csrf_token();
?>

<div class="row fade-in-up">
    <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle mb-0">Create, view, edit, and delete system user accounts</p>
        </div>
        <div>
            <a href="add-user.php" class="btn btn-primary-custom">
                <i class="bi bi-person-plus-fill me-2"></i> Add New User
            </a>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="row fade-in-up mb-4">
    <div class="col-12">
        <div class="glass-panel p-3">
            <form action="users.php" method="GET" class="row g-2 align-items-center">
                <!-- Keep sorting parameters -->
                <input type="hidden" name="sort" value="<?php echo escape_html($sort); ?>">
                <input type="hidden" name="order" value="<?php echo escape_html($order); ?>">
                
                <div class="col-md-6 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 border-color text-muted" style="border-radius: 10px 0 0 10px;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control form-control-custom border-start-0" 
                               placeholder="Search users by name or email..." value="<?php echo escape_html($search); ?>" style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                <div class="col-md-3 col-lg-2">
                    <button type="submit" class="btn btn-primary-custom w-100 py-2">
                        Filter Search
                    </button>
                </div>
                <?php if (!empty($search)): ?>
                <div class="col-md-3 col-lg-2">
                    <a href="users.php" class="btn btn-outline-custom w-100 py-2">
                        Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-custom alert-dismissible fade show alert-dismissible-auto mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php 
        $msg = $_GET['msg'];
        if ($msg === 'added') echo 'User registered successfully!';
        elseif ($msg === 'updated') echo 'User account updated successfully!';
        elseif ($msg === 'deleted') echo 'User account deleted successfully!';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-danger alert-custom alert-dismissible fade show alert-dismissible-auto mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php 
        $err = $_GET['err'];
        if ($err === 'csrf') echo 'Security validation mismatch. Action blocked.';
        elseif ($err === 'notfound') echo 'Requested user not found.';
        elseif ($err === 'selfdelete') echo 'Security block: You cannot delete your own active account.';
        elseif ($err === 'db') echo 'Database operation failure. Please try again.';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Users Table Card -->
<div class="row fade-in-up">
    <div class="col-12">
        <div class="glass-panel p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table custom-table mb-0">
                    <thead>
                        <tr>
                            <th>
                                <a href="users.php?search=<?php echo urlencode($search); ?>&sort=full_name&order=<?php echo ($sort === 'full_name') ? $next_order : 'asc'; ?>" class="text-decoration-none text-secondary">
                                    Name 
                                    <?php if ($sort === 'full_name'): ?>
                                        <i class="bi bi-arrow-<?php echo ($order === 'asc') ? 'up' : 'down'; ?>-short"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Email Address</th>
                            <th>Mobile</th>
                            <th>
                                <a href="users.php?search=<?php echo urlencode($search); ?>&sort=role&order=<?php echo ($sort === 'role') ? $next_order : 'asc'; ?>" class="text-decoration-none text-secondary">
                                    Role 
                                    <?php if ($sort === 'role'): ?>
                                        <i class="bi bi-arrow-<?php echo ($order === 'asc') ? 'up' : 'down'; ?>-short"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="users.php?search=<?php echo urlencode($search); ?>&sort=created_at&order=<?php echo ($sort === 'created_at') ? $next_order : 'asc'; ?>" class="text-decoration-none text-secondary">
                                    Date Registered 
                                    <?php if ($sort === 'created_at'): ?>
                                        <i class="bi bi-arrow-<?php echo ($order === 'asc') ? 'up' : 'down'; ?>-short"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary">
                                    <i class="bi bi-people-fill fs-2 d-block mb-3 text-muted"></i>
                                    No user accounts matched the filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $row): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php
                                            $u_image = 'assets/images/default-avatar.svg';
                                            if (!empty($row['profile_image']) && file_exists(__DIR__ . '/uploads/' . $row['profile_image'])) {
                                                $u_image = 'uploads/' . $row['profile_image'];
                                            }
                                            ?>
                                            <img src="<?php echo $u_image; ?>" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <span class="fw-semibold d-block text-primary"><?php echo escape_html($row['full_name']); ?></span>
                                                <?php if ($row['id'] == $user_id): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo escape_html($row['email']); ?></td>
                                    <td><?php echo escape_html($row['mobile']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['role'] === 'admin') ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'; ?> text-uppercase" style="font-size: 0.75rem;">
                                            <?php echo escape_html($row['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-secondary">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-custom" title="Edit User">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            
                                            <?php if ($row['id'] != $user_id): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger border-light text-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" 
                                                        data-userid="<?php echo $row['id']; ?>" 
                                                        data-username="<?php echo escape_html($row['full_name']); ?>"
                                                        title="Delete User">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-custom border-light text-muted" disabled title="Cannot delete yourself">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center p-3 border-top border-light">
                    <span class="text-secondary small">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> records
                    </span>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Previous Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link border-color" href="users.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link border-color" href="users.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next Page -->
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link border-color" href="users.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Bootstrap Modal) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 shadow">
            <div class="modal-header border-bottom border-light">
                <h5 class="modal-title fw-bold text-danger" id="deleteConfirmModalLabel">Delete User Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="delete-user.php" method="POST">
                <div class="modal-body py-4">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <!-- User ID input -->
                    <input type="hidden" name="user_id" id="delete-userid-input" value="">
                    
                    <p class="mb-0">Are you sure you want to permanently delete the user account for <strong id="delete-username-span" class="text-primary"></strong>?</p>
                    <p class="text-danger small mt-2 mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> This action is irreversible and all associated activity records will be deleted.</p>
                </div>
                <div class="modal-footer border-top border-light">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius: 10px;">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
