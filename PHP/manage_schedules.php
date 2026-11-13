<?php
session_start();
require_once 'db.php'; 

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Login.html");
    exit;
}

$error = '';
$success = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle adding a new schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
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
                INSERT INTO class_schedules (subject_code, subject_name, instructor, day_of_week, start_time, end_time, room, section) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$subject_code, $subject_name, $instructor, $day_of_week, $start_time, $end_time, $room, $section]);
            $success = "Class schedule successfully created!";
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch schedules with search filter support
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
        .schedule-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .schedule-layout { grid-template-columns: 1fr; }
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn-danger { background-color: #dc3545; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-edit { background-color: #ffc107; color: #212529; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; }
        .btn-edit:hover { background-color: #e0a800; }
        .badge-room { background: #e2e3e5; color: #383d41; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        
        /* Directory Header & Search Layout */
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
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | ADMIN</div>
        
        <div class="menu-category">Navigation</div>
        <a href="admin_dashboard.php">Dashboard</a>
        
        <div class="menu-category">System Control</div>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_schedules.php" class="active">Class Schedules & Rooms</a>
        <a href="admin_grades_view.php">Student Grades</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | CLASS SCHEDULES & ROOM MANAGEMENT</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <!-- Content Area -->
        <div class="content-container">
            
            <?php if (!empty($success)): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px;"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="schedule-layout">
                
                <!-- Left Column: Add Schedule Form -->
                <div class="card">
                    <h3>Add Class Schedule</h3>
                    <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Assign subjects, times, and room locations.</p>

                    <form action="manage_schedules.php" method="POST">
                        <input type="hidden" name="action" value="add">

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Subject Code</label>
                            <input type="text" name="subject_code" placeholder="e.g. CpE 311" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Subject Name</label>
                            <input type="text" name="subject_name" placeholder="e.g. Computer Architecture" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Instructor</label>
                            <input type="text" name="instructor" placeholder="e.g. Dr. Juan Dela Cruz" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>

                        <div class="form-row" style="margin-bottom: 12px;">
                            <div>
                                <label style="font-size:13px; font-weight:600;">Day</label>
                                <select name="day_of_week" style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:13px; font-weight:600;">Section</label>
                                <input type="text" name="section" placeholder="e.g. BSCpE 3A" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 12px;">
                            <div>
                                <label style="font-size:13px; font-weight:600;">Start Time</label>
                                <input type="time" name="start_time" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                            </div>
                            <div>
                                <label style="font-size:13px; font-weight:600;">End Time</label>
                                <input type="time" name="end_time" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label style="font-size:13px; font-weight:600;">Assigned Room</label>
                            <input type="text" name="room" placeholder="e.g. Lab 3B / Rm 402" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>

                        <button type="submit" class="btn-approve" style="width:100%; padding:10px;">Create Schedule Entry</button>
                    </form>
                </div>

                <!-- Right Column: Existing Schedules Directory with Search Box -->
                <div class="card">
                    <div class="directory-header">
                        <div>
                            <h3>Master Class Schedules & Room Directory</h3>
                            <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Active classes and room assignments across the department.</p>
                        </div>
                        
                        <form method="GET" action="manage_schedules.php" class="search-form">
                            <input type="text" name="search" class="search-input" placeholder="Search subject, room, instructor..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn-search">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="manage_schedules.php" class="btn-clear">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (count($schedules) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Day & Time</th>
                                    <th>Room</th>
                                    <th>Section</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $sch): ?>
                                    <tr>
                                        <td>
                                            <b><?php echo htmlspecialchars($sch['subject_code']); ?></b><br>
                                            <span style="font-size: 12px; color: #6c757d;"><?php echo htmlspecialchars($sch['subject_name']); ?></span><br>
                                            <span style="font-size: 11px; color: #0056b3;">Instructor: <?php echo htmlspecialchars($sch['instructor']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($sch['day_of_week']); ?><br>
                                            <span style="font-size: 12px; color: #6c757d;">
                                                <?php echo date("g:i A", strtotime($sch['start_time'])) . ' - ' . date("g:i A", strtotime($sch['end_time'])); ?>
                                            </span>
                                        </td>
                                        <td><span class="badge-room"><?php echo htmlspecialchars($sch['room']); ?></span></td>
                                        <td><?php echo htmlspecialchars($sch['section']); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 6px; align-items: center;">
                                                <a href="edit_schedule.php?id=<?php echo $sch['id']; ?>" class="btn-edit">Edit</a>
                                                
                                                <form action="delete_schedule.php" method="POST" onsubmit="return confirm('Delete this schedule entry?');" style="margin: 0;">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                    <button type="submit" class="btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">No class schedules found matching your criteria.</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>