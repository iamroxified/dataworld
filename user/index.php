<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Ensure user is logged in and has 'admin' or 'operator' role
if (!isset($_SESSION['user_id']) || !in_array(getCurrentUser()['role'], ['admin', 'operator'])) {
    header('Location:../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();

// Placeholder for dashboard statistics if needed
$total_analytics_requests = 0; // Replace with actual count
$total_users = 0; // Replace with actual count

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Admin Dashboard - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">Admin Dashboard</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Dashboard</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Welcome, <?php echo htmlspecialchars($current_user['first_name']); ?>!</h4>
                                </div>
                                <div class="card-body">
                                    <p>This is your admin dashboard. Use the sidebar to navigate.</p>
                                    <?php if ($current_user['role'] === 'admin') : ?>
                                        <p><a href="users.php" class="btn btn-info">Manage Users</a></p>
                                    <?php endif; ?>
                                    <?php if (in_array($current_user['role'], ['admin', 'operator'])) : ?>
                                        <p><a href="operator_analytics_requests.php" class="btn btn-primary">View Analytics Requests</a></p>
                                    <?php endif; ?>
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