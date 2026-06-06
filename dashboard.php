<?php
/**
 * Smart User Management System - Dashboard Page
 */

require_once __DIR__ . '/includes/header.php';

// Enforce authentication
if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit();
}

$conn = get_db_connection();
$user_id = $_SESSION['user_id'];
$user_role = get_user_role();

// 1. Fetch Statistics (Count)
$total_users = 0;
$new_users = 0;
$log_count = 0;

// Total Users Count (Visible to both, but admin sees system-wide stats)
$count_query = "SELECT COUNT(*) as total FROM users";
if ($result = mysqli_query($conn, $count_query)) {
    $row = mysqli_fetch_assoc($result);
    $total_users = $row['total'];
}

// New Users (Registered in last 7 days)
$new_query = "SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
if ($result = mysqli_query($conn, $new_query)) {
    $row = mysqli_fetch_assoc($result);
    $new_users = $row['total'];
}

// Total activity logs count
if ($user_role === 'admin') {
    $logs_count_query = "SELECT COUNT(*) as total FROM activity_logs";
} else {
    $logs_count_query = "SELECT COUNT(*) as total FROM activity_logs WHERE user_id = " . intval($user_id);
}
if ($result = mysqli_query($conn, $logs_count_query)) {
    $row = mysqli_fetch_assoc($result);
    $log_count = $row['total'];
}

// 2. Fetch Recent Activities (Limit 5)
$activities = [];
if ($user_role === 'admin') {
    $activities_sql = "SELECT a.action, a.created_at, u.full_name, u.role, u.profile_image 
                       FROM activity_logs a 
                       JOIN users u ON a.user_id = u.id 
                       ORDER BY a.created_at DESC LIMIT 5";
} else {
    $activities_sql = "SELECT a.action, a.created_at, u.full_name, u.role, u.profile_image 
                       FROM activity_logs a 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.user_id = ? 
                       ORDER BY a.created_at DESC LIMIT 5";
}

if ($user_role === 'admin') {
    if ($result = mysqli_query($conn, $activities_sql)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activities[] = $row;
        }
    }
} else {
    if ($stmt = mysqli_prepare($conn, $activities_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $activities[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// 3. Fetch Chart Data (Role breakdown for Chart.js)
$role_counts = ['admin' => 0, 'user' => 0];
$role_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
if ($result = mysqli_query($conn, $role_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $role_counts[$row['role']] = intval($row['count']);
    }
}

// 4. Fetch Registration Timeline (Last 7 Days) for Admin, or User log timeline
$timeline_labels = [];
$timeline_data = [];
$timeline_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM users 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                 GROUP BY DATE(created_at) 
                 ORDER BY DATE(created_at) ASC";
if ($result = mysqli_query($conn, $timeline_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $timeline_labels[] = date('M d', strtotime($row['date']));
        $timeline_data[] = intval($row['count']);
    }
}

// If timeline is empty, fill with placeholder
if (empty($timeline_data)) {
    $timeline_labels = [date('M d')];
    $timeline_data = [$total_users];
}
?>

<div class="row fade-in-up">
    <div class="col-12">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome to your workspace control center</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row fade-in-up mb-4">
    <!-- Stat 1: Total Users -->
    <div class="col-md-4 mb-3">
        <div class="glass-card p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary mb-1">Total Users</h6>
                    <h3 class="fw-bold mb-0 text-primary"><?php echo $total_users; ?></h3>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-success small fw-semibold"><i class="bi bi-arrow-up"></i> Active</span>
                <span class="text-muted small ms-1">in database</span>
            </div>
        </div>
    </div>
    
    <!-- Stat 2: New Users -->
    <div class="col-md-4 mb-3">
        <div class="glass-card p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary mb-1">New Users (7 Days)</h6>
                    <h3 class="fw-bold mb-0 text-success"><?php echo $new_users; ?></h3>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-success small fw-semibold"><i class="bi bi-plus"></i> Recent registrations</span>
            </div>
        </div>
    </div>

    <!-- Stat 3: Activity Logs -->
    <div class="col-md-4 mb-3">
        <div class="glass-card p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary mb-1"><?php echo ($user_role === 'admin') ? 'Total Logs' : 'My Logs'; ?></h6>
                    <h3 class="fw-bold mb-0 text-warning"><?php echo $log_count; ?></h3>
                </div>
                <div class="stat-icon warning">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-warning small fw-semibold"><i class="bi bi-clock-history"></i> Logged operations</span>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in-up">
    <!-- Chart.js Widget -->
    <div class="col-lg-8 mb-4">
        <div class="glass-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">System Visualizations</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Real-time</span>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="userMetricsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Profile Card Widget -->
    <div class="col-lg-4 mb-4">
        <div class="glass-panel p-4 h-100 text-center">
            <h5 class="fw-bold mb-4 text-start">My Profile</h5>
            
            <?php
            $avatar_filename = $_SESSION['user_image'] ?? null;
            $avatar_path = 'assets/images/default-avatar.svg';
            if (!empty($avatar_filename) && file_exists(__DIR__ . '/uploads/' . $avatar_filename)) {
                $avatar_path = 'uploads/' . $avatar_filename;
            }
            ?>
            <div class="mb-3">
                <img src="<?php echo $avatar_path; ?>" alt="Profile Picture" class="rounded-circle border border-3 border-light shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
            </div>
            
            <h5 class="fw-bold mb-1"><?php echo escape_html($_SESSION['user_name']); ?></h5>
            <p class="text-muted small mb-3"><?php echo escape_html($_SESSION['user_email']); ?></p>
            
            <span class="badge rounded-pill bg-primary px-3 py-2 text-uppercase mb-4">
                <i class="bi bi-shield-check me-1"></i> <?php echo escape_html($_SESSION['user_role']); ?>
            </span>
            
            <div class="border-top border-light pt-3 mt-2 text-start">
                <div class="row text-center">
                    <div class="col-6 border-end border-light">
                        <small class="text-secondary d-block">Role</small>
                        <strong class="text-primary text-uppercase"><?php echo escape_html($_SESSION['user_role']); ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Action</small>
                        <a href="profile.php" class="btn btn-sm btn-outline-custom mt-1 py-1 px-3">Edit Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in-up">
    <!-- Recent Activities Table -->
    <div class="col-12 mb-4">
        <div class="glass-panel p-4">
            <h5 class="fw-bold mb-4">Recent System Activities</h5>
            
            <?php if (empty($activities)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-journal-x fs-1 text-muted"></i>
                    <p class="text-secondary mt-2">No activity logged yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th>Actor</th>
                                <th>Role</th>
                                <th>Activity Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $log): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                            $log_pic = 'assets/images/default-avatar.svg';
                                            if (!empty($log['profile_image']) && file_exists(__DIR__ . '/uploads/' . $log['profile_image'])) {
                                                $log_pic = 'uploads/' . $log['profile_image'];
                                            }
                                            ?>
                                            <img src="<?php echo $log_pic; ?>" alt="Actor avatar" class="rounded-circle" style="width: 30px; height: 30px; object-fit: cover;">
                                            <span class="fw-semibold"><?php echo escape_html($log['full_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo ($log['role'] === 'admin') ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'; ?> text-uppercase" style="font-size: 0.75rem;">
                                            <?php echo escape_html($log['role']); ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary"><?php echo escape_html($log['action']); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('userMetricsChart').getContext('2d');
    
    // Dynamic values from database
    const totalAdmins = <?php echo $role_counts['admin']; ?>;
    const totalUsers = <?php echo $role_counts['user']; ?>;
    
    // Check current theme
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const gridColor = currentTheme === 'dark' ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = currentTheme === 'dark' ? '#94a3b8' : '#475569';
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Administrator Role', 'Standard User Role'],
            datasets: [{
                label: 'Registered Accounts',
                data: [totalAdmins, totalUsers],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.75)', // Red/coral for Admin
                    'rgba(99, 102, 241, 0.75)'  // Indigo for User
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(99, 102, 241, 1)'
                ],
                borderWidth: 1.5,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    padding: 12,
                    cornerRadius: 10,
                    backgroundColor: 'rgba(15, 23, 42, 0.85)',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: textColor,
                        stepSize: 1
                    },
                    grid: {
                        color: gridColor
                    }
                },
                x: {
                    ticks: {
                        color: textColor
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
