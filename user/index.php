<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get current user details
$user_id = $_SESSION['user_id'];
$user_details = get_user_details($user_id);

// If user details are not found, redirect to login
if (!$user_details) {
    header('Location: ../login.php');
    exit;
}

// Check for appropriate role
$user_role = $user_details['role'];
if ($user_role !== 'user' && $user_role !== 'customer') {
    header('Location: ../login.php');
    exit;
}

// Set variables for the header
$last_name = $user_details['last_name'] ?? '';
$first_name = $user_details['first_name'] ?? '';
$username = $user_details['username'] ?? '';
$email = $user_details['email'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>User Dashboard - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">User Dashboard</h3>
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
                                    <h4 class="card-title">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h4>
                                </div>
                                <div class="card-body">
                                    <p>This is your user dashboard. Use the sidebar to navigate to your orders, profile, and more.</p>
                                    <p><a href="my-orders.php" class="btn btn-primary">View My Orders</a></p>
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