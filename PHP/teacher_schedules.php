<?php
session_start();
require_once 'db.php'; 

// Security check: Teacher only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../Login.html");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$schedules = [];

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT * FROM class_schedules 
            WHERE subject_code ILIKE ? OR subject_name ILIKE ? OR instructor ILIKE ? OR room ILIKE ? OR section ILIKE ?
            ORDER BY day_of_week, start_time ASC
        ");
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt = $pdo->query("SELECT * FROM class_schedules ORDER BY day_of_week, start_time ASC");
    }
    $schedules = $stmt->fetchAll();
} catch (PDOException $e) {
    $schedules = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Class Schedules & Rooms</title>
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
        .badge-room { background: #e2e3e5; color: #383d41; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
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
        <a href="teacher_students.php">Registered Students</a>
        <a href="teacher_schedules.php" class="active">Class Schedules & Rooms</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | CLASS SCHEDULES & ROOMS</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <div class="card">
                <div class="directory-header">
                    <div>
                        <h3>Active Class Schedules & Room Assignments</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Review department timetables, room designations, and section assignments.</p>
                    </div>
                    
                    <form method="GET" action="teacher_schedules.php" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Search subject, instructor, room..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="teacher_schedules.php" class="btn-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (count($schedules) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code & Name</th>
                                <th>Instructor</th>
                                <th>Day & Time</th>
                                <th>Room Location</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $sch): ?>
                                <tr>
                                    <td>
                                        <b><?php echo htmlspecialchars($sch['subject_code']); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($sch['subject_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($sch['instructor']); ?></td>
                                    <td>
                                        <b><?php echo htmlspecialchars($sch['day_of_week']); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;">
                                            <?php echo date("g:i A", strtotime($sch['start_time'])) . ' - ' . date("g:i A", strtotime($sch['end_time'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-room"><?php echo htmlspecialchars($sch['room']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($sch['section']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 30px;">No class schedules found.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>