<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'operator') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];

    if (!isset($_FILES['completed_work']) || $_FILES['completed_work']['error'] == UPLOAD_ERR_NO_FILE) {
        $_SESSION['message'] = 'Please select a file to upload.';
        $_SESSION['message_type'] = 'danger';
        header('Location: operator_analytics_requests.php');
        exit;
    }

    if ($_FILES['completed_work']['error'] != UPLOAD_ERR_OK) {
        $_SESSION['message'] = 'An error occurred during file upload. Please try again.';
        $_SESSION['message_type'] = 'danger';
        header('Location: operator_analytics_requests.php');
        exit;
    }

    $target_dir = "../uploads/completed_analytics/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["completed_work"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (file_exists($target_file)) {
        $_SESSION['message'] = "Sorry, a file with that name already exists.";
        $_SESSION['message_type'] = "danger";
        $uploadOk = 0;
    }

    if ($uploadOk && $_FILES["completed_work"]["size"] > 50000000) { // 50MB
        $_SESSION['message'] = "Sorry, your file is too large (max 50MB).";
        $_SESSION['message_type'] = "danger";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        header('Location: operator_analytics_requests.php');
        exit;
    }

    if (move_uploaded_file($_FILES["completed_work"]["tmp_name"], $target_file)) {
        $completed_work_path = $target_file;

        try {
            // Get order_id from analytics_requests
            $stmt = $pdo->prepare("SELECT order_id FROM analytics_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $order_id = $stmt->fetchColumn();

            if($order_id) {
                // Update analytics_requests table
                $sql = "UPDATE analytics_requests SET completed_work_path = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$completed_work_path, $request_id]);

                // Update orders table
                $sql = "UPDATE orders SET status = 'completed' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$order_id]);

                $_SESSION['message'] = 'Completed work uploaded successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error: Could not find the associated order.';
                $_SESSION['message_type'] = 'danger';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "A database error occurred. Please try again.";
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = "Sorry, there was an error uploading your file.";
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: operator_analytics_requests.php');
    exit;
}
?>