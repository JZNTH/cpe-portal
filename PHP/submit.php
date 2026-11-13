<?php
require_once 'db.php';

// Redirect if accessed directly without POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../SignUp.html");
    exit;
}

$isSuccess = false;
$title = "";
$message = "";
$buttonText = "";
$buttonLink = "";

// Fetch and trim data from signup form
$name        = trim($_POST['Name'] ?? '');
$middleName  = trim($_POST['middleName'] ?? '');
$surname     = trim($_POST['surname'] ?? '');
$email       = trim($_POST['email'] ?? '');
$studentID   = trim($_POST['studentID'] ?? '');
$username    = trim($_POST['username'] ?? '');
$rawPassword = $_POST['password'] ?? '';
$role        = $_POST['role'] ?? '';
$status      = 'pending';

if ($role === 'teacher') {
    $studentID = null;
}

// Validation checks
if ($role === 'student' && empty($studentID)) {
    $title = "Registration Incomplete";
    $message = "Student ID is required when registering as a student.";
    $buttonText = "Back to Sign Up";
    $buttonLink = "../SignUp.html";
} elseif (empty($name) || empty($surname) || empty($email) || empty($username) || empty($rawPassword) || empty($role)) {
    $title = "Missing Information";
    $message = "Please fill in all required fields before submitting.";
    $buttonText = "Back to Sign Up";
    $buttonLink = "../SignUp.html";
} else {
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (name, middle_name, surname, email, student_id, username, password, role, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name, 
            $middleName, 
            $surname, 
            $email, 
            $studentID, 
            $username, 
            $hashedPassword, 
            $role, 
            $status
        ]);

        $isSuccess = true;
        $title = "Registration Successful!";
        $message = "Your <b>BSCpESP</b> account has been submitted and is currently <b>pending</b> administrator approval.";
        $buttonText = "Proceed to Log In";
        $buttonLink = "../Login.html";

    } catch (PDOException $e) {
        $title = "Registration Failed";
        if ($e->getCode() == 23505) {
            $message = "That username, email, or Student ID is already registered in the system.";
        } else {
            $message = "A database error occurred: " . htmlspecialchars($e->getMessage());
        }
        $buttonText = "Try Again";
        $buttonLink = "../SignUp.html";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSCpESP - Registration Status</title>
    <link rel="stylesheet" href="/css/style.css">
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
        }
        .icon-success {
            background-color: #d4edda;
            color: #155724;
        }
        .icon-error {
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
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-success {
            background-color: #0056b3;
            color: #ffffff;
        }
        .btn-success:hover {
            background-color: #004085;
        }
        .btn-error {
            background-color: #dc3545;
            color: #ffffff;
        }
        .btn-error:hover {
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
        
        <div class="status-icon <?php echo $isSuccess ? 'icon-success' : 'icon-error'; ?>">
            <?php echo $isSuccess ? '&#10003;' : '&#10005;'; ?>
        </div>

        <h2><?php echo htmlspecialchars($title); ?></h2>
        <p><?php echo $message; ?></p>

        <a href="<?php echo htmlspecialchars($buttonLink); ?>" class="btn-action <?php echo $isSuccess ? 'btn-success' : 'btn-error'; ?>">
            <?php echo htmlspecialchars($buttonText); ?>
        </a>
    </div>

</body>
</html>