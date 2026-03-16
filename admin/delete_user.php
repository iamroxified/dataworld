<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id_to_delete = $_POST['user_id'];

    // Prevent admin from deleting their own account
    if ($user_id_to_delete == $_SESSION['user_id']) {
        header('Location: all_users.php?error=cannot_delete_self');
        exit;
    }

    // Delete user from database
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$user_id_to_delete])) {
        header('Location: all_users.php?delete_success=true');
        exit;
    } else {
        header('Location: all_users.php?error=delete_failed');
        exit;
    }
} else {
    header('Location: all_users.php');
    exit;
}
