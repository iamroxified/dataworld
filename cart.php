<?php
require_once 'db/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $cart_id = (int)$_POST['cart_id'];
                $quantity = max(1, (int)$_POST['quantity']);
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $user_id]);
                break;
                
            case 'remove_item':
                $cart_id = (int)$_POST['cart_id'];
                
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
                break;
                
            case 'checkout':
                // Create order
                $order_number = generateOrderNumber();
                
                // Get cart items with prices
                $cart_stmt = $pdo->prepare("
                    SELECT c.*, d.title, d.price 
                    FROM cart c 
                    JOIN datasets d ON c.dataset_id = d.id 
                    WHERE c.user_id = ? AND d.is_active = 1
                ");
                $cart_stmt->execute([$user_id]);
                $cart_items = $cart_stmt->fetchAll();
                
                if (!empty($cart_items)) {
                    $total = 0;
                    foreach ($cart_items as $item) {
                        $total += $item['price'] * $item['quantity'];
                    }
                    
                    // Create order
                    $order_stmt = $pdo->prepare("
                        INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status) 
                        VALUES (?, ?, ?, 'pending', 'bank_transfer', 'pending')
                    ");
                    $order_stmt->execute([$user_id, $order_number, $total]);
                    $order_id = $pdo->lastInsertId();
                    
                    // Add order items
                    $item_stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, dataset_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    foreach ($cart_items as $item) {
                        $item_stmt->execute([$order_id, $item['dataset_id'], $item['quantity'], $item['price']]);
                    }
                    
                    // Clear cart
                    $clear_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $clear_stmt->execute([$user_id]);
                    
                    $_SESSION['success'] = "Order #$order_number created successfully! Please proceed with payment.";
                    header("Location: dashboard.php");
                    exit();
                }
                break;
        }
    }
}

// Get cart items
$cart_query = "
    SELECT c.*, d.title, d.price, d.description, d.format, d.file_size
    FROM cart c 
    JOIN datasets d ON c.dataset_id = d.id 
    WHERE c.user_id = ? AND d.is_active = 1
    ORDER BY c.added_at DESC
";
$cart_stmt = $pdo->prepare($cart_query);
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Shopping Cart - DataWorld</title>
  <?php include('nav/links.php'); ?>
  <style>
    .cart-item {
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .quantity-input {
      width: 80px;
    }

    .total-section {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
    }
  </style>
</head>

<body class="blog-page">
  <?php include('nav/header.php'); ?>

  <main class="main">
    <!-- Page Title -->
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Shopping Cart</h1>
              <p class="mb-0">Review your selected datasets before checkout</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index">Home</a></li>
            <li><a href="datasets">Datasets</a></li>
            <li class="current">Cart</li>
          </ol>
        </div>
      </nav>
    </div>

    <div class="container">
      <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <?php if (empty($cart_items)): ?>
      <div class="text-center py-5">
        <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
        <h3 class="mt-3">Your cart is empty</h3>
        <p class="text-muted">Browse our datasets and add items to your cart</p>
        <a href="datasets.php" class="btn btn-primary mt-3">
          <i class="bi bi-search"></i> Browse Datasets
        </a>
      </div>
      <?php else: ?>
      <div class="row">
        <div class="col-lg-8">
          <h4><i class="bi bi-cart"></i> Cart Items (<?php echo count($cart_items); ?>)</h4>

          <?php foreach ($cart_items as $item): ?>
          <div class="cart-item">
            <div class="row align-items-center">
              <div class="col-md-6">
                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                <small class="text-muted">
                  <i class="bi bi-file-earmark"></i> <?php echo htmlspecialchars($item['format']); ?> |
                  <i class="bi bi-hdd"></i> <?php echo htmlspecialchars($item['file_size']); ?>
                </small>
              </div>
              <div class="col-md-3">
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="update_quantity">
                  <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                  <label for="quantity_<?php echo $item['id']; ?>" class="form-label">Qty:</label>
                  <input type="number" class="form-control quantity-input" name="quantity"
                    value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                </form>
              </div>
              <div class="col-md-2">
                <strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong>
              </div>
              <div class="col-md-1">
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="remove_item">
                  <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Remove this item from cart?')">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
          <div class="total-section">
            <h5><i class="bi bi-calculator"></i> Order Summary</h5>
            <hr>
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal:</span>
              <span><?php echo formatPrice($total); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Processing Fee:</span>
              <span>$0.00</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
              <strong>Total:</strong>
              <strong><?php echo formatPrice($total); ?></strong>
            </div>

            <form method="post">
              <input type="hidden" name="action" value="checkout">
              <button type="submit" class="btn btn-success w-100 mb-3">
                <i class="bi bi-credit-card"></i> Proceed to Checkout
              </button>
            </form>

            <a href="datasets.php" class="btn btn-outline-primary w-100">
              <i class="bi bi-arrow-left"></i> Continue Shopping
            </a>

            <div class="mt-3 p-3 bg-light rounded">
              <small>
                <strong>Payment Information:</strong><br>
                After checkout, you'll receive payment instructions.
                Downloads will be available once payment is confirmed.
              </small>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <?php include('nav/footer.php'); ?>
</body>

</html>