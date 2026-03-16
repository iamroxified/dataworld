<?php 
ob_start(); 
session_start(); 
require('../db/config.php'); 
require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
  exit();
}

// Get current user
$user_id = $_SESSION['user_id'];
$uss = extract(get_user_details($user_id));
$user_details = get_user_details($user_id);
$user_referral_code = $user_details['code'];

// --- Commission Processing ---
$commission_per_order = 2000;

// Get referrals
$stmt_referrals = $pdo->prepare("SELECT * FROM users WHERE referral = ?");
$stmt_referrals->execute([$user_referral_code]);
$referrals = $stmt_referrals->fetchAll();

foreach ($referrals as $referral) {
    $referred_user_id = $referral['id'];

    // Get completed orders for the referred user
    $stmt_orders = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND payment_status = 'completed'");
    $stmt_orders->execute([$referred_user_id]);
    $completed_orders = $stmt_orders->fetchAll();

    foreach ($completed_orders as $order) {
        $order_id = $order['id'];
        $description = "Commission for order #{$order_id}";

        // Check if commission for this order has already been awarded
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ? AND description = ?");
        $stmt_check->execute([$user_id, $description]);
        $commission_exists = $stmt_check->fetchColumn();

        if ($commission_exists == 0) {
            // Award the commission
            $pdo->beginTransaction();
            try {
                $previous_balance = get_user_wallet_balance($user_id);
                $new_balance = $previous_balance + $commission_per_order;
                $reference = 'COMM-' . strtoupper(uniqid());

                // 1. Add to transactions history
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO wallet_transactions (user_id, transaction_type, amount, previous_balance, new_balance, reference, description, status) 
                     VALUES (?, 'commission', ?, ?, ?, ?, ?, 'completed')"
                );
                $stmt_insert->execute([$user_id, $commission_per_order, $previous_balance, $new_balance, $reference, $description]);

                // 2. Update the main wallet balance
                $stmt_update = $pdo->prepare("UPDATE user_wallet SET balance = ? WHERE user_id = ?");
                $stmt_update->execute([$new_balance, $user_id]);
                
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage()); // Log error
            }
        }
    }
}

// --- Fetch Data for Display ---

// Fetch current wallet balance
$wallet_balance = get_user_wallet_balance($user_id);

// Fetch transaction history
$stmt_history = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC");
$stmt_history->execute([$user_id]);
$transactions = $stmt_history->fetchAll();

// Fetch total withdrawal
$stmt_withdrawal = $pdo->prepare("SELECT SUM(amount) as total_withdrawal FROM wallet_transactions WHERE user_id = ? AND transaction_type = 'debit' AND status = 'approved'");
$stmt_withdrawal->execute([$user_id]);
$total_withdrawal = $stmt_withdrawal->fetchColumn();
if ($total_withdrawal === false || $total_withdrawal === null) {
    $total_withdrawal = 0;
}

// Fetch total withdrawal requested
$stmt_withdrawal = $pdo->prepare("SELECT SUM(amount) as total_withdrawal FROM wallet_transactions WHERE user_id = ? AND transaction_type = 'debit' AND status = 'pending'");
$stmt_withdrawal->execute([$user_id]);
$total_pwithdrawal = $stmt_withdrawal->fetchColumn();
if ($total_pwithdrawal === false || $total_pwithdrawal === null) {
    $total_pwithdrawal = 0;
}

?>
<?php
function get_badge_class($status) {
    switch (strtolower($status)) {
        case 'completed':
            return 'bg-success';
        case 'pending':
            return 'bg-warning';
        case 'failed':
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>My Wallet - SYi - Tech Global Services</title>
  <?php include('nav/links.php'); ?>
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
            <h3 class="fw-bold mb-3">My Wallet</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">My Wallet</a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Wallet Balance</h4>
                </div>
                <div class="card-body">
                              <p>Your wallet balance is the total commission earned from your referrals.
                        You earn ₦2000 for each referral who makes a successful analytics
                        request and pays for it.</p>
                      <p>Withdrawals are processed every Friday.</p>
                      
                      <button type="button" class="btn btn-success" data-bs-toggle="modal"
                        data-bs-target="#withdrawalModal">
                        Withdraw Funds
                      </button>
                </div>
                <div class="card-body">
                  <?php if (isset($_SESSION['withdrawal_error'])): ?>
                  <div class="alert alert-danger" role="alert">
                    <?php 
                                    echo $_SESSION['withdrawal_error']; 
                                    unset($_SESSION['withdrawal_error']);
                                ?>
                  </div>
                  <?php endif; ?>
                  <?php if (isset($_SESSION['withdrawal_success'])): ?>
                  <div class="alert alert-success" role="alert">
                    <?php 
                                    echo $_SESSION['withdrawal_success']; 
                                    unset($_SESSION['withdrawal_success']);
                                ?>
                  </div>
                  <?php endif; ?>
            
                  <div class="row">
                    <div class="col-md-4">
                      <div class="card card-stats card-info card-round">
                        <div class="card-body">
                          <div class="row align-items-center">
                            <div class="col-icon">
                              <div class="icon-big text-center icon-info bubble-shadow-small">
                                <i class="fas fa-wallet"></i>
                              </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                              <div class="numbers">
                                <p class="card-category">Available Balance</p>
                                <h4 class="card-title">
                                  ₦<?php echo number_format($wallet_balance, 2); ?>
                                </h4>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                
                    </div>
                    <div class="col-md-4">
                      <div class="card card-stats card-success card-round">
                        <div class="card-body">
                          <div class="row align-items-center">
                            <div class="col-icon">
                              <div class="icon-big text-center icon-success bubble-shadow-small">
                                <i class="fas fa-wallet"></i>
                              </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                              <div class="numbers">
                                <p class="card-category">Total Approved Withdrawal</p>
                                <h4 class="card-title">
                                  ₦<?php echo number_format($total_withdrawal, 2); ?>
                                </h4>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                        <div class="col-md-4">
                      <div class="card card-stats card-warning card-round">
                        <div class="card-body">
                          <div class="row align-items-center">
                            <div class="col-icon">
                              <div class="icon-big text-center icon-warning bubble-shadow-small">
                                <i class="fas fa-wallet"></i>
                              </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                              <div class="numbers">
                                <p class="card-category">Total Pending Withdrawal</p>
                                <h4 class="card-title">
                                  ₦<?php echo number_format($total_pwithdrawal, 2); ?>
                                </h4>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Transaction History</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="transaction-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>SN</th>
                          <th>Date</th>
                          <th>Reference</th>
                          <th>Type</th>
                          <th>Amount</th>
                          <th>Status</th>
                          <th>Description</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sn = 1; foreach ($transactions as $transaction): ?>
                        <tr>
                          <td><?php echo $sn++; ?></td>
                          <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?>
                          </td>
                          <td><?php echo htmlspecialchars($transaction['reference']); ?></td>
                          <td><?php echo ucfirst($transaction['transaction_type']); ?></td>
                          <td>₦<?php echo number_format($transaction['amount'], 2); ?></td>
                          <td><span
                              class="badge <?php echo get_badge_class($transaction['status']); ?>"><?php echo ucfirst($transaction['status']); ?></span>
                          </td>
                          <td><?php echo htmlspecialchars($transaction['description']); ?>
                          </td>
                          <td>
                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-info btn-sm">View</a>
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
    </div>
  </div>

  <!-- Withdrawal Modal -->
  <div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="withdrawalModalLabel">Request Withdrawal</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="process_withdrawal" method="POST">
            <div class="form-group">
              <label for="withdrawal_amount">Amount to Withdraw</label>
              <input type="number" class="form-control" id="withdrawal_amount" name="withdrawal_amount" min="2000"
                step="0.01" required>
              <small class="form-text text-muted">Minimum withdrawal is ₦2000. A 10% fee will be
                applied.</small>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request</button>
          </form>
        </div>
      </div>
    </div>
  </div>
       <script src="assets/js/datatables.min.js"></script>
      <script>
        $(document).ready(function () {
          $('#transaction-table').DataTable();
        });
      </script>

</body>

</html>