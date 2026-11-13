<?php
session_start();
require_once 'db.php'; 

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

$teacher_search = isset($_GET['t_search']) ? trim($_GET['t_search']) : '';
$student_search = isset($_GET['s_search']) ? trim($_GET['s_search']) : '';

try {
    // Fetch teachers with independent search
    if ($teacher_search !== '') {
        $stmtT = $pdo->prepare("
            SELECT id, username, name, middle_name, surname, email, student_id, role, status, created_at 
            FROM users 
            WHERE role = 'teacher' 
            AND (name ILIKE ? OR surname ILIKE ? OR username ILIKE ? OR email ILIKE ?)
            ORDER BY created_at DESC
        ");
        $termT = "%" . $teacher_search . "%";
        $stmtT->execute([$termT, $termT, $termT, $termT]);
    } else {
        $stmtT = $pdo->prepare("
            SELECT id, username, name, middle_name, surname, email, student_id, role, status, created_at 
            FROM users 
            WHERE role = 'teacher' 
            ORDER BY created_at DESC
        ");
        $stmtT->execute();
    }
    $teachers = $stmtT->fetchAll();

    // Fetch students with independent search
    if ($student_search !== '') {
        $stmtS = $pdo->prepare("
            SELECT id, username, name, middle_name, surname, email, student_id, role, status, created_at 
            FROM users 
            WHERE role = 'student' 
            AND (name ILIKE ? OR surname ILIKE ? OR username ILIKE ? OR student_id ILIKE ? OR email ILIKE ?)
            ORDER BY created_at DESC
        ");
        $termS = "%" . $student_search . "%";
        $stmtS->execute([$termS, $termS, $termS, $termS, $termS]);
    } else {
        $stmtS = $pdo->prepare("
            SELECT id, username, name, middle_name, surname, email, student_id, role, status, created_at 
            FROM users 
            WHERE role = 'student' 
            ORDER BY created_at DESC
        ");
        $stmtS->execute();
    }
    $students = $stmtS->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Manage Users</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .btn-danger { background-color: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-edit { background-color: #ffc107; color: #212529; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; font-weight: 600; }
        .btn-edit:hover { background-color: #e0a800; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge.approved { background-color: #d4edda; color: #155724; }
        .badge.pending { background-color: #fff3cd; color: #856404; }
        
        /* Section Header Layout with Search Box */
        .directory-header {
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
        <a href="admin_dashboard.php">Dashboard</a>
        
        <div class="menu-category">System Control</div>
        <a href="manage_users.php" class="active">Manage Users</a>
        <a href="manage_schedules.php">Class Schedules & Rooms</a>
        <a href="admin_grades_view.php">Student Grades</a>
        
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | USER MANAGEMENT</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <!-- MAIN PAGE TITLE CONTAINER -->
            <div class="card" style="margin-bottom: 25px;">
                <h3>System User Accounts Directory</h3>
                <p style="color: #6c757d; font-size: 14px; margin-top: 4px;">Review, update, or remove student and teacher accounts.</p>
            </div>

            <!-- TEACHERS CARD SECTION -->
            <div class="card" style="margin-bottom: 25px;">
                <div class="directory-header">
                    <div>
                        <h3>Teacher Accounts</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Manage instructor accounts and approvals.</p>
                    </div>
                    
                    <form method="GET" action="manage_users.php" class="search-form">
                        <input type="text" name="t_search" class="search-input" placeholder="Search teacher name..." value="<?php echo htmlspecialchars($teacher_search); ?>">
                        <?php if (!empty($student_search)): ?>
                            <input type="hidden" name="s_search" value="<?php echo htmlspecialchars($student_search); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($teacher_search)): ?>
                            <a href="manage_users.php<?php echo !empty($student_search) ? '?s_search=' . urlencode($student_search) : ''; ?>" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($teachers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Middle Name</th>
                                <th>Surname</th>
                                <th>Username / Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['middle_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['surname'] ?? ''); ?></td>
                                    <td>
                                        @<?php echo htmlspecialchars($u['username'] ?? ''); ?><br>
                                        <span style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($u['status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($u['status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn-edit">Edit</a>
                                        
                                        <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this teacher account?');">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="padding: 20px 0; text-align: center; color: #6c757d; font-size: 14px;">No teacher accounts found.</p>
                <?php endif; ?>
            </div>

            <!-- STUDENTS CARD SECTION -->
            <div class="card">
                <div class="directory-header">
                    <div>
                        <h3>Student Accounts</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Manage student accounts and approvals.</p>
                    </div>
                    
                    <form method="GET" action="manage_users.php" class="search-form">
                        <input type="text" name="s_search" class="search-input" placeholder="Search student ID or name..." value="<?php echo htmlspecialchars($student_search); ?>">
                        <?php if (!empty($teacher_search)): ?>
                            <input type="hidden" name="t_search" value="<?php echo htmlspecialchars($teacher_search); ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($student_search)): ?>
                            <a href="manage_users.php<?php echo !empty($teacher_search) ? '?t_search=' . urlencode($teacher_search) : ''; ?>" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($students) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Middle Name</th>
                                <th>Surname</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $u): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($u['student_id'] ?? 'N/A'); ?></code></td>
                                    <td><?php echo htmlspecialchars($u['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['middle_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['surname'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($u['status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($u['status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="btn-edit">Edit</a>
                                        
                                        <form action="delete_user.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student account?');">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="padding: 20px 0; text-align: center; color: #6c757d; font-size: 14px;">No student accounts found.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>