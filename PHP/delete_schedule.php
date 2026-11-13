<?php
session_start();
require_once 'db.php';

// Security check: Ensure only admin can delete schedules
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);

    if ($scheduleId) {
        try {
            // Prepare and execute the delete statement
            $stmt = $pdo->prepare("DELETE FROM class_schedules WHERE id = ?");
            $stmt->execute([$scheduleId]);

            // Redirect back to manage schedules with success flag
            header("Location: manage_schedules.php?success=deleted");
            exit;
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Invalid Schedule ID.");
    }
} else {
    // Prevent direct browser URL access
    header("Location: manage_schedules.php");
    exit;
}
?>