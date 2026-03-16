<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit;
}
 $user_id = $_SESSION['user_id'];
  $user_details = get_user_details($user_id);
  $last_name = $user_details['last_name'] ?? '';
  $first_name = $user_details['first_name'] ?? '';
  $username = $user_details['username'] ?? '';
  $email = $user_details['email'] ?? '';
// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>All Users - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
  <!-- Add datatables css -->
  <link rel="stylesheet" href="assets/css/datatables.min.css">
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
            <h3 class="fw-bold mb-3">All Users</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">All Users</a></li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">All Registered Users</h4>
                  <a href="create_user.php" class="btn btn-primary btn-sm">Create User</a>
                </div>
                <div class="card-body">
                  <?php if (isset($_GET['delete_success'])): ?>
                    <div class="alert alert-success">User deleted successfully!</div>
                  <?php endif; ?>
                  <?php if (isset($_GET['edit_success'])): ?>
                    <div class="alert alert-success">User updated successfully!</div>
                  <?php endif; ?>
                  <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                            if ($_GET['error'] === 'cannot_delete_self') {
                                echo 'You cannot delete your own account.';
                            } else {
                                echo 'Failed to delete user.';
                            }
                        ?>
                    </div>
                  <?php endif; ?>
                  <div class="table-responsive">
                    <table id="all-users-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Phone</th>
                          <th>Date Joined</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                          <td><?php echo $user['id']; ?></td>
                          <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                          <td><?php echo !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
                          <td>
                            <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">View</a>
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <form method="post" action="delete_user.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
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
      <!-- Add datatables js -->
      <script src="assets/js/datatables.min.js"></script>
      <script>
        $(document).ready(function () {
          $('#all-users-table').DataTable();
        });
      </script>
    </div>
  </div>
</body>

</html>