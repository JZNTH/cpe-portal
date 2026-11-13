<?php
session_start();
require_once 'db.php';

// Security check: Student only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../Login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$student = null;
$grades = [];
$schedules = [];

// Fetch student profile details from users table
try {
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $student = $stmtUser->fetch();
} catch (PDOException $e) {
    $student = null;
}

// Fetch grades specifically tied to this student's user_id
try {
    $stmtGrades = $pdo->prepare("
        SELECT * FROM student_grades 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmtGrades->execute([$user_id]);
    $grades = $stmtGrades->fetchAll();
} catch (PDOException $e) {
    $grades = [];
}

// Fetch class schedules
try {
    $stmtSchedules = $pdo->query("SELECT * FROM class_schedules ORDER BY day_of_week, start_time ASC");
    $schedules = $stmtSchedules->fetchAll();
} catch (PDOException $e) {
    $schedules = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Student Dashboard</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        .profile-card { background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .profile-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .profile-item:last-child { border-bottom: none; }
        .badge-grade { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-passed { background-color: #d4edda; color: #155724; }
        .badge-failed { background-color: #f8d7da; color: #721c24; }
        .section-title { margin-top: 30px; margin-bottom: 15px; font-size: 18px; color: #333; border-bottom: 2px solid #0056b3; padding-bottom: 5px; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | STUDENT</div>
        
        <div class="menu-category">Navigation</div>
        <a href="student_dashboard.php" class="active">My Dashboard</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | STUDENT PORTAL</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <?php if ($student): ?>
                <!-- Welcome Banner & Profile Summary -->
                <div class="dashboard-grid">
                    <div class="profile-card">
                        <h3>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h3>
                        <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Here is your student account overview.</p>
                        
                        <div class="profile-item">
                            <span style="color: #6c757d;">Student ID:</span>
                            <b><?php echo htmlspecialchars($student['student_id'] ?? 'Not Assigned'); ?></b>
                        </div>
                        <div class="profile-item">
                            <span style="color: #6c757d;">Full Name:</span>
                            <b><?php echo htmlspecialchars($student['surname'] . ', ' . $student['name'] . ' ' . ($student['middle_name'] ?? '')); ?></b>
                        </div>
                        <div class="profile-item">
                            <span style="color: #6c757d;">Username:</span>
                            <span>@<?php echo htmlspecialchars($student['username']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span style="color: #6c757d;">Email Address:</span>
                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="profile-item">
                            <span style="color: #6c757d;">Account Status:</span>
                            <span style="color: #28a745; font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($student['status']); ?></span>
                        </div>
                    </div>

                    <div class="profile-card" style="display: flex; flex-direction: column; justify-content: center; text-align: center; background: linear-gradient(135deg, #0056b3, #004085); color: white;">
                        <h2 style="margin-bottom: 10px;">BS Computer Engineering</h2>
                        <p style="font-size: 14px; opacity: 0.9; margin-bottom: 20px;">Student Self-Service Portal & Academic Tracker</p>
                        <div style="font-size: 13px; background: rgba(255,255,255,0.1); padding: 10px; border-radius: 4px;">
                            Total Enrolled/Recorded Grades: <b><?php echo count($grades); ?></b>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- My Academic Grades Section -->
            <div class="card">
                <div class="section-title" style="margin-top: 0;">My Academic Grades</div>
                <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Performance records submitted by your instructors.</p>

                <?php if (count($grades) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject Code & Name</th>
                                <th>Instructor</th>
                                <th>Grade</th>
                                <th>Remarks</th>
                                <th>Date Recorded</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $g): ?>
                                <tr>
                                    <td>
                                        <b><?php echo htmlspecialchars($g['subject_code']); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($g['subject_name']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($g['teacher_name']); ?></td>
                                    <td><b style="font-size: 15px;"><?php echo htmlspecialchars($g['grade']); ?></b></td>
                                    <td>
                                        <?php $remarks = strtolower($g['remarks']); ?>
                                        <span class="badge-grade <?php echo ($remarks == 'passed' || $remarks == 'pass') ? 'badge-passed' : 'badge-failed'; ?>">
                                            <?php echo htmlspecialchars($g['remarks']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px; color: #6c757d;">
                                        <?php echo date("M d, Y", strtotime($g['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 25px;">No grade records have been posted to your account yet.</p>
                <?php endif; ?>
            </div>

            <!-- Class Schedules Section -->
            <div class="card" style="margin-top: 25px;">
                <div class="section-title" style="margin-top: 0;">Class Schedules & Room Directory</div>
                <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Review active department schedules and room locations.</p>

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
                                        <b><?php echo htmlspecialchars($sch['day_of_week']); ?></b><br>
                                        <span style="font-size: 12px; color: #6c757d;">
                                            <?php echo date("g:i A", strtotime($sch['start_time'])) . ' - ' . date("g:i A", strtotime($sch['end_time'])); ?>
                                        </span>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($sch['room']); ?></code></td>
                                    <td><?php echo htmlspecialchars($sch['section']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 25px;">No class schedules currently available.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>