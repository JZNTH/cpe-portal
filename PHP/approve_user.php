<?php
session_start();
require_once 'db.php';

// Security check: Ensure the user logged in is actually an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: Login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the user ID from the form submission
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($userId) {
        try {
            // Update the user's status to 'approved' using a prepared statement
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
            $stmt->execute([$userId]);

            // Redirect back to the admin dashboard with success
            header("Location: admin_dashboard.php?success=approved");
            exit;

        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Invalid User ID.");
    }
} else {
    // Prevent direct browser URL access
    header("Location: admin_dashboard.php");
    exit;
}
?>