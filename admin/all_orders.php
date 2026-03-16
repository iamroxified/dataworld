<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit;
}

// Fetch all orders with user information
$stmt = $pdo->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>All Orders - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">All Orders</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">All Orders</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">All Order History</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="all-orders-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Order Number</th>
                          <th>User</th>
                          <th>Amount</th>
                          <th>Status</th>
                          <th>Payment Status</th>
                          <th>Date</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                          <td><?php echo $order['id']; ?></td>
                          <td><strong><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></strong></td>
                          <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                          <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                           <td>
                            <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'processing' => 'info',
                                    'completed' => 'success',
                                    'delivered' => 'primary',
                                    'rejected' => 'danger'
                                ];
                                $class = $status_class[$order['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>">
                              <?php echo ucfirst($order['status']); ?>
                            </span>
                          </td>
                          <td>
                            <?php
                                $payment_status_class = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'failed' => 'danger'
                                ];
                                $p_class = $payment_status_class[$order['payment_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $p_class; ?>">
                              <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                          </td>
                          <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                          <td>
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">View</a>
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
          $('#all-orders-table').DataTable();
        });
      </script>
    </div>
  </div>
</body>

</html>