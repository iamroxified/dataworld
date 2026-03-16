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

$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Add User - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
</head>

<body>
    <div class="wrapper">
        <?php include('nav/sidebar.php'); ?>
        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Add New User</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="users.php">Manage Users</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Add User</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <?php if ($message) : ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">User Details</h4>
                                </div>
                                <div class="card-body">
                                    <form action="process_user.php" method="POST">
                                        <input type="hidden" name="action" value="add">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select class="form-control" id="role" name="role" required>
                                                <option value="user">User</option>
                                                <option value="operator">Operator</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Add User</button>
                                        <a href="users.php" class="btn btn-secondary">Cancel</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
</body>

</html>