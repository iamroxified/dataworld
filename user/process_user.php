<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Ensure user is logged in and has 'admin' role
if (!isset($_SESSION['user_id']) || getCurrentUser()['role'] !== 'admin') {
    header('Location:../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $role = $_POST['role'];

        if (createUser($username, $email, $password, $first_name, $last_name, $role)) {
            $_SESSION['message'] = 'User added successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to add user.';
            $_SESSION['message_type'] = 'danger';
        }
        header('Location: users.php');
        exit;
    } elseif ($action == 'edit') {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $role = $_POST['role'];

        if (updateUser($user_id, $username, $email, $first_name, $last_name, $role)) {
            $_SESSION['message'] = 'User updated successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to update user.';
            $_SESSION['message_type'] = 'danger';
        }
        header('Location: users.php');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete') {
    $user_id = $_GET['id'];
    if (deleteUser($user_id)) {
        $_SESSION['message'] = 'User deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to delete user.';
        $_SESSION['message_type'] = 'danger';
    }
    header('Location: users.php');
    exit;
}

$_SESSION['message'] = 'Invalid action.';
$_SESSION['message_type'] = 'danger';
header('Location: users.php');
exit;
?>