<?php ob_start(); session_start(); require('../db/config.php'); require('../db/functions.php');

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
  header('Location:../login.php');
}else{
$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$user_details = get_user_details($user_id);
if (is_array($user_details)) {
    extract($user_details);
} else {
    die("Error: Unable to retrieve user details.");
}
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

try {
    // Fetch order details
    $stmt = QueryDB("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or you are not authorized to view this order.');
    }

    // Fetch binding request details
    $binding_stmt = QueryDB("SELECT * FROM binding_requests WHERE order_id = ?", [$order_id]);
    $binding_request = $binding_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$binding_request) {
        throw new Exception('Binding request not found.');
    }

} catch (Exception $e) {
    error_log("Order retrieval error: " . $e->getMessage());
    header('Location: my-orders.php');
    exit;
}
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Binding Request Details - SYi-Tech</title>
  <?php include('nav/links.php'); ?>
  <style>
    .order-details-container {
      padding: 40px;
      background: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <?php include('nav/sidebar.php'); ?>
    <!-- End Sidebar -->
    <div class="main-panel">
      <?php include('nav/header.php'); ?>
      <div class='container'>
        <div class='page-inner'>
          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Binding Request Details</h3>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
              <a href="my-orders.php" class="btn btn-primary btn-round">My Orders</a>
            </div>
          </div>
          <div class='row'>
            <div class='col-md-12'>
              <div class='card'>
                <div class="card-body">
                  <div class="order-details-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                      <span class="order-status status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                      </span>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <h5>Request Information</h5>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($binding_request['full_name']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($binding_request['department']); ?></p>
                        <p><strong>Program:</strong> <?php echo htmlspecialchars($binding_request['programe']); ?></p>
                        <p><strong>Pages:</strong> <?php echo htmlspecialchars($binding_request['pages']); ?></p>
                        <p><strong>Color:</strong> <div style="width: 20px; height: 20px; background-color: <?php echo htmlspecialchars($binding_request['color']); ?>;"></div></p>
                        <p><strong>Request Date:</strong>
                          <?php echo date('F j, Y g:i A', strtotime($binding_request['created_at'])); ?>
                        </p>
                      </div>
                      <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <p><strong>Payment Method:</strong>
                          <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        <p><strong>Payment Status:</strong> <span
                            class="badge badge-secondary"><?php echo ucfirst($order['payment_status']); ?></span></p>
                        <p><strong>Total Amount:</strong> N<?php echo number_format($order['total_amount'], 2); ?>
                        </p>
                      </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Cover Page</h5>
                            <a href="../<?php echo htmlspecialchars($binding_request['cover_page_path']); ?>" target="_blank">View Cover Page</a>
                        </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include('nav/footer.php'); ?>
</body>

</html>