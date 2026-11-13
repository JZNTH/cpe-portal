<?php
session_start();
require_once 'db.php'; 

// Security check: Ensure only admin can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

// Initialize default variables to prevent undefined errors
$totalUsers = 0;
$pendingCount = 0;
$scheduleCount = 0;
$gradeCount = 0;
$pendingUsers = [];

$searchPending = isset($_GET['search_pending']) ? trim($_GET['search_pending']) : '';

try {
    // Core user metrics
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $totalUsers = $stmtUsers->fetchColumn();

    $stmtPending = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $pendingCount = $stmtPending->fetchColumn();

    // Pending users list for approval table with search filtering
    if ($searchPending !== '') {
        $stmtPendingList = $pdo->prepare("
            SELECT id, name, surname, email, role, created_at 
            FROM users 
            WHERE status = 'pending' 
            AND (name ILIKE ? OR surname ILIKE ? OR email ILIKE ?) 
            ORDER BY created_at DESC
        ");
        $searchTerm = "%" . $searchPending . "%";
        $stmtPendingList->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmtPendingList = $pdo->prepare("SELECT id, name, surname, email, role, created_at FROM users WHERE status = 'pending' ORDER BY created_at DESC");
        $stmtPendingList->execute();
    }
    $pendingUsers = $stmtPendingList->fetchAll();
} catch (PDOException $e) {
    $pendingUsers = [];
}

// Separate try-catch blocks for modules not built yet
try {
    $stmtSchedules = $pdo->query("SELECT COUNT(*) FROM class_schedules");
    $scheduleCount = $stmtSchedules->fetchColumn();
} catch (PDOException $e) {
    $scheduleCount = 0;
}

try {
    $stmtGrades = $pdo->query("SELECT COUNT(*) FROM student_grades");
    $gradeCount = $stmtGrades->fetchColumn();
} catch (PDOException $e) {
    $gradeCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Admin Dashboard</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        /* Card Header Search Layout */
        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .search-form {
            display: flex;
            gap: 8px;
        }
        .search-input {
            padding: 7px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
            width: 220px;
        }
        .btn-search {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-search:hover {
            background-color: #004085;
        }
        .btn-clear {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 7px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
        }
        .btn-clear:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | ADMIN</div>
        
        <div class="menu-category">Navigation</div>
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        
        <div class="menu-category">System Control</div>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_schedules.php">Class Schedules & Rooms</a>
        <a href="admin_grades_view.php">Student Grades</a>
        
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | ADMINISTRATOR CONTROL PANEL</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <!-- Banner Section -->
            <div class="banner-card">
                <div style="background:#0056b3; color:white; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:20px;">CpE</div>
                <div>
                    <h1>ADMINISTRATIVE DASHBOARD</h1>
                    <p style="color: #6c757d; font-size: 14px; margin-top: 4px;">Welcome back, Administrator.</p>
                </div>
            </div>

            <!-- Stats Metric Cards -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-top"><?php echo $totalUsers; ?></div>
                    <div class="stat-bottom">Total Registered Users</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-top"><?php echo $pendingCount; ?></div>
                    <div class="stat-bottom">Pending Approvals</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-top"><?php echo $scheduleCount; ?></div>
                    <div class="stat-bottom">Class Schedules & Rooms</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-top"><?php echo $gradeCount; ?></div>
                    <div class="stat-bottom">Student Grade Records</div>
                </div>
            </div>

            <!-- Pending Approvals Table Card -->
            <div class="card">
                <div class="card-header-flex">
                    <div>
                        <h3>Pending User Approvals (Action Required)</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Review and approve newly registered accounts.</p>
                    </div>
                    
                    <form method="GET" action="admin_dashboard.php" class="search-form">
                        <input type="text" name="search_pending" class="search-input" placeholder="Search pending user..." value="<?php echo htmlspecialchars($searchPending); ?>">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($searchPending)): ?>
                            <a href="admin_dashboard.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($pendingUsers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Surname</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Date Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingUsers as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                                    <td><b><?php echo htmlspecialchars($user['surname'] ?? ''); ?></b></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                    <td>
                                        <form action="approve_user.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-approve">Approve</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-users" style="padding: 20px 0; text-align: center; color: #6c757d;">No pending accounts found matching your criteria.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>