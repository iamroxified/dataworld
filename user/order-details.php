<?php
ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get user data and order ID
$user_id = (int) $_SESSION['user_id'];
$user_data = get_user_details($user_id);
if (!$user_data) {
    header('Location: ../login.php');
    exit;
}
$full_name = trim((string) ($user_data['first_name'] ?? '') . ' ' . (string) ($user_data['last_name'] ?? ''));
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

try {
    // Fetch order details
    $stmt = QueryDB("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or you are not authorized to view this order.');
    }

    // Fetch order items
    $items_stmt = QueryDB("SELECT oi.*, p.p_img FROM order_items oi LEFT JOIN products p ON oi.product_id = p.pid WHERE oi.order_id = ?", [$order['id']]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Order retrieval error: " . $e->getMessage());
    header('Location: my-orders.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Order Details - Smart People Global</title>
  <?php include('nav/links.php'); ?>
  <style>
    .order-details-container {
      padding: 40px;
      background: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .order-item {
      border-bottom: 1px solid #eee;
      padding: 15px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .order-summary {
      background: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      margin-top: 20px;
    }

    .order-details h2 {
      font-size: 22px;
      margin-bottom: 20px;
      color: #333;
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

    .order-status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-processing {
      background: #d4edda;
      color: #155724;
    }

    .status-shipped {
      background: #d1ecf1;
      color: #0c5460;
    }

    .status-delivered {
      background: #d4edda;
      color: #155724;
    }

    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }

    .status-approved {
      background: #d4edda;
      color: #155724;
    }

    .status-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .payment-evidence {
      background: #e9ecef;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }

    .item-details {
      flex: 1;
    }

    .item-pricing {
      text-align: right;
      min-width: 150px;
    }

    .order-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 30px;
    }

    .item-images {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 10px;
    }

    .order-item-img {
      width: 300px;
      height: 300px;
      object-fit: cover;
      border-radius: 5px;
      border: 1px solid #ddd;
      transition: transform 0.2s;
    }
    .order-item-img:hover {
      transform: scale(1.1);
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
              <h3 class="fw-bold mb-3">Order Details</h3>
              <!-- <h6 class="op-7 mb-2">Free Bootstrap 5 Admin Dashboard</h6> -->
            </div>
            <div class="ms-md-auto py-2 py-md-0">
              <?php  if (($order['order_status'] ?? $order['status'] ?? '') === 'processing'): ?>
              <span class=" badge badge-info">Order Processing</span>
              <?php elseif (($order['order_status'] ?? $order['status'] ?? '') === 'approved'): ?>
              <span class=" badge badge-success">Order Approved</span>
              <?php elseif (($order['order_status'] ?? $order['status'] ?? '') === 'rejected'): ?>
              <span class="badge badge-danger">Order Rejected</span>
              <?php endif; ?>
              <a href="my-orders.php" class="btn btn-primary btn-round">My Orders</a>
            </div>
          </div>
          <div class='row'>
            <div class='col-md-12'>
              <div class='card'>
                <div class="card-body">
                  <div class="">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                      <?php $orderStatus = $order['order_status'] ?? $order['status'] ?? 'pending'; ?>
                      <span class="order-status status-<?php echo $orderStatus; ?>">
                        <?php echo ucfirst($orderStatus); ?>
                      </span>
                    </div>
                    <div class="order-info-grid">
                      <div>
                        <h5>Order Information</h5>
                        <p><strong>Order Date:</strong>
                          <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                        </p>
                        <p><strong>Payee Name:</strong> <?php echo htmlspecialchars((string) ($order['full_name'] ?? $full_name ?: 'N/A')); ?></p>
                        <p><strong>Payment Method:</strong>
                          <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        <p><strong>Payment Status:</strong> <span
                            class="badge badge-secondary"><?php echo ucfirst($order['payment_status']); ?></span></p>
                        
                        <p><strong>Delivery Address:</strong>
                          <?php if($order['pickup'] == 0): ?>
                        No delivery address provided.
                        
                        <?php else: ?>

                        <?php echo $order['pickup_point'].', '.pickup_location($order['pickup']); ?> 
                        <?php endif; ?>
                        
                        </p>
                      </div>
                      <div>
                        <h5>Order Summary</h5>
                        <p><strong>Total Items:</strong> <?php echo count($order_items); ?></p>
                        <p><strong>Total Amount:</strong> N<?php echo number_format($order['total_amount'] * 500, 2); ?>
                        </p>
                        <p><strong>Total PV:</strong> <span
                            class="pv-highlight"><?php echo number_format($order['total_pv']); ?> PV</span></p>
                        <p><strong>Last Updated:</strong>
                          <?php echo date('F j, Y g:i A', strtotime($order['updated_at'])); ?>
                        </p>
                      </div>
                    </div>
                  <div class="order-items mt-4">
                      <h5>Ordered Items</h5>
                      <?php foreach($order_items as $item):     
                        $product_stmt = QueryDB("SELECT p_img FROM products WHERE pid = ?", [$item['product_id']]);
                        $product_images_json = $product_stmt->fetchColumn();
                        
                        // Handle both single string and JSON array formats
                        $images = [];
                        if (!empty($product_images_json)) {
                            // First decode attempt
                            $decoded = json_decode($product_images_json, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                if (is_array($decoded)) {
                                    $images = $decoded;
                                } elseif (is_string($decoded)) {
                                    // Handle possible double-encoded JSON
                                    $second_decode = json_decode($decoded, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($second_decode)) {
                                        $images = $second_decode;
                                    } else {
                                        $images = [$decoded]; // Single image string
                                    }
                                }
                            } elseif (is_string($product_images_json)) {
                                $images = [$product_images_json]; // Single image string
                            }
                        }
                      ?>
                      <div class="order-item">
                        <div class="item-details">
                          <h6><?php echo htmlspecialchars($item['product_name']); ?></h6>
                              <p class="text-muted mb-1">Description: <?php echo htmlspecialchars($order['notes'] ?? 'No description available'); ?></p>
                          <p class="text-muted mb-1">Quantity: <?php echo $item['quantity']; ?> ×
                            $<?php echo number_format($item['price'], 2); ?></p>
                          <p class="text-muted mb-0">PV: <?php echo number_format($item['pv']); ?> ×
                            <?php echo $item['quantity']; ?> = <span
                              class="pv-highlight"><?php echo number_format($item['total_pv']); ?> PV</span></p>
                          <div class="item-images">
                            <?php 
                            // Handle possible double-encoded JSON
                            if (is_string($images)) {
                                $decoded = json_decode($images, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $images = $decoded;
                                }
                            }
                            
                            if (is_array($images) && !empty($images)): ?>
                              <?php foreach ($images as $image): ?>
                                <?php
                                // Construct the correct image path
                                $imagePath = $image;
                                if (strpos($image, 'images/') === 0) {
                                    $imagePath = "../superadmin/".$image; // Use as is if starts with images/
                                } else if (strpos($image, 'products/') !== false) {
                                    $imagePath = '../superadmin/images/' . substr($image, strpos($image, 'products/')); // Extract products/ part
                                } else {
                                    $imagePath = '../superadmin/images/products/' . $image; // Add full path if just filename
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($imagePath); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" class="order-item-img" alt="Product Image">
                                </a>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <img src="images/products/default-product.png" class="order-item-img" alt="No Image">
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="order-summary">
                      <h5>Order Totals</h5>
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
                        <?php if ($order['discount'] > 0): ?>
                        <div class="total-row">
                          <span>Discount:</span>
                          <span class="text-success">-$<?php echo number_format($order['discount'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="total-row">
                          <strong><span>Conversion:</span></strong>
                          <strong><span>1 $TC = 500.00 NGN</span></strong>
                        </div>
                        <div class="total-row final">
                          <span>Total Amount:</span>
                          <span>N<?php echo number_format($order['total_amount'] * 500, 2); ?></span>
                        </div>
                      </div>
                      <div class="mt-4 text-center">
                        <a href="my-orders.php" class="btn btn-secondary mr-2">
                          <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                        </a>
                        <?php if ($order['order_status'] == 'delivered'): ?>
                        <a href="reorder.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success">
                          <i class="fas fa-redo mr-2"></i>Reorder
                        </a>
                        <?php endif; ?>
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
