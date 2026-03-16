<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit;
}


$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header('Location: analytics_request.php');
    exit;
}

$request_id = $_GET['id'];

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["chapter3_file"])) {
    $upload_dir = '../uploads/chapter3/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

    $file_extension = pathinfo($_FILES['chapter3_file']['name'], PATHINFO_EXTENSION);
    $chapter3_file = $upload_dir . uniqid() . '.' . $file_extension;

    // Debugging: Check if the file exists and is readable
    if (!is_uploaded_file($_FILES['chapter3_file']['tmp_name'])) {
      $error_message = "Error: File not uploaded properly.";
        echo "<script>alert('$error_message');</script>";
        header('Location: view_request.php?id=' . $request_id . '&upload=failed&error=filenotuploaded');
        exit;
    }

    if (move_uploaded_file($_FILES['chapter3_file']['tmp_name'],  $chapter3_file)) {
        // Update the analytics_requests table with the new file path
        $stmt = $pdo->prepare("UPDATE analytics_requests SET chapter3_file = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$chapter3_file, $request_id, $user_id]);
        
        header('Location: view_request.php?id=' . $request_id . '&upload=success');
        exit;
    } else {
      $error_message = "Error: Failed to move uploaded file.";
        echo "<script>alert('$error_message');</script>";
        header('Location: view_request.php?id=' . $request_id . '&upload=failed');
        exit;
    }
} else {
    header('Location: view_request.php?id=' . $request_id);
    exit;
}
?>