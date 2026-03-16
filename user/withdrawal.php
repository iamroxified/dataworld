<?php
session_start();
require('../db/config.php');
require('../db/functions.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

// Get user details
extract(get_user_details($_SESSION['username']));
$user_id = $bmid;

// Get current wallet balance
$current_balance = get_user_wallet_balance($user_id);

// Get all debit transactions (withdrawals)
$debit_transactions = QueryDB("SELECT * FROM wallet_transactions WHERE user_id = ? AND transaction_type = 'debit' ORDER BY created_at DESC", [$user_id]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Withdrawal History - Smart People Global</title>
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
                        <h3 class="fw-bold mb-3">Withdrawal History</h3>
                        <ul class="breadcrumbs mb-3">
                            <li class="nav-home">
                                <a href="index.php">
                                    <i class="icon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Wallet</a>
                            </li>
                            <li class="separator">
                                <i class="icon-arrow-right"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Withdrawals</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Balance Card -->
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-wallet"></i> Current Wallet Balance
                                    </h4>
                                </div>
                                <div class="card-body text-center">
                                    <h2 class="text-info">$<?php echo number_format($current_balance, 2); ?></h2>
                                    <p class="text-muted">Available Balance</p>
                                </div>
                            </div>

                            <!-- Transactions Table -->
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h4 class="mb-0">
                                        <i class="fas fa-arrow-up"></i> Withdrawal Transactions
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($debit_transactions && $debit_transactions->rowCount() > 0): ?>
                                                 <div class="table-responsive">
                    <table id="add-row" class="display table  table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Transaction ID</th>
                                                        <th>Transaction Reference</th>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                        <th>Balance After</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($transaction = $debit_transactions->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <tr>
                                                            <td>
                                                                <code><?php echo htmlspecialchars($transaction['id']); ?></code>
                                                            </td>
                                                             <td>
                                                                <code><?php echo htmlspecialchars($transaction['reference']); ?></code>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger fs-6">
                                                                    -$<?php echo number_format($transaction['amount'], 2); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                                    <?php echo ucfirst($transaction['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                $<?php echo number_format($transaction['new_balance'], 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-inbox display-1 text-muted"></i>
                                            <h4 class="mt-3">No Withdrawals Found</h4>
                                            <p class="text-muted">You haven't made any withdrawals yet.</p>
                                            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                                <a href="deposit.php" class="btn btn-success">
                                    <i class="fas fa-arrow-down"></i> View Deposits
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
     
   

    <?php include('nav/footer.php'); ?>
          <script>
        $(document).ready(function () {
          $("#basic-datatables").DataTable({});

          $("#multi-filter-select").DataTable({
            pageLength: 5,
            initComplete: function () {
              this.api()
                .columns()
                .every(function () {
                  var column = this;
                  var select = $(
                      '<select class="form-select"><option value=""></option></select>'
                    )
                    .appendTo($(column.footer()).empty())
                    .on("change", function () {
                      var val = $.fn.dataTable.util.escapeRegex($(this).val());
                      column
                        .search(val ? "^" + val + "$" : "", true, false)
                        .draw();
                    });
                  column
                    .data()
                    .unique()
                    .sort()
                    .each(function (d, j) {
                      select.append(
                        '<option value="' + d + '">' + d + "</option>"
                      );
                    });
                });
            },
          });
          // Add Row
          $("#add-row").DataTable({
            pageLength: 10,
          });


        });
      </script>

</body>
</html>
