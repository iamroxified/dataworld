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

$users = getAllUsers();

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
    <title>Manage Users - SYi - Tech Global Services</title>
    <?php include('nav/links.php'); ?>
    <link rel="stylesheet" href="assets/css/datatables.min.css">
</head>

<body>
    <div class="wrapper">
        <?php include('nav/sidebar.php'); ?>
        <div class="main-panel">
            <?php include('nav/header.php'); ?>
            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Manage Users</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Admin Panel</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Users</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php if ($message) : ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">All Users</h4>
                                    <a href="add_user.php" class="btn btn-primary btn-round float-end">Add New User</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="users-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>SN</th>
                                                    <th>Username</th>
                                                    <th>Full Name</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Registered On</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $sn = 1;
                                                foreach ($users as $user) : ?>
                                                    <tr>
                                                        <td><?php echo $sn++; ?></td>
                                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                        <td>
                                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                                            <a href="process_user.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('nav/footer.php'); ?>
        </div>
    </div>
    <script src="assets/js/datatables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#users-table').DataTable();
        });
    </script>
</body>

</html>