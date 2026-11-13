<?php
session_start();
require_once 'db.php';

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

$scheduleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$scheduleId) {
    header("Location: manage_schedules.php");
    exit;
}

$error = '';

// Handle form submission for updating the schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_code = trim($_POST['subject_code']);
    $subject_name = trim($_POST['subject_name']);
    $instructor   = trim($_POST['instructor']);
    $day_of_week  = $_POST['day_of_week'];
    $start_time   = $_POST['start_time'];
    $end_time     = $_POST['end_time'];
    $room         = trim($_POST['room']);
    $section      = trim($_POST['section']);

    if (!empty($subject_code) && !empty($subject_name) && !empty($instructor) && !empty($room)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE class_schedules 
                SET subject_code = ?, subject_name = ?, instructor = ?, day_of_week = ?, start_time = ?, end_time = ?, room = ?, section = ? 
                WHERE id = ?
            ");
            $stmt->execute([$subject_code, $subject_name, $instructor, $day_of_week, $start_time, $end_time, $room, $section, $scheduleId]);

            header("Location: manage_schedules.php?success=updated");
            exit;
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch existing schedule data
try {
    $stmt = $pdo->prepare("SELECT * FROM class_schedules WHERE id = ?");
    $stmt->execute([$scheduleId]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        header("Location: manage_schedules.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CpE Portal - Edit Schedule</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | ADMIN</div>
        <div class="menu-category">Navigation</div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_schedules.php" class="active">Class Schedules & Rooms</a>
        <a href="admin_grades_view.php">Student Grades</a>
        
    </div>

    <div class="main-wrapper">
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | EDIT CLASS SCHEDULE</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <div class="content-container">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <h3>Edit Schedule: <?php echo htmlspecialchars($schedule['subject_code']); ?></h3>
                <p style="color: #6c757d; font-size: 13px; margin-bottom: 20px;">Modify class timings, assigned rooms, or instructor details.</p>

                <?php if (!empty($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="edit_schedule.php?id=<?php echo $scheduleId; ?>" method="POST">
                    
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size:13px; font-weight:600;">Subject Code</label>
                        <input type="text" name="subject_code" value="<?php echo htmlspecialchars($schedule['subject_code']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size:13px; font-weight:600;">Subject Name</label>
                        <input type="text" name="subject_name" value="<?php echo htmlspecialchars($schedule['subject_name']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size:13px; font-weight:600;">Instructor</label>
                        <input type="text" name="instructor" value="<?php echo htmlspecialchars($schedule['instructor']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                    </div>

                    <div class="form-row" style="margin-bottom: 12px;">
                        <div>
                            <label style="font-size:13px; font-weight:600;">Day</label>
                            <select name="day_of_week" style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                                <?php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; ?>
                                <?php foreach ($days as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php if($schedule['day_of_week'] == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px; font-weight:600;">Section</label>
                            <input type="text" name="section" value="<?php echo htmlspecialchars($schedule['section']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom: 12px;">
                        <div>
                            <label style="font-size:13px; font-weight:600;">Start Time</label>
                            <input type="time" name="start_time" value="<?php echo htmlspecialchars($schedule['start_time']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>
                        <div>
                            <label style="font-size:13px; font-weight:600;">End Time</label>
                            <input type="time" name="end_time" value="<?php echo htmlspecialchars($schedule['end_time']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-size:13px; font-weight:600;">Assigned Room</label>
                        <input type="text" name="room" value="<?php echo htmlspecialchars($schedule['room']); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                    </div>

                    <button type="submit" class="btn-approve" style="padding: 10px 20px;">Save Changes</button>
                    <a href="manage_schedules.php" style="margin-left: 15px; color: #6c757d; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </div>

</body>
</html>