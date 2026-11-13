<?php
session_start();
require_once 'db.php';

// Redirect if accessed directly without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Login.html");
    exit;
}

$hasError = false;
$title = "";
$message = "";
$buttonText = "Try Again";
$buttonLink = "../Login.html"; // Fixed path to root

// Capture username and password from form submission
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $hasError = true;
    $title = "Missing Fields";
    $message = "Please enter both your username and password.";
} else {
    try {
        // Query database for user with matching username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Verify that the user exists and the password hash matches
        if ($user && password_verify($password, $user['password'])) {
            
            // Check if account status is approved
            if ($user['status'] !== 'approved') {
                $hasError = true;
                $title = "Access Denied";
                $message = "Your account is currently <b>pending</b> administrator approval.";
            } else {
                // Set session variables for authentication tracking
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['name']     = $user['name'] ?? $user['username'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Route user based on role permissions
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'teacher') {
                    header("Location: teacher_dashboard.php");
                    exit;
                } elseif ($user['role'] === 'student') {
                    header("Location: student_dashboard.php");
                    exit;
                }
            }

        } else {
            $hasError = true;
            $title = "Login Failed";
            $message = "Invalid username or password. Please check your credentials and try again.";
        }

    } catch (PDOException $e) {
        $hasError = true;
        $title = "Database Error";
        $message = "A system error occurred: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSCpESP - Login Status</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 20px;
        }
        .status-card {
            background: #ffffff;
            width: 100%;
            max-width: 430px;
            padding: 40px 35px;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.35s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .portal-tag {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #0056b3;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .status-icon {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            background-color: #f8d7da;
            color: #721c24;
        }
        h2 {
            color: #212529;
            font-size: 22px;
            margin-bottom: 12px;
        }
        p {
            color: #5a6268;
            font-size: 14.5px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .btn-action {
            display: inline-block;
            width: 100%;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            background-color: #dc3545;
            color: #ffffff;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-action:hover {
            background-color: #c82333;
        }
        .btn-action:active {
            transform: scale(0.99);
        }
    </style>
</head>
<body>

    <div class="status-card">
        <div class="portal-tag">BSCpESP | CpE Portal</div>
        
        <div class="status-icon">
            &#10005;
        </div>

        <h2><?php echo htmlspecialchars($title); ?></h2>
        <p><?php echo $message; ?></p>

        <a href="<?php echo htmlspecialchars($buttonLink); ?>" class="btn-action">
            <?php echo htmlspecialchars($buttonText); ?>
        </a>
    </div>

</body>
</html>