<?php
session_start();
require('../db/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: analytics_request.php');
    exit;
}

$request_id = $_GET['id'];

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["questionnaire"])) {
    $target_dir = "../uploads/";
    $questionnaire = $target_dir . basename($_FILES["questionnaire"]["name"]);
    
    if (move_uploaded_file($_FILES["questionnaire"]["tmp_name"], $questionnaire)) {
        // Update the analytics_requests table with the new file path
        $stmt = $pdo->prepare("UPDATE analytics_requests SET questionaire = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$questionnaire, $request_id, $user_id]);
        
        header('Location: view_request.php?id=' . $request_id . '&upload=success');
        exit;
    } else {
        header('Location: view_request.php?id=' . $request_id . '&upload=failed');
        exit;
    }
} else {
    header('Location: view_request.php?id=' . $request_id);
    exit;
}
?>