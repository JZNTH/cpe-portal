<?php
session_start();
require_once 'db.php'; 

// Security check: Ensure only teacher can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../Login.html");
    exit;
}

// Initialize default variables
$totalStudents = 0;
$totalSchedules = 0;
$totalGradesSubmitted = 0;
$schedules = [];

try {
    // Count total students registered
    $stmtStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = $stmtStudents->fetchColumn();
} catch (PDOException $e) {
    $totalStudents = 0;
}

try {
    // Count total class schedules
    $stmtSchedules = $pdo->query("SELECT COUNT(*) FROM class_schedules");
    $totalSchedules = $stmtSchedules->fetchColumn();
} catch (PDOException $e) {
    $totalSchedules = 0;
}

try {
    // Count total grade records submitted
    $stmtGrades = $pdo->query("SELECT COUNT(*) FROM student_grades");
    $totalGradesSubmitted = $stmtGrades->fetchColumn();
} catch (PDOException $e) {
    $totalGradesSubmitted = 0;
}

// Fetch recent class schedules for overview
try {
    $stmtList = $pdo->query("SELECT * FROM class_schedules ORDER BY day_of_week, start_time ASC LIMIT 5");
    $schedules = $stmtList->fetchAll();
} catch (PDOException $e) {
    $schedules = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Teacher Dashboard</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .badge-room { background: #e2e3e5; color: #383d41; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | TEACHER</div>
        
        <div class="menu-category">Navigation</div>
        <a href="teacher_dashboard.php" class="active">Dashboard</a>
        
        <div class="menu-category">Academic Tools</div>
        <a href="teacher_grades.php">Manage Student Grades</a>
        <a href="teacher_students.php">Registered Students</a>
        <a href="teacher_schedules.php">Class Schedules & Rooms</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | INSTRUCTOR PORTAL</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <!-- Banner Section -->
            <div class="banner-card">
                <div style="background:#28a745; color:white; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:20px;">CpE</div>
                <div>
                    <h1>INSTRUCTOR DASHBOARD</h1>
                    <p style="color: #6c757d; font-size: 14px; margin-top: 4px;">Welcome back, Instructor <?php echo htmlspecialchars($_SESSION['name'] ?? 'Teacher'); ?>.</p>
                </div>
            </div>

            <!-- Stats Metric Cards -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-top"><?php echo $totalStudents; ?></div>
                    <div class="stat-bottom">Registered Students</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-top"><?php echo $totalSchedules; ?></div>
                    <div class="stat-bottom">Class Schedules & Rooms</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-top"><?php echo $totalGradesSubmitted; ?></div>
                    <div class="stat-bottom">Grade Records Submitted</div>
                </div>
            </div>

            <!-- Quick Schedule Overview Card -->
            <div class="card">
                <h3>Department Class Schedules & Room Overview</h3>
                <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Quick reference of current active class assignments.</p>

                <?php if (count($schedules) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code & Name</th>
                                <th>Instructor</th>
                                <th>Day & Time</th>
                                <th>Room</th>
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
                                        <?php echo htmlspecialchars($sch['day_of_week']); ?><br>
                                        <span style="font-size: 12px; color: #6c757d;">
                                            <?php echo date("g:i A", strtotime($sch['start_time'])) . ' - ' . date("g:i A", strtotime($sch['end_time'])); ?>
                                        </span>
                                    </td>
                                    <td><span class="badge-room"><?php echo htmlspecialchars($sch['room']); ?></span></td>
                                    <td><?php echo htmlspecialchars($sch['section']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">No class schedules available yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>