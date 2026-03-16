<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: all_users.php');
    exit;
}

$user_id_to_edit = $_GET['id'];
$new_password = null;

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Generate a random password
    $new_password = bin2hex(random_bytes(4)); // 8 characters long
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the user's password in the database
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed_password, $user_id_to_edit])) {
        // Password updated successfully
        // The new password will be displayed in the form
    } else {
        header('Location: edit_user.php?id=' . $user_id_to_edit . '&error=password_reset_failed');
        exit;
    }
}


// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id_to_edit]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: all_users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reset_password'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
    if ($stmt->execute([$first_name, $last_name, $email, $phone, $role, $user_id_to_edit])) {
        header('Location: all_users.php?edit_success=true');
        exit;
    } else {
        header('Location: edit_user.php?id=' . $user_id_to_edit . '&error=update_failed');
        exit;
    }
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
    <!-- Sidebar -->
    <?php include('nav/sidebar.php'); ?>
    <!-- End Sidebar -->

    <div class="main-panel">
      <?php include('nav/header.php'); ?>
      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Edit User</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="all_users.php">All Users</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Edit User</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                </div>
                <div class="card-body">
                  <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">Failed to update user.</div>
                  <?php endif; ?>
                  <form method="post" action="">
                    <div class="form-group">
                      <label for="first_name">First Name</label>
                      <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label for="last_name">Last Name</label>
                      <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label for="email">Email</label>
                      <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label for="phone">Phone</label>
                      <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    <div class="form-group">
                      <label for="role">Role</label>
                      <select name="role" id="role" class="form-control">
                        <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>User</option>
                        <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                        <option value="operator" <?php if ($user['role'] === 'operator') echo 'selected'; ?>>Operator</option>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update User</button>
                  </form>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'password_reset_failed'): ?>
                        <div class="alert alert-danger">Failed to reset password.</div>
                    <?php endif; ?>
                    <?php if ($new_password): ?>
                        <div class="alert alert-success">
                            Password has been reset successfully. New password is: <strong><?php echo $new_password; ?></strong>
                        </div>
                    <?php endif; ?>
                  <form method="post" action="">
                    <p>Click the button below to reset the password for this user. A new password will be generated and displayed.</p>
                    <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
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