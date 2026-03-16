<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:login.php');
  exit;
}

// Fetch all wallet transactions with user information
$stmt = $pdo->query("SELECT wt.*, u.first_name, u.last_name FROM wallet_transactions wt JOIN users u ON wt.user_id = u.id ORDER BY wt.created_at DESC");
$transactions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Transaction History - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">Transaction History</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Transaction History</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">All Wallet Transactions</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="transaction-history-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>User</th>
                          <th>Amount</th>
                          <th>Type</th>
                          <th>Description</th>
                          <th>Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sn=1; foreach ($transactions as $transaction): ?>
                        <tr>
                          <td><?php echo $sn++; ?></td>
                          <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                          <td><?php echo htmlspecialchars($transaction['amount']); ?></td>
                          <td><?php echo htmlspecialchars($transaction['transaction_type']); ?></td>
                          <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                          <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
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
          $('#transaction-history-table').DataTable();
        });
      </script>
    </div>
  </div>
</body>

</html>