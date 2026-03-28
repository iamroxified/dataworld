<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

if(!isset($_SESSION['user_id'])){
  header('Location:login.php');
  exit;
}

$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id === 0) {
    header('Location: payment_history.php');
    exit;
}

// Fetch the payment details
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email, u.phone FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: payment_history.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Payment Details - SYi - Tech Global Services</title>
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
            <h3 class="fw-bold mb-3">Payment Details</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home"><a href="index.php"><i class="icon-home"></i></a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="payment_history.php">Payment History</a></li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item"><a href="#">Payment #<?php echo htmlspecialchars($payment['id']); ?></a></li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Payment #<?php echo htmlspecialchars($payment['reference']); ?></h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <h5>Payment Information</h5>
                      <p><strong>Amount:</strong> <?php echo htmlspecialchars($payment['currency']); ?><?php echo number_format($payment['amount'], 2); ?></p>
                      <p><strong>Status:</strong> <span class="badge bg-<?php echo in_array(($payment['status'] ?? ''), ['paid', 'completed', 'success'], true) ? 'success' : (($payment['status'] ?? '') === 'failed' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($payment['status']); ?></span></p>
                      <p><strong>Reference:</strong> <?php echo htmlspecialchars($payment['reference']); ?></p>
                      <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></p>
                      <p><strong>Order ID:</strong> <?php echo htmlspecialchars($payment['order_id'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                      <h5>User Information</h5>
                      <p><strong>Name:</strong> <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></p>
                      <p><strong>Email:</strong> <?php echo htmlspecialchars($payment['email']); ?></p>
                      <p><strong>Phone:</strong> <?php echo htmlspecialchars($payment['phone']); ?></p>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                    <a href="payment_history.php" class="btn btn-secondary">Back to Payment History</a>
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
