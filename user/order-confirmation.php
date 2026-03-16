<?php 
ob_start(); 
session_start();
require('../db/config.php');
require('../db/functions.php');

// Redirect to login page if not logged in
if(!isset($_SESSION['username'])){
   header('Location: login.php');
   exit;
} else {
  $user_data = get_user_details($_SESSION['username']);
  extract($user_data);
}

// Check if order success data exists
if (!isset($_SESSION['order_success'])) {
    header('Location: cart.php');
    exit;
}

$order_data = $_SESSION['order_success'];
$order_number = $order_data['order_number'];
$total_amount = $order_data['total_amount'];
$total_pv = $order_data['total_pv'];
$order_status = $order_data['order_status'];

// Get order details from database
try {
    $stmt = QueryDB("SELECT * FROM orders WHERE order_number = ?", [$order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $items_stmt = QueryDB("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Order retrieval error: " . $e->getMessage());
    header('Location: cart.php');
    exit;
}

// Clear session data
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Order Confirmation - Smart People Global</title>
  <?php include('nav/links.php'); ?>
  <style>
    .order-confirmation {
      background: #f8f9fa;
      padding: 40px;
      border-radius: 10px;
      text-align: center;
      margin-bottom: 30px;
    }

    .order-status {
      background: #fff3cd;
      color: #856404;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #ffc107;
    }

    .order-status.pending {
      background: #fff3cd;
      color: #856404;
      border-left-color: #ffc107;
    }

    .order-status.approved {
      background: #d4edda;
      color: #155724;
      border-left-color: #28a745;
    }

    .order-status.denied {
      background: #f8d7da;
      color: #721c24;
      border-left-color: #dc3545;
    }

    .order-details {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .order-item {
      border-bottom: 1px solid #eee;
      padding: 15px 0;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .bank-details {
      background: #e9ecef;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }

    .success-icon {
      font-size: 64px;
      color: #28a745;
      margin-bottom: 20px;
    }

    .pending-icon {
      font-size: 64px;
      color: #ffc107;
      margin-bottom: 20px;
    }

    .total-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }

    .total-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      padding: 5px 0;
    }

    .total-row.final {
      border-top: 2px solid #82ae46;
      padding-top: 15px;
      margin-top: 15px;
      font-weight: bold;
      font-size: 18px;
    }

    .pv-highlight {
      color: #82ae46;
      font-weight: bold;
    }

    .next-steps {
      background: #d1ecf1;
      color: #0c5460;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
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
      <div class="container">
        <div class="page-inner">
          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h3 class="fw-bold mb-3">Order Confirmation</h3>
              <h6 class="op-7 mb-2"><?php echo _greetin().', '.username($_SESSION['username']); ?>! Here are your recent
                orders</h6>
            </div>
          </div>

          <!-- <p class="lead">Add money to your wallet for seamless shopping</p> -->

          <div class="row ">
            <div class="col-md-10">
              <div class="order-confirmation">
                <div class="pending-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <h2 class="mb-3">Order Submitted Successfully!</h2>
                <p class="lead mb-4">Thank you for your order. Your order number is
                  <strong><?php echo htmlspecialchars($order_number); ?></strong></p>

                <div class="order-status pending">
                  <h5><i class="fas fa-hourglass-half mr-2"></i>Order Status: Pending Review</h5>
                  <p class="mb-0">Your order is currently under review. Our admin will verify your payment and
                    approve your order shortly.</p>
                </div>
              </div>

              <div class="order-details">
                <h3 class="mb-4">Order Details</h3>

                <div class="row mb-4">
                  <div class="col-md-6">
                    <h6>Order Information</h6>
                    <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                    <p><strong>Order Date:</strong>
                      <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    <p><strong>Payment Method:</strong>
                      <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    <p><strong>Payee Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                          <p><strong>Delivery Address:</strong>
                          <?php if($order['pickup'] == 0): ?>
                        No delivery address provided.
                        
                        <?php else: ?>

                        <?php echo $order['pickup_point'].', '.pickup_location($order['pickup']); ?> 
                        <?php endif; ?>
                        
                        </p>
                  </div>
                  <div class="col-md-6">
                    <h6>Order Summary</h6>
                    <p><strong>Total Items:</strong> <?php echo count($order_items); ?></p>
                    <p><strong>Total Amount:</strong> N<?php echo number_format($total_amount * 500, 2); ?></p>
                    <p><strong>Total PV:</strong> <span class="pv-highlight"><?php echo number_format($total_pv); ?>
                        PV</span></p>
                    <p><strong>Status:</strong>
                      <span class="badge badge-warning">
                        <?php echo ucfirst($order_status); ?>
                      </span>
                    </p>
                  </div>
                </div>

                <h6>Ordered Items</h6>
                <div class="order-items">
                  <?php foreach($order_items as $item): ?>
                  <div class="order-item">
                    <div class="row align-items-center">
                      <div class="col-md-6">
                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                        <small class="text-muted">Description: <?php echo @$item['notes']; ?></small>
                        <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                      </div>
                      <div class="col-md-3">
                        <span class="pv-highlight"><?php echo number_format($item['total_pv']); ?> PV</span>
                      </div>
                      <div class="col-md-3 text-right">
                        <strong>$<?php echo number_format($item['total_price'], 2); ?> $TC</strong>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>

                <div class="total-section">
                  <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['subtotal'], 2); ?> $TC</span>
                  </div>
                  <div class="total-row">
                    <span>Total PV:</span>
                    <span class="pv-highlight"><?php echo number_format($order['total_pv']); ?> PV</span>
                  </div>
                  <div class="total-row">
                    <span>Shipping:</span>
                    <span><?php echo $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'Free'; ?></span>
                  </div>
                  <div class="total-row">
                    <strong><span>Conversion:</span></strong>
                    <strong><span>1 $TC = 500.00 NGN</span></strong>
                  </div>
                  <div class="total-row final">
                    <span>Total Amount:</span>
                    <span>N<?php echo number_format($order['total_amount'] * 500, 2); ?></span>
                  </div>
                </div>



                <div class="next-steps">
                  <h6><i class="fas fa-info-circle mr-2"></i>What's Next?</h6>
                  <ol class="mb-0">
                   
                    <li>Our admin will process your order within 1-2 hours</li>
                    <li>Once approved, your order will be processed and delivered</li>
                    <li>You'll receive email notifications for status updates</li>
                    <li>You can track your order status in <a href="my-orders.php">My Orders</a></li>
                  </ol>
                </div>

                <div class="text-center mt-4">
                  <a href="my-orders.php" class="btn btn-primary mr-3">
                    <i class="fas fa-list mr-2"></i>View My Orders
                  </a>
                  <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-home mr-2"></i>Continue Shopping
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include('nav/footer.php'); ?>

</body>

</html>