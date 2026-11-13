<?php
session_start();
require_once 'db.php';

// Security check: Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Login.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    // Prevent admin from accidentally deleting their own currently logged-in account
    if ($userId && $userId !== $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            header("Location: manage_users.php?success=deleted");
            exit;
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } else {
        die("Invalid action or you cannot delete your own active administrator account.");
    }
} else {
    header("Location: manage_users.php");
    exit;
}
?>