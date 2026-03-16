<?php
error_reporting(E_ALL);

ob_start();
session_start();
require('../db/config.php');
require('../db/functions.php');

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location:../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_user = getCurrentUser();
$uss = extract(get_user_details($user_id));

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get total orders count
$count_query = QueryDB("SELECT COUNT(*) as total FROM orders o LEFT JOIN binding_requests br ON o.id = br.order_id WHERE o.user_id = ? AND br.order_id IS NULL", [$user_id]);
$total_orders = $count_query->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $records_per_page);

// Get orders for current page
$orders_query = QueryDB("SELECT o.* FROM orders o LEFT JOIN binding_requests br ON o.id = br.order_id WHERE o.user_id = ? AND br.order_id IS NULL ORDER BY o.created_at DESC", 
                      [$user_id]);
$orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);

$binding_requests_query = QueryDB("SELECT br.*, o.status FROM binding_requests br JOIN orders o ON br.order_id = o.id WHERE br.user_id = ? ORDER BY br.created_at DESC", [$user_id]);
$binding_requests = $binding_requests_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>My Orders - Smart People Global</title>
  <?php include('nav/links.php'); ?>
  <style>
    .order-card {
      background: #ffffff;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .order-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f8f9fa;
    }
    
    .order-number {
      font-size: 18px;
      font-weight: bold;
      color: #82ae46;
      font-family: 'Courier New', monospace;
    }
    
    .order-date {
      color: #6c757d;
      font-size: 14px;
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
    
    .pv-highlight {
      color: #82ae46;
      font-weight: bold;
    }
    
    .empty-orders {
      text-align: center;
      padding: 80px 20px;
      color: #6c757d;
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
              <h3 class="fw-bold mb-3">My Orders</h3>
              <h6 class="op-7 mb-2"><?php echo _greetin().', '.$first_name.' '.$last_name; ?>! Here are your recent orders</h6>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Product Orders</h4>
                </div>
                <div class="card-body">
                  <?php if (!empty($orders)): ?>
                    
                    <!-- Order Statistics -->
                    <div class="row mb-4">
                      <div class="col-md-3">
                        <div class="card card-stats card-primary card-round">
                          <div class="card-body">
                            <div class="row align-items-center">
                              <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                  <i class="fas fa-shopping-cart"></i>
                                </div>
                              </div>
                              <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                  <p class="card-category">Total Orders</p>
                                  <h4 class="card-title"><?php echo $total_orders; ?></h4>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card card-stats card-warning card-round">
                          <div class="card-body">
                            <div class="row align-items-center">
                              <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                  <i class="fas fa-clock"></i>
                                </div>
                              </div>
                              <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                  <p class="card-category">Pending Orders</p>
                                  <h4 class="card-title">
                                    <?php 
                                    $pending_count = 0;
                                    foreach($orders as $order) {
                                      if($order['order_status'] == 'pending') $pending_count++;
                                    }
                                    echo $pending_count;
                                    ?>
                                  </h4>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card card-stats card-success card-round">
                          <div class="card-body">
                            <div class="row align-items-center">
                              <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                  <i class="fas fa-dollar-sign"></i>
                                </div>
                              </div>
                              <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                  <p class="card-category">Total Spent</p>
                                  <h4 class="card-title">
                                    $<?php 
                                    $total_spent = 0;
                                    foreach($orders as $order) {
                                      if($order['order_status'] != 'cancelled') $total_spent += $order['total_amount'];
                                    }
                                    echo number_format($total_spent, 2);
                                    ?>
                                  </h4>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="card card-stats card-info card-round">
                          <div class="card-body">
                            <div class="row align-items-center">
                              <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                  <i class="fas fa-star"></i>
                                </div>
                              </div>
                              <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                  <p class="card-category">Total PV Earned</p>
                                  <h4 class="card-title pv-highlight">
                                    <?php 
                                    $total_pv = 0;
                                    foreach($orders as $order) {
                                      if($order['order_status'] != 'cancelled') $total_pv += $order['total_pv'];
                                    }
                                    echo number_format($total_pv);
                                    ?>
                                  </h4>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Orders List -->
                    <?php foreach($orders as $order): ?>
                    <div class="order-card">
                      <div class="order-header">
                        <div>
                          <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                          <div class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div>
                          <span class="order-status status-<?php echo $order['order_status']; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                          </span>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-8">
                          <div class="row">
                            <div class="col-md-4">
                              <strong>Payment Method:</strong><br>
                              <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                            </div>
                            <div class="col-md-4">
                              <strong>Payment Status:</strong><br>
                              <span class="text-muted"><?php echo ucfirst($order['payment_status']); ?></span>
                            </div>
                            <div class="col-md-4">
                              <strong>Paid By:</strong><br>
                              <span class="text-muted"><?php echo $order['full_name']; ?></span>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4 text-end">
                          <div><strong>Total: $<?php echo number_format($order['total_amount'], 2); ?></strong></div>
                          <div class="pv-highlight"><?php echo number_format($order['total_pv']); ?> PV</div>
                          <div class="mt-2">
                            <a href="order-details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                              <i class="fas fa-eye mr-1"></i>View Details
                            </a>
                            
                            <?php if($order['order_status'] == 'delivered'): ?>
                            <a href="reorder.php?order=<?php echo $order['order_number']; ?>" class="btn btn-outline-success btn-sm">
                              <i class="fas fa-redo mr-1"></i>Reorder
                            </a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <nav aria-label="Orders pagination">
                      <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                        <li class="page-item">
                          <a class="page-link" href="?page=<?php echo ($page - 1); ?>">&laquo; Previous</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                          <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                        <li class="page-item">
                          <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Next &raquo;</a>
                        </li>
                        <?php endif; ?>
                      </ul>
                    </nav>
                    <?php endif; ?>
                    
                  <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-orders">
                      <!-- <i class="fas fa-shopping-cart fa-5x mb-3 text-muted"></i> -->
                      <h3>No Orders Yet</h3>
                      <p>You haven't placed any orders yet. Make Analytics or Binding Request!</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Binding Requests</h4>
                </div>
                <div class="card-body">
                  <?php if (!empty($binding_requests)): ?>
                    <div class="table-responsive">
                    <table id="analytics-table" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Order ID</th>
                          <th>Full Name</th>
                          <th>Department</th>
                          <th>Program</th>
                          <th>Pages</th>
                          <th>Color</th>
                          <th>Date</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach($binding_requests as $request): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($request['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['department']); ?></td>
                            <td><?php echo htmlspecialchars($request['programe']); ?></td>
                            <td><?php echo htmlspecialchars($request['pages']); ?></td>
                            <td><div style="width: 20px; height: 20px; background-color: <?php echo htmlspecialchars($request['color']); ?>;"></div></td>
                            <td><?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?></td>
                            <td><?php echo ucfirst($request['status']); ?></td>
                            <td>
                              <a href="view_bind_request.php?order_id=<?php echo $request['order_id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye mr-1"></i>View Details
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                    </div>
                  <?php else: ?>
                    <div class="empty-orders">
                      <i class="fas fa-file-alt fa-5x mb-3 text-muted"></i>
                      <h3>No Binding Requests Yet</h3>
                      <p>You haven't made any binding requests yet.</p>
                      <a href="add_bind_request.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus mr-2"></i>Make a Request
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
   
  <?php include('nav/footer.php'); ?>
</body>

</html>
