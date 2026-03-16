<?php 
ob_start(); 
session_start();
require('db/config.php');
require('db/functions.php');

// Check if user is logged in
if(!isset($_SESSION['username'])){
   header('Location: login.php');
   exit;
}

// Check if order success data exists
if(!isset($_SESSION['order_success'])){
   header('Location: cart.php');
   exit;
}

$order_data = $_SESSION['order_success'];
unset($_SESSION['order_success']); // Remove from session after use

$order_number = $order_data['order_number'];
$total_amount = $order_data['total_amount'];
$total_pv = $order_data['total_pv'];

// Get user data
$user_data = get_user_details($_SESSION['username']);
extract($user_data);

// Get order details from database
$order_query = QueryDB("SELECT * FROM orders WHERE order_number = ? AND user_id = ?", [$order_number, $_SESSION['username']]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: cart.php');
    exit;
}

// Get order items
$items_query = QueryDB("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
$order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
 <title>Order Success - Smart People Global</title>
 <?php include('nav/links.php'); ?>
 <style>
  .success-container {
    background: #ffffff;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
    margin: 40px auto;
    max-width: 800px;
  }
  
  .success-icon {
    font-size: 80px;
    color: #28a745;
    margin-bottom: 20px;
  }
  
  .order-number {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 24px;
    font-weight: bold;
    color: #82ae46;
    margin: 20px 0;
  }
  
  .order-summary {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    margin: 30px 0;
  }
  
  .order-item {
    border-bottom: 1px solid #dee2e6;
    padding: 15px 0;
  }
  
  .order-item:last-child {
    border-bottom: none;
  }
  
  .total-section {
    background: #e9ecef;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
  }
  
  .total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
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
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin: 30px 0;
  }
  
  .payment-info {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
  }
  
  .action-buttons {
    margin-top: 30px;
  }
  
  .action-buttons .btn {
    margin: 0 10px;
    padding: 12px 30px;
    font-size: 16px;
  }
 </style>
</head>

<body class="goto-here">

 <?php include('nav/header.php'); ?>

 <div class="hero-wrap hero-bread" style="background-image: url('images/bg_6.jpg');">
  <div class="container">
   <div class="row no-gutters slider-text align-items-center justify-content-center">
    <div class="col-md-9 ftco-animate text-center">
     <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Home</a></span> <span class="mr-2"><a href="cart.php">Cart</a></span> <span>Order Success</span></p>
     <h1 class="mb-0 bread">Order Successful</h1>
     <p class="mb-0">Thank you for your purchase!</p>
    </div>
   </div>
  </div>
 </div>

 <section class="ftco-section">
  <div class="container">
   <div class="success-container">
    <div class="success-icon">
     <i class="fas fa-check-circle"></i>
    </div>
    
    <h2 class="text-success mb-3">Order Placed Successfully!</h2>
    <p class="lead">Thank you for your order. We've received your order and will process it shortly.</p>
    
    <div class="order-number">
     Order #<?php echo htmlspecialchars($order_number); ?>
    </div>
    
    <div class="row">
     <div class="col-md-4">
      <div class="info-card">
       <h5><i class="fas fa-user mr-2"></i>Customer</h5>
       <p><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
       <p><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small></p>
      </div>
     </div>
     
     <div class="col-md-4">
      <div class="info-card">
       <h5><i class="fas fa-credit-card mr-2"></i>Payment</h5>
       <p><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
       <p><small class="text-muted">Status: <?php echo ucfirst($order['payment_status']); ?></small></p>
      </div>
     </div>
     
     <div class="col-md-4">
      <div class="info-card">
       <h5><i class="fas fa-shipping-fast mr-2"></i>Shipping</h5>
       <p><?php echo htmlspecialchars($order['city'] . ', ' . $order['country']); ?></p>
       <p><small class="text-muted">Status: <?php echo ucfirst($order['order_status']); ?></small></p>
      </div>
     </div>
    </div>
    
    <div class="order-summary">
     <h4 class="mb-4">Order Summary</h4>
     
     <div class="order-items">
      <?php foreach($order_items as $item): ?>
      <div class="order-item">
       <div class="row align-items-center">
        <div class="col-md-6 text-left">
         <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
         <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
        </div>
        <div class="col-md-3 text-center">
         <span class="pv-highlight"><?php echo number_format($item['total_pv']); ?> PV</span>
        </div>
        <div class="col-md-3 text-right">
         <strong>$<?php echo number_format($item['total_price'], 2); ?></strong>
        </div>
       </div>
      </div>
      <?php endforeach; ?>
     </div>
     
     <div class="total-section">
      <div class="total-row">
       <span>Subtotal:</span>
       <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
      </div>
      
      <div class="total-row">
       <span>Total PV:</span>
       <span class="pv-highlight"><?php echo number_format($order['total_pv']); ?> PV</span>
      </div>
      
      <div class="total-row">
       <span>Shipping:</span>
       <span><?php echo $order['shipping_cost'] > 0 ? '$' . number_format($order['shipping_cost'], 2) : 'Free'; ?></span>
      </div>
      
      <?php if($order['discount'] > 0): ?>
      <div class="total-row">
       <span>Discount:</span>
       <span class="text-success">-$<?php echo number_format($order['discount'], 2); ?></span>
      </div>
      <?php endif; ?>
      
      <div class="total-row final">
       <span>Total Amount:</span>
       <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
      </div>
     </div>
    </div>
    
    <?php if($order['payment_method'] == 'bank_transfer'): ?>
    <div class="payment-info">
     <h5><i class="fas fa-university mr-2"></i>Bank Transfer Instructions</h5>
     <p>Please make your payment to the following bank account:</p>
     <div class="row">
      <div class="col-md-6">
       <p><strong>Bank Name:</strong> Smart People Global Bank</p>
       <p><strong>Account Name:</strong> Smart People Global Ltd</p>
       <p><strong>Account Number:</strong> 1234567890</p>
      </div>
      <div class="col-md-6">
       <p><strong>Routing Number:</strong> 123456789</p>
       <p><strong>SWIFT Code:</strong> SPGBANKUS</p>
       <p><strong>Reference:</strong> <?php echo htmlspecialchars($order_number); ?></p>
      </div>
     </div>
     <p class="text-muted mt-3">
      <small>Please use your order number as the payment reference. Your order will be processed after payment confirmation.</small>
     </p>
    </div>
    <?php endif; ?>
    
    <div class="next-steps">
     <h5><i class="fas fa-info-circle mr-2"></i>What's Next?</h5>
     <ul class="list-unstyled text-left">
      <li><i class="fas fa-check mr-2 text-success"></i>You will receive an order confirmation email shortly</li>
      <li><i class="fas fa-check mr-2 text-success"></i>We'll send you tracking information once your order ships</li>
      <li><i class="fas fa-check mr-2 text-success"></i>Your PV points will be credited to your account after payment confirmation</li>
      <li><i class="fas fa-check mr-2 text-success"></i>Expected delivery: 3-5 business days</li>
     </ul>
    </div>
    
    <div class="action-buttons">
     <a href="products.php" class="btn btn-outline-primary">
      <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
     </a>
     <a href="my-orders.php" class="btn btn-primary">
      <i class="fas fa-list mr-2"></i>View My Orders
     </a>
     <button onclick="window.print()" class="btn btn-outline-secondary">
      <i class="fas fa-print mr-2"></i>Print Order
     </button>
    </div>
   </div>
  </div>
 </section>

 <?php include('nav/footer.php'); ?>

 <script>
  $(document).ready(function() {
    // Confetti animation (optional)
    setTimeout(function() {
      // You can add confetti animation here if desired
    }, 500);
    
    // Auto-scroll to success message
    $('html, body').animate({
      scrollTop: $('.success-container').offset().top - 100
    }, 1000);
    
    // Print functionality
    $('#print-order').click(function() {
      window.print();
    });
  });
 </script>

</body>

</html>
