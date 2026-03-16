<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $username = $_POST['username'];


    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, role, password, username, code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$first_name, $last_name, $email, $phone, $role, $hashed_password, $username, $username])) {
        header('Location: all_users.php?create_success=true');
        exit;
    } else {
        header('Location: create_user.php?error=create_failed');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Create User - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
</head>

<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <?php include('nav/sidebar.php'); ?>
    <!-- End Sidebar -->

    <div class="main-panel">
      <?php include('nav/header.php'); ?>
      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Create User</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="all_users.php">All Users</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Create User</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Create New User</h4>
                </div>
                <div class="card-body">
                  <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">Failed to create user.</div>
                  <?php endif; ?>
                  <form method="post" action="">
                    <div class="form-group">
                      <label for="first_name">First Name</label>
                      <input type="text" name="first_name" id="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label for="last_name">Last Name</label>
                      <input type="text" name="last_name" id="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label for="username">Username</label>
                      <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label for="email">Email</label>
                      <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label for="phone">Phone</label>
                      <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                      <label for="role">Role</label>
                      <select name="role" id="role" class="form-control">
                        <option value="customer" selected>Customer</option>
                        <option value="admin">Admin</option>
                        <option value="operator">Operator</option>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create User</button>
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