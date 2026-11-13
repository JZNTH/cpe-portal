<?php
session_start();
require_once 'db.php'; 

// Security check: Teacher only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../Login.html");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$students = [];

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT id, name, middle_name, surname, username, email, student_id, status, created_at 
            FROM users 
            WHERE role = 'student' AND (
                name ILIKE ? OR surname ILIKE ? OR username ILIKE ? OR email ILIKE ? OR student_id ILIKE ?
            )
            ORDER BY surname ASC
        ");
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->query("
            SELECT id, name, middle_name, surname, username, email, student_id, status, created_at 
            FROM users 
            WHERE role = 'student' 
            ORDER BY surname ASC
        ");
    }
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Registered Students Directory</title>
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
        .badge-status { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .badge-approved { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | TEACHER</div>
        
        <div class="menu-category">Navigation</div>
        <a href="teacher_dashboard.php">Dashboard</a>
        
        <div class="menu-category">Academic Tools</div>
        <a href="teacher_grades.php">Manage Student Grades</a>
        <a href="teacher_students.php" class="active">Registered Students</a>
        <a href="teacher_schedules.php">Class Schedules & Rooms</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | REGISTERED STUDENTS DIRECTORY</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <div class="card">
                <div class="directory-header">
                    <div>
                        <h3>Student Directory</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">View registered student accounts and institutional details.</p>
                    </div>
                    
                    <form method="GET" action="teacher_students.php" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Search name, ID, email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="teacher_students.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($students) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>Status</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $stu): ?>
                                <tr>
                                    <td>
                                        <b><?php echo htmlspecialchars($stu['surname'] . ', ' . $stu['name'] . ' ' . (!empty($stu['middle_name']) ? substr($stu['middle_name'], 0, 1) . '.' : '')); ?></b>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($stu['student_id'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>@<?php echo htmlspecialchars($stu['username']); ?></td>
                                    <td><?php echo htmlspecialchars($stu['email']); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo ($stu['status'] === 'approved') ? 'badge-approved' : 'badge-pending'; ?>">
                                            <?php echo htmlspecialchars($stu['status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px; color: #6c757d;">
                                        <?php echo date("M d, Y", strtotime($stu['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 30px;">No registered student records found.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>