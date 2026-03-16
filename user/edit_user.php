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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = 'Invalid user ID.';
    $_SESSION['message_type'] = 'danger';
    header('Location: users.php');
    exit;
}

$user_id_to_edit = $_GET['id'];
$user_to_edit = getUserById($user_id_to_edit);

if (!$user_to_edit) {
    $_SESSION['message'] = 'User not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: users.php');
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
    <title>Edit User - SYi - Tech Global Services</title>
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
                        <h3 class="fw-bold mb-3">Edit User</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="users.php">Manage Users</a></li>
                            <li class="separator"><i class="icon-arrow-right"></i></li>
                            <li class="nav-item"><a href="#">Edit User</a></li>
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
                                    <h4 class="card-title">Edit User Details: <?php echo htmlspecialchars($user_to_edit['username']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <form action="process_user.php" method="POST">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_to_edit['first_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_to_edit['last_name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_to_edit['username']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select class="form-control" id="role" name="role" required>
                                                <option value="user" <?php echo $user_to_edit['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="operator" <?php echo $user_to_edit['role'] == 'operator' ? 'selected' : ''; ?>>Operator</option>
                                                <option value="admin" <?php echo $user_to_edit['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Update User</button>
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