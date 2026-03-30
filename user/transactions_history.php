<?php
session_start();
require('../db/config.php');
require('../db/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get user details
$user_id = (int) $_SESSION['user_id'];
$user_details = get_user_details($user_id);
if (!$user_details) {
    header('Location: ../login.php');
    exit;
}
$full_name = trim((string) ($user_details['first_name'] ?? '') . ' ' . (string) ($user_details['last_name'] ?? ''));

// Get current wallet balance
$current_balance = get_user_wallet_balance($user_id);

// Get all credit transactions (deposits)
$credit_transactions = QueryDB("SELECT * FROM wallet_transactions WHERE user_id = ?  ORDER BY created_at DESC", [$user_id]);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Transactions History - Smart People Global</title>
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
                        <h3 class="fw-bold mb-3">Transactions History</h3>
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
                                <a href="#">Transactions</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Balance Card -->
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                <div class="d-flex align-items-center">
                                    <h4 class="mb-0">
                                        <i class="fas fa-wallet"></i> Current Wallet Balance
                                    </h4>
                                    <a href="add_funds" class="btn btn-primary btn-round ms-auto" >
                                        <i class="fa fa-plus"></i>
                                        Add Funds
                                    </a>
                                </div>
                                </div>
                                <div class="card-body text-center">
                                    <h2 class="text-success">$<?php echo number_format($current_balance, 2); ?></h2>
                                    <p class="text-muted">Available Balance</p>
                                </div>
                            </div>

                            <!-- Transactions Table -->
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-arrow-down"></i> Transactions History
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if ($credit_transactions && $credit_transactions->rowCount() > 0): ?>
                                           <div class="table-responsive">
                    <table id="add-row" class="display table  table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>SN</th>
                                                    <th>Transaction ID</th>
                                                  
                                                    <th>Amount</th>
                                                    <th>Description</th>
                                                      <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Balance After</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $sn=1; while ($transaction = $credit_transactions->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><?php echo $sn; ?></td>
                                                    <td>
                                                       SPT_TRNX_<?php echo htmlspecialchars($transaction['id']); ?>
                                                    </td>
                                                
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaction['transaction_type'] === 'credit' ? 'success' : 'danger'; ?> fs-6">
                                                            +$<?php echo number_format($transaction['amount'], 2); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($transaction['description']); ?>
                                                    </td>
                                                        <td>
                                                        <?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($transaction['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        $<?php echo number_format($transaction['new_balance'], 2); ?>
                                                    </td>
                                                </tr>
                                                <?php $sn++; endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox display-1 text-muted"></i>
                                        <h4 class="mt-3">No Deposits Found</h4>
                                        <p class="text-muted">You haven't made any deposits yet.</p>
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
                                <a href="withdrawal.php" class="btn btn-warning">
                                    <i class="fas fa-arrow-up"></i> View Withdrawals
                                </a>
                                   <a href="deposit.php" class="btn btn-info">
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

                  <!-- Wallet Modal -->
      <div class="modal fade" id="walletModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Deposit Money - <?php echo htmlspecialchars($full_name !== '' ? $full_name : 'User'); ?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="walletForm" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <div class="form-group">
                  <label for="amount">Amount ($TC) <i>($1 TC = N500.00)</i></label>
                  <input type="number" name="amount" class="form-control" placeholder="Enter amount" required min="0.01" step="0.01">
                </div>
                <div class="form-group">
                  <label for="description">Description</label>
                  <input type="text" name="description" class="form-control" placeholder="Admin wallet credit" value="Admin wallet credit">
                </div>
                <div class="form-group">
                  <button type="submit" class="btn btn-primary">Add Balance</button>
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      
</body>

</html>
