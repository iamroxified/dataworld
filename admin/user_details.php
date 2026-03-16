<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get current user details for header
$current_user_id = $_SESSION['user_id'];
$user_details = get_user_details($current_user_id);
$last_name = $user_details['last_name'] ?? '';
$first_name = $user_details['first_name'] ?? '';
$username = $user_details['username'] ?? '';
$email = $user_details['email'] ?? '';

if(!isset($_GET['id'])) {
    header('Location: all_users.php');
    exit;
}

$user_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    header('Location: all_users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>User Details - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">User Details</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="all_users.php">All Users</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">User Details</a></li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">User Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                                            <p><strong>Date Joined:</strong> <?php echo $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit User</a>
                                        <a href="all_users.php" class="btn btn-secondary">Back to Users List</a>
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
</body>
</html>