<?php
session_start();
require_once 'db.php';

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$userId) {
    header("Location: manage_users.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']);
    $middle_name = trim($_POST['middle_name']);
    $surname    = trim($_POST['surname']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $role       = $_POST['role'];

    // If role is teacher, student_id can be null or empty
    if ($role === 'teacher') {
        $student_id = null;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, middle_name = ?, surname = ?, username = ?, email = ?, student_id = ?, role = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $middle_name, $surname, $username, $email, $student_id, $role, $userId]);

        header("Location: manage_users.php?success=updated");
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23505) {
            $error = "Error: That username or email is already taken by another account.";
        } else {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch user data for pre-filling the form
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: manage_users.php");
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
    <title>CpE Portal - Edit User</title>
    <link rel="stylesheet" href="/CSS/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">BSCpESP | ADMIN</div>
        <div class="menu-category">Navigation</div>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php" class="active">Manage Users</a>
        <a href="manage_schedules.php">Class Schedules & Rooms</a>
        <a href="admin_grades_view.php">Student Grades (View-Only)</a>
        <div class="menu-category">Account</div>
        
    </div>

    <div class="main-wrapper">
        <div class="top-navbar">
            <span class="nav-title">BSCpESP | EDIT USER ACCOUNT</span>
            <a href="logout.php" class="logout-btn">Logout ➔</a>
        </div>

        <div class="content-container">
            <div class="card" style="max-width: 650px; margin: 0 auto;">
                <h3>Edit User Account: <?php echo htmlspecialchars($user['username']); ?></h3>
                <p style="color: #6c757d; font-size: 14px; margin-bottom: 20px;">Update personal details, institutional identifiers, and system access roles.</p>
                
                <?php if (!empty($error)): ?>
                    <p style="color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?php echo $error; ?></p>
                <?php endif; ?>

                <form action="edit_user.php?id=<?php echo $userId; ?>" method="POST">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>First Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>" style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Surname</label>
                        <input type="text" name="surname" value="<?php echo htmlspecialchars($user['surname'] ?? ''); ?>" required style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Student ID (Required for Students)</label>
                        <input type="text" name="student_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Role</label>
                        <select name="role" style="width:100%; padding:8px; margin-top:5px; border: 1px solid #ced4da; border-radius: 4px;">
                            <option value="student" <?php if(($user['role'] ?? '') == 'student') echo 'selected'; ?>>Student</option>
                            <option value="teacher" <?php if(($user['role'] ?? '') == 'teacher') echo 'selected'; ?>>Teacher</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-approve" style="padding: 10px 20px;">Save Changes</button>
                    <a href="manage_users.php" style="margin-left: 15px; color: #6c757d; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </div>

</body>
</html>