<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:login.php');
  exit;
}

// Fetch all payments with user information
$stmt = $pdo->query("SELECT p.*, u.first_name, u.last_name FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$payments = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Payment History - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">Payment History</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Payment History</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">All Payment History</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="payment-history-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>User</th>
                          <th>Amount</th>
                          <th>Currency</th>
                          <th>Status</th>
                          <th>Reference</th>
                          <th>Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sn=1; foreach ($payments as $payment): ?>
                        <tr>
                          <td><?php echo $sn++; ?></td>
                       
                          <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                          <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                          <td><?php echo htmlspecialchars($payment['currency']); ?></td>
                          <td>
                            <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'completed' => 'success',
                                    'success' => 'success',
                                    'failed' => 'danger'
                                ];
                                $class = $status_class[$payment['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $class; ?>">
                              <?php echo ucfirst($payment['status']); ?>
                            </span>
                          </td>
                          <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                          <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                          <td>
                            <a href="view_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm">View</a>
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
          $('#payment-history-table').DataTable();
        });
      </script>
    </div>
  </div>
</body>

</html>
