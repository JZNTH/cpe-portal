<?php
session_start();
require_once 'db.php'; 

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$grades = [];

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT sg.*, u.student_id, u.name, u.middle_name, u.surname 
            FROM student_grades sg
            JOIN users u ON sg.user_id = u.id
            WHERE u.name ILIKE ? OR u.surname ILIKE ? OR u.student_id ILIKE ? OR sg.subject_code ILIKE ? OR sg.subject_name ILIKE ? OR sg.teacher_name ILIKE ?
            ORDER BY sg.created_at DESC
        ");
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->query("
            SELECT sg.*, u.student_id, u.name, u.middle_name, u.surname 
            FROM student_grades sg
            JOIN users u ON sg.user_id = u.id
            ORDER BY sg.created_at DESC
        ");
    }
    $grades = $stmt->fetchAll();
} catch (PDOException $e) {
    $grades = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Admin Grade Records</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .directory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .search-form { display: flex; gap: 8px; }
        .search-input { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; width: 220px; }
        .btn-search { background-color: #0056b3; color: white; border: none; padding: 7px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-search:hover { background-color: #004085; }
        .btn-clear { background-color: #6c757d; color: white; border: none; padding: 7px 10px; border-radius: 4px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; }
        .btn-clear:hover { background-color: #5a6268; }
        .badge-grade { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-passed { background-color: #d4edda; color: #155724; }
        .badge-failed { background-color: #f8d7da; color: #721c24; }
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
            <span class="nav-title">BSCpESP | ALL STUDENT GRADE RECORDS</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <div class="card">
                <div class="directory-header">
                    <div>
                        <h3>System Grade Records Overview</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Review all student grades submitted across all instructors.</p>
                    </div>
                    
                    <form method="GET" action="admin_grades_view.php" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Search student, subject..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="admin_grades_view.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($grades) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name & ID</th>
                                <th>Subject Code & Name</th>
                                <th>Instructor</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                                <th>Date Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $g): ?>
                                <?php 
                                    $surname = $g['surname'] ?? '';
                                    $name = $g['name'] ?? '';
                                    $middle_name = $g['middle_name'] ?? '';
                                    $studentId = $g['student_id'] ?? 'N/A';
                                    
                                    $fullName = trim($surname . ', ' . $name . ' ' . (!empty($middle_name) ? substr($middle_name, 0, 1) . '.' : ''));
                                ?>
                                <tr>
                                    <td>
                                        <b><?php echo htmlspecialchars($fullName !== '' ? $fullName : 'Unknown Student'); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;">ID: <?php echo htmlspecialchars($studentId); ?></span>
                                    </td>
                                    <td>
                                        <b><?php echo htmlspecialchars($g['subject_code'] ?? ''); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($g['subject_name'] ?? ''); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($g['teacher_name'] ?? 'Instructor'); ?></td>
                                    <td><b style="font-size: 15px;"><?php echo htmlspecialchars($g['grade'] ?? '0.00'); ?></b></td>
                                    <td>
                                        <?php $remarks = strtolower($g['remarks'] ?? ''); ?>
                                        <span class="badge-grade <?php echo ($remarks == 'passed' || $remarks == 'pass') ? 'badge-passed' : 'badge-failed'; ?>">
                                            <?php echo htmlspecialchars($g['remarks'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px; color: #6c757d;">
                                        <?php echo isset($g['created_at']) ? date("M d, Y", strtotime($g['created_at'])) : ''; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 30px;">No grade records found in the database.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>