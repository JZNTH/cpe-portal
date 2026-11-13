<?php
session_start();
require_once 'db.php'; 

// Security check: Teacher only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: Login.html");
    exit;
}

$error = '';
$success = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$editGrade = null;

// Fetch all approved students directly from the users database table
try {
    $stmtStudents = $pdo->query("
        SELECT id, student_id, name, middle_name, surname 
        FROM users 
        WHERE role = 'student' AND status = 'approved' 
        ORDER BY surname ASC
    ");
    $registeredStudents = $stmtStudents->fetchAll();
} catch (PDOException $e) {
    $registeredStudents = [];
}

// Fetch available subjects from the class_schedules table
try {
    $stmtSubjects = $pdo->query("
        SELECT DISTINCT subject_code, subject_name 
        FROM class_schedules 
        ORDER BY subject_code ASC
    ");
    $availableSubjects = $stmtSubjects->fetchAll();
} catch (PDOException $e) {
    $availableSubjects = [];
}

// Handle Add or Update Grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $user_id     = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $subject_info = $_POST['subject_info'] ?? ''; // Contains "subject_code|subject_name"
    $teacher_name = $_SESSION['name'] ?? 'Instructor';
    $grade       = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_FLOAT);
    $remarks     = trim($_POST['remarks']);

    if ($user_id && !empty($subject_info) && $grade !== false) {
        // Split subject info back into code and name
        list($subject_code, $subject_name) = explode('|', $subject_info);

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO student_grades (user_id, subject_code, subject_name, teacher_name, grade, remarks) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $subject_code, $subject_name, $teacher_name, $grade, $remarks]);
                $success = "Student grade successfully recorded and linked to user account!";
            } elseif ($action === 'update') {
                $grade_id = filter_input(INPUT_POST, 'grade_id', FILTER_VALIDATE_INT);
                $stmt = $pdo->prepare("
                    UPDATE student_grades 
                    SET user_id = ?, subject_code = ?, subject_name = ?, grade = ?, remarks = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$user_id, $subject_code, $subject_name, $grade, $remarks, $grade_id]);
                $success = "Student grade successfully updated!";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please select a valid student, subject, and grade.";
    }
}

// Handle request to edit a specific grade record
if (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        try {
            $stmtEdit = $pdo->prepare("SELECT * FROM student_grades WHERE id = ?");
            $stmtEdit->execute([$editId]);
            $editGrade = $stmtEdit->fetch();
        } catch (PDOException $e) {
            $editGrade = null;
        }
    }
}

// Fetch all grade records by JOINING with the users database table
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT sg.*, u.student_id, u.name, u.middle_name, u.surname 
            FROM student_grades sg
            JOIN users u ON sg.user_id = u.id
            WHERE u.name ILIKE ? OR u.surname ILIKE ? OR u.student_id ILIKE ? OR sg.subject_code ILIKE ? OR sg.subject_name ILIKE ?
            ORDER BY sg.created_at DESC
        ");
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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
    <title>CpE Portal - Manage Student Grades</title>
    <link rel="stylesheet" href="/CSS/style.css">
    <style>
        .grade-layout {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 1024px) {
            .grade-layout { grid-template-columns: 1fr; }
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .directory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .search-form { display: flex; gap: 8px; }
        .search-input { padding: 7px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; width: 200px; }
        .btn-search { background-color: #0056b3; color: white; border: none; padding: 7px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-search:hover { background-color: #004085; }
        .btn-clear { background-color: #6c757d; color: white; border: none; padding: 7px 10px; border-radius: 4px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; }
        .btn-clear:hover { background-color: #5a6268; }
        .btn-edit { background-color: #ffc107; color: #212529; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; }
        .btn-edit:hover { background-color: #e0a800; }
        .badge-grade { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-passed { background-color: #d4edda; color: #155724; }
        .badge-failed { background-color: #f8d7da; color: #721c24; }
    </style>
    <script>
        // Real-time search filter for the student select dropdown
        function filterStudents() {
            const input = document.getElementById('studentSearchInput').value.toLowerCase();
            const select = document.getElementById('studentSelect');
            const options = select.options;

            for (let i = 1; i < options.length; i++) {
                const text = options[i].text.toLowerCase();
                if (text.includes(input)) {
                    options[i].style.display = '';
                } else {
                    options[i].style.display = 'none';
                }
            }
        }
    </script>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | TEACHER</div>
        
        <div class="menu-category">Navigation</div>
        <a href="teacher_dashboard.php">Dashboard</a>
        
        <div class="menu-category">Academic Tools</div>
        <a href="teacher_grades.php" class="active">Manage Student Grades</a>
        <a href="teacher_students.php">Registered Students</a>
        <a href="teacher_schedules.php">Class Schedules & Rooms</a>
    </div>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | STUDENT GRADE MANAGEMENT</span>
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

            <div class="grade-layout">
                
                <!-- Left Column: Add / Edit Grade Form -->
                <div class="card">
                    <h3><?php echo $editGrade ? 'Update Student Grade' : 'Add Student Grade'; ?></h3>
                    <p style="color: #6c757d; font-size: 13px; margin-bottom: 15px;">Search and select an approved student account.</p>

                    <form action="teacher_grades.php" method="POST">
                        <input type="hidden" name="action" value="<?php echo $editGrade ? 'update' : 'add'; ?>">
                        <?php if ($editGrade): ?>
                            <input type="hidden" name="grade_id" value="<?php echo $editGrade['id']; ?>">
                        <?php endif; ?>

                        <!-- Searchable Student Selection -->
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Search & Select Registered Student</label>
                            <input type="text" id="studentSearchInput" onkeyup="filterStudents()" placeholder="Type to filter student name or ID..." style="width:100%; padding:7px; margin-top:4px; margin-bottom:6px; border:1px solid #ced4da; border-radius:4px; font-size:13px;">
                            
                            <select id="studentSelect" name="user_id" required style="width:100%; padding:7px; border:1px solid #ced4da; border-radius:4px;">
                                <option value="">-- Choose Student User --</option>
                                <?php foreach ($registeredStudents as $student): ?>
                                    <?php 
                                        $isSelected = ($editGrade && $editGrade['user_id'] == $student['id']) ? 'selected' : '';
                                        $displayName = $student['surname'] . ', ' . $student['name'] . ' ' . (!empty($student['middle_name']) ? $student['middle_name'][0].'.' : '') . ' (ID: ' . ($student['student_id'] ?? 'N/A') . ')';
                                    ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo $isSelected; ?>>
                                        <?php echo htmlspecialchars($displayName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dropdown for Available Subjects from Schedules -->
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Select Subject (From Active Schedules)</label>
                            <select name="subject_info" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($availableSubjects as $subj): ?>
                                    <?php 
                                        $combinedVal = $subj['subject_code'] . '|' . $subj['subject_name'];
                                        $isSelectedSubj = ($editGrade && $editGrade['subject_code'] === $subj['subject_code']) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo htmlspecialchars($combinedVal); ?>" <?php echo $isSelectedSubj; ?>>
                                        <?php echo htmlspecialchars($subj['subject_code'] . ' - ' . $subj['subject_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($availableSubjects)): ?>
                                <small style="color: #dc3545;">No subjects found. Please ask Admin to add class schedules first.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size:13px; font-weight:600;">Grade (e.g. 1.75)</label>
                            <input type="number" step="0.01" name="grade" placeholder="e.g. 1.75" value="<?php echo htmlspecialchars($editGrade['grade'] ?? ''); ?>" required style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label style="font-size:13px; font-weight:600;">Remarks / Status</label>
                            <select name="remarks" style="width:100%; padding:7px; margin-top:4px; border:1px solid #ced4da; border-radius:4px;">
                                <option value="Passed" <?php if(($editGrade['remarks'] ?? '') == 'Passed') echo 'selected'; ?>>Passed</option>
                                <option value="Failed" <?php if(($editGrade['remarks'] ?? '') == 'Failed') echo 'selected'; ?>>Failed</option>
                                <option value="Incomplete" <?php if(($editGrade['remarks'] ?? '') == 'Incomplete') echo 'selected'; ?>>Incomplete</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-approve" style="width:100%; padding:10px;"><?php echo $editGrade ? 'Save Grade Updates' : 'Submit Grade Record'; ?></button>
                        <?php if ($editGrade): ?>
                            <a href="teacher_grades.php" style="display:block; text-align:center; margin-top:10px; font-size:13px; color:#6c757d; text-decoration:none;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Right Column: Grade Directory -->
                <div class="card">
                    <div class="directory-header">
                        <div>
                            <h3>Student Grade Records</h3>
                            <p style="color: #6c757d; font-size: 13px; margin-top: 2px;">Comprehensive directory of submitted class grades.</p>
                        </div>
                        
                        <form method="GET" action="teacher_grades.php" class="search-form">
                            <input type="text" name="search" class="search-input" placeholder="Search student name, ID..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn-search">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="teacher_grades.php" class="btn-clear">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (count($grades) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name & ID</th>
                                    <th>Subject</th>
                                    <th>Instructor</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $g): ?>
                                    <tr>
                                        <td>
                                            <b><?php echo htmlspecialchars($g['surname'] . ', ' . $g['name'] . ' ' . (!empty($g['middle_name']) ? substr($g['middle_name'],0,1).'.' : '')); ?></b><br>
                                            <span style="font-size: 12px; color: #6c757d;">ID: <?php echo htmlspecialchars($g['student_id'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($g['subject_code']); ?><br>
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
                                        <td>
                                            <a href="teacher_grades.php?edit=<?php echo $g['id']; ?>" class="btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 25px;">No grade records found matching your search.</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

</body>
</html>